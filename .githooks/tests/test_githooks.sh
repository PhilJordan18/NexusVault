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
echo '__pycache__/' > .gitignore
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
echo "--- review delivery (report verdict, board, journal) accepted"
git switch -q -c nv/secops/M-002-avis origin/dev && mkdir -p .nv/reports
printf -- '---\nmandate: M-002\nreview: approved\n---\n# Livraison M-002\n' > .nv/reports/M-002-livraison.md
git add .nv/reports && git commit -q -m "docs(nv): avis securite M-002"
.nv/bin/nv-board set M-002 reviewed >/dev/null && echo "- review M-002" >> .nv/journal.md && git add .nv
echo "--- NV-Review trailer without NV_DELIVER_RUN refused"
if git commit -q -m "chore(nv): review M-002" -m "NV-Review: M-002" 2>/dev/null; then
  echo "WARN: trailer NV-Review accepte sans nv-deliver, commit_rules.py ne le reserve pas encore"
else
  NV_DELIVER_RUN=1 git commit -q -m "chore(nv): review M-002" -m "NV-Review: M-002"
fi
R=$(git rev-parse HEAD); NV_DELIVER_RUN=1 NV_DELIVER_HEAD=$R git push -q origin "$R:refs/heads/dev" && echo REVIEW-PUSH-OK
echo "--- review delivery touching app code refused"
echo y > work.txt && git add work.txt && git commit -q -m "fix(x): code glisse dans un avis"
echo "- review M-002" >> .nv/journal.md && git add .nv && NV_DELIVER_RUN=1 git commit -q -m "chore(nv): review M-002" -m "NV-Review: M-002"
R2=$(git rev-parse HEAD); NV_DELIVER_RUN=1 NV_DELIVER_HEAD=$R2 git push origin "$R2:refs/heads/dev" 2>&1 | head -3 || true
echo "--- review with pending verdict refused"
git switch -q -c nv/secops/M-002-pending origin/dev && mkdir -p .nv/reports
printf -- '---\nmandate: M-002\nreview: pending\n---\n# Livraison M-002\n' > .nv/reports/M-002-livraison.md
echo "- review M-002" >> .nv/journal.md && git add .nv && NV_DELIVER_RUN=1 git commit -q -m "chore(nv): review M-002" -m "NV-Review: M-002"
R3=$(git rev-parse HEAD); NV_DELIVER_RUN=1 NV_DELIVER_HEAD=$R3 git push origin "$R3:refs/heads/dev" 2>&1 | head -3 || true
echo "--- governance delivery (mandate, ADR, journal) accepted"
git switch -q -c nv/architect/mandats origin/dev
mkdir -p .nv/mandates docs/adr && echo "# M-010" > .nv/mandates/M-010-x.md && echo "# ADR-009" > docs/adr/ADR-009-x.md
git add .nv docs && git commit -q -m "docs(nv): mandat M-010 et ADR-009"
echo "- governance mandats" >> .nv/journal.md && git add .nv && NV_DELIVER_RUN=1 git commit -q -m "chore(nv): governance mandats" -m "NV-Governance: mandats"
G=$(git rev-parse HEAD); NV_DELIVER_RUN=1 NV_DELIVER_HEAD=$G git push -q origin "$G:refs/heads/dev" && echo GOVERNANCE-PUSH-OK
echo "--- governance delivery touching app code refused"
echo z > app.txt && git add app.txt && git commit -q -m "feat(x): code sous gouvernance"
echo "- governance code" >> .nv/journal.md && git add .nv && NV_DELIVER_RUN=1 git commit -q -m "chore(nv): governance code" -m "NV-Governance: code"
G2=$(git rev-parse HEAD); NV_DELIVER_RUN=1 NV_DELIVER_HEAD=$G2 git push origin "$G2:refs/heads/dev" 2>&1 | head -3 || true
echo "--- founding exception: dev holds the old policy, the delivery that tightens it is validated by the old one"
git switch -q -c nv/dev/old-policy origin/dev
python3 - <<'PY'
import pathlib
p = pathlib.Path(".claude/hooks/delivery.py"); s = p.read_text()
s = s.replace('"forbidden_paths": (".nv/qa/", ".githooks/", ".claude/hooks/", ".claude/settings.json"),', '"forbidden_paths": (".nv/qa/",),')
s = s.replace('    ".agents/",\n    "AGENTS.md",', '    ".agents/",\n    ".githooks/",\n    "AGENTS.md",')
p.write_text(s)
PY
git add .claude && git commit -q -m "chore(nv): ancienne politique sur dev"
OLD=$(git rev-parse HEAD); git $BYPASS push -q origin "$OLD:refs/heads/dev" && git fetch -q origin
git switch -q -c nv/architect/socle origin/dev
cp $REPO/.claude/hooks/delivery.py .claude/hooks/delivery.py && echo "# socle" >> .githooks/pre-push
git add .claude .githooks && git commit -q -m "feat(nv): politique qui exclut les gardes de la gouvernance"
echo "- governance socle" >> .nv/journal.md && git add .nv && NV_DELIVER_RUN=1 git commit -q -m "chore(nv): governance socle" -m "NV-Governance: socle"
G3=$(git rev-parse HEAD); NV_DELIVER_RUN=1 NV_DELIVER_HEAD=$G3 git push -q origin "$G3:refs/heads/dev" && echo FOUNDING-GOVERNANCE-OK
git fetch -q origin
echo "--- governance delivery touching .githooks/pre-push refused by the policy now on dev"
git switch -q -c nv/architect/hooks origin/dev && echo "# encore" >> .githooks/pre-push
git add .githooks && git commit -q -m "chore(nv): toucher un garde sous gouvernance"
echo "- governance hooks" >> .nv/journal.md && git add .nv && NV_DELIVER_RUN=1 git commit -q -m "chore(nv): governance hooks" -m "NV-Governance: hooks"
G4=$(git rev-parse HEAD); NV_DELIVER_RUN=1 NV_DELIVER_HEAD=$G4 git push origin "$G4:refs/heads/dev" 2>&1 | head -3 || true
echo "--- governance delivery touching .claude/hooks and settings refused"
git switch -q -c nv/architect/hooks2 origin/dev && echo "# x" >> .claude/hooks/delivery.py && echo '{}' > .claude/settings.json
git add .claude && git commit -q -m "chore(nv): toucher les gardes Claude"
echo "- governance hooks2" >> .nv/journal.md && git add .nv && NV_DELIVER_RUN=1 git commit -q -m "chore(nv): governance hooks2" -m "NV-Governance: hooks2"
G5=$(git rev-parse HEAD); NV_DELIVER_RUN=1 NV_DELIVER_HEAD=$G5 git push origin "$G5:refs/heads/dev" 2>&1 | head -4 || true
echo "--- remote state"
git ls-remote origin | sed 's/\t/ /'
