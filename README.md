<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Docker での開発

前提: [Docker Desktop](https://www.docker.com/products/docker-desktop/) など Compose v2 対応環境。

このプロジェクトの開発フローは **Docker Compose のみ**です。**ホストで `php artisan serve` は使わないでください**（`8000` 番ポートは nginx が使います。競合します）。

- **DB / APP_URL**: [docker-compose.yml](docker-compose.yml) の `app.environment` で `DB_*` と `APP_URL` / `ASSET_URL`（`http://localhost:8000`）がコンテナに渡ります。[docker/php/zz-clear-env.conf](docker/php/zz-clear-env.conf) で php-fpm が環境変数を引き継ぎます。`.env` に `DB_HOST=postgres` を書く必要はありません（ホストだけで `artisan` を動かす上級者向けは `.env.example` の注記を参照）。

1. プロジェクトルートで `.env` を用意する。

   ```bash
   cp .env.example .env
   ```

2. 初回ビルドと起動。

   ```bash
   docker compose build app
   docker compose up -d
   ```

   `app` コンテナ起動時に `vendor` が無ければ `composer install` が自動実行されます（[docker/php/docker-entrypoint.sh](docker/php/docker-entrypoint.sh)）。

3. アプリケーションキーとマイグレーション（コンテナ内の Artisan）。

   ```bash
   docker compose exec app php artisan key:generate
   docker compose exec app php artisan migrate --seed
   ```

4. ブラウザまたは API クライアントで **`http://localhost:8000`** を開く（例: `http://localhost:8000/up` でヘルス、`http://localhost:8000/api/tasks`）。

### API を curl で試すとき（`http_code` が `000` になる場合）

`curl -w "%{http_code}"` が **`000`** のときは **Laravel が返したステータスではありません**。**TCP 接続の前に失敗**しており、よくある原因は次のとおりです。

- **`...` をそのままコマンドに含めている**（省略記号は使えません。**フルの URL** を書く）
- **Docker が起動していない**、または **`php artisan serve` が 8000 を占有**している（このプロジェクトでは nginx が 8000 を使う）
- URL の typo（`http://` 忘れ、ポート違いなど）

**正しい例（コピペ用。URL は一行で省略しない）:**

```bash
curl -s -o /dev/null -w "%{http_code}\n" -X POST "http://localhost:8000/api/tasks" \
  -H "Content-Type: application/json" \
  -d '{"title":"t","status":"invalid"}'
```

期待される終了表示は **`422`**（バリデーションエラー）です。本文を見る場合は `-o /dev/null` を外す。

同じ確認をスクリプトで行う場合:

```bash
chmod +x scripts/curl-api-smoke.sh
./scripts/curl-api-smoke.sh
# 別ホスト例: ./scripts/curl-api-smoke.sh http://127.0.0.1:8000
```

## テスト（更新耐性）

PHPStan / ESLint / PHPUnit / Postman の概要・手順は [docs/TESTING.md](docs/TESTING.md) を参照してください。

```bash
./scripts/check-quality.sh   # PHPStan + ESLint + PHPUnit
```

よく使うコマンド:

- ログ: `docker compose logs -f`
- 停止: `docker compose down`（DB ボリュームは残る）
- DB ごと消す: `docker compose down -v`

ホストの `5432` が既に使われている場合は、`docker-compose.yml` の `postgres` の `ports` を `5433:5432` などに変更してください。

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).