# シナリオ: API 仕様変更 — 属性追加（`api-spec-change-priority`）

## 目的

REST API に新属性 `priority` を追加し、**非破壊的な仕様変更がアーキテクチャ各層にどう波及するか**を比較する。

## 想定される破壊箇所

| 構成 | 主な修正箇所 |
|------|--------------|
| 改良構成 | `TaskResource`, `StoreTaskRequest` / `UpdateTaskRequest`, `TaskService`（正規化）, テスト |
| 従来構成 | 上記に加え **Web/API の Controller 内 `normalizeTaskPayload`** も同時修正 |

**触れない（改良構成）:** Controller、Repository、Interface

## 事前条件

- `experiment-baseline-v1` タグまたは同等の CI 緑状態（**main ベースラインは 4 属性のみ**: `title` / `description` / `due_date` / `status`）
- メトリクス用に **`baseline` を先に取得**し、`experiment/metrics/runs/<run_id>/` が作成されていること

## 変更内容（両リポジトリで同一適用）

1. **レスポンスに `priority` フィールドを追加**（`low` / `medium` / `high`、デフォルト `medium`）
2. **マイグレーション:** `tasks` テーブルに `priority` カラム（string, default `medium`）
3. **`TaskResource`:** `priority` を JSON に含める
4. **FormRequest:** `priority` の `Rule::in([...])` を追加
5. **`TaskService::normalizeTaskPayload`:** `priority` を許可リストに追加
6. **Web フォーム:** `tasks/_form.blade.php` に select を追加（任意だが機能 parity のため推奨）
7. **テスト:** `TaskApiTest`, `TaskWebTest`, Postman コレクションの期待値を更新

| ファイル | 変更 |
|---------|------|
| migration | `tasks.priority` string, default `medium` |
| `app/Http/Resources/TaskResource.php` | JSON に `priority` 追加 |
| `app/Http/Requests/StoreTaskRequest.php` / `UpdateTaskRequest.php` | `Rule::in(['low', 'medium', 'high'])` |
| `app/Services/TaskService.php` | `normalizeTaskPayload` の許可リストに `priority` 追加 |
| `resources/views/tasks/_form.blade.php` | select 追加 |
| `tests/Feature/TaskApiTest.php` / `TaskWebTest.php` | 期待値更新 |
| `postman/Task-API.postman_collection.json` | アサーション更新 |

## 実施手順

```bash
git checkout -b exp/api-spec-change-priority experiment-baseline-v1

composer experiment:metrics -- --phase baseline --diff-ref experiment-baseline-v1

docker compose exec app php artisan make:migration add_priority_to_tasks_table
docker compose exec app php artisan migrate

# 上記ファイルを順に編集（テスト・Postman はまだ触らない）

composer npm:docker-build

composer experiment:metrics -- --phase after_update --diff-ref experiment-baseline-v1

# テスト・Postman を修正して CI 緑に
./scripts/check-quality.sh

composer experiment:metrics -- --phase after_fix --diff-ref experiment-baseline-v1

composer experiment:record -- --scenario api-spec-change-priority --write
./scripts/publish-experiment-results.sh --scenario api-spec-change-priority
```

## 記録するメトリクス（主指標）

| 優先 | 指標 | 取得元 |
|------|------|--------|
| 1 | 変更ファイル数 | `git.files_changed`（`--diff-ref experiment-baseline-v1`） |
| 2 | 追加 / 削除行数 | `git.lines_added` / `git.lines_deleted` |
| 3 | 更新直後のテスト失敗数 | `phpunit.fail` / `newman.fail`（`after_update`） |
| 4 | 作業時間（分） | 手動（テンプレート） |

**期待される差:** 従来構成は `files_changed` が改良構成より **+2（Web / API Controller）** 程度多い。

## 完了条件

- [ ] GitHub Actions 4 ジョブすべて成功
- [ ] `experiment/metrics/runs/<run_id>/` に 3 フェーズ JSON がある
- [ ] （任意）`composer experiment:record -- --scenario api-spec-change-priority --write`
- [ ] `docs/experiment/results/` に結果をコピー（`scripts/publish-experiment-results.sh`）
- [ ] 従来構成リポジトリ（`tech-update-task-app-legacy`）で同一手順を実施

## 関連

- [EXPERIMENT.md](../../EXPERIMENT.md) — 実験設計・主評価指標の定義
- [metrics-record-template.md](../metrics-record-template.md) — スプレッドシート列定義
- [api-spec-change-status-int.md](./api-spec-change-status-int.md) — 既存属性の型変更シナリオ
- [db-schema-change.md](./db-schema-change.md) — クエリ変更シナリオ
