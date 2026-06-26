# シナリオ: API 仕様変更 — 属性追加（`api-spec-change-priority`）

## 目的

REST API に新属性 `priority` を追加し、**非破壊的な仕様変更がアーキテクチャ各層にどう波及するか**を比較する。Web UI では legacy と同様、**一覧テーブルへの優先度列追加**までを必須とし、「属性を追加した」と説明できる最小 UX を満たす。

## UX 方針（v2）

| 対応 | 必須 | 変更規模 | 備考 |
|------|------|---------|------|
| 作成・編集フォームに priority select | ✅ | 小 | `_form.blade.php` |
| 一覧テーブルに優先度列 | ✅ | 小 | `index.blade.php` のみ。legacy / improved 同一 |
| 優先度フィルタ | ❌ | 中 | Request + Controller（legacy は 2 箇所） |
| 優先度並び替え | ❌ | 大 | クエリ層変更。別シナリオに近い |

## 想定される破壊箇所

| 構成 | 主な修正箇所 |
|------|--------------|
| 改良構成 | `TaskResource`, FormRequest×2, `TaskService`（正規化）, `config/task.php`, Model, migration, `_form.blade.php`, **`index.blade.php`**, テスト |
| 従来構成 | 上記に加え **Web/API Controller 内 `normalizeTaskPayload`** も同時修正 |

**触れない（改良構成）:** Controller、Repository、Interface、`IndexTaskRequest`

## 事前条件

- `experiment-baseline-v1` タグまたは同等の CI 緑状態（**main ベースラインは 4 属性のみ**: `title` / `description` / `due_date` / `status`）
- メトリクス用に **`baseline` を先に取得**し、`experiment/metrics/runs/<run_id>/` が作成されていること

## 変更内容（両リポジトリで同一適用）

1. **レスポンスに `priority` フィールドを追加**（`low` / `medium` / `high`、デフォルト `medium`）
2. **マイグレーション:** `tasks` テーブルに `priority` カラム（string, default `medium`）
3. **`TaskResource`:** `priority` を JSON に含める
4. **FormRequest（Store / Update）:** `priority` の `Rule::in([...])` を追加
5. **`TaskService::normalizeTaskPayload`:** `priority` を許可リストに追加（improved のみ。legacy は Controller 側も）
6. **Web フォーム:** `tasks/_form.blade.php` に select を追加
7. **Web 一覧:** `tasks/index.blade.php` に優先度列を追加
8. **テスト:** `TaskApiTest`, `TaskWebTest`（一覧 `assertSee` 含む）, Postman の期待値を更新

| ファイル | 変更 |
|---------|------|
| migration | `tasks.priority` string, default `medium` |
| `config/task.php` | `priority_values` 追加 |
| `app/Models/Task.php` | fillable / PHPDoc |
| `app/Http/Resources/TaskResource.php` | JSON に `priority` 追加 |
| `app/Http/Requests/StoreTaskRequest.php` / `UpdateTaskRequest.php` | `Rule::in(['low', 'medium', 'high'])` |
| `app/Services/TaskService.php` | `normalizeTaskPayload` の許可リスト（improved） |
| `resources/views/tasks/_form.blade.php` | select 追加 |
| `resources/views/tasks/index.blade.php` | **テーブル列追加** |
| `tests/Feature/TaskApiTest.php` / `TaskWebTest.php` | 期待値・一覧表示更新 |
| `postman/Task-API.postman_collection.json` | アサーション更新 |

## 実施手順

```bash
git checkout -b exp/api-spec-change-priority experiment-baseline-v1

composer experiment:metrics -- --phase baseline --diff-ref experiment-baseline-v1

docker compose exec app php artisan make:migration add_priority_to_tasks_table
docker compose exec app php artisan migrate

# 上記ファイルを順に編集（テスト・Postman はまだ触らない）
# Phase 2 末尾: index.blade.php の列追加 → composer npm:docker-build

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

**期待される差:** 従来構成は `files_changed` が改良構成より **+2（Web / API Controller）** 程度多い。`index.blade.php` は両構成で **同一 1 ファイル** のため、比較実験への影響は小さい。

## 完了条件

- [ ] GitHub Actions 4 ジョブすべて成功
- [ ] `experiment/metrics/runs/<run_id>/` に 3 フェーズ JSON がある
- [ ] API / DB / Web フォーム / **Web 一覧** で `priority` が確認できる
- [ ] （任意）`composer experiment:record -- --scenario api-spec-change-priority --write`
- [ ] `experiment/results/` に結果をコピー（`scripts/publish-experiment-results.sh`）
- [ ] 従来構成リポジトリ（`tech-update-task-app-legacy`）で同一手順を実施

## 関連

- [EXPERIMENT.md](../../EXPERIMENT.md) — 実験設計・主評価指標の定義
- [EXPERIMENT.md — メトリクス記録テンプレート](../EXPERIMENT.md#メトリクス記録テンプレート) — スプレッドシート列定義
- [api-spec-change-status-int.md](./api-spec-change-status-int.md) — 既存属性の型変更シナリオ
- [db-schema-change.md](./db-schema-change.md) — クエリ変更シナリオ
