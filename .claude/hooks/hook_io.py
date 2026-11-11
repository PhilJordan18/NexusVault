"""Entrees/sorties communes des hooks Claude Code de NexusVault.

Un hook lit un JSON sur stdin, puis sort 0 pour laisser passer ou 2 pour
bloquer, le message d'explication etant ecrit sur stderr.
"""

from __future__ import annotations

import json
import os
import sys
from typing import Any

ROLES = ("architect", "secops", "ui", "dev")
PROTECTED_BRANCHES = ("main", "master", "dev")


def read_input() -> dict[str, Any]:
    try:
        data = json.load(sys.stdin)
    except (json.JSONDecodeError, ValueError):
        return {}
    return data if isinstance(data, dict) else {}


def tool_name(payload: dict[str, Any]) -> str:
    return str(payload.get("tool_name") or "")


def tool_input(payload: dict[str, Any]) -> dict[str, Any]:
    value = payload.get("tool_input")
    return value if isinstance(value, dict) else {}


def project_dir(payload: dict[str, Any] | None = None) -> str:
    env = os.environ.get("CLAUDE_PROJECT_DIR")
    if env:
        return env
    if payload and payload.get("cwd"):
        return str(payload["cwd"])
    return os.getcwd()


def current_role() -> str:
    """Role de la session : variable NV_ROLE, sinon fichier .nv/.role, sinon dev."""
    role = os.environ.get("NV_ROLE", "").strip().lower()
    if role in ROLES:
        return role
    role_file = os.path.join(project_dir(), ".nv", ".role")
    try:
        with open(role_file, encoding="utf-8") as handle:
            role = handle.read().strip().lower()
    except OSError:
        return "dev"
    return role if role in ROLES else "dev"


def deny(message: str) -> None:
    sys.stderr.write(f"[nv-hook] {message}\n")
    sys.exit(2)


def allow() -> None:
    sys.exit(0)


def is_protected_branch(name: str) -> bool:
    name = name.strip()
    if name.startswith("refs/heads/"):
        name = name[len("refs/heads/"):]
    if name.startswith("origin/"):
        name = name[len("origin/"):]
    return name in PROTECTED_BRANCHES
