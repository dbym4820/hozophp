# HozoPHP：PHP用の法造オントロジーのデータ操作ライブラリ
アップロードした法造形式オントロジーのデータをPHPで処理するためのライブラリ．
オントロジーの修正・ファイルの変更に柔軟に対応できるよう設計したもの．

## Installation
### 環境構築
#### Composerのインストール
- Composer公式：[https://getcomposer.org/download/](https://getcomposer.org/download/)
- Windows：[ダウンローダ(Composer-Setup.ext)](https://getcomposer.org/Composer-Setup.exe)からインストール
- macOS
```
~$ brew install composer
```

#### ライブラリのインストール

```
~$ composer require dbym4820/hozophp
```

#### オントロジーのアップロード・バージョン管理
プロジェクトの任意の場所に法造のオントロジーファイル（XML）をアップ．

以下では，プロジェクトが
```
── project-root/
  ├── index.php
  └── ontology/
	  └──  20220916-sample.xml
```
というディレクトリ構造になっていて，index.phpの中で呼び出す前提で解説．


### プロジェクトへの導入
#### 方法１：コンストラクタでオントロジーを設定

```
require_once('./vendor/autoload.php'); // autoloaderの読み込み
use HozoPHP\OntologyManager; // 名前空間の使用宣言
$ontology = new OntologyManager(__DIR__."/ontology/", "20220916-sample.xml"); // 初期設定の反映とインスタンス化
```

もちろん，useせずに， new HozoPHP\OntologyManager("/ontology/", "20220916-sample.xml") としても別にいい

#### 方法２：個別に設定

```
$ontology = new OntologyManager(); // インスタンス化
$ontology->setOntologyDirectory(__DIR__."/ontology/"); // オントロジーのディレクトリを指定
$ontology->setOntology("20220916-sample.xml"); // 自分のオントロジーの指定
$ontology->treatOntology(); // オントロジーをPHPオブジェクト化

```

### 使用例

```
require_once('./vendor/autoload.php'); // autoloaderの読み込み
use HozoPHP\OntologyManager; // 名前空間の使用宣言
$ontology = new OntologyManager(__DIR__."/ontology/", "20220916-sample.xml"); // 初期設定の反映とインスタンス化
$result_array = $ontology->getAllConcepts(); //処理の実行(全概念の取得)
$ontology->showJson($result_array); // 結果のArrayをJSONとして表示
```

詳細な使用例は，本プロジェクトの[index.php](./index.php)を参照のこと．


## 提供メソッド一覧
### 基本概念の取得
- 全基本概念の一覧
```
$result = $ontology->getAllConcepts();
```

- 特定のIDを持つ基本概念
```
$result = $ontology->getConceptInfoFromID('基本概念ID');
```

- 特定のラベルを持つ基本概念
```
$result = $ontology->getConceptInfoFromLabel('基本概念ラベル');
```

- 特定のIDのを持つ基本概念の親概念
```
$result = $ontology->getParentConcept('基本概念ID');
```

- 特定のIDのを持つ基本概念の子概念一覧
```
$result = $ontology->getChildrenConcepts('基本概念ID');
```

- 特定のIDのを持つ基本概念の祖先概念
```
$result = $ontology->getAncestorConcepts('基本概念ID');
```

- 特定のIDのを持つ基本概念の子孫概念一覧
```
$result = $ontology->getDescendantConcepts('基本概念ID');
```

### 部分概念の取得
- 特定の基本概念に付随する部分概念・属性概念の一覧
```
$result = $ontology->getPartOfConceptInfo('基本概念ID');
```

- 特定の基本概念に付随する部分概念・属性概念（祖先のものも含む）一覧
```
$result = $ontology->getAncestorSubConcepts('基本概念ID');
```

- ~~特定のIDを持つ部分概念~~

### インスタンス概念の取得
- 全インスタンスの一覧
```
$result = $ontology->getAllInstance()
```

- ~~特定の部分概念を持つインスタンスの一覧~~
```
$result = $ontology->getAllInstanceWhichHasSpecificPartInput(..........)
```
 

### 関係概念の取得
- IS-A関係の一覧
```
$result = $ontology->getISARelationshipList();
```

- ~~その他~~

## 概念の構造
### 基本概念／インスタンス概念
```
[
  'id' => '概念ID（string）',
  'label' => '概念ラベル（string）',
  'instantiation' => 'インスタンスか否か（string："true"か"false"）',
  'sub_concept' => [ 
      [
		  'id' => '部分・属性概念のID（string）',
		  'kind' => '部分概念か属性概念か（string："p/o"か"a/o"）,
		  'cardinality' => '個数制約（string）',
		  'role' => 'ロール概念名（string）',
		  'class_constraint' => 'クラス制約概念ラベル（string）',
		  'role_holder' => 'ロールホルダー名（string）',
		  'value' => '値（string）',
		  'sub_concept' => 
			  [
			    // ＊以下同形（部分概念の部分概念があれば）
			  ]
	  ]
	  // ＊以下同形（他の部分概念があれば）
	]
]
```

## 依存ライブラリ・対応システムなど
- PHP（>=7.3）
- [Composer](https://getcomposer.org/)（>=2）
- [法造(https://hozo.jp/index_jp.html)](https://hozo.jp/index_jp.html)（5.7）
