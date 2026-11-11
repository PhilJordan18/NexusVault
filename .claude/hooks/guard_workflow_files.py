"""Fichiers de gouvernance que seul certains roles peuvent modifier.

Module importable et hook PreToolUse pour Edit, Write, MultiEdit, NotebookEdit.
"""

from __future__ import annotations

import os
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from hook_io import allow, current_role, deny, project_dir, read_input, tool_input  # noqa: E402

RULES: tuple[tuple[str, tuple[str, ...], str], ...] = (
    (".nv/board.json", (), "passe par .nv/bin/nv-board"),
    (".nv/qa/", (), "ecrit uniquement par nv-deliver"),
    (".nv/mandates/", ("architect",), "les mandats sont rediges par la session Architecture"),
    ("docs/adr/", ("architect",), "les ADR sont rediges par la session Architecture"),
    (".github/", ("secops", "architect"), "la CI appartient a la session Securite"),
    (".githooks/", ("secops", "architect"), "les hooks git appartiennent a la session Securite"),
    (".claude/hooks/", ("secops", "architect"), "les hooks Claude appartiennent a la session Securite"),
    (".claude/settings.json", ("secops", "architect"), "les reglages Claude appartiennent a la session Securite"),
    (".claude/agents/", ("secops", "architect"), "les agents sont definis par Architecture ou Securite"),
    (".agents/skills/", ("secops", "architect"), "les skills sont definis par Architecture ou Securite"),
    (".claude/skills/", ("secops", "architect"), "les skills sont definis par Architecture ou Securite"),
    ("AGENTS.md", ("architect",), "AGENTS.md est le contrat, tenu par Architecture"),
    ("CLAUDE.md", ("architect",), "CLAUDE.md est tenu par Architecture"),
)


def relative_path(path: str, root: str) -> str:
    path = path.strip().strip("'\"").replace("\\", "/")
    if os.path.isabs(path):
        try:
            path = os.path.relpath(path, root)
        except ValueError:
            return path
    while path.startswith("./"):
        path = path[2:]
    return path.replace("\\", "/")


def violation(path: str, role: str, root: str) -> str | None:
    """Retourne le motif de refus, ou None si le role peut ecrire ce chemin."""
    rel = relative_path(path, root)
    if rel.startswith("../"):
        return None
    for prefix, roles, reason in RULES:
        matches = rel == prefix or (prefix.endswith("/") and rel.startswith(prefix))
        if matches and role not in roles:
            who = "personne" if not roles else " ou ".join(roles)
            return f"{rel} : {reason} (roles permis : {who}, role courant : {role})"
    return None


def main() -> None:
    payload = read_input()
    data = tool_input(payload)
    path = str(data.get("file_path") or data.get("notebook_path") or "")
    if not path:
        allow()
    reason = violation(path, current_role(), project_dir(payload))
    if reason:
        deny(f"ecriture refusee, {reason}")
    allow()


if __name__ == "__main__":
    main()
