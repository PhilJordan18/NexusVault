"""Fichiers secrets que les agents ne lisent ni n'ecrivent.

Module importable et hook PreToolUse pour Read, Edit, Write et Bash.
Ligne de commande pour le hook git pre-commit :

    python3 secret_files.py check chemin1 chemin2 ...
"""

from __future__ import annotations

import fnmatch
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from hook_io import allow, deny, read_input, tool_input, tool_name  # noqa: E402
from shell_segments import split_segments, strip_prefixes, tokens  # noqa: E402

SECRET_PATTERNS = (
    ".env",
    ".env.*",
    "*.pem",
    "*.key",
    "*.p12",
    "*.pfx",
    "*.jks",
    "id_rsa*",
    "id_ed25519*",
    "id_ecdsa*",
    "auth.json",
    "*.sqlite",
    "*.sqlite3",
    ".netrc",
    ".npmrc.local",
)
ALLOWED_EXACT = (".env.example", ".env.testing.example")
SETUP_COPY = ("cp", ".env.example", ".env")


def normalize(path: str) -> str:
    path = path.strip().strip("'\"")
    path = path.replace("\\", "/")
    while path.startswith("./"):
        path = path[2:]
    return path


def is_secret_path(path: str) -> bool:
    path = normalize(path)
    if not path:
        return False
    base = os.path.basename(path)
    if base in ALLOWED_EXACT:
        return False
    for pattern in SECRET_PATTERNS:
        if fnmatch.fnmatch(base, pattern):
            return True
    return False


def secret_tokens(segment: str) -> list[str]:
    words = strip_prefixes(tokens(segment))
    if words[:3] == list(SETUP_COPY):
        return []
    found: list[str] = []
    for word in words:
        candidate = word.lstrip("<>@=")
        if is_secret_path(candidate):
            found.append(candidate)
    return found


def main() -> None:
    payload = read_input()
    name = tool_name(payload)
    data = tool_input(payload)
    if name == "Bash":
        command = str(data.get("command") or "")
        for segment in split_segments(command):
            found = secret_tokens(segment)
            if found:
                deny(
                    f"acces a un fichier secret refuse : {', '.join(found)}. "
                    "Les agents ne lisent ni n'ecrivent .env, cles ou bases locales."
                )
        allow()
    path = str(data.get("file_path") or data.get("notebook_path") or "")
    if path and is_secret_path(path):
        deny(f"acces a un fichier secret refuse : {path}")
    allow()


def cli(argv: list[str]) -> int:
    if len(argv) >= 2 and argv[1] == "check":
        bad = [path for path in argv[2:] if is_secret_path(path)]
        if bad:
            sys.stderr.write("[nv-pre-commit] fichiers secrets dans l'index : " + ", ".join(bad) + "\n")
            return 1
        return 0
    main()
    return 0


if __name__ == "__main__":
    sys.exit(cli(sys.argv))
