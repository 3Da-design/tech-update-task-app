# 実験設計（改良構成）

本リポジトリは、技術更新時の影響を定量評価するための **改良構成（良い例）** の実験台です。

## 研究ゴール

**設計（モジュール化 + CI/CD）が、技術更新時の影響をどれだけ抑えられるか**を、従来構成（別リポジトリ）と比較して定量的に示す。

| 比較軸 | 従来構成（別リポジトリで作成予定） | 本リポジトリ（改良構成） |
|--------|-----------------------------------|-------------------------|
| 構造 | モノリシック、Controller にロジック集中、DB 直接操作 | Controller / Service / Repository 分離、Interface による抽象化 |
| CI/CD | 本リポジトリと **同一ワークフロー** をコピーして揃える | GitHub Actions（4 ジョブ） |
| アプリ | 同一機能のタスク管理（Laravel） | 同一 |

## 本リポジトリの役割

- 改良構成の **ベースライン** を確立する
- 更新シナリオ実施後のメトリクスを記録する
- 従来構成リポジトリ作成時の **クローン元** となる

従来構成へのリファクタ手順は [experiment/LEGACY_MIGRATION.md](./experiment/LEGACY_MIGRATION.md) を参照してください（本リポジトリでは実施しません）。

## 比較条件

| 項目 | 内容 |
|------|------|
| アプリケーション | タスク管理（Web + REST API） |
| 技術スタック | Laravel 13、PHP 8.4、PostgreSQL、Breeze、Vite |
| 評価スコープ | **アプリ全体**（認証・プロフィール・タスク・CI 全ジョブ） |

### アーキテクチャの範囲

- **タスク領域（改良の核）:** `Controller` → `TaskService` → `TaskRepositoryInterface` → `TaskRepository` → Eloquent
- **認証・プロフィール:** Laravel Breeze 標準（Controller から Model 直接）。構造は「悪い例」だが、Laravel / テストツール / JS 更新の影響は **全体メトリクスに含める**

## 更新シナリオ

各シナリオの手順は [experiment/scenarios/](./experiment/scenarios/) を参照してください。

| シナリオ | ドキュメント |
|----------|--------------|
| バックエンド API 仕様変更 | [api-spec-change.md](./experiment/scenarios/api-spec-change.md) |
| Laravel バージョン更新 | [laravel-upgrade.md](./experiment/scenarios/laravel-upgrade.md) |
| テストツール更新 | [test-tool-upgrade.md](./experiment/scenarios/test-tool-upgrade.md) |
| JavaScript ライブラリ変更 | [js-library-change.md](./experiment/scenarios/js-library-change.md) |

**原則:** 1 シナリオ = 1 実験ラン。両リポジトリに **同一の変更内容** を適用し、メトリクスを比較する。

## 評価指標

### 1. テスト通過率

```
通過率 (%) = 成功数 ÷ 総数 × 100
```

| 対象 | 成功の定義 |
|------|------------|
| PHPUnit | `php artisan test` の pass |
| Newman | Postman コレクションの `pm.test` 成功数 |
| ESLint | `npm run lint` が exit 0 |
| PHPStan | `composer phpstan` が exit 0（エラー 0 件） |

自動収集: `composer experiment:metrics`（[scripts/collect-experiment-metrics.sh](../scripts/collect-experiment-metrics.sh)）。JSON は **`experiment/metrics/runs/<run_id>/<phase>.json`** に保存されます（`baseline` で新規 `run-*` が作成され、`.active-run` に記録されます）。

表形式の記録自動生成: `composer experiment:record`（[scripts/generate_experiment_record.py](../scripts/generate_experiment_record.py)）。詳細は [metrics-record-template.md](./experiment/metrics-record-template.md) を参照してください。

### 2. 修正工数

自動化しない項目。 [metrics-record-template.md](./experiment/metrics-record-template.md) に手動記録する。

| 項目 | 取得方法 |
|------|----------|
| 作業時間（分） | タイマーまたは手入力 |
| 変更ファイル数 | `git diff --stat` |
| 追加 / 削除行数 | `git diff --shortstat` |
| コミット数 | シナリオ開始〜CI 緑まで |

**完了基準:** 両リポジトリで CI 全ジョブが成功（`after_fix` フェーズ）。

### 3. エラー発生率

事前に定義した「エラー」を数える。

| 種別 | 数え方 |
|------|--------|
| PHPUnit 失敗数 | 更新直後（`after_update`）の fail 件数 |
| PHPStan エラー件数 | 更新直後の error 行数 |
| CI ジョブ失敗 | push ごとの失敗ジョブ数 ÷ 実行ジョブ数 |
| 手動不具合 | ブラウザ / API で発見したバグ件数（メモ欄） |

## 実験フェーズ

| フェーズ | 説明 | メトリクス収集 |
|----------|------|----------------|
| `baseline` | 更新前・CI 緑の状態 | `composer experiment:metrics -- --phase baseline`（新しい `runs/run-<UTC>/` が作成される） |
| `after_update` | 更新適用直後・テスト未修正 | `composer experiment:metrics -- --phase after_update`（`.active-run` のランに保存） |
| `after_fix` | 修正完了・CI 緑 | `composer experiment:metrics -- --phase after_fix` |

任意: すべてのフェーズを同じフォルダにまとめたい場合は `composer experiment:metrics -- --phase baseline --run my-experiment-1` のように **`--run`** で ID を固定します。

## ベースラインの確立

改良構成が完成し、CI がすべて成功したら:

```bash
git tag -a experiment-baseline-v1 -m "Experiment baseline: improved architecture"
```

以降のシナリオはこのタグからブランチを切ることを推奨します。

## 実験の進め方（概要）

1. ベースライン tag を作成
2. シナリオ用ブランチを切る（例: `exp/api-spec-change`）
3. [scenarios/](./experiment/scenarios/) に従い更新を適用
4. `after_update` でメトリクス収集（同一 `runs/<run_id>/`）
5. テスト・コードを修正し CI を緑にする
6. `after_fix` でメトリクス収集
7. `composer experiment:record -- --scenario <シナリオID> --write` で `RECORD.md` を生成し、必要ならスプレッドシートへ転記（[metrics-record-template.md](./experiment/metrics-record-template.md) 参照）
8. 従来構成リポジトリで 3〜7 を繰り返し、比較表を作成

## 関連ドキュメント

| ドキュメント | 内容 |
|--------------|------|
| [README.md](../README.md) | プロジェクト概要・クイックスタート |
| [TESTING.md](./TESTING.md) | テストツールの詳細 |
| [CI.md](./CI.md) | GitHub Actions |
| [FeatureList.md](./FeatureList.md) | 機能一覧 |
| [metrics-record-template.md](./experiment/metrics-record-template.md) | 記録テンプレート・3 行の意味・`experiment:record` |
| [scripts/collect-experiment-metrics.sh](../scripts/collect-experiment-metrics.sh) | メトリクス収集（runs フォルダ） |
| [scripts/generate_experiment_record.py](../scripts/generate_experiment_record.py) | `RECORD.md` 自動生成 |
