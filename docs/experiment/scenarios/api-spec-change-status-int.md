# シナリオ: API 仕様変更 — 既存属性の型変更（`api-spec-change-status-int`）

## 目的

既存フィールド `status` を string（`todo` / `in_progress` / `done`）から integer（`0` / `1` / `2`）へ変更し、**破壊的な API 仕様変更の波及範囲**を、属性追加（`api-spec-change-priority`）と比較する。

## 想定される破壊箇所

| 構成 | 主な修正箇所 |
|------|--------------|
| 改良構成 | `config/task.php`, FormRequest×3, `TaskService`, `TaskRepository`, `TaskRepositoryInterface`（PHPDoc）, `Task` Model, migration（データ移行）, テスト, Web View |
| 従来構成 | 上記に加え **Web/API Controller 内の normalize / バリデーション** も同時修正 |

**触れない（改良構成）:** Controller、Interface のメソッドシグネチャ、`TaskResource`（Model の integer cast により JSON も int になる）

## 事前条件

- `experiment-baseline-v1` タグまたは同等の CI 緑状態
- CI / ローカルテストが **PostgreSQL** で実行されていること（マイグレーションが PostgreSQL 専用 SQL のため）
- `baseline` メトリクス取得済み

## 変更内容（両リポジトリで同一適用）

### 1. マッピング定義

`config/task.php` を int ベースに変更:

```php
'status_values' => [0, 1, 2],
'status_labels' => [
    0 => 'todo',
    1 => 'in_progress',
    2 => 'done',
],
```

### 2. 永続化

- マイグレーション: `tasks.status` を string → smallint（**PostgreSQL 専用**）
- **既存行のデータ移行**（`todo`→0, `in_progress`→1, `done`→2）

例:

```php
DB::statement("
  ALTER TABLE tasks
  ALTER COLUMN status TYPE smallint
  USING (
    CASE status
      WHEN 'todo' THEN 0
      WHEN 'in_progress' THEN 1
      WHEN 'done' THEN 2
      ELSE 0
    END
  )
");
```

- `Task` Model: `'status' => 'integer'` cast、PHPDoc を `@property int $status` に更新

### 3. HTTP 境界

- **`StoreTaskRequest` / `UpdateTaskRequest`:** `integer` + `Rule::in(config('task.status_values'))`
- **`IndexTaskRequest`:** 一覧フィルタ `?status=` も int 化

### 4. ユースケース・永続化

- **`TaskService::normalizeTaskPayload`:** `status` を `(int)` キャスト
- **`TaskService::normalizeListFilters`:** `is_numeric` + `in_array(..., config('task.status_values'))` で int 正規化
- **`TaskRepository::getFiltered`:** status フィルタを `is_int` 比較に変更
- **`TaskRepositoryInterface`:** PHPDoc の `status?: string` を `status?: int` に更新

### 5. Web

- `tasks/_form.blade.php`, `tasks/index.blade.php` — option value を int、表示は `config('task.status_labels')`

### 6. テスト・Postman

- `TaskApiTest`, `TaskWebTest` — 既存の **全 status アサーション**を int 期待値に書き換え
- **`TaskListFilterTest`**（main に既存）— status フィルタテストを int 化（例: `?status=2` で `done` 相当のタスクがヒット）
- `postman/Task-API.postman_collection.json` — status 期待値を int に更新

| ファイル | 変更 |
|---------|------|
| `config/task.php` | `status_values` → `[0,1,2]`、`status_labels` 追加 |
| migration | PostgreSQL `ALTER COLUMN ... USING CASE` |
| `app/Models/Task.php` | integer cast、PHPDoc |
| FormRequest×3 | `string` → `integer` |
| `app/Services/TaskService.php` | `normalizeTaskPayload` / `normalizeListFilters` |
| `app/Repositories/TaskRepository.php` | status フィルタを `is_int` 比較に |
| `app/Repositories/Contracts/TaskRepositoryInterface.php` | PHPDoc `status?: int` |
| `resources/views/tasks/_form.blade.php` / `index.blade.php` | option value = int、表示 = labels |
| テスト群 | 上記 3 テストファイル + Postman |

## 実施手順

```bash
git checkout -b exp/api-spec-change-status-int experiment-baseline-v1

composer experiment:metrics -- --phase baseline --diff-ref experiment-baseline-v1

# 1. マイグレーション作成（PostgreSQL データ移行含む）・適用
docker compose exec app php artisan make:migration change_status_to_integer_on_tasks_table
docker compose exec app php artisan migrate

# 2. config / Request×3 / Service / Model / Repository / View を編集
#    （テスト・Postman はまだ触らない）

composer npm:docker-build

composer experiment:metrics -- --phase after_update --diff-ref experiment-baseline-v1

# 3. テスト・Postman・View を修正して CI 緑に
./scripts/check-quality.sh

composer experiment:metrics -- --phase after_fix --diff-ref experiment-baseline-v1

composer experiment:record -- --scenario api-spec-change-status-int --write
./scripts/publish-experiment-results.sh --scenario api-spec-change-status-int
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
- [ ] 3 フェーズ JSON がある
- [ ] 従来構成リポジトリ（`tech-update-task-app-legacy`）で同一手順を実施

## 関連

- [EXPERIMENT.md](../../EXPERIMENT.md) — 実験設計・主評価指標の定義
- [metrics-record-template.md](../metrics-record-template.md) — スプレッドシート列定義
- [api-spec-change-priority.md](./api-spec-change-priority.md) — 属性追加シナリオ（対比用）
- [db-schema-change.md](./db-schema-change.md) — クエリ変更シナリオ
