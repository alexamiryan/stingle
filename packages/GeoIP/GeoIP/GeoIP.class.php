<?
class GeoIP extends DbAccessor
{
	const TBL_BLOCKS = 'GeoIPBlocks';
	const TBL_LOCATIONS = 'GeoIPLocation';
	
	public function __construct($dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
	}
	
	public function getLocation($ip = null){
		if($ip === null){
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		 if(!preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/",$ip)){
		 	throw new InvalidArgumentException('IP is in wrong format');
		 }
		$this->query->exec("SELECT `country`, `region`, `city` FROM ".self::TBL_BLOCKS."
							LEFT JOIN ".self::TBL_LOCATIONS." USING (`locId`)
							WHERE index_geo = INET_ATON('".$ip."')-(INET_ATON('".$ip."')%65536) AND INET_ATON('".$ip."') BETWEEN `startIpNum` AND `endIpNum`");
		if($this->query->countRecords()){
			return $this->query->fetchRecord();
		}
		return array();		
	}
	
	public function getCountryCode(){
		$ip = $_SERVER['REMOTE_ADDR'];

		$this->query->exec("SELECT `country` FROM ".self::TBL_BLOCKS."
							LEFT JOIN ".self::TBL_LOCATIONS." USING (`locId`)
							WHERE index_geo = INET_ATON('".$ip."')-(INET_ATON('".$ip."')%65536) AND INET_ATON('".$ip."') BETWEEN `startIpNum` AND `endIpNum`");
		if($this->query->countRecords()){
			return $this->query->fetchField('country');
		}		
		return null;
	}
}
?>
