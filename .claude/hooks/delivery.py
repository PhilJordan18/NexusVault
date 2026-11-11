"""Les trois livraisons vers dev : mandat, avis securite, gouvernance.

Module partage par .nv/bin/nv-deliver (qui cree le commit de livraison) et par
check_delivery.py (appele par le hook pre-push pour revalider la plage poussee
vers dev). Une seule table decrit, pour chaque sorte de livraison, le trailer,
le role qui a le droit de la lancer, et ce que les commits peuvent toucher.

La politique qui valide un push vers dev est celle deja presente sur dev
(`policy_at`), jamais celle de l'arbre de travail : une livraison ne peut pas
assouplir les regles qui la valident, elle ne les change que pour les
suivantes.
"""

from __future__ import annotations

import re
import subprocess
import types
from typing import Any

POLICY_PATH = ".claude/hooks/delivery.py"

MANDATE_RE = re.compile(r"^M-\d{3}$")
SLUG_RE = re.compile(r"^[a-z0-9][a-z0-9-]{2,40}$")
TRAILER_RE = re.compile(r"^NV-(?P<kind>Deliver|Review|Governance):\s*(?P<ref>\S+)\s*$", re.MULTILINE)
REVIEW_VERDICTS = ("approved", "changes_requested")

GOVERNANCE_PATHS = (
    ".nv/",
    "docs/",
    ".claude/",
    ".agents/",
    "AGENTS.md",
    "CLAUDE.md",
    "README.md",
    ".gitignore",
)
REVIEW_PATHS = (".nv/reports/", ".nv/board.json", ".nv/journal.md", "docs/")

KINDS: dict[str, dict[str, Any]] = {
    "deliver": {
        "trailer": "NV-Deliver",
        "ref": MANDATE_RE,
        "roles": ("dev", "ui", "secops"),
        "range_paths": None,
        "forbidden_paths": (),
        "tip_paths": (".nv/",),
        "status_before": ("in_progress",),
        "status_after": "delivered",
    },
    "review": {
        "trailer": "NV-Review",
        "ref": MANDATE_RE,
        "roles": ("secops",),
        "range_paths": REVIEW_PATHS,
        "forbidden_paths": (".nv/qa/", ".nv/mandates/", "docs/adr/"),
        "tip_paths": (".nv/",),
        "status_before": ("delivered",),
        "status_after": "reviewed",
    },
    "governance": {
        "trailer": "NV-Governance",
        "ref": SLUG_RE,
        "roles": ("architect",),
        "range_paths": GOVERNANCE_PATHS,
        "forbidden_paths": (".nv/qa/", ".githooks/", ".claude/hooks/", ".claude/settings.json"),
        "tip_paths": (".nv/",),
        "status_before": (),
        "status_after": None,
    },
}


def parse_trailer(message: str) -> tuple[str, str] | None:
    """Retourne (sorte, reference) si le message porte un trailer NV-*, sinon None."""
    match = TRAILER_RE.search(message)
    if not match:
        return None
    kind = match.group("kind").lower()
    if kind == "governance":
        return "governance", match.group("ref")
    return kind, match.group("ref")


def valid_ref(kind: str, ref: str) -> bool:
    return bool(KINDS[kind]["ref"].match(ref))


def trailer(kind: str, ref: str) -> str:
    return f"{KINDS[kind]['trailer']}: {ref}"


def subject(kind: str, ref: str) -> str:
    return f"chore(nv): {kind} {ref}"


def commit_message(kind: str, ref: str) -> str:
    return f"{subject(kind, ref)}\n\n{trailer(kind, ref)}\n"


def _matches(path: str, prefixes: tuple[str, ...]) -> bool:
    return any(path == prefix or (prefix.endswith("/") and path.startswith(prefix)) for prefix in prefixes)


def disallowed_range_paths(paths: list[str], kind: str) -> list[str]:
    """Chemins de la plage livree que cette sorte de livraison ne peut pas toucher."""
    spec = KINDS[kind]
    bad: list[str] = []
    for path in paths:
        if _matches(path, spec["forbidden_paths"]):
            bad.append(path)
        elif spec["range_paths"] is not None and not _matches(path, spec["range_paths"]):
            bad.append(path)
    return bad


def disallowed_tip_paths(paths: list[str], kind: str) -> list[str]:
    """Chemins du commit de livraison lui-meme hors de .nv/."""
    return [path for path in paths if not _matches(path, KINDS[kind]["tip_paths"])]


def review_verdict(report: str) -> str | None:
    """Valeur de `review:` dans le frontmatter d'un rapport de livraison."""
    if not report.startswith("---"):
        return None
    end = report.find("\n---", 3)
    header = report[3:end] if end != -1 else report[3:]
    match = re.search(r"^review:\s*(\S+)\s*$", header, re.MULTILINE)
    return match.group(1) if match else None


def journal_line(kind: str, ref: str, head: str, branch: str, date: str) -> str:
    return f"- {date} {kind} {ref} {head[:10]} {branch}\n"


def policy_at(ref: str, cwd: str | None = None) -> types.ModuleType:
    """Ce module tel qu'il existe au commit `ref`, ou ce module-ci si `ref` ne le porte pas encore."""
    result = subprocess.run(["git", "show", f"{ref}:{POLICY_PATH}"], capture_output=True, text=True, check=False, cwd=cwd)
    if result.returncode != 0:
        import sys

        return sys.modules[__name__]
    module = types.ModuleType(f"delivery@{ref}")
    exec(compile(result.stdout, f"{POLICY_PATH}@{ref}", "exec"), module.__dict__)
    return module
