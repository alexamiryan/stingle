<?php
class Text
{
	const TBL_TEXTS = "texts";
	const TBL_TEXTS_VALUES = "texts_values";
	const TBL_TEXTS_ALIASES = "texts_aliases";
	
	public $id;			//int
	public $name;		//string
	public $language; 	//Language
	public $host;		//Host
	public $value;		//text
	public $description;//string
	public $display;	//Bool
	protected $default; //Bool


	public function __construct($value_id=null){
		if($value_id !== null){
			if(!is_numeric($value_id)){
				throw new InvalidIntegerArgumentException("value_id argument should be an integer.");
			}

			$this->id = $value_id;

			$sql = MySqlDbManager::getQueryObject();
			$sql->exec("SELECT *, tv.id value_id FROM `".Tbl::get('TBL_TEXTS_VALUES') ."` tv
						LEFT JOIN `".Tbl::get('TBL_TEXTS') ."` t ON tv.text_id = t.id
						WHERE tv.id = '{$value_id}'");
			$data = $sql->fetchRecord();

			static::setData($data, $this);
		}
	}

	/**
	 * set Object members from Database data
	 *
	 * @param array Db query result $data
	 * @return void
	 */
	public static function setData($data, Text $object){
		$object->id = $data["value_id"];
		$object->name = $data["name"];
		$object->value = $data["value"];
		$object->description = $data["description"];

		$object->default = ($data["default"] == 1);
		$object->display = ($data["display"] == 1);
		//$object->default = ($data["default"] == 1 ? true : false);

		$host_language_pair = HostLanguageManager::getHostLanguagePair($data["host_language"]);

		$object->language = $host_language_pair ["language"];
		$object->host = $host_language_pair["host"];

		return ;
	}

	public static function getTextId($textName){
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec("SELECT `id` FROM `".Tbl::get('TBL_TEXTS')  ."` WHERE `name` = '{$textName}'");
		return $sql->fetchField("id");
	}

	public static function getTextName($textId){
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec("SELECT `name` FROM `".Tbl::get('TBL_TEXTS')  ."` WHERE `id` = '{$textId}'");
		return $sql->fetchField("name");
	}

	public static function getTextDescription($textId){
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec("SELECT `description` FROM `".Tbl::get('TBL_TEXTS')  ."` WHERE `id` = '{$textId}'");
		return $sql->fetchField("description");
	}

	public static function getTexts(){
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec("SELECT * FROM `".Tbl::get('TBL_TEXTS')  ."`");
		return $sql->fetchRecords();
	}
	
	public static function getAliases($value_id){
		$hl_ids = array();
		if(!is_numeric($value_id)){
			throw new InvalidIntegerArgumentException("value_id argument should be an integer.");
		}
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec("SELECT * FROM `".Tbl::get('TBL_TEXTS_ALIASES') ."` WHERE `value_id`=".$value_id);
		while($hl_id = $sql->fetchField("host_language")){
			$hl_ids[]=$hl_id;
		}
		return $hl_ids;
	}
	
}
?>