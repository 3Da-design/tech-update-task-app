# シナリオ: バックエンド API 仕様変更

## 目的

REST API のレスポンス形式・バリデーションルールを変更し、**仕様変更がアーキテクチャ各層にどう波及するか**を比較する。

## 想定される破壊箇所

| 構成 | 主な修正箇所 |
|------|--------------|
| 改良構成 | `TaskResource`, `StoreTaskRequest` / `UpdateTaskRequest`, `TaskService`（正規化）, テスト |
| 従来構成 | 上記に加え **Web/API の Controller 内ロジック** も同時修正の可能性 |

## 事前条件

- `experiment-baseline-v1` タグまたは同等の CI 緑状態
- メトリクス用に **`baseline` を先に取得**し、`experiment/metrics/runs/<run_id>/` が作成されていること（`composer experiment:metrics -- --phase baseline`）

## 変更内容の具体例（両リポジトリで同一適用）

以下を **セットで** 適用することを推奨します。

1. **レスポンスに `priority` フィールドを追加**（`low` / `medium` / `high`、デフォルト `medium`）
2. **マイグレーション:** `tasks` テーブルに `priority` カラム（string, default `medium`）
3. **`TaskResource`:** `priority` を JSON に含める
4. **FormRequest:** `priority` の `Rule::in([...])` を追加
5. **`TaskService::normalizeTaskPayload`:** `priority` を許可リストに追加
6. **Web フォーム:** `tasks/_form.blade.php` に select を追加（任意だが機能 parity のため推奨）
7. **テスト:** `TaskApiTest`, `TaskWebTest`, Postman コレクションの期待値を更新

## 実施手順

```bash
git checkout -b exp/api-spec-change experiment-baseline-v1

# 0. ベースライン（新しい run-<UTC> が experiment/metrics/.active-run に記録される）
composer experiment:metrics -- --phase baseline

# 1. マイグレーション作成・適用
docker compose exec app php artisan make:migration add_priority_to_tasks_table
docker compose exec app php artisan migrate

# 2. 上記ファイルを順に編集

# 3. フロントビルド（Blade テスト用）
docker compose --profile node run --rm node sh -c "npm ci && npm run build"

# 4. 更新直後メトリクス（失敗が想定される。同一 run_id の after_update.json）
composer experiment:metrics -- --phase after_update

# 5. テスト・Postman・PHPStan を修正して CI 緑に
./scripts/check-quality.sh

# 6. 修正完了メトリクス（同一 run_id の after_fix.json）
composer experiment:metrics -- --phase after_fix

# 7. 記録用 Markdown（任意）
composer experiment:record -- --scenario api-spec-change --write
```

## 記録するメトリクス

- PHPUnit / Newman 通過率（`after_update` vs `after_fix`）
- PHPStan エラー件数
- 修正工数（分、変更ファイル数、diff stat）
- 主な修正ファイル一覧（メモ欄）

## 完了条件

- [ ] GitHub Actions 4 ジョブすべて成功
- [ ] `experiment/metrics/runs/<run_id>/` に `baseline.json` / `after_update.json` / `after_fix.json` がある
- [ ] （任意）`composer experiment:record -- --scenario <id> --write` で `RECORD.md` を生成
- [ ] スプレッドシートまたは [metrics-record-template.md](../metrics-record-template.md) に手動列を追記
- [ ] 従来構成リポジトリで同一手順を実施（従来構成完成後）
