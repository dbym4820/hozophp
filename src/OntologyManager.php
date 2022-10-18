<?php
namespace HozoPHP;

class OntologyManager {
    private $ontology_filename;
    private $ontology_directory;
    private $ontology_path;
    private $current_ontology_object;
    
    public function __construct() {
	// 引数の数でコンストラクタ多態性
	$arg_list = func_get_args();
        $arg_num = func_num_args();
        if (method_exists($this, $func='__construct'.$arg_num)) {
            call_user_func_array(array($this, $func), $arg_list);
        } else {
	    // 引数なしコンストラクタ呼び出しの場合
	    $this->setOntologyDirectory(realpath(dirname(__FILE__))."/ontology/");
	    $this->setOntologyFile("20220916-sample.xml");
	    $this->treatOntology();
	}
    }

    public function __construct1($ontology_directory) {
	// setOntologyについてはユーザが自分でやらないといけない
	$this->setOntologyDirectory($ontology_directory);
    }

    public function __construct2($ontology_directory, $ontology_filename) {
	// setOntologyについてはユーザが自分でやらないといけない
	$this->setOntologyDirectory($ontology_directory);
	$this->setOntologyFile($ontology_filename);
	$this->treatOntology();
    }

    
    /*** 起動時の設定用メソッド ***/    
    public function setOntologyDirectory($ontology_directory_path) {
	// オントロジーのファイルを置く場所を設定
	$this->ontology_directory = $ontology_directory_path;
    }
    
    public function setOntologyFile($ontology_filename) {
	// オントロジーファイルの場所（名前）を設定
	$this->ontology_filename = $ontology_filename;
	$this->ontology_path = $this->ontology_directory.$this->ontology_filename;
    }

    public function treatOntology() {
	$this->current_ontology_object = simplexml_load_file($this->ontology_path);
    }
    
    /*** ユーティリティ  ***/
    public function getOntologyFilePath() {
	return $this->ontology_path;
    }

    public function getOntologyObject() {
	return $this->current_ontology_object;
    }

    public function showJson($data) {
	// JSONデータを表示するための関数
        header("Content-Type: application/json; charset=utf-8");
	echo json_encode($data);
	return;
    }

    public function flatten(array $array) {
	// 入れ子配列を平坦化する関数
	$return = array();
	array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
	return $return;
    }
    
    
    /*** オントロジーの検索など  ***/
    /***** 基本概念の処理 *****/
    public function getAllConcepts() {
	$concept_info =  $this->current_ontology_object->xpath("W_CONCEPTS/CONCEPT");
	$concept_count = count($concept_info);
	$concept_list = array();
	//return $concept_info;
	for($i=0; $i<$concept_count; $i++) {
	    $c = $concept_info[$i];
	    array_push($concept_list, array(
		"id" => (string)$c->attributes()->id,
		"label" => (string)$c->LABEL,
		"parts" => $this->extractSubSlotsFromPlainConceptInfo($c)
	    ));
	}
	return $concept_list;
    }
    
    private function extractSubSlotsFromPlainConceptInfo($plain_concept_info) {
	// 概念定義の塊からPart-of/Attribute-ofを取り出して変形
	if(count($plain_concept_info->SLOTS) === 0) {
	    return array();
	}
	$parts = $plain_concept_info->SLOTS->SLOT;
	$parts_count = count($parts);
	$parts_list = array();

	for($i=0; $i<$parts_count; $i++) {
	    $part = $parts[$i]->attributes();
	    array_push($parts_list, array(
		"id" => (string)$part->id,
		"kind" => (string)$part->kind,
		"cardinality" => (string)$part->num,
		"role" => (string)$part->role,
		"class_constraint" => (string)$part->class_constraint,
		"role_holder" => (string)$part->rh_name,
		"value" => (string)$part->value,
		"sub_concept" => $this->extractSubSlotsFromPlainConceptInfo($parts[$i])
	    ));
	}
	return $parts_list;	    
    }
    
    public function getConceptPlainInfoFromID($basic_concept_id) {
	// オントロジーの概念IDからその基本概念の情報を取得する(プレーンなXMLの変換そのまま)
	$concept_info =  $this->current_ontology_object->xpath("W_CONCEPTS/CONCEPT[@id='$basic_concept_id']")[0];
	return $concept_info;
    }

    public function getConceptPlainInfoFromLabel($basic_concept_label) {
	// オントロジーの概念IDからその基本概念の情報を取得する(プレーンなXMLの変換そのまま)
	$concept_info =  $this->current_ontology_object->xpath("W_CONCEPTS/CONCEPT[LABEL='$basic_concept_label']");
	return $concept_info;
    }


    public function getConceptInfoFromID($basic_concept_id) {
	// オントロジーの概念IDからその基本概念の情報を取得する（オリジナル整形）
	$plain_concept =  $this->current_ontology_object->xpath("W_CONCEPTS/CONCEPT[@id='$basic_concept_id']")[0];
	$concept_info = array(
	    "id" => (string)$plain_concept->attributes()->id,
	    "label" => (string)$plain_concept->LABEL
	);

	return $concept_info;
    }

    public function getConceptInfoFromLabel($basic_concept_label) {
	// オントロジーの概念IDからその基本概念の情報を取得する（オリジナル整形）
	$plain_concept =  $this->current_ontology_object->xpath("W_CONCEPTS/CONCEPT[LABEL='$basic_concept_label']")[0];
	$concept_info = array(
	    "id" => (string)$plain_concept->attributes()->id,
	    "label" => (string)$plain_concept->LABEL
	);
	return $concept_info;
    }

    

    /***** 部分概念の処理 *****/
    public function getPartOfConceptInfo($basic_concept_id) {
	// 部分概念の情報一覧を取得
	$part_concept_attributes = $this->getConceptPlainInfoFromID($basic_concept_id)->SLOTS;
	if(count($part_concept_attributes) === 0) {
	    return array();
	}
	$parts = $part_concept_attributes->SLOT;
	$parts_count = count($parts);
	$parts_list = array();

	for($i=0; $i<$parts_count; $i++) {
	    $part = $parts[$i]->attributes();
	    array_push($parts_list, array(
		"id" => (string)$part->id,
		"kind" => (string)$part->kind,
		"cardinality" => (string)$part->num,
		"role" => (string)$part->role,
		"class_constraint" => (string)$part->class_constraint,
		"role_holder" => (string)$part->rh_name,
		"value" => (string)$part->value,
	    ));
	}
	return $parts_list;
    }
    


    /***** IS-A階層の処理 *****/
    public function getISARelationshipList() {
	// IS-A関係にあるものをすべて取得
	$isa_relation =  $this->current_ontology_object->xpath("W_CONCEPTS/ISA");
	$shaped_isa_relation = array_map(function($isa) {
	    return array(
		"parent" => $this->getConceptIDFromLabel((string)$isa->attributes()->parent),
		"child" => $this->getConceptIDFromLabel((string)$isa->attributes()->child)
	    );
	}, $isa_relation);
	return $shaped_isa_relation;
    }


    public function getChildrenConcepts($basic_concept_id) {
	// 特定の基本概念の子概念情報一覧を取得(配列で返す)
	$isa_relation_list = $this->getISARelationshipList();
	$candidate_list = array_filter($isa_relation_list, function($relation) use ($basic_concept_id) {
	    return $basic_concept_id === $relation["parent"];
	});
	return array_values(array_map(function($relation) {
	    return (string)$relation["child"];
	}, $candidate_list));
    }

    public function getParentConcept($basic_concept_id) {
	// 特定の基本概念の親概念を取得
	$isa_relation_list = $this->getISARelationshipList();
	$candidate_list = array_filter($isa_relation_list, function($relation) use ($basic_concept_id) {
	    return $basic_concept_id === $relation["child"];
	});
	$return_val = count($candidate_list) !== 0 ? array(array_values($candidate_list)[0]["parent"]) : array();
	return $return_val;
    }

    private function isRootConcept($basic_concept_id) {
	// IDの概念がルート概念（基本はAny）かどうか
	$parent_concept_id = $this->getParentConcept($basic_concept_id);
	return count($parent_concept_id) === 0;
    }

    public function getAncestorConcepts($basic_concept_id) {
	// 先祖に当たる基本概念をすべて取得
	$isa_relation_list = $this->getISARelationshipList();
	$ancestors = array();
	$target_concept_id = $basic_concept_id;
	while(true) {
	    $candidate_list = array_filter($isa_relation_list, function($relation) use ($target_concept_id) {
		return $target_concept_id === $relation["child"];
	    });
	    $target_concept_id = count($candidate_list) !== 0 ? array_values($candidate_list)[0]["parent"] : null;
	    if($target_concept_id === null) {
		break;
	    }
	    array_push($ancestors, $target_concept_id);
	}
	return $ancestors;
    }

    public function getDescendantConcepts($basic_concept_id) {
	// 子孫に当たる基本概念をすべて取得
	$isa_relation_list = $this->getISARelationshipList();
	$descendants = array();

	$target_concepts = array($basic_concept_id);
	while(true) {
	    $children_list = array_map(function ($concept_id) use ($isa_relation_list) {
		$cand = array_filter($isa_relation_list, function($relation) use ($concept_id) {
		    return $concept_id === $relation["parent"];
		});
		return count($cand) !== 0 ? array_values(array_map(function ($d) {
		    return $d["child"];
		}, $cand)) : null;
	    }, $target_concepts);
	    
	    $children = $this->flatten($children_list);
	    if($children[0] === null) {
		break;
	    }
	    array_push($descendants, $children);
	    $target_concepts = $children;
	}
	return array_values(array_filter($this->flatten($descendants)));
    }



    /***** 問いの扱い *****/
    public function getAllInstance() {
	// ドメイン固有の問いオントロジーからインスタンスノードを抽出する
	$concept_list =  $this->current_ontology_object->xpath("W_CONCEPTS/CONCEPT[@instantiation='true']");
	$shaped_concept_list = array();
	foreach($concept_list as $concept) {
	    array_push($shaped_concept_list, array(
		"concept_id" => (string)$concept->attributes()->id,
		"label" => (string)$concept->LABEL
	    ));
	}
	return $shaped_concept_list;
    }

    
    public function getAllInstanceWhichHasSpecificPartInput($basic_concept_id) {
	// あるインスタンス概念に対して，特定の部分概念を持つインスタンスを抽出する
	$question_concept = $this->getParentConcept($basic_concept_id)[0];
	$require_question_id_list = $this->flatten(
	    array_map(function ($sub) {
		return $this->getChildrenConcepts($this->getConceptIDFromLabel($sub["class_constraint"]));
	    }, $this->getPartOfConceptInfo($question_concept))
	);
	return array_map(function ($rq){
	    return array(
		"concept_id" => (string)$this->getConceptPlainInfoFromID($rq)->attributes()->id,
		"label" => (string)$this->getConceptPlainInfoFromID($rq)->LABEL
	    );
	}, $require_question_id_list);
    }


    
}

