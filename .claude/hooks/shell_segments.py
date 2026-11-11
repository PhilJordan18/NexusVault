"""Decoupe une commande shell en segments simples pour les gardes.

Gere les quotes simples et doubles, les separateurs ; | & && || et les
retours a la ligne, les sous-shells $( ) et les backticks, et retire les corps
de heredoc pour ne pas les prendre pour des commandes.
"""

from __future__ import annotations

import re
import shlex

HEREDOC_RE = re.compile(r"<<-?\s*(['\"]?)([A-Za-z_][A-Za-z0-9_]*)\1")


def strip_heredocs(command: str) -> tuple[str, list[str]]:
    """Retourne la commande sans corps de heredoc, et la liste des corps."""
    lines = command.split("\n")
    kept: list[str] = []
    bodies: list[str] = []
    index = 0
    while index < len(lines):
        line = lines[index]
        match = HEREDOC_RE.search(line)
        if not match:
            kept.append(line)
            index += 1
            continue
        delimiter = match.group(2)
        kept.append(line[: match.start()] + line[match.end():])
        body: list[str] = []
        index += 1
        while index < len(lines) and lines[index].strip() != delimiter:
            body.append(lines[index])
            index += 1
        bodies.append("\n".join(body))
        index += 1
    return "\n".join(kept), bodies


def split_segments(command: str) -> list[str]:
    """Segments de commande, sous-shells inclus comme segments separes."""
    command, _bodies = strip_heredocs(command)
    segments: list[str] = []
    _split_into(command, segments)
    return [segment for segment in segments if segment.strip()]


def _split_into(text: str, out: list[str]) -> None:
    current: list[str] = []
    in_single = False
    in_double = False
    i = 0
    length = len(text)
    while i < length:
        char = text[i]
        nxt = text[i + 1] if i + 1 < length else ""
        if in_single:
            current.append(char)
            if char == "'":
                in_single = False
            i += 1
            continue
        if char == "\\" and nxt:
            current.append(char + nxt)
            i += 2
            continue
        if char == "'" and not in_double:
            in_single = True
            current.append(char)
            i += 1
            continue
        if char == '"':
            in_double = not in_double
            current.append(char)
            i += 1
            continue
        if char == "$" and nxt == "(":
            end = _matching_paren(text, i + 1)
            inner = text[i + 2:end]
            _split_into(inner, out)
            current.append(" __NV_SUBSHELL__ ")
            i = end + 1
            continue
        if char == "`":
            end = text.find("`", i + 1)
            if end == -1:
                end = length
            _split_into(text[i + 1:end], out)
            current.append(" __NV_SUBSHELL__ ")
            i = end + 1
            continue
        if not in_double and (char in ";\n" or (char == "&" and nxt == "&") or (char == "|")):
            out.append("".join(current))
            current = []
            i += 2 if (char == "&" and nxt == "&") or (char == "|" and nxt == "|") else 1
            continue
        if not in_double and char == "&":
            out.append("".join(current))
            current = []
            i += 1
            continue
        current.append(char)
        i += 1
    out.append("".join(current))


def _matching_paren(text: str, open_index: int) -> int:
    depth = 0
    in_single = False
    in_double = False
    for i in range(open_index, len(text)):
        char = text[i]
        if in_single:
            if char == "'":
                in_single = False
            continue
        if char == "\\":
            continue
        if char == "'" and not in_double:
            in_single = True
        elif char == '"':
            in_double = not in_double
        elif char == "(" and not in_double:
            depth += 1
        elif char == ")" and not in_double:
            depth -= 1
            if depth == 0:
                return i
    return len(text)


def tokens(segment: str) -> list[str]:
    try:
        return shlex.split(segment, posix=True)
    except ValueError:
        return segment.split()


PREFIX_COMMANDS = {"sudo", "env", "command", "nice", "time", "nohup", "exec"}


def strip_prefixes(words: list[str]) -> list[str]:
    """Retire les assignations VAR=val et les prefixes comme sudo ou env."""
    result = list(words)
    while result:
        head = result[0]
        if re.match(r"^[A-Za-z_][A-Za-z0-9_]*=", head):
            result.pop(0)
            continue
        if head in PREFIX_COMMANDS:
            result.pop(0)
            while result and result[0].startswith("-"):
                result.pop(0)
            continue
        break
    return result
