"""Verifie le message des commits lances par un agent via Bash.

Hook PreToolUse pour Bash. Le hook git commit-msg refait la meme verification
pour Codex et les humains ; celui-ci donne un retour plus tot a Claude.
"""

from __future__ import annotations

import os
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from commit_rules import validate  # noqa: E402
from hook_io import allow, deny, read_input, tool_input, tool_name  # noqa: E402
from shell_segments import split_segments, strip_heredocs, strip_prefixes, tokens  # noqa: E402


def extract_message(words: list[str], heredoc_bodies: list[str]) -> str | None:
    """Message passe par -m, --message ou par un heredoc dans un sous-shell."""
    parts: list[str] = []
    index = 0
    while index < len(words):
        word = words[index]
        if word in ("-m", "--message") and index + 1 < len(words):
            parts.append(words[index + 1])
            index += 2
            continue
        if word.startswith("--message="):
            parts.append(word[len("--message="):])
        elif word.startswith("-m") and len(word) > 2:
            parts.append(word[2:])
        index += 1
    if parts:
        joined = "\n\n".join(parts)
        if "__NV_SUBSHELL__" in joined and heredoc_bodies:
            return heredoc_bodies[0]
        return joined
    if heredoc_bodies and ("-F" in words or "--file" in words):
        return heredoc_bodies[0]
    return None


def main() -> None:
    payload = read_input()
    if tool_name(payload) != "Bash":
        allow()
    command = str(tool_input(payload).get("command") or "")
    _stripped, bodies = strip_heredocs(command)
    for segment in split_segments(command):
        words = strip_prefixes(tokens(segment))
        if len(words) < 2 or os.path.basename(words[0]) != "git":
            continue
        rest = [w for w in words[1:] if not w.startswith("-")]
        if not rest or rest[0] != "commit":
            continue
        if "--allow-empty-message" in words:
            deny("commit sans message refuse")
        message = extract_message(words, bodies)
        if message is None:
            if "-F" in words or "--file" in words:
                continue
            deny("git commit sans -m refuse, un editeur ne peut pas s'ouvrir ici")
        errors = validate(message)
        if errors:
            deny("message de commit refuse : " + " ; ".join(errors))
    allow()


if __name__ == "__main__":
    main()
