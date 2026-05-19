# テスト概要

本プロジェクトは、フレームワークや API の更新時に壊れにくくするため、**実行前（静的解析）** と **実行後（動作確認）** の2段階でテストを行います。

## 全体像

```text
[実行前]  コードを動かす前
  ├─ PHPStan   … 型・構造（主軸）… API 変更に強い
  └─ ESLint    … 構文・規約（補助）… 間接的な品質維持

[実行後]  アプリを起動したあと
  ├─ PHPUnit   … ロジック・仕様（主軸）… 仕様変更の回帰検知
  └─ Postman   … API 通信（主軸）… 実 HTTP・セッションの検証
```

| タイミング | ツール | 検知内容 | 更新耐性での役割 | 主/補 |
|-----------|--------|----------|------------------|-------|
| 実行前 | PHPStan | 型・クラス構造 | API シグネチャ変更の早期検知 | 主軸 |
| 実行前 | ESLint | JS 構文・基本ルール | フロント資産の品質維持 | 補助 |
| 実行後 | PHPUnit | ビジネスロジック・HTTP 仕様 | リファクタ後の仕様維持 | 主軸 |
| 実行後 | Postman | 実際の API 通信 | 本番に近い経路での回帰確認 | 主軸 |

---

## 前提

- 開発は **Docker Compose** 前提（`http://localhost:8000`）
- ホストで `php artisan serve` は使わない（ポート競合のため）
- API の認証は **Breeze のセッション（web ガード）** を利用
- Postman から API を叩くため、`bootstrap/app.php` で API ルートにもセッション middleware を付与している

---

## 1. PHPStan（実行前）

### 目的

PHP コードの型不整合・存在しないプロパティ参照などを、実行前に検出します。コントローラやサービスの引数・戻り値が変わったときに有効です。

### 構成

| ファイル | 説明 |
|---------|------|
| `phpstan.neon` | 解析設定（Larastan、level 5） |
| `composer.json` | `scripts.phpstan` / `scripts.check` |

解析対象: `app/`, `routes/`, `config/`

### 実行

```bash
docker compose exec app composer phpstan
```

---

## 2. ESLint（実行前）

### 目的

`resources/js/` 配下の JavaScript の構文エラーや基本的なコーディング規約違反を検出します。

### 構成

| ファイル | 説明 |
|---------|------|
| `eslint.config.js` | ESLint 9 flat config |
| `package.json` | `npm run lint` / `npm run lint:fix` |

### 実行

```bash
docker compose --profile node run --rm node npm run lint
```

---

## 3. PHPUnit（実行後）

### 目的

Laravel のテストランナーで、認証・バリデーション・CRUD など **アプリケーションの仕様** を自動検証します。インメモリ SQLite を使うため高速です。

### 構成

| ファイル | 説明 |
|---------|------|
| `phpunit.xml` | テストスイート・テスト用 DB（SQLite in-memory） |
| `tests/Feature/TaskApiTest.php` | Task API の主テスト |
| `tests/Feature/Auth/*` | Breeze 認証まわり |
| `composer.json` | `scripts.test` → `php artisan test` |

### Task API テスト一覧（`TaskApiTest`）

| テスト | 期待 |
|--------|------|
| `test_index_returns_200_and_task_count` | GET 一覧 200・件数 |
| `test_store_returns_201_with_created_task` | POST 正常 201 |
| `test_store_with_invalid_status_returns_422` | 不正 status → 422 |
| `test_store_without_title_returns_422` | title なし → 422 |
| `test_update_returns_200` | PUT 正常 200 |
| `test_update_unknown_id_returns_404` | 存在しない ID → 404 |
| `test_destroy_return_204_and_removes_row` | DELETE 204・DB から削除 |

### 実行

```bash
# 全テスト
docker compose exec app composer test

# または PHPStan と続けて
docker compose exec app composer check

# Task API のみ
docker compose exec app php artisan test --filter=TaskApiTest
```

---

## 4. Postman（実行後）

### 目的

Docker 上で動いている実サーバに対し、**セッション Cookie・CSRF** を含む実際の HTTP で API を検証します。PHPUnit では再現しにくい nginx → PHP → DB の経路を確認します。

### 構成

| ファイル | 説明 |
|---------|------|
| `postman/Task-API.postman_collection.json` | リクエスト・テストスクリプト |
| `postman/local.postman_environment.json` | `baseUrl`, `email`, `password` |
| `postman/README.md` | Import・Runner の手順 |

### シードユーザ（デフォルト）

| 項目 | 値 |
|------|-----|
| Email | `test@example.com` |
| Password | `password` |

### 実行順（Collection Runner）

1. `docker compose up -d`
2. `docker compose exec app php artisan migrate --seed`（初回・DB 空のとき）
3. Postman でコレクション・環境を Import
4. Environment: **Local (Docker)**
5. Runner の順序: **Health → Auth → Tasks API**
6. **Save cookies** を ON
7. 各フォルダを上から実行

### コレクション構成

| フォルダ | 内容 |
|--------|------|
| Health | `GET /up` |
| Auth | `GET /login`（CSRF 取得）→ `POST /login` |
| Tasks API | `GET/POST/PUT/DELETE /api/tasks` |

### CSRF・認証の注意

- **POST /login** には HTML の `<meta name="csrf-token">` の平文を `_token` として送る（Cookie の暗号化値は使わない）
- **POST /login** には `X-XSRF-TOKEN` ヘッダーを付けない
- **Tasks API** の POST/PUT/DELETE には `X-XSRF-TOKEN`（Cookie 由来）を付与
- API でセッション認証を使うため `bootstrap/app.php` に `StartSession` 等を設定済み

### Newman（CLI・任意）

```bash
npx newman run postman/Task-API.postman_collection.json \
  -e postman/local.postman_environment.json \
  --folder "Health" \
  --folder "Auth" \
  --folder "Tasks API"
```

---

## 一括実行

PHPStan・ESLint・PHPUnit をまとめて実行:

```bash
./scripts/check-quality.sh
```

Postman は GUI または Newman で別途実行します。

---

## ツール別の使い分け

| 確認したいこと | 使うツール |
|----------------|-----------|
| メソッド引数の型変更 | PHPStan |
| JS の typo・未使用変数 | ESLint |
| バリデーション 422 / 404 などの仕様 | PHPUnit |
| ログイン後の Cookie で API が動くか | Postman |
| フレームワーク更新後の一括確認 | `check-quality.sh` + Postman Runner |

---

## よくある失敗と対処

| 症状 | 原因の例 | 対処 |
|------|----------|------|
| POST /login が **419** | CSRF 不一致 | Cookie 削除 → Auth を再実行。コレクション最新版を Import |
| GET /api/tasks が **401** | 未ログイン / セッション無効 | Auth を先に実行。`migrate --seed` |
| PHPUnit のみ失敗 | 仕様変更 | `TaskApiTest` を更新 |
| PHPStan 失敗 | 型・PHPDoc 不足 | 該当クラスに `@property` 等を追加 |
| curl / Postman 接続 **000** | Docker 未起動・URL 誤り | `docker compose ps`、`http://localhost:8000` を確認 |

---

## 関連ファイル一覧

```text
phpstan.neon
eslint.config.js
phpunit.xml
bootstrap/app.php          # API セッション middleware
composer.json              # phpstan, test, check
package.json               # lint
scripts/check-quality.sh
scripts/curl-api-smoke.sh  # 簡易疎通（認証なし・422 確認）
tests/Feature/TaskApiTest.php
postman/
  Task-API.postman_collection.json
  local.postman_environment.json
  README.md
```
