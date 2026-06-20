# tech-update-task-app

技術更新時の影響を定量評価するための **改良構成（良い例）** 実験台です。  
同一機能のタスク管理アプリを、Controller / Service / Repository 分離と CI/CD で守り、更新シナリオごとに従来構成（別リポジトリ）と比較します。

[![CI](https://github.com/3Da-design/tech-update-task-app/actions/workflows/ci.yml/badge.svg)](https://github.com/3Da-design/tech-update-task-app/actions/workflows/ci.yml)

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
| **ベースライン** | **`main`** および **`experiment-baseline-v1` タグ**。タスク属性は **`title` / `description` / `due_date` / `status` の 4 項目のみ**。`priority` 追加・status integer 化などの仕様変更は **`exp/*` ブランチ** で実施 |
| **対照** | 従来構成リポジトリ（`tech-update-task-app-legacy`、Fat Controller・Service/Repository なし） |
| **比較条件** | 同一アプリ（タスク管理）、同一スタック（Laravel）、同一 CI ワークフロー・同一 Feature テスト |
| **評価スコープ** | **アプリ全体**（認証・プロフィール・タスク・CI 全ジョブ） |

詳細は [docs/EXPERIMENT.md](docs/EXPERIMENT.md) を参照してください。

> **フロントエンド:** 本リポジトリは Blade + Tailwind CSS + Vite + Alpine.js。React 等への移行比較は主シナリオ外の **拡張比較**（[EXPERIMENT.md — フロントエンドスタック](docs/EXPERIMENT.md#フロントエンドスタック拡張比較)）として位置づける。

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

機能一覧は [docs/FEATURE_LIST.md](docs/FEATURE_LIST.md) を参照してください。

---

## クイックスタート

### 前提

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) など Compose v2 対応環境
- 開発フローは **Docker Compose のみ**（ホストで `php artisan serve` は使わない。ポート `8000` は nginx が使用）
- **フロント（npm）は Docker の `node` サービスのみ**（ホストで `npm install` / `npm ci` しない。`node_modules` の混在で `ENOTEMPTY` などが起きる）

### 初回セットアップ

```bash
cp .env.example .env

docker compose build app
docker compose up -d

docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed

# フロント資産（ログイン画面など @vite 用）
composer npm:docker-build
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

### フロントエンド（Docker のみ）

ホストに Node が入っていても、**依存のインストール・ビルドはコンテナ内だけ**で行います。

```bash
composer npm:docker-ci      # rm -rf node_modules && npm ci
composer npm:docker-build   # 上記 + npm run build
docker compose --profile node run --rm node npm run lint
docker compose --profile node run --rm --service-ports node npm run dev   # Vite 開発サーバー
```

### 個別（PHP / API）

```bash
docker compose exec app composer phpstan
docker compose exec app composer test
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
composer experiment:metrics -- --phase baseline --diff-ref experiment-baseline-v1
git tag -a experiment-baseline-v1 -m "Experiment baseline: improved architecture"
```

メトリクス JSON は `experiment/metrics/` に出力されます（Git 管理外）。

### 2. 更新シナリオの実施

[docs/experiment/scenarios/](docs/experiment/scenarios/) の手順に従い、ブランチで変更を適用します。

```bash
git checkout -b exp/my-scenario experiment-baseline-v1
# … シナリオに沿って変更 …
composer experiment:metrics -- --phase after_update --diff-ref experiment-baseline-v1
# … テスト・コードを修正 …
./scripts/check-quality.sh
composer experiment:metrics -- --phase after_fix --diff-ref experiment-baseline-v1
```

### 3. 記録

[docs/experiment/metrics-record-template.md](docs/experiment/metrics-record-template.md) の列定義に従い、スプレッドシート等に記録します。

### 4. 従来構成との比較

従来構成リポジトリ（`tech-update-task-app-legacy`）で、**同じシナリオ・同じ手順** を繰り返します。

---

## 更新シナリオ

本研究の **主シナリオは 3 件**。いずれも [docs/experiment/scenarios/](docs/experiment/scenarios/) に手順があり、`experiment-baseline-v1` から `exp/*` ブランチで実施します。

| # | シナリオ | ドキュメント |
|---|----------|--------------|
| 1 | API 仕様変更: status integer 化 | [api-spec-change-status-int.md](docs/experiment/scenarios/api-spec-change-status-int.md) |
| 2 | API 仕様変更: priority 追加 | [api-spec-change-priority.md](docs/experiment/scenarios/api-spec-change-priority.md) |
| 3 | DB / クエリ変更（タイトル検索） | [db-schema-change.md](docs/experiment/scenarios/db-schema-change.md) |

**拡張実験（参考）:** Laravel バージョン更新・テストツール更新・JavaScript ライブラリ変更は、主シナリオとは別枠の参考計測です。手順 MD は本リポジトリには含めず、収集済み結果は `tech-update-task-app-legacy` リポジトリの `docs/experiment/results/COMPARISON.md` の「拡張実験」節を参照してください。

---

## 評価指標

**主指標は修正工数**（`after_fix` フェーズの変更ファイル数・行数）。API 仕様変更シナリオでは、改良構成と従来構成で **テスト通過率が同一になることがある** ため、通過率だけでは構成差を評価できません。

| 優先 | 指標 | 概要 | 取得 |
|------|------|------|------|
| **1** | **修正工数** | 変更ファイル数・追加/削除行 | `composer experiment:metrics -- --diff-ref experiment-baseline-v1` の `git.*`（**after_fix**） |
| 2 | 更新直後のテスト失敗数 | PHPUnit / Newman の fail 件数 | 同上（**after_update**） |
| 3 | 作業時間 | 分 | 手動（[metrics-record-template.md](docs/experiment/metrics-record-template.md)） |
| 4 | エラー発生率 | PHPStan 件数、CI 失敗ジョブ | スクリプト + 手動 |

定義の詳細: [docs/EXPERIMENT.md](docs/EXPERIMENT.md)

---

## ドキュメント索引

| ドキュメント | 内容 |
|--------------|------|
| [docs/EXPERIMENT.md](docs/EXPERIMENT.md) | 実験設計・指標・フェーズ |
| [docs/FEATURE_LIST.md](docs/FEATURE_LIST.md) | 機能一覧 |
| [docs/TESTING.md](docs/TESTING.md) | テストツールの使い方 |
| [docs/CI.md](docs/CI.md) | GitHub Actions |
| [docs/experiment/metrics-record-template.md](docs/experiment/metrics-record-template.md) | メトリクス記録テンプレート |
| [docs/experiment/scenarios/](docs/experiment/scenarios/) | 更新シナリオ手順 |

---

## ライセンス

MIT（Laravel プロジェクトスケルトンに準拠）
