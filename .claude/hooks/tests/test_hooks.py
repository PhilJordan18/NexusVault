"""Tests des hooks Claude Code de NexusVault.

    python3 -m unittest discover -s .claude/hooks/tests -v
"""

from __future__ import annotations

import json
import os
import subprocess
import sys
import tempfile
import unittest

HOOKS = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
ROOT = os.path.dirname(os.path.dirname(HOOKS))
sys.path.insert(0, HOOKS)

from commit_rules import validate  # noqa: E402
from shell_segments import split_segments, strip_heredocs  # noqa: E402


def run_hook(script: str, payload: dict, role: str = "dev", cwd: str | None = None) -> tuple[int, str]:
    env = dict(os.environ, NV_ROLE=role, CLAUDE_PROJECT_DIR=cwd or ROOT)
    result = subprocess.run(
        [sys.executable, os.path.join(HOOKS, script)],
        input=json.dumps(payload), capture_output=True, text=True, env=env, cwd=cwd or ROOT,
    )
    return result.returncode, result.stderr


def bash(command: str, role: str = "dev", cwd: str | None = None) -> tuple[int, str]:
    return run_hook("guard_bash.py", {"tool_name": "Bash", "tool_input": {"command": command}, "cwd": cwd or ROOT}, role, cwd)


def commit(command: str) -> tuple[int, str]:
    return run_hook("guard_commit.py", {"tool_name": "Bash", "tool_input": {"command": command}})


class ShellSegmentsTest(unittest.TestCase):
    def test_splits_on_operators(self) -> None:
        self.assertEqual(split_segments("a && b || c; d | e\nf"), ["a ", " b ", " c", " d ", " e", "f"])

    def test_keeps_quoted_separators(self) -> None:
        self.assertEqual(split_segments("echo 'a; b' && echo \"c | d\""), ["echo 'a; b' ", " echo \"c | d\""])

    def test_subshell_becomes_segment(self) -> None:
        segments = split_segments('git commit -m "$(cat <<\'EOF\'\nfeat: x\nEOF\n)"')
        self.assertTrue(any(s.strip().startswith("cat") for s in segments))
        self.assertTrue(any("git commit" in s for s in segments))

    def test_heredoc_bodies_are_removed(self) -> None:
        stripped, bodies = strip_heredocs("cat <<'EOF' > f\ngit push origin main\nEOF\necho done")
        self.assertEqual(bodies, ["git push origin main"])
        self.assertNotIn("push", stripped)


class GuardBashTest(unittest.TestCase):
    def assert_denied(self, command: str, role: str = "dev") -> None:
        code, err = bash(command, role)
        self.assertEqual(code, 2, f"{command!r} aurait du etre refuse : {err}")

    def assert_allowed(self, command: str, role: str = "dev") -> None:
        code, err = bash(command, role)
        self.assertEqual(code, 0, f"{command!r} aurait du passer : {err}")

    def test_push_to_protected_branches(self) -> None:
        for cmd in ("git push origin main", "git push origin HEAD:dev", "git push -u origin dev",
                    "git push origin nv/dev/x:refs/heads/main", "git -C /tmp push origin master"):
            self.assert_denied(cmd)

    def test_push_to_work_branch(self) -> None:
        self.assert_allowed("git push -u origin nv/dev/M-002-sprint-0")
        self.assert_allowed("git push -u origin claude/great-euler-2cgkmp")

    def test_force_and_delete(self) -> None:
        for cmd in ("git push --force origin nv/dev/x", "git push -f origin nv/dev/x",
                    "git push --force-with-lease origin nv/dev/x", "git push origin :nv/dev/x",
                    "git push origin +nv/dev/x", "git push --delete origin nv/dev/x"):
            self.assert_denied(cmd)

    def test_history_rewrite(self) -> None:
        for cmd in ("git merge dev", "git rebase origin/dev", "git rebase -i HEAD~3",
                    "git commit --amend -m 'fix: x'", "git reset --hard HEAD~1", "git filter-branch --all",
                    "git branch -D nv/dev/x", "git stash drop", "git clean -fd", "git push --no-verify origin nv/x",
                    "git -c core.hooksPath=/dev/null commit -m 'feat: x'", "git config core.hooksPath .nope"):
            self.assert_denied(cmd)

    def test_rebase_continuation_is_allowed(self) -> None:
        self.assert_allowed("git rebase --continue")
        self.assert_allowed("git rebase --abort")
        self.assert_allowed("git merge --abort")

    def test_destructive_checkout(self) -> None:
        self.assert_denied("git checkout -- .")
        self.assert_denied("git restore .")
        self.assert_allowed("git restore app/Models/User.php")
        self.assert_allowed("git checkout -b nv/dev/M-002-x origin/dev")

    def test_chained_commands_are_all_checked(self) -> None:
        self.assert_denied("composer test && git push origin main")
        self.assert_denied("echo ok; git merge dev")

    def test_gh_merge(self) -> None:
        self.assert_denied("gh pr merge 12 --rebase")
        self.assert_allowed("gh pr view 12")

    def test_rm_governance(self) -> None:
        self.assert_denied("rm -rf .git")
        self.assert_denied("rm -rf .nv/qa")
        self.assert_allowed("rm -f storage/logs/laravel.log")

    def test_writes_to_governed_paths_by_role(self) -> None:
        self.assert_denied("echo x > .github/workflows/ci.yml", role="dev")
        self.assert_allowed("echo x > .github/workflows/ci.yml", role="secops")
        self.assert_denied("sed -i 's/a/b/' .nv/board.json", role="architect")
        self.assert_denied("cp foo .githooks/pre-push", role="ui")
        self.assert_allowed("cat > app/Services/Auth/LoginService.php <<'EOF'\nx\nEOF", role="dev")

    def test_architect_cannot_deliver(self) -> None:
        self.assert_denied(".nv/bin/nv-deliver M-002", role="architect")
        self.assert_allowed(".nv/bin/nv-deliver M-002", role="dev")

    def test_bare_push_on_protected_branch(self) -> None:
        with tempfile.TemporaryDirectory() as tmp:
            subprocess.run(["git", "init", "-q", "-b", "dev", tmp], check=True)
            code, err = bash("git push", cwd=tmp)
            self.assertEqual(code, 2, err)


class GuardCommitTest(unittest.TestCase):
    def test_conventional_subject_passes(self) -> None:
        code, err = commit("git commit -m 'fix(auth): purger les drapeaux de session avant login'")
        self.assertEqual(code, 0, err)

    def test_bad_subject_fails(self) -> None:
        code, _ = commit("git commit -m 'Fixed stuff'")
        self.assertEqual(code, 2)

    def test_heredoc_message(self) -> None:
        cmd = 'git commit -m "$(cat <<\'EOF\'\nfeat(vault): ajouter AAD au format v2\n\nCorps.\nEOF\n)"'
        code, err = commit(cmd)
        self.assertEqual(code, 0, err)

    def test_deliver_trailer_reserved(self) -> None:
        code, _ = commit("git commit -m 'chore(nv): deliver M-002' -m 'NV-Deliver: M-002'")
        self.assertEqual(code, 2)

    def test_commit_without_message(self) -> None:
        code, _ = commit("git commit")
        self.assertEqual(code, 2)


class CommitRulesTest(unittest.TestCase):
    def test_rules(self) -> None:
        self.assertEqual(validate("feat(ui): page legale en vue"), [])
        self.assertTrue(validate("feat(ui): page legale en vue."))
        self.assertTrue(validate("wip: x"))
        self.assertTrue(validate("unknown: x"))
        self.assertTrue(validate("feat: " + "x" * 80))
        self.assertTrue(validate("feat: a\nbody sans ligne vide"))
        self.assertEqual(validate("chore(nv): deliver M-001\n\nNV-Deliver: M-001", allow_deliver_trailer=True), [])


class SecretFilesTest(unittest.TestCase):
    def test_read_env_denied(self) -> None:
        code, _ = run_hook("secret_files.py", {"tool_name": "Read", "tool_input": {"file_path": f"{ROOT}/.env"}})
        self.assertEqual(code, 2)

    def test_env_example_allowed(self) -> None:
        code, _ = run_hook("secret_files.py", {"tool_name": "Read", "tool_input": {"file_path": ".env.example"}})
        self.assertEqual(code, 0)

    def test_bash_cat_env_denied(self) -> None:
        code, _ = run_hook("secret_files.py", {"tool_name": "Bash", "tool_input": {"command": "cat .env | head"}})
        self.assertEqual(code, 2)

    def test_setup_copy_allowed(self) -> None:
        code, _ = run_hook("secret_files.py", {"tool_name": "Bash", "tool_input": {"command": "cp .env.example .env && php artisan key:generate"}})
        self.assertEqual(code, 0)

    def test_cli_check(self) -> None:
        result = subprocess.run([sys.executable, os.path.join(HOOKS, "secret_files.py"), "check", "app/x.php", "storage/oauth.key"], capture_output=True)
        self.assertEqual(result.returncode, 1)


class GuardWorkflowFilesTest(unittest.TestCase):
    def edit(self, path: str, role: str) -> int:
        code, _ = run_hook("guard_workflow_files.py", {"tool_name": "Edit", "tool_input": {"file_path": path}}, role)
        return code

    def test_matrix(self) -> None:
        self.assertEqual(self.edit(".github/workflows/ci.yml", "dev"), 2)
        self.assertEqual(self.edit(".github/workflows/ci.yml", "secops"), 0)
        self.assertEqual(self.edit(".nv/mandates/M-004-x.md", "dev"), 2)
        self.assertEqual(self.edit(".nv/mandates/M-004-x.md", "architect"), 0)
        self.assertEqual(self.edit(".nv/board.json", "architect"), 2)
        self.assertEqual(self.edit(f"{ROOT}/docs/adr/ADR-006-x.md", "ui"), 2)
        self.assertEqual(self.edit("app/Models/User.php", "dev"), 0)
        self.assertEqual(self.edit(".nv/reports/M-002-livraison.md", "dev"), 0)


class GuardAgentSpawnTest(unittest.TestCase):
    def spawn(self, agent: str, role: str, prompt: str = "x" * 200 + " M-002") -> int:
        code, _ = run_hook("guard_agent_spawn.py", {"tool_name": "Agent", "tool_input": {"subagent_type": agent, "prompt": prompt}}, role)
        return code

    def test_matrix(self) -> None:
        self.assertEqual(self.spawn("nv-implementer", "architect"), 2)
        self.assertEqual(self.spawn("nv-implementer", "dev"), 0)
        self.assertEqual(self.spawn("nv-implementer", "dev", prompt="fais le mandat"), 2)
        self.assertEqual(self.spawn("nv-pentester", "dev"), 2)
        self.assertEqual(self.spawn("nv-pentester", "secops"), 0)
        self.assertEqual(self.spawn("Explore", "dev"), 0)


class RoleFallbackTest(unittest.TestCase):
    def test_role_file_is_used_when_env_is_missing(self) -> None:
        with tempfile.TemporaryDirectory() as tmp:
            os.makedirs(os.path.join(tmp, ".nv"))
            with open(os.path.join(tmp, ".nv", ".role"), "w", encoding="utf-8") as handle:
                handle.write("secops\n")
            env = {k: v for k, v in os.environ.items() if k != "NV_ROLE"}
            env["CLAUDE_PROJECT_DIR"] = tmp
            payload = {"tool_name": "Edit", "tool_input": {"file_path": ".github/workflows/ci.yml"}}
            result = subprocess.run([sys.executable, os.path.join(HOOKS, "guard_workflow_files.py")],
                                    input=json.dumps(payload), capture_output=True, text=True, env=env, cwd=tmp)
            self.assertEqual(result.returncode, 0, result.stderr)


class QaEvidenceTest(unittest.TestCase):
    def test_check(self) -> None:
        from qa_evidence import build, problems
        evidence = build("M-002", "nv/dev/x", "abc", [{"command": "composer run ci:check", "exit_code": 0, "duration_s": 1}])
        self.assertEqual(problems(evidence, "M-002", "abc"), [])
        self.assertTrue(problems(evidence, "M-002", "def"))
        self.assertTrue(problems(evidence, "M-003", "abc"))
        failed = build("M-002", "nv/dev/x", "abc", [{"command": "composer run ci:check", "exit_code": 1}])
        self.assertTrue(problems(failed, "M-002", "abc"))


if __name__ == "__main__":
    unittest.main()
