"""Revalide une plage poussee vers dev, depuis le hook pre-push.

    python3 check_delivery.py <sha distant de dev> <sha pousse>

Lit le trailer du commit de tete, puis applique la politique de delivery.py
telle qu'elle est deja sur dev (au sha distant) : chemins permis dans la
plage et dans le commit de livraison, preuve QA et rapport pour un mandat,
verdict du rapport pour un avis. Une livraison qui change la politique est
validee par l'ancienne et impose la nouvelle aux suivantes.
"""

from __future__ import annotations

import os
import subprocess
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from delivery import policy_at  # noqa: E402
from qa_evidence import load, problems  # noqa: E402

ZERO = "0" * 40


def git(*args: str) -> str:
    result = subprocess.run(["git", *args], capture_output=True, text=True, check=False)
    return result.stdout if result.returncode == 0 else ""


def blob(sha: str, path: str) -> str | None:
    result = subprocess.run(["git", "show", f"{sha}:{path}"], capture_output=True, text=True, check=False)
    return result.stdout if result.returncode == 0 else None


def check(remote: str, local: str) -> list[str]:
    errors: list[str] = []
    policy = policy_at(remote) if remote and remote != ZERO else policy_at(local)
    parsed = policy.parse_trailer(git("log", "-1", "--format=%B", local))
    if parsed is None:
        return ["le commit pousse vers dev n'a pas de trailer NV-Deliver, NV-Review ou NV-Governance"]
    kind, ref = parsed
    if not policy.valid_ref(kind, ref):
        return [f"reference {ref!r} invalide pour une livraison {kind}"]
    tip_paths = git("diff", "--name-only", f"{local}^", local).split()
    for path in policy.disallowed_tip_paths(tip_paths, kind):
        errors.append(f"le commit de livraison touche {path}, il ne doit toucher que .nv/")
    range_paths = git("diff", "--name-only", remote, local).split() if remote and remote != ZERO else []
    for path in policy.disallowed_range_paths(range_paths, kind):
        errors.append(f"une livraison {kind} ne peut pas toucher {path}")
    if kind == "deliver":
        tested = git("rev-parse", f"{local}^").strip()
        evidence_text = blob(local, f".nv/qa/{ref}.json")
        if evidence_text is None:
            errors.append(f"preuve .nv/qa/{ref}.json absente du commit livre")
        else:
            try:
                errors.extend(problems(load(evidence_text, from_text=True), ref, tested))
            except ValueError as error:
                errors.append(f"preuve illisible : {error}")
        if blob(local, f".nv/reports/{ref}-livraison.md") is None:
            errors.append(f"rapport .nv/reports/{ref}-livraison.md absent du commit livre")
    elif kind == "review":
        report = blob(local, f".nv/reports/{ref}-livraison.md")
        if report is None:
            errors.append(f"rapport .nv/reports/{ref}-livraison.md absent, un avis porte sur un rapport existant")
        else:
            verdict = policy.review_verdict(report)
            if verdict not in policy.REVIEW_VERDICTS:
                errors.append(f"review: {verdict!r} dans le rapport, attendu {' ou '.join(policy.REVIEW_VERDICTS)}")
    return errors


def main(argv: list[str]) -> int:
    if len(argv) < 3:
        sys.stderr.write("usage: check_delivery.py REMOTE_SHA LOCAL_SHA\n")
        return 1
    errors = check(argv[1], argv[2])
    if errors:
        sys.stderr.write("[nv-pre-push] livraison vers dev refusee :\n")
        for error in errors:
            sys.stderr.write(f"  - {error}\n")
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main(sys.argv))
