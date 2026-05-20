# tech-update-task-app

技術更新時の影響を定量評価するための **改良構成（良い例）** 実験台です。  
同一機能のタスク管理アプリを、Controller / Service / Repository 分離と CI/CD で守り、更新シナリオごとに従来構成（別リポジトリ）と比較します。

[![CI](https://github.com/OWNER/tech-update-task-app/actions/workflows/ci.yml/badge.svg)](https://github.com/OWNER/tech-update-task-app/actions/workflows/ci.yml)

> `OWNER` は GitHub のユーザー名または Organization 名に置き換えてください。

---

## 目次

1. [研究ゴールと比較設計](#研究ゴールと比較設計)
2. [アーキテクチャ](#アーキテクチャ)
3. [技術スタック](#技術スタック)
4. [クイックスタート](#クイックスタート)
5. [テストと CI](#テストと-ci)
6. [実験の進め方](#実験の進め方)
7. [更新シナリオ](#更新シナリオ)
8. [評価指標](#評価指標)
9. [ドキュメント索引](#ドキュメント索引)

---

## 研究ゴールと比較設計

| 項目 | 内容 |
|------|------|
| **ゴール** | 設計（モジュール化 + CI/CD）が技術更新時の影響をどれだけ抑えられるかを定量的に示す |
| **本リポジトリ** | 改良構成（Controller / Service / Repository + Interface） |
| **対照** | 完成後に本リポジトリをクローンし、タスク領域を Controller 直 DB の従来構成に戻した別リポジトリ |
| **比較条件** | 同一アプリ（タスク管理）、同一スタック（Laravel）、同一 CI ワークフロー |
| **評価スコープ** | **アプリ全体**（認証・プロフィール・タスク・CI 全ジョブ） |

詳細は [docs/EXPERIMENT.md](docs/EXPERIMENT.md) を参照してください。

---

## アーキテクチャ

### タスク領域（改良構成の核）

```text
HTTP (Web / API)
    │
    ▼
TaskController (Web / API)   … HTTP の受け渡しのみ
    │
    ▼
TaskService                  … 認可・入力正規化・ユースケース
    │
    ▼
TaskRepositoryInterface
    │
    ▼
TaskRepository               … Eloquent による永続化
    │
    ▼
Task (Model)
```

| レイヤ | クラス |
|--------|--------|
| Web | `App\Http\Controllers\Web\TaskController` |
| API | `App\Http\Controllers\API\TaskController` |
| Service | `App\Services\TaskService` |
| Repository | `App\Repositories\TaskRepository` |
| Interface | `App\Repositories\Contracts\TaskRepositoryInterface` |
| DI | `App\Providers\RepositoryServiceProvider` |
| 入出力 | `StoreTaskRequest`, `UpdateTaskRequest`, `TaskResource` |

Web と API は **同じ `TaskService`** を共有するため、API 仕様変更時の修正を Service / Repository 周辺に集約しやすい構成です。

### 認証・プロフィール

Laravel Breeze 標準（Controller から User Model を直接操作）。  
比較実験の「悪い例」は **別リポジトリのタスク領域** で再現し、Laravel / テストツール / JS 更新の影響は全体メトリクスに含めます。

### ディレクトリ（タスク関連）

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── API/TaskController.php
│   │   └── Web/TaskController.php
│   ├── Requests/          # バリデーション
│   └── Resources/         # API JSON
├── Services/TaskService.php
└── Repositories/
    ├── Contracts/TaskRepositoryInterface.php
    └── TaskRepository.php
```

---

## 技術スタック

| 区分 | 技術 |
|------|------|
| バックエンド | Laravel 13、PHP 8.4 |
| 認証 | Laravel Breeze（セッション） |
| DB | PostgreSQL（Docker Compose） |
| フロント | Blade、Tailwind CSS、Vite、Alpine.js |
| 品質 | PHPStan (Larastan)、Laravel Pint、ESLint |
| テスト | PHPUnit、Postman / Newman |
| CI | GitHub Actions（4 ジョブ並列） |
| 開発環境 | Docker Compose（`http://localhost:8000`） |

機能一覧は [docs/FeatureList.md](docs/FeatureList.md) を参照してください。

---

## クイックスタート

### 前提

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) など Compose v2 対応環境
- 開発フローは **Docker Compose のみ**（ホストで `php artisan serve` は使わない。ポート `8000` は nginx が使用）

### 初回セットアップ

```bash
cp .env.example .env

docker compose build app
docker compose up -d

docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

ブラウザで `http://localhost:8000` を開きます。シードユーザー: `test@example.com` / `password`

### よく使うコマンド

```bash
docker compose logs -f
docker compose down              # DB ボリュームは残る
docker compose down -v           # DB ごと削除
```

### API の疎通確認

```bash
chmod +x scripts/curl-api-smoke.sh
./scripts/curl-api-smoke.sh
```

`http_code` が `000` のときは Docker 未起動・URL 誤り・ポート競合を確認してください（README 旧版の curl 例も有効です）。

---

## テストと CI

### ローカル一括（推奨）

```bash
./scripts/check-quality.sh
```

実行内容: PHPStan → ESLint → Vite build → PHPUnit → Newman

### 個別

```bash
docker compose exec app composer phpstan
docker compose exec app composer test
docker compose --profile node run --rm node npm run lint
docker compose --profile node run --rm node sh -c "npm ci && npm run build"
docker compose --profile node run --rm node npm run test:api
```

### GitHub Actions（CI）

| ジョブ | 内容 |
|--------|------|
| `php-tests` | PHPUnit（事前に Vite build） |
| `php-quality` | Pint + PHPStan |
| `frontend` | ESLint + Vite build |
| `api-tests` | Newman（Postman コレクション） |

詳細: [docs/CI.md](docs/CI.md)、[docs/TESTING.md](docs/TESTING.md)

---

## 実験の進め方

### 1. ベースラインの確立

改良構成が CI 緑の状態で:

```bash
./scripts/check-quality.sh
composer experiment:metrics -- --phase baseline
git tag -a experiment-baseline-v1 -m "Experiment baseline: improved architecture"
```

メトリクス JSON は **`experiment/metrics/runs/<run_id>/baseline.json`** などに出力されます（Git 管理外）。`baseline` のたびに新しい `run-<UTC>` が作られ、続く `after_update` / `after_fix` は同じフォルダに保存されます。表の自動生成: `composer experiment:record -- --scenario <名前> --write`（`RECORD.md` をラン内に作成）。

### アクティブランをリセットしたいとき

別の実験を始める前に `experiment/metrics/.active-run` を削除するか、改めて `baseline`（または `baseline --run 新ID`）を実行してください。

### 2. 更新シナリオの実施

[docs/experiment/scenarios/](docs/experiment/scenarios/) の手順に従い、ブランチで変更を適用します。

```bash
git checkout -b exp/api-spec-change experiment-baseline-v1
# … シナリオに沿って変更 …
composer experiment:metrics -- --phase after_update
# … テスト・コードを修正 …
./scripts/check-quality.sh
composer experiment:metrics -- --phase after_fix
```

### 3. 記録

1. `composer experiment:record -- --scenario <シナリオID> --write` で `experiment/metrics/runs/<run_id>/RECORD.md` を生成（任意・自動で表が出ます）。
2. [docs/experiment/metrics-record-template.md](docs/experiment/metrics-record-template.md) の手動列（工数・CI など）をスプレッドシートへ追記。

### 4. 従来構成との比較

本リポジトリをクローンし、[docs/experiment/LEGACY_MIGRATION.md](docs/experiment/LEGACY_MIGRATION.md) に従ってタスク領域を従来構成に戻したうえで、**同じシナリオ・同じ手順** を繰り返します。

---

## 更新シナリオ

| シナリオ | ドキュメント |
|----------|--------------|
| バックエンド API 仕様変更 | [api-spec-change.md](docs/experiment/scenarios/api-spec-change.md) |
| Laravel バージョン更新 | [laravel-upgrade.md](docs/experiment/scenarios/laravel-upgrade.md) |
| テストツール更新 | [test-tool-upgrade.md](docs/experiment/scenarios/test-tool-upgrade.md) |
| JavaScript ライブラリ変更 | [js-library-change.md](docs/experiment/scenarios/js-library-change.md) |

---

## 評価指標

| 指標 | 概要 | 取得 |
|------|------|------|
| **テスト通過率** | PHPUnit / Newman 等の成功 ÷ 総数 | `composer experiment:metrics` → `runs/<id>/<phase>.json` |
| **修正工数** | 作業時間、変更ファイル数、diff、コミット数 | 手動 + `git diff --stat` |
| **エラー発生率** | PHPStan 件数、CI 失敗ジョブ、手動不具合 | スクリプト + 手動 |

定義の詳細: [docs/EXPERIMENT.md](docs/EXPERIMENT.md)

---

## ドキュメント索引

| ドキュメント | 内容 |
|--------------|------|
| [docs/EXPERIMENT.md](docs/EXPERIMENT.md) | 実験設計・指標・フェーズ |
| [docs/FeatureList.md](docs/FeatureList.md) | 機能一覧 |
| [docs/TESTING.md](docs/TESTING.md) | テストツールの使い方 |
| [docs/CI.md](docs/CI.md) | GitHub Actions |
| [docs/experiment/LEGACY_MIGRATION.md](docs/experiment/LEGACY_MIGRATION.md) | 従来構成リポジトリ作成手順 |
| [docs/experiment/metrics-record-template.md](docs/experiment/metrics-record-template.md) | メトリクス記録テンプレート |
| [docs/experiment/scenarios/](docs/experiment/scenarios/) | 更新シナリオ手順 |

---

## ライセンス

MIT（Laravel プロジェクトスケルトンに準拠）
