"""Regles de message de commit NexusVault.

Convention : type(scope)!: sujet. Utilise par guard_commit.py (hook Claude) et
par .githooks/commit-msg (hook git) via la ligne de commande :

    python3 commit_rules.py chemin/vers/COMMIT_EDITMSG
"""

from __future__ import annotations

import os
import re
import sys

TYPES = (
    "feat",
    "fix",
    "security",
    "refactor",
    "perf",
    "test",
    "docs",
    "style",
    "build",
    "ci",
    "chore",
    "revert",
)
SUBJECT_RE = re.compile(
    r"^(?P<type>[a-z]+)(\((?P<scope>[a-z0-9._/-]+)\))?(?P<bang>!)?: (?P<desc>\S.*)$"
)
SUBJECT_MAX = 72
DELIVER_TRAILER_RE = re.compile(r"^NV-Deliver:\s*M-\d{3}\s*$", re.MULTILINE)
FORBIDDEN_PREFIXES = ("fixup!", "squash!", "wip", "WIP")


def validate(message: str, allow_deliver_trailer: bool = False) -> list[str]:
    errors: list[str] = []
    lines = [line for line in message.strip().split("\n") if not line.startswith("#")]
    if not lines or not lines[0].strip():
        return ["message de commit vide"]
    subject = lines[0].rstrip()
    if subject.startswith(FORBIDDEN_PREFIXES):
        errors.append("les commits fixup, squash et WIP sont interdits, l'historique reste lineaire et propre")
    match = SUBJECT_RE.match(subject)
    if not match:
        errors.append(
            "sujet attendu sous la forme type(scope): description, par exemple "
            "fix(auth): purger les drapeaux de session avant login"
        )
    else:
        if match.group("type") not in TYPES:
            errors.append(f"type inconnu {match.group('type')!r}, types permis : {', '.join(TYPES)}")
        if match.group("desc").endswith("."):
            errors.append("le sujet ne se termine pas par un point")
    if len(subject) > SUBJECT_MAX:
        errors.append(f"sujet de {len(subject)} caracteres, maximum {SUBJECT_MAX}")
    if len(lines) > 1 and lines[1].strip():
        errors.append("une ligne vide separe le sujet du corps")
    if DELIVER_TRAILER_RE.search(message) and not allow_deliver_trailer:
        errors.append("le trailer NV-Deliver est reserve au script nv-deliver")
    return errors


def main(argv: list[str]) -> int:
    if len(argv) < 2:
        sys.stderr.write("usage: commit_rules.py COMMIT_MSG_FILE\n")
        return 1
    with open(argv[1], encoding="utf-8") as handle:
        message = handle.read()
    allow_trailer = os.environ.get("NV_DELIVER_RUN") == "1"
    errors = validate(message, allow_deliver_trailer=allow_trailer)
    if errors:
        sys.stderr.write("[nv-commit-msg] message refuse :\n")
        for error in errors:
            sys.stderr.write(f"  - {error}\n")
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main(sys.argv))
