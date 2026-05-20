#!/usr/bin/env python3
"""
実験ランの baseline / after_update / after_fix の JSON から、
metrics-record-template に沿った Markdown 表と RECORD.md を生成する。
"""
from __future__ import annotations

import argparse
import json
import sys
from pathlib import Path


def load_json(path: Path) -> dict | None:
    if not path.is_file():
        return None
    with path.open(encoding="utf-8") as f:
        return json.load(f)


def row_from_json(
    scenario: str,
    phase: str,
    data: dict | None,
    run_id: str,
) -> list[str]:
    if data is None:
        empties = [""] * 17
        return [
            "improved",
            scenario,
            phase,
            *empties,
            f"(missing {phase}.json)",
            "",
        ]

    repo = str(data.get("repository", "improved"))
    recorded = str(data.get("recorded_at", ""))
    php = data.get("phpunit", {})
    nm = data.get("newman", {})
    ps = data.get("phpstan", {})
    es = data.get("eslint", {})
    git = data.get("git", {})

    php_pass = str(php.get("pass", ""))
    php_total = str(php.get("total", ""))
    php_rate = str(php.get("pass_rate", ""))
    nm_pass = str(nm.get("pass", ""))
    nm_total = str(nm.get("total", ""))
    nm_rate = str(nm.get("pass_rate", ""))
    phpstan_err = str(ps.get("error_count", ""))
    eslint_ok = "1" if es.get("ok") else "0"
    shortstat = str(git.get("diff_shortstat", ""))
    json_rel = f"experiment/metrics/runs/{run_id}/{phase}.json"

    return [
        repo,
        scenario,
        phase,
        recorded,
        php_pass,
        php_total,
        php_rate,
        nm_pass,
        nm_total,
        nm_rate,
        phpstan_err,
        eslint_ok,
        "",
        "",
        "",
        "",
        "",
        "",
        f"`{json_rel}`",
        "",
    ]


def build_markdown(run_id: str, scenario: str, phases: dict[str, dict | None]) -> str:
    headers = [
        "repository",
        "scenario",
        "phase",
        "recorded_at",
        "phpunit_pass",
        "phpunit_total",
        "phpunit_pass_rate",
        "newman_pass",
        "newman_total",
        "newman_pass_rate",
        "phpstan_errors",
        "eslint_ok",
        "ci_jobs_failed",
        "ci_jobs_total",
        "work_minutes",
        "files_changed",
        "lines_added",
        "lines_deleted",
        "commits",
        "manual_bugs",
        "metrics_json",
        "notes",
    ]

    lines: list[str] = []
    lines.append(f"# 実験記録（自動生成）\n")
    lines.append(f"- **run_id:** `{run_id}`\n")
    lines.append(f"- **scenario:** `{scenario}`\n")
    lines.append("\n手動列（`ci_jobs_*`, `work_minutes`, `commits`, `manual_bugs`, `notes`）は空です。スプレッドシートにコピーしたあと追記してください。\n")
    lines.append("\n## メトリクス表（テンプレート列）\n")
    lines.append("| " + " | ".join(headers) + " |\n")
    lines.append("| " + " | ".join(["---"] * len(headers)) + " |\n")

    for phase in ("baseline", "after_update", "after_fix"):
        data = phases.get(phase)
        cells = row_from_json(scenario, phase, data, run_id)
        lines.append("| " + " | ".join(cells) + " |\n")

    lines.append("\n## JSON ファイル\n")
    for phase in ("baseline", "after_update", "after_fix"):
        p = f"experiment/metrics/runs/{run_id}/{phase}.json"
        exists = phases.get(phase) is not None
        lines.append(f"- `{p}` — {'あり' if exists else '**なし**'}\n")

    lines.append("\n## git diff_shortstat（収集時点）\n")
    for phase in ("baseline", "after_update", "after_fix"):
        data = phases.get(phase)
        stat = data.get("git", {}).get("diff_shortstat", "") if data else ""
        lines.append(f"- **{phase}:** `{stat or '(なし)'}`\n")

    return "".join(lines)


def main() -> int:
    parser = argparse.ArgumentParser(description="Generate experiment RECORD.md from metrics JSON files.")
    parser.add_argument(
        "--run",
        help="Run directory name under experiment/metrics/runs/. Default: read experiment/metrics/.active-run",
    )
    parser.add_argument(
        "--scenario",
        default="(unset)",
        help="Scenario id for the spreadsheet scenario column (e.g. api-spec-change)",
    )
    parser.add_argument(
        "--write",
        action="store_true",
        help="Write to experiment/metrics/runs/<run_id>/RECORD.md",
    )
    args = parser.parse_args()

    root = Path(__file__).resolve().parent.parent
    metrics_root = root / "experiment" / "metrics"
    active_file = metrics_root / ".active-run"

    run_id = args.run
    if not run_id:
        if not active_file.is_file():
            print("error: no --run and no experiment/metrics/.active-run", file=sys.stderr)
            print("hint: run `composer experiment:metrics -- --phase baseline` first, or pass --run <id>", file=sys.stderr)
            return 1
        run_id = active_file.read_text(encoding="utf-8").strip()

    run_dir = metrics_root / "runs" / run_id
    if not run_dir.is_dir():
        print(f"error: run directory not found: {run_dir}", file=sys.stderr)
        return 1

    phases: dict[str, dict | None] = {}
    for phase in ("baseline", "after_update", "after_fix"):
        phases[phase] = load_json(run_dir / f"{phase}.json")

    md = build_markdown(run_id, args.scenario, phases)
    print(md)

    if args.write:
        out = run_dir / "RECORD.md"
        out.write_text(md, encoding="utf-8")
        print(f"\nWrote {out}", file=sys.stderr)

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
