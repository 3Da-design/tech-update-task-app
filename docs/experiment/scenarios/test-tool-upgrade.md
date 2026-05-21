# シナリオ: テストツール更新

## 目的

PHPUnit / PHPStan / Newman などの **テスト・品質ツールのメジャー更新** 時の影響を比較する。CI パイプラインは同一のまま、ツールバージョンのみ上げる。

## 想定される破壊箇所

| ツール | 例 |
|--------|-----|
| PHPUnit 12 → 13 | 属性名、DataProvider、カバレッジ設定 |
| PHPStan / Larastan | ルール厳格化、新しい型推論 |
| Newman | CLI オプション、レポート形式 |
| Pint | フォーマットルール変更 |

## 事前条件

- ベースライン CI 緑
- 更新対象ツールの CHANGELOG を確認

## 変更内容の具体例

両リポジトリで **同じバージョン** に更新する。

```bash
# 例: PHPUnit
docker compose exec app composer require --dev phpunit/phpunit:^13.0 --with-all-dependencies

# 例: PHPStan
docker compose exec app composer require --dev phpstan/phpstan:^2.2 --with-all-dependencies

# 例: Newman（package.json）
# package.json の newman を上げ、npm ci
```

`phpunit.xml`, `phpstan.neon`, `.github/workflows/ci.yml` の必要箇所も揃えて更新する。

## 実施手順

```bash
git checkout -b exp/test-tool-upgrade experiment-baseline-v1

# 1. composer.json / package.json を更新
docker compose exec app composer update phpunit/phpunit phpstan/phpstan --with-all-dependencies
docker compose --profile node run --rm node sh -c "npm ci"

# 2. 設定ファイルを公式マイグレーションガイドに沿って修正

composer experiment:metrics -- --phase after_update

# 3. テストコード・PHPStan 指摘を修正（アプリ本体は最小限）
docker compose exec app vendor/bin/pint
./scripts/check-quality.sh

composer experiment:metrics -- --phase after_fix
```

## 記録するメトリクス

- ツールごとの exit code / エラー件数
- テストコードのみの変更行数 vs アプリコードの変更行数
- 修正工数

## 完了条件

- [ ] 更新したツールのバージョンが両リポジトリで一致
- [ ] CI 4 ジョブ成功
- [ ] `experiment/metrics/runs/<run_id>/` に `after_update` / `after_fix` の JSON がある
