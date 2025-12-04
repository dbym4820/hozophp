<?php
namespace HozoPHP;

class OntologyManager {
    private $ontology_filename; // ローカル法造ファイルのファイル名
    private $ontology_directory; // ローカル法造ファイルを置くディレクトリ名
    private $ontology_path; // オントロジーのフルパス（ディレクトリ名＋ファイル名）
    private $current_ontology_object_plain; // オントロジーのXMLテキストをパースしたままのもの
    private $current_ontology_object; // 基本概念を抽出したもの
    private $current_ontology_isa_object; // ISAリンクを抽出したもの

    private $ontology_string; // 文字列指定したオントロジーXMLテキスト
    private $remote_ontology_url; // リモートのオントロジーのURL
    
    
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
            $this->treatOntologyFile();
        }
    }

    public function __construct1($ontology_directory) {
        // 引数が1つのインスタンス化時（オントロジーのディレクトリのみ指定）
        // setOntologyについてはユーザが自分でやらないといけない
        $this->setOntologyDirectory($ontology_directory);
    }

    public function __construct2($ontology_directory, $ontology_filename) {
        // 引数が2つのインスタンス化時（オントロジーのディレクトリとファイル名の指定）
        // setOntologyについてはユーザが自分でやらないといけない
        $this->setOntologyDirectory($ontology_directory);
        $this->setOntologyFile($ontology_filename);
        $this->treatOntologyFile();
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

    public function treatOntologyFile() {
        // ローカル法造ファイルをパースする
        $this->current_ontology_object_plain = simplexml_load_file($this->ontology_path);
        $this->current_ontology_object = $this->extractConcepts($this->current_ontology_object_plain->xpath("W_CONCEPTS/CONCEPT"));
        $this->current_ontology_isa_object = $this->extractISARelationConcept($this->current_ontology_object_plain->xpath("W_CONCEPTS/ISA")); 
    }

    public function treatOntologyString($target_xml_string) {
        // 文字列を対象にパースする
        $this->current_ontology_object_plain = simplexml_load_string($target_xml_string);
        $this->current_ontology_object = $this->extractConcepts($this->current_ontology_object_plain->xpath("W_CONCEPTS/CONCEPT"));
        $this->current_ontology_isa_object = $this->extractISARelationConcept($this->current_ontology_object_plain->xpath("W_CONCEPTS/ISA")); 
    }

    public function treatRemoteOntologyString($target_url) {
        // Webを通じたオントロジーXMLデータのパース
        $this->remote_ontology_url = $target_url; // URLの保存

        $this->ontology_string = file_get_contents($target_url);
        $this->treatOntologyString($this->ontology_string);
    }
    
    /*** ユーティリティ  ***/
    public function getOntologyFilePath() {
        return $this->ontology_path;
    }

    public function getOntologyString() {
        return $this->ontology_string;
    }

    public function getOntologyObject() {
        return $this->current_ontology_object;
    }

    public function showJson($data) {
        // JSONデータを表示するための関数
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode($data);
        return;
    }

    public function showXMLFile() {
        // コンバートしたファイルをXMLとして表示
        //$data = file_get_contents();
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/xml; charset=utf-8");
        //echo $data;
        readfile($this->getOntologyFilePath());
        return;
    }

    public function showXMLString() {
        // コンバートした文字列をXMLとして表示
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/xml; charset=utf-8");
        echo $this->getOntologyString();
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
    private function extractConcepts($plain_ontology_object) {
        $concept_count = count($plain_ontology_object);
        $concept_list = array();
        //return $$plain_ontology_object;
        for($i=0; $i<$concept_count; $i++) {
            $c = $plain_ontology_object[$i];
            array_push($concept_list, array(
                "id" => (string)$c->attributes()->id,
                "label" => (string)$c->LABEL,
                "instantiation" => (string)$c->attributes()->instantiation,
                "sub_concept" => $this->extractSubSlotsFromPlainConceptInfo($c)
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

    private function extractISARelationConcept($plain_isa_relation) {
        // ISAリンクを元オブジェクトから取り出す
        $shaped_isa_relation = array_map(function($isa) {
            return array(
                "parent" => $this->getConceptInfoFromLabel($isa->attributes()->parent)['id'],
                "child" => $this->getConceptInfoFromLabel((string)$isa->attributes()->child)['id']
            );
        }, $plain_isa_relation);
        return $shaped_isa_relation;

    }



    /** ここから消す **/
    
    public function getConceptPlainInfoFromID($basic_concept_id) {
        // オントロジーの概念IDからその基本概念の情報を取得する(プレーンなXMLの変換そのまま)
        $concept_info =  array_search($basic_concept_id, array_column($this->current_ontology_object, 'id'));
        return $concept_info;
    }

    public function getConceptPlainInfoFromLabel($basic_concept_label) {
        // オントロジーの概念IDからその基本概念の情報を取得する(プレーンなXMLの変換そのまま)
        $concept_info =  $this->current_ontology_object->xpath("W_CONCEPTS/CONCEPT[LABEL='$basic_concept_label']");
        return $concept_info;
    }

    /** ここまで消す **/

    /***** 基本概念の検索 *****/
    public function getAllConcepts() {
        // 全基本概念の一覧
        return $this->current_ontology_object;
    }
    
    public function getConceptInfoFromID($basic_concept_id) {
        // オントロジーの概念IDからその基本概念の情報を取得する
        $target_concept_key =  array_search($basic_concept_id, array_column($this->current_ontology_object, "id"));
        return $target_concept_key !== false ? $this->current_ontology_object[$target_concept_key] : [];
    }

    public function getConceptInfoFromLabel($basic_concept_label) {
        // オントロジーの概念IDからその基本概念の情報を取得する
        $target_concept_key =  array_search($basic_concept_label, array_column($this->current_ontology_object, "label"));
        return $target_concept_key !== false ? $this->current_ontology_object[$target_concept_key] : [];
    }

    

    /***** 部分概念の処理 *****/
    public function getPartOfConceptInfo($basic_concept_id) {
        // 部分概念の情報一覧を取得
        $target_concept = $this->getConceptInfoFromID($basic_concept_id);
        return $target_concept !== [] ? $target_concept['sub_concept'] : [];	
    }

    public function getAncestorSubConcepts($basic_concept_id) {
        // 祖先概念にある部分概念・属性概念をすべて取得
        $param_list = [];
        $ancestors = $this->getAncestorConcepts($basic_concept_id);
        foreach($ancestors as $concept) {
            $c = $this->getPartOfConceptInfo($concept);
            if($c !== []) {
                array_map(function($sub) use (&$param_list) {
                    array_push($param_list, $sub);
                }, $c);
            }
        }
        return array_merge($this->getPartOfConceptInfo($basic_concept_id), $param_list);
    }

    /***** IS-A階層の処理 *****/
    public function getISARelationshipList() {
        // IS-A関係にあるものをすべて取得
        return $this->current_ontology_isa_object;
    }


    public function getChildrenConcepts($basic_concept_id) {
        // 特定の基本概念の子概念情報一覧を取得(配列で返す)
        $candidate_list = array_filter($this->current_ontology_isa_object,
                                       function($relation) use ($basic_concept_id) {
                                           return $basic_concept_id === $relation["parent"];
                                       });
        return array_values(array_map(function($relation) {
            return (string)$relation["child"];
        }, $candidate_list));
    }

    public function getParentConcept($basic_concept_id) {
        // 特定の基本概念の親概念を取得
        $candidate_list = array_filter($this->current_ontology_isa_object,
                                       function($relation) use ($basic_concept_id) {
                                           return $basic_concept_id === $relation["child"];
                                       });
        $return_val = count($candidate_list) !== 0 ?
                    array(array_values($candidate_list)[0]["parent"]) : [];
        return $return_val;
    }

    private function isRootConcept($basic_concept_id) {
        // IDの概念がルート概念（基本はAny）かどうか
        $parent_concept_id = $this->getParentConcept($basic_concept_id);
        return count($parent_concept_id) === 0;
    }

    public function getAncestorConcepts($basic_concept_id) {
        // 先祖に当たる基本概念をすべて取得
        $self = $this;
        $rec = function ($target_concept, $arr) use ($self, &$rec) {
            $parent_concept = $self->getParentConcept($target_concept);
            return $parent_concept === [] ?
                                   $arr : $rec($parent_concept[0], array_merge($arr, $parent_concept));
		   
        };	
        return $rec($basic_concept_id, array());
    }

    public function getDescendantConcepts($basic_concept_id) {
        // 子孫に当たる基本概念をすべて取得
        $self = $this;
        $rec = function ($target_concept) use ($self, &$rec) {
            $child_concepts = $self->getChildrenConcepts($target_concept);
            return $child_concepts === [] ?
                                   [] :
                                   array_map(function ($concept_id) use ($self, &$rec) {
                                       return array_merge([$concept_id], $rec($concept_id));
                                   }, $child_concepts);
        };
        return $this->flatten($rec($basic_concept_id));
    }



    /***** インスタンスの扱い *****/
    public function getAllInstance() {
        // ドメイン固有の問いオントロジーからインスタンスノードを抽出する
        return array_values(array_filter($this->current_ontology_object, function($concept) {
            return $concept["instantiation"] === "true";
        }));
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

