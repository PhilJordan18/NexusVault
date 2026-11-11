"""Preuve QA d'une livraison : .nv/qa/M-xxx.json.

Ecrite par nv-deliver, lue par check_qa_evidence.py et par le hook pre-push.
"""

from __future__ import annotations

import json
import os
from datetime import datetime, timezone
from typing import Any

QA_DIR = os.path.join(".nv", "qa")


def evidence_path(root: str, mandate: str) -> str:
    return os.path.join(root, QA_DIR, f"{mandate}.json")


def build(mandate: str, branch: str, head: str, checks: list[dict[str, Any]]) -> dict[str, Any]:
    status = "pass" if all(int(check.get("exit_code", 1)) == 0 for check in checks) else "fail"
    return {
        "mandate": mandate,
        "head": head,
        "branch": branch,
        "created": datetime.now(timezone.utc).replace(microsecond=0).isoformat().replace("+00:00", "Z"),
        "status": status,
        "checks": checks,
    }


def write(root: str, evidence: dict[str, Any]) -> str:
    path = evidence_path(root, evidence["mandate"])
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, "w", encoding="utf-8") as handle:
        json.dump(evidence, handle, indent=2, ensure_ascii=False)
        handle.write("\n")
    return path


def load(path_or_text: str, from_text: bool = False) -> dict[str, Any]:
    if from_text:
        return json.loads(path_or_text)
    with open(path_or_text, encoding="utf-8") as handle:
        return json.load(handle)


def problems(evidence: dict[str, Any], mandate: str, head: str | None) -> list[str]:
    """Liste des raisons pour lesquelles une preuve n'est pas valable."""
    found: list[str] = []
    if evidence.get("mandate") != mandate:
        found.append(f"preuve pour {evidence.get('mandate')!r}, attendu {mandate}")
    if evidence.get("status") != "pass":
        found.append("preuve en echec, status != pass")
    checks = evidence.get("checks") or []
    if not checks:
        found.append("aucune verification enregistree")
    commands = {str(check.get("command", "")) for check in checks}
    if not any("ci:check" in command for command in commands):
        found.append("composer run ci:check absent de la preuve")
    for check in checks:
        if int(check.get("exit_code", 1)) != 0:
            found.append(f"verification en echec : {check.get('command')}")
    if head and evidence.get("head") != head:
        found.append(f"preuve pour le sha {evidence.get('head')}, attendu {head}")
    return found
