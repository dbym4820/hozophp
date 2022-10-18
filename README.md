# OntologyManager：オントロジーのデータ検索
アップしたオントロジーのバージョン・検索条件を指定するとJSONでデータを返す用システム

## 使い方

### 環境構築

- オントロジーのアップロード・バージョン管理
「/ontology/」ディレクトリに，法造のオントロジーファイル（XML）をアップ

### リクエスト方法
例．http://localhost/index.php?type=get-all-concepts

- 全概念の一覧取得

```
index.php?type=get-all-concepts
```
