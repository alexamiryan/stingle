<?
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


	public function __construct($value_id=null, $cacheMinutes = null){
		if($value_id !== null){
			if(!is_numeric($value_id)){
				throw new InvalidIntegerArgumentException("value_id argument should be an integer.");
			}

			$this->id = $value_id;

			$sql = MySqlDbManager::getQueryObject();
			$sql->exec("SELECT *, tv.id value_id FROM `".Tbl::get('TBL_TEXTS_VALUES') ."` tv
						LEFT JOIN `".Tbl::get('TBL_TEXTS') ."` t ON tv.text_id = t.id
						WHERE tv.id = '{$value_id}'", $cacheMinutes);
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
	public static function setData($data, Text $object, $cacheMinutes = null){
		$object->id = $data["value_id"];
		$object->name = $data["name"];
		$object->value = $data["value"];
		$object->description = $data["description"];

		$object->default = ($data["default"] == 1);
		$object->display = ($data["display"] == 1);
		//$object->default = ($data["default"] == 1 ? true : false);

		$host_language_pair = HostLanguageManager::getHostLanguagePair($data["host_language"], $cacheMinutes);

		$object->language = $host_language_pair ["language"];
		$object->host = $host_language_pair["host"];

		return ;
	}

	public static function getTextId($textName, $cacheMinutes = null){
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec("SELECT `id` FROM `".Tbl::get('TBL_TEXTS')  ."` WHERE `name` = '{$textName}'", $cacheMinutes);
		return $sql->fetchField("id");
	}

	public static function getTextName($textId, $cacheMinutes = null){
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec("SELECT `name` FROM `".Tbl::get('TBL_TEXTS')  ."` WHERE `id` = '{$textId}'", $cacheMinutes);
		return $sql->fetchField("name");
	}

	public static function getTextDescription($textId, $cacheMinutes = null){
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec("SELECT `description` FROM `".Tbl::get('TBL_TEXTS')  ."` WHERE `id` = '{$textId}'", $cacheMinutes);
		return $sql->fetchField("description");
	}

	public static function getTexts($cacheMinutes = null){
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec("SELECT * FROM `".Tbl::get('TBL_TEXTS')  ."`", $cacheMinutes);
		return $sql->fetchRecords();
	}
	
	public static function getAliases($value_id, $cacheMinutes = null){
		$hl_ids = array();
		if(!is_numeric($value_id)){
			throw new InvalidIntegerArgumentException("value_id argument should be an integer.");
		}
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec("SELECT * FROM `".Tbl::get('TBL_TEXTS_ALIASES') ."` WHERE `value_id`=".$value_id, $cacheMinutes);
		while(($hl_id = $sql->fetchField("host_language")) != null){
			$hl_ids[]=$hl_id;
		}
		return $hl_ids;
	}
	
}
?>