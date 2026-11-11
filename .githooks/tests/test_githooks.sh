#!/usr/bin/env bash
# Test de bout en bout des hooks git dans un depot jetable. Les contournements
# -c core.hooksPath=/dev/null servent uniquement a preparer le scenario.
# Lancer : bash .githooks/tests/test_githooks.sh
set -e
REPO="$(cd "$(dirname "$0")/../.." && pwd)"
SCRATCH="$(mktemp -d)"
rm -rf "$SCRATCH" && mkdir -p "$SCRATCH" && cd "$SCRATCH"
git init -q --bare origin.git
git init -q -b main work && cd work
git config user.email t@t && git config user.name t
mkdir -p .claude/hooks .githooks .nv/qa .nv/reports .nv/bin
cp $REPO/.claude/hooks/*.py .claude/hooks/
find "$REPO/.githooks" -maxdepth 1 -type f -exec cp {} .githooks/ \;
cp $REPO/.nv/bin/nv-board .nv/bin/
cp $REPO/.nv/board.json .nv/
git config core.hooksPath .githooks
git remote add origin ../origin.git
echo base > base.txt && git add -A
echo "--- commit on main must be refused"
git commit -q -m "chore: init" 2>&1 | head -2 || true
BYPASS="-c core.hooksPath=/dev/null"
git $BYPASS commit -q -m "chore: init"
echo "--- push main refused"
git push -q origin main 2>&1 | head -2 || true
git $BYPASS push -q origin main
git $BYPASS push -q origin main:dev
git fetch -q origin
echo "--- bad commit message on work branch"
git switch -q -c nv/dev/M-002-x
echo w > work.txt && git add work.txt
git commit -q -m "Fixed stuff" 2>&1 | head -3 || true
echo "--- secret in index"
echo s > .env && git add -f .env && (git commit -q -m "feat: x" 2>&1 | head -2 || true); git rm -q --cached .env; rm .env
echo "--- good commit"
git commit -q -m "feat(x): ajouter work" && echo committed
echo "--- manual push to dev must be refused"
git push origin HEAD:dev 2>&1 | head -2 || true
echo "--- push work branch allowed"
git push -q -u origin nv/dev/M-002-x && echo pushed-work
echo "--- rebase of pushed commits refused without deliver"
git switch -q -c nv/dev/M-008-adv origin/dev && echo adv > adv.txt && git add adv.txt && git commit -q -m "feat: advance dev"
ADV=$(git rev-parse HEAD); NV_DELIVER_RUN=1 NV_DELIVER_HEAD=$ADV git push -q origin "$ADV:refs/heads/dev" 2>&1 | head -1 || true
git $BYPASS push -q origin "$ADV:refs/heads/dev"
git fetch -q origin && git switch -q nv/dev/M-002-x
git rebase origin/dev 2>&1 | head -2 || true
echo "--- same rebase allowed inside deliver"
NV_DELIVER_RUN=1 git rebase -q origin/dev && echo rebase-in-deliver-ok
echo "--- simulate deliver commit and push to dev"
TESTED=$(git rev-parse HEAD)
python3 - "$TESTED" <<'PY'
import sys, os
sys.path.insert(0, ".claude/hooks")
from qa_evidence import build, write
write(os.getcwd(), build("M-002", "nv/dev/M-002-x", sys.argv[1], [{"command": "composer run ci:check", "exit_code": 0, "duration_s": 1}]))
PY
echo "# Livraison M-002" > .nv/reports/M-002-livraison.md
.nv/bin/nv-board set M-002 in_progress --branch nv/dev/M-002-x >/dev/null
.nv/bin/nv-board set M-002 delivered >/dev/null
git add .nv
echo "--- trailer without NV_DELIVER_RUN refused"
git commit -q -m "chore(nv): deliver M-002" -m "NV-Deliver: M-002" 2>&1 | head -2 || true
NV_DELIVER_RUN=1 git commit -q -m "chore(nv): deliver M-002" -m "NV-Deliver: M-002" && echo deliver-committed
HEAD=$(git rev-parse HEAD)
echo "--- push dev with wrong env refused"
NV_DELIVER_RUN=1 NV_DELIVER_HEAD=abc git push origin "$HEAD:refs/heads/dev" 2>&1 | head -2 || true
echo "--- push dev with right env accepted"
NV_DELIVER_RUN=1 NV_DELIVER_HEAD=$HEAD git push -q origin "$HEAD:refs/heads/dev" && echo DEV-PUSH-OK
echo "--- deliver commit touching app code refused"
echo z > work.txt && git add work.txt && NV_DELIVER_RUN=1 git commit -q -m "chore(nv): deliver M-002" -m "NV-Deliver: M-002"
H2=$(git rev-parse HEAD); NV_DELIVER_RUN=1 NV_DELIVER_HEAD=$H2 git push origin "$H2:refs/heads/dev" 2>&1 | head -2 || true
echo "--- merge commit refused"
git switch -q -c nv/dev/M-009-y origin/dev && echo m > m.txt && git add m.txt && git commit -q -m "feat: m"
git switch -q nv/dev/M-002-x && git $BYPASS merge -q --no-ff nv/dev/M-009-y -m "merge"
git push origin HEAD:refs/heads/nv/dev/M-002-x 2>&1 | head -2 || true
echo "--- force push refused"
git push --force origin HEAD~4:refs/heads/nv/dev/M-002-x 2>&1 | head -2 || true
echo "--- remote state"
git ls-remote origin | sed 's/\t/ /'
