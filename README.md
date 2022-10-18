# HozoPHP：PHP用の法造オントロジーのデータ操作ライブラリ
アップしたオントロジーのバージョン・検索条件を指定するとJSONでデータを返す用ライブラリ

## 使い方

### 環境構築

- ライブラリのインストール

```
~$ composer require dbym4820/hozophp
```

- オントロジーのアップロード・バージョン管理

法造のオントロジーファイル（XML）をアップ．
（基本は「/ontology/」ディレクトリ）


### 初期化

- コンストラクタでオントロジーを設定

```
require_once('./vendor/autoload.php');
use HozoPHP\OntologyManager;
$ontology = new OntologyManager("/ontology/", "20220916-sample.xml");
```

- 個別に設定

```
$ontology = new OntologyManager(); // オントロジーを初期化
$ontology->setOntologyDirectory("/ontology/"); // オントロジーのディレクトリを指定
$ontology->setOntology("20220916-sample.xml"); // 自分のオントロジーの指定
$ontology->treatOntology(); // オントロジーをPHPのオブジェクト化

```

### リクエスト方法
例．http://localhost/index.php?type=get-all-concepts

```
$json_val = $ontology->getAllInstance();
$ontology->showJson($json_val);
```


- 全概念の一覧取得

```
index.php?type=get-all-concepts
```


## 依存ライブラリ・対応システムなど
- PHP（>=7.3）
- Composer（2.4.2）
- 法造（5.7）
