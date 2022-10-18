<?php
// 基本ページ
require_once(realpath(dirname(__FILE__)) . '/vendor/autoload.php');
require_once(realpath(dirname(__FILE__)) . "/src/OntologyManager.php");
Dotenv\Dotenv::createImmutable(realpath(dirname(__FILE__)))->load();

use HozoPHP\OntologyManager;
$ontology = new OntologyManager(realpath(dirname(__FILE__)).$_ENV['ONTOLOGY_DIRECTORY'], $_ENV['ONTOLOGY_FILENAME']);

// ライブラリとして使う場合の別の呼び出し方
// $ontology = new OntologyManager(); // オントロジーを初期化
// $ontology->setOntologyDirectory("/ontology/"); // オントロジーのディレクトリを指定
// $ontology->setOntology("20220916-sample.xml"); // 自分のオントロジーの指定
// $ontology->treatOntology(); // オントロジーをPHPのオブジェクト化

$parameter_list = array();

// GETとして送信想定されるパラメータ一覧
$parameter_type_list = array(
    "type",
    "concept-id",
    "concept-label"
);

foreach($parameter_type_list as $param) {
    // Getパラメータを変数化
    $parameter_list = array_merge($parameter_list, array(
	$param => !empty($_GET[$param]) ? $_GET[$param] : null
    ));
}

/*** オントロジーに関するデータ要求に応えるAPI部分  ***/
/***** リクエストの分岐処理  *****/

$json_value;
switch($parameter_list['type']) {
    case 'get-all-concepts':
	// すべてのインスタンス一覧を取得
	$json_value = $ontology->getAllConcepts();
	break;
    case 'get-all-instance':
	// すべてのインスタンス一覧を取得
	$json_value = $ontology->getAllInstance();
	break;
    case 'get-all-instance-which-has-subactivity':
	// 特定の部分概念を持つインスタンス概念を取得
	$json_value = $ontology->getAllInstanceWhichHasSpecificPartInput($parameter_list['concept-id']);
	break;
    case 'get-concept-from-id':
	// IDから概念情報を取得
	$json_value = $ontology->getConceptInfoFromID($parameter_list['concept-id']);
	break;
    case 'get-concept-from-label':
	// ラベルから概念情報を取得
	$json_value = $ontology->getConceptInfoFromLabel($parameter_list['concept-label']);
	break;
    case 'get-part-from-id':
	// 基本概念IDから，そこに付随する部分概念を取得
	$json_value = $ontology->getPartOfConceptInfo($parameter_list['concept-id']);
	break;
    case 'get-isa-relation':
	// IS-A関係をすべて取得
	$json_value = $ontology->getISARelationshipList($parameter_list['concept-label']);
	break;
    case 'get-child-concepts':
	// オントロジー内の基本概念について，特定の基本概念の子概念を取得
	$json_value = $ontology->getChildrenConcepts($parameter_list['concept-id']);
	break;
    case 'get-parent-concept':
	// オントロジー内の基本概念について，特定の基本概念の親概念を取得
	$json_value = $ontology->getParentConcept($parameter_list['concept-id']);
	break;
    case 'get-ancestor-concepts':
	// オントロジー内の基本概念について，特定の基本概念の先祖概念を取得
	$json_value = $ontology->getAncestorConcepts($parameter_list['concept-id']);
	break;
    case 'get-descendant-concepts':
	// オントロジー内の基本概念について，特定の基本概念の子孫概念を取得
	$json_value = $ontology->getDescendantConcepts($parameter_list['concept-id']);
	break;	
    default:
	$json_value = array();
	break;
}

// 出力
$ontology->showJson($json_value);
