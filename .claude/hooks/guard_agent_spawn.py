"""Quel role peut lancer quel agent nv-*.

Hook PreToolUse pour Agent et Task.
"""

from __future__ import annotations

import os
import re
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from hook_io import allow, current_role, deny, read_input, tool_input  # noqa: E402

ALLOWED: dict[str, tuple[str, ...]] = {
    "architect": ("nv-explorer", "nv-qa-code", "nv-qa-feature", "nv-ui-reviewer"),
    "secops": ("nv-explorer", "nv-pentester", "nv-qa-code"),
    "ui": ("nv-explorer", "nv-ui-reviewer", "nv-implementer", "nv-qa-feature"),
    "dev": ("nv-explorer", "nv-implementer", "nv-qa-code", "nv-qa-feature"),
}
MANDATE_RE = re.compile(r"\bM-\d{3}\b")


def violation(agent: str, prompt: str, role: str) -> str | None:
    if not agent.startswith("nv-"):
        return None
    if agent not in ALLOWED.get(role, ()):
        return f"le role {role} ne lance pas {agent} (permis : {', '.join(ALLOWED.get(role, ()))})"
    if agent == "nv-implementer" and not MANDATE_RE.search(prompt):
        return "nv-implementer exige un brief qui nomme le mandat M-xxx, le critere vise et les fichiers"
    if agent == "nv-implementer" and len(prompt.strip()) < 120:
        return "brief trop court pour nv-implementer, decrire le critere d'acceptation et les fichiers attendus"
    return None


def main() -> None:
    payload = read_input()
    data = tool_input(payload)
    agent = str(data.get("subagent_type") or "")
    prompt = str(data.get("prompt") or "")
    reason = violation(agent, prompt, current_role())
    if reason:
        deny(f"lancement d'agent refuse : {reason}")
    allow()


if __name__ == "__main__":
    main()
