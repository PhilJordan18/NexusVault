"""Garde des commandes shell : git, gh, rm et ecritures sur fichiers proteges.

Hook PreToolUse pour Bash. Chaque segment de la commande est verifie.
"""

from __future__ import annotations

import os
import re
import subprocess
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from guard_workflow_files import violation  # noqa: E402
from hook_io import (  # noqa: E402
    allow,
    current_role,
    deny,
    is_protected_branch,
    project_dir,
    read_input,
    tool_input,
    tool_name,
)
from shell_segments import split_segments, strip_prefixes, tokens  # noqa: E402

GIT_GLOBAL_WITH_ARG = {"-C", "-c", "--git-dir", "--work-tree", "--namespace"}
FORBIDDEN_GIT_SUBCOMMANDS = {
    "merge": "aucun merge, l'historique de NexusVault est lineaire ; nv-deliver rebase puis pousse",
    "filter-branch": "reecriture d'historique interdite",
    "filter-repo": "reecriture d'historique interdite",
    "replace": "reecriture d'historique interdite",
    "clean": "git clean supprime du travail non versionne, interdit",
}
REBASE_ALLOWED_FLAGS = {"--continue", "--abort", "--skip", "--quit", "--show-current-patch"}
RM_FORBIDDEN_TARGETS = {".", "..", "/", "~", "*", ".git", ".nv", ".githooks", ".claude", ".agents", ".github", "docs"}
WRITE_COMMANDS = {"tee", "touch", "mkdir", "cp", "mv", "ln", "chmod", "sed", "truncate", "install"}


def git_subcommand(words: list[str]) -> tuple[str, list[str]]:
    """Retourne (sous-commande, arguments) en sautant les options globales."""
    rest = words[1:]
    while rest and rest[0].startswith("-"):
        option = rest.pop(0)
        if option in GIT_GLOBAL_WITH_ARG and rest:
            value = rest.pop(0)
            if option == "-c" and "hookspath" in value.lower():
                deny("desactivation des hooks git refusee (core.hooksPath)")
        elif option.startswith("--git-dir=") or option.startswith("--work-tree="):
            continue
    if not rest:
        return "", []
    return rest[0], rest[1:]


def current_branch(cwd: str) -> str:
    try:
        result = subprocess.run(
            ["git", "symbolic-ref", "--short", "HEAD"],
            cwd=cwd, capture_output=True, text=True, timeout=5, check=False,
        )
    except (OSError, subprocess.SubprocessError):
        return ""
    return result.stdout.strip() if result.returncode == 0 else ""


def upstream_branch(cwd: str) -> str:
    try:
        result = subprocess.run(
            ["git", "rev-parse", "--abbrev-ref", "@{u}"],
            cwd=cwd, capture_output=True, text=True, timeout=5, check=False,
        )
    except (OSError, subprocess.SubprocessError):
        return ""
    return result.stdout.strip() if result.returncode == 0 else ""


def check_git_push(args: list[str], cwd: str) -> None:
    flags = [arg for arg in args if arg.startswith("-")]
    positional = [arg for arg in args if not arg.startswith("-")]
    for flag in flags:
        if flag in ("-f", "--force", "--mirror", "--delete", "-d", "--prune", "--all", "--tags", "--no-verify") \
                or flag.startswith("--force"):
            deny(f"git push {flag} refuse : pas de force push, pas de suppression de branche, pas de contournement des hooks")
    refspecs = positional[1:] if positional else []
    for refspec in refspecs:
        if refspec.startswith("+"):
            deny("refspec force (+) refuse")
        if refspec.startswith(":"):
            deny("suppression de branche distante refusee")
        destination = refspec.split(":", 1)[1] if ":" in refspec else refspec
        if is_protected_branch(destination):
            deny(
                f"push vers {destination} refuse. main ne recoit que des PR fusionnees par le PM, "
                "dev ne recoit que des livraisons via .nv/bin/nv-deliver"
            )
    if not refspecs:
        branch = current_branch(cwd)
        if is_protected_branch(branch):
            deny(f"push de la branche courante {branch} refuse, elle est protegee")
        upstream = upstream_branch(cwd)
        if is_protected_branch(upstream):
            deny(f"push vers l'upstream {upstream} refuse, elle est protegee")


def check_git(words: list[str], cwd: str) -> None:
    if "--no-verify" in words:
        deny("--no-verify refuse, les hooks font partie du contrat")
    sub, args = git_subcommand(words)
    if sub in FORBIDDEN_GIT_SUBCOMMANDS:
        if sub == "merge" and "--abort" in args:
            return
        deny(f"git {sub} refuse : {FORBIDDEN_GIT_SUBCOMMANDS[sub]}")
    if sub == "push":
        check_git_push(args, cwd)
    elif sub == "rebase":
        if not any(arg in REBASE_ALLOWED_FLAGS for arg in args):
            deny("git rebase refuse hors de nv-deliver ; seuls --continue, --abort et --skip sont permis pour finir un rebase lance par le script")
    elif sub == "commit":
        if "--amend" in args:
            deny("git commit --amend refuse, on ajoute un commit, on ne reecrit pas")
        if "--no-verify" in args or "-n" in args:
            deny("--no-verify refuse")
    elif sub == "reset":
        if "--hard" in args or "--merge" in args:
            deny("git reset --hard refuse, il detruit du travail ; utiliser git stash ou git restore sur un fichier precis")
    elif sub in ("checkout", "switch", "restore"):
        if "-f" in args or "--force" in args or "--discard-changes" in args:
            deny(f"git {sub} force refuse")
        targets = [arg for arg in args if not arg.startswith("-")]
        if sub == "restore" and "--staged" not in args and any(t in (".", "*", ":/") for t in targets):
            deny("git restore . refuse, il efface toutes les modifications ; cibler un fichier")
        if sub == "checkout" and "--" in args and any(t in (".", "*") for t in targets):
            deny("git checkout -- . refuse, il efface toutes les modifications")
        if sub == "checkout" and targets == ["."]:
            deny("git checkout . refuse, il efface toutes les modifications")
    elif sub == "branch":
        if "-D" in args or "-f" in args or "--force" in args or "-M" in args:
            deny("suppression ou renommage force de branche refuse")
        if "-d" in args or "--delete" in args:
            for arg in args:
                if is_protected_branch(arg):
                    deny(f"suppression de {arg} refusee")
    elif sub == "stash":
        if args[:1] in (["drop"], ["clear"]):
            deny("git stash drop/clear refuse, il detruit du travail")
    elif sub == "tag":
        if "-d" in args or "-f" in args or "--delete" in args or "--force" in args:
            deny("suppression ou ecrasement de tag refuse")
    elif sub == "config":
        joined = " ".join(args).lower()
        if "hookspath" in joined or "--global" in args or "--system" in args:
            deny("modification de core.hooksPath ou de la config globale refusee")
    elif sub == "update-ref" and "-d" in args:
        deny("suppression de ref refusee")
    elif sub == "reflog" and args[:1] in (["expire"], ["delete"]):
        deny("purge du reflog refusee")
    elif sub == "gc" and any(arg.startswith("--prune") for arg in args):
        deny("git gc --prune refuse")


def check_gh(words: list[str]) -> None:
    rest = words[1:]
    if rest[:2] == ["pr", "merge"]:
        deny("gh pr merge refuse, seul le PM fusionne vers main")
    if rest[:2] == ["repo", "delete"]:
        deny("gh repo delete refuse")
    if rest[:1] == ["api"]:
        joined = " ".join(rest).lower()
        mutating = any(method in rest for method in ("-X", "--method")) and any(
            m in rest for m in ("PUT", "DELETE", "PATCH", "POST", "put", "delete", "patch", "post")
        )
        if mutating and re.search(r"/(merges?|rulesets|protection|branches/|git/refs)", joined):
            deny("modification de branches, merges ou rulesets via gh api refusee")


def check_rm(words: list[str]) -> None:
    flags = [w for w in words[1:] if w.startswith("-")]
    targets = [w for w in words[1:] if not w.startswith("-")]
    recursive = any("r" in f.lstrip("-") or f == "--recursive" for f in flags)
    for target in targets:
        cleaned = target.rstrip("/") or "/"
        if cleaned in RM_FORBIDDEN_TARGETS or cleaned.startswith(".git/") or cleaned.startswith("/"):
            deny(f"rm sur {target} refuse")
        if recursive and cleaned.startswith((".nv/", ".claude/", ".githooks/", ".github/", ".agents/", "docs/")):
            deny(f"rm -r sur {target} refuse, dossier de gouvernance")


def write_targets(words: list[str]) -> list[str]:
    """Chemins qu'un segment ecrit : redirections et commandes d'ecriture."""
    targets: list[str] = []
    for index, word in enumerate(words):
        if word in (">", ">>", "&>", "1>", "2>", "&>>") and index + 1 < len(words):
            targets.append(words[index + 1])
        elif word.startswith((">", "&>")) and len(word) > 1 and not word.startswith(">&"):
            targets.append(word.lstrip(">&"))
    head = words[0] if words else ""
    if head in WRITE_COMMANDS:
        positional = [w for w in words[1:] if not w.startswith("-")]
        if head in ("cp", "mv", "ln", "install") and positional:
            targets.append(positional[-1])
        elif head == "sed" and any(f.startswith("-i") for f in words[1:]):
            targets.extend(positional[1:] if positional else [])
        else:
            targets.extend(positional)
    return targets


def check_writes(words: list[str], role: str, root: str) -> None:
    for target in write_targets(words):
        reason = violation(target, role, root)
        if reason:
            deny(f"ecriture refusee, {reason}")


def check_segment(segment: str, role: str, cwd: str, root: str) -> None:
    words = strip_prefixes(tokens(segment))
    if not words:
        return
    head = os.path.basename(words[0])
    if head == "git":
        check_git(words, cwd)
    elif head == "gh":
        check_gh(words)
    elif head == "rm":
        check_rm(words)
    if head in ("nv-deliver",) or words[0].endswith("/nv-deliver"):
        if role == "architect":
            deny("la session Architecture ne livre pas vers dev")
    check_writes(words, role, root)


def main() -> None:
    payload = read_input()
    if tool_name(payload) != "Bash":
        allow()
    command = str(tool_input(payload).get("command") or "")
    if not command.strip():
        allow()
    role = current_role()
    root = project_dir(payload)
    cwd = str(payload.get("cwd") or root)
    for segment in split_segments(command):
        check_segment(segment, role, cwd, root)
    allow()


if __name__ == "__main__":
    main()
