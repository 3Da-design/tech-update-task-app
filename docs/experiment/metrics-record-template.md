# メトリクス記録テンプレート

実験結果はスプレッドシートまたは表計算ソフトに記録します。自動収集 JSON と併用してください。

## 列定義

| 列名 | 説明 | 例 |
|------|------|-----|
| `repository` | リポジトリ識別子 | `improved` / `legacy` |
| `scenario` | シナリオ ID | `api-spec-change` |
| `phase` | 実験フェーズ | `baseline` / `after_update` / `after_fix` |
| `recorded_at` | 記録日時（ISO 8601） | `2026-05-20T14:00:00+09:00` |
| `phpunit_pass` | PHPUnit 成功数 | `32` |
| `phpunit_total` | PHPUnit 総数 | `32` |
| `phpunit_pass_rate` | PHPUnit 通過率（%） | `100` |
| `newman_pass` | Newman アサーション成功数 | `12` |
| `newman_total` | Newman アサーション総数 | `12` |
| `newman_pass_rate` | Newman 通過率（%） | `100` |
| `phpstan_errors` | PHPStan エラー件数 | `0` |
| `eslint_ok` | ESLint 成功（1/0） | `1` |
| `ci_jobs_failed` | CI 失敗ジョブ数（当該 push） | `2` |
| `ci_jobs_total` | CI 実行ジョブ数 | `4` |
| `work_minutes` | 作業時間（分） | `45` |
| `files_changed` | 変更ファイル数 | `8` |
| `lines_added` | 追加行数 | `120` |
| `lines_deleted` | 削除行数 | `35` |
| `commits` | コミット数 | `3` |
| `manual_bugs` | 手動で発見した不具合数 | `1` |
| `metrics_json` | 自動収集 JSON のパス | `experiment/metrics/baseline-....json` |
| `notes` | 自由記述 | `TaskService のみ修正で復旧` |

## 記録例（本リポジトリで実施済み）

| repository | scenario | phase | phpunit_pass | phpunit_total | phpstan_errors | work_minutes | notes |
|------------|----------|-------|--------------|---------------|----------------|--------------|-------|
| improved | api-spec-change | after_update | 36 | 38 | 0 | — | 詳細: [results/api-spec-change/](./results/api-spec-change/) |
| improved | api-spec-change | after_fix | 38 | 38 | 0 | — | TaskService + Resource + テスト |
| legacy | api-spec-change | after_update | 36 | 38 | 0 | — | Web/API Controller 両方で normalize 更新 |
| legacy | api-spec-change | after_fix | 38 | 38 | 0 | — | 同上 + テスト |

自動収集 JSON の一覧: [results/COMPARISON.md](./results/COMPARISON.md)

## 自動収集との対応

`composer experiment:metrics -- --phase <phase>` が出力する JSON の主なフィールド:

| JSON フィールド | スプレッドシート列 |
|-----------------|-------------------|
| `phpunit.pass` | `phpunit_pass` |
| `phpunit.total` | `phpunit_total` |
| `phpunit.pass_rate` | `phpunit_pass_rate` |
| `newman.pass` | `newman_pass` |
| `newman.total` | `newman_total` |
| `phpstan.error_count` | `phpstan_errors` |
| `eslint.ok` | `eslint_ok` |
| `git.shortstat` | `lines_added` / `lines_deleted`（要パース） |

手動項目（`work_minutes`, `commits`, `manual_bugs`, `ci_jobs_*`）は実験者が記入します。
