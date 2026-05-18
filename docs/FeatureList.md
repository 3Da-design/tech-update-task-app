# 機能一覧

Laravel 製のタスク管理 Web アプリケーションの機能一覧です。

## 概要

| 項目 | 内容 |
|------|------|
| フレームワーク | Laravel 13 |
| フロントエンド | Blade + Tailwind CSS（Vite） |
| 認証 | Laravel Breeze（セッション認証） |
| データベース | PostgreSQL（Docker Compose） |
| 開発環境 | Docker Compose（`http://localhost:8000`） |

ログインしたユーザーごとにタスクを管理します。未ログイン時はタスク画面へアクセスできません。

---

## 認証・アカウント

### ゲスト向け（未ログイン）

| 機能 | URL / ルート名 | 説明 |
|------|----------------|------|
| ログイン | `GET /login` (`login`) | カード型のログイン画面。メール・パスワード・ログイン状態保持 |
| ログイン処理 | `POST /login` | 認証成功後、タスク一覧へリダイレクト |
| 新規登録 | `GET /register` (`register`) | カード型の新規登録画面（名前・メール・パスワード） |
| 登録処理 | `POST /register` | 登録後に自動ログインし、タスク一覧へリダイレクト |
| パスワード再設定（申請） | `GET /forgot-password` (`password.request`) | 再設定メール送信用フォーム |
| パスワード再設定（申請送信） | `POST /forgot-password` (`password.email`) | 再設定リンクをメール送信 |
| パスワード再設定（入力） | `GET /reset-password/{token}` (`password.reset`) | 新パスワード入力画面 |
| パスワード再設定（保存） | `POST /reset-password` (`password.store`) | 新パスワードを保存 |

ログイン画面から新規登録画面へ、新規登録画面からログイン画面へ相互リンクできます。

### 認証済みユーザー向け

| 機能 | URL / ルート名 | 説明 |
|------|----------------|------|
| ログアウト | `POST /logout` (`logout`) | セッション終了 |
| プロフィール編集 | `GET /profile` (`profile.edit`) | 名前・メールアドレスの変更 |
| プロフィール更新 | `PATCH /profile` (`profile.update`) | プロフィール情報の保存 |
| アカウント削除 | `DELETE /profile` (`profile.destroy`) | パスワード確認後にアカウント削除 |
| パスワード変更 | `PUT /password` (`password.update`) | ログイン中のパスワード変更 |
| パスワード確認 | `GET/POST /confirm-password` (`password.confirm`) | 機密操作前のパスワード再確認 |
| メール認証案内 | `GET /verify-email` (`verification.notice`) | メール未認証時の案内（ルート定義あり） |
| メール認証 | `GET /verify-email/{id}/{hash}` (`verification.verify`) | 認証リンクの処理 |
| 認証メール再送 | `POST /email/verification-notification` (`verification.send`) | 認証メールの再送信 |

### UI（認証まわり）

- ヘッダー（ナビゲーション）にログイン中の **名前・メールアドレス** を表示
- ドロップダウンからプロフィール編集・ログアウト
- レスポンシブ対応（モバイル用ハンバーガーメニュー）

---

## タスク管理（Web）

認証ミドルウェア（`auth`）配下。操作対象は **ログインユーザー自身のタスクのみ** です。

| 機能 | URL / ルート名 | HTTP | 説明 |
|------|----------------|------|------|
| トップ | `GET /` | — | `/tasks` へリダイレクト |
| タスク一覧 | `GET /tasks` (`tasks.index`) | GET | 一覧表示・検索・フィルタ |
| タスク作成画面 | `GET /tasks/create` (`tasks.create`) | GET | 新規作成フォーム |
| タスク作成 | `POST /tasks` (`tasks.store`) | POST | タスクの登録 |
| タスク編集画面 | `GET /tasks/{id}/edit` (`tasks.edit`) | GET | 編集フォーム |
| タスク更新 | `PUT /tasks/{id}` (`tasks.update`) | PUT | タスクの更新 |
| タスク削除 | `DELETE /tasks/{id}` (`tasks.destroy`) | DELETE | タスクの削除（確認ダイアログあり） |

### タスク一覧の検索・フィルタ

| クエリパラメータ | 説明 |
|------------------|------|
| `title` | タイトルの部分一致検索 |
| `status` | ステータスで絞り込み（`todo` / `in_progress` / `done`） |
| `due_date_sort` | 期限の並び替え（`asc` 昇順 / `desc` 降順。未指定時は昇順） |

期限が未設定のタスクは、並び替え時に一覧の先頭側に表示されます。

### タスクの項目

| 項目 | 必須 | 説明 |
|------|------|------|
| タイトル (`title`) | ○ | 最大 255 文字 |
| 説明 (`description`) | — | 任意のテキスト |
| ステータス (`status`) | ○ | `todo` / `in_progress` / `done` |
| 期限 (`due_date`) | — | 日付（`Y-m-d`） |

### 画面のその他

- 操作成功時にフラッシュメッセージ（例: 「タスクを作成しました。」）
- バリデーションエラー時はフォームにエラー表示

---

## タスク管理（REST API）

ベース URL: `/api/tasks`

JSON でタスクの CRUD を提供します。`TaskService` 経由で **認証ユーザーのタスク** のみを操作します（テストでは `actingAs` で認証を模擬）。

| 機能 | メソッド | URL | 説明 |
|------|----------|-----|------|
| 一覧取得 | `GET` | `/api/tasks` | フィルタ付き一覧（Web と同じクエリパラメータ） |
| 作成 | `POST` | `/api/tasks` | タスク作成（成功時 `201`） |
| 更新 | `PUT` / `PATCH` | `/api/tasks/{id}` | タスク更新 |
| 削除 | `DELETE` | `/api/tasks/{id}` | タスク削除（成功時 `204`） |

> `GET /api/tasks/{id}`（詳細取得）はルート未定義のため提供していません。

### レスポンス形式

`TaskResource` により、各タスクは次の JSON フィールドを返します。

- `id`
- `title`
- `status`
- `due_date`（`Y-m-d` または `null`）
- `description`

バリデーションエラー時は `422` とエラー詳細を返します。

### API 動作確認

```bash
./scripts/curl-api-smoke.sh
```

---

## プロフィール

| 機能 | 説明 |
|------|------|
| 基本情報の更新 | 表示名・メールアドレスの変更 |
| メール変更時 | `email_verified_at` をリセット（再認証が必要な場合あり） |
| パスワード更新 | プロフィール画面内の専用フォーム |
| アカウント削除 | 現在のパスワード入力が必要 |

---

## データ・セキュリティ

| 項目 | 説明 |
|------|------|
| ユーザーとタスクの関連 | `tasks.user_id` で外部キー制約。ユーザー削除時はタスクもカスケード削除 |
| タスクの分離 | 一覧・参照・更新・削除はすべて `user_id = ログインユーザーID` でスコープ |
| CSRF | Web フォームは CSRF トークン必須 |
| パスワード | `bcrypt`（ハッシュ）で保存 |

---

## インフラ・運用

| 機能 | URL / コマンド | 説明 |
|------|----------------|------|
| ヘルスチェック | `GET /up` | アプリケーションの稼働確認 |
| Docker 開発 | `docker compose up -d` | nginx + PHP + PostgreSQL |
| マイグレーション | `php artisan migrate --seed` | テーブル作成・初期ユーザー投入 |
| 初期ユーザー（シード） | `test@example.com` / `password` | 開発用テストアカウント |

---

## 技術構成（参考）

| レイヤ | 主なクラス・ファイル |
|--------|----------------------|
| Web コントローラ | `App\Http\Controllers\Web\TaskController` |
| API コントローラ | `App\Http\Controllers\API\TaskController` |
| ビジネスロジック | `App\Services\TaskService` |
| データアクセス | `App\Repositories\TaskRepository` |
| バリデーション | `StoreTaskRequest`, `UpdateTaskRequest`, `IndexTaskRequest` |
| 設定 | `config/task.php`（ステータス一覧など） |

---

## 画面一覧

| 画面 | パス |
|------|------|
| ログイン | `/login` |
| 新規登録 | `/register` |
| タスク一覧 | `/tasks` |
| タスク作成 | `/tasks/create` |
| タスク編集 | `/tasks/{id}/edit` |
| プロフィール | `/profile` |
| パスワード再設定（申請） | `/forgot-password` |

---

*最終更新: プロジェクト現行実装に基づく*
