# メトリクス記録テンプレート

スプレッドシート等に転記する列定義。**主指標は `after_fix` の修正工数**（変更ファイル数・行数）。API 仕様変更シナリオでは通過率が構成間で同一になりうるため、通過率だけで構成差を判定しないこと。

## 列定義

| 列 | 説明 | 取得元 |
|----|------|--------|
| `repository` | `legacy` または `improved` | 手動 |
| `scenario` | シナリオ ID（例: `api-spec-change-status-int`） | 手動 |
| `phase` | `baseline` / `after_update` / `after_fix` | 手動 |
| `recorded_at` | ISO 8601 タイムスタンプ | metrics JSON |
| `phpunit_pass` | PHPUnit 成功数 | metrics JSON |
| `phpunit_total` | PHPUnit 総数 | metrics JSON |
| `phpunit_pass_rate` | 通過率（参考） | metrics JSON |
| `newman_pass` | Newman 成功数 | metrics JSON |
| `newman_total` | Newman 総数 | metrics JSON |
| `newman_pass_rate` | 通過率（参考） | metrics JSON |
| `phpstan_errors` | PHPStan エラー件数 | metrics JSON |
| `eslint_ok` | ESLint 成功（1/0） | metrics JSON |
| `ci_jobs_failed` | CI 失敗ジョブ数 | 手動 |
| `ci_jobs_total` | CI 実行ジョブ数 | 手動 |
| `work_minutes` | 作業時間（分） | **手動** |
| `files_changed` | 変更ファイル数 | metrics JSON `git.files_changed`（**after_fix が主指標**） |
| `lines_added` | 追加行数 | metrics JSON `git.lines_added` |
| `lines_deleted` | 削除行数 | metrics JSON `git.lines_deleted` |
| `commits` | コミット数 | **手動** |
| `manual_bugs` | 手動で発見した不具合件数 | **手動** |
| `metrics_json` | JSON ファイルへの相対パス | 自動 |
| `notes` | メモ | **手動** |

## 記録例（CSV ヘッダ）

```text
repository,scenario,phase,recorded_at,phpunit_pass,phpunit_total,phpunit_pass_rate,newman_pass,newman_total,newman_pass_rate,phpstan_errors,eslint_ok,ci_jobs_failed,ci_jobs_total,work_minutes,files_changed,lines_added,lines_deleted,commits,manual_bugs,metrics_json,notes
```

## ベースライン仕様

- **`main` / `experiment-baseline-v1`:** タスク属性は `title` / `description` / `due_date` / `status`（string）の **4 項目のみ**
- **`priority` 追加・status integer 化:** 各 `exp/*` ブランチで実施（ベースラインと混在させない）

## 関連

- [EXPERIMENT.md](../EXPERIMENT.md) — 評価指標の定義
- [README.md](../../README.md) — ベースライン確立・実験の進め方
- `composer experiment:record -- --scenario <id> --write` — 上記列に沿った `RECORD.md` 生成
