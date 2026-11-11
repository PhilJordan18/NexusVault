"""Valide une preuve QA pour un mandat et le sha teste.

    python3 check_qa_evidence.py M-002 <sha teste> [chemin.json | - pour stdin]

La preuve est ecrite par nv-deliver apres les checks sur le commit de travail
le plus recent, puis commitee dans un commit de livraison qui ne touche que
.nv/. Le hook pre-push verifie que le parent du commit livre est le sha teste.
"""

from __future__ import annotations

import os
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from qa_evidence import evidence_path, load, problems  # noqa: E402


def main(argv: list[str]) -> int:
    if len(argv) < 3:
        sys.stderr.write("usage: check_qa_evidence.py M-xxx SHA [fichier|-]\n")
        return 1
    mandate, head = argv[1], argv[2]
    source = argv[3] if len(argv) > 3 else evidence_path(os.getcwd(), mandate)
    try:
        if source == "-":
            evidence = load(sys.stdin.read(), from_text=True)
        else:
            evidence = load(source)
    except (OSError, ValueError) as error:
        sys.stderr.write(f"[nv-qa] preuve illisible pour {mandate} : {error}\n")
        return 1
    found = problems(evidence, mandate, head)
    if found:
        sys.stderr.write(f"[nv-qa] preuve refusee pour {mandate} :\n")
        for problem in found:
            sys.stderr.write(f"  - {problem}\n")
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main(sys.argv))
