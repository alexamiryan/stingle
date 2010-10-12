<?
class Constant{
	/**
	 * Available types of constants in database
	 */

	/**
	 * Name of the table which contains constants and types
	 *
	 * @access protected
	 * @var string
	 */
	const TBL_CONSTANTS= 'lm_constants';

	/**
	 * Name of the table which contains values of constant
	 *
	 * @access protected
	 * @var string
	 */
	const TBL_VALUES = 'lm_values';

	/**
	 * Identifier for group of constants which are being used in the site only
	 *
	 * @const integer
	 */
	const SITE_TYPE = 1;

	/**
	 * Identifier for group of constants which  are being used in administartion panel and site
	 *
	 * @const integer
	 */
	const COMMON_TYPE = 2;

	/**
	 * Identifier for group of constants which are being used in the admin panel only
	 *
	 * @const integer
	 */
	const ADMIN_TYPE = 3;

	/**
	 * An array wich contains all types of constants
	 *
	 * @staticvar $available_types
	 */
	protected static $available_types = array(self::SITE_TYPE, self::ADMIN_TYPE, self::COMMON_TYPE);

	/**
	 * @return array
	 */
	static public function getAvailableTypes(){
		return self::$available_types;
		/*return array(
		"CONSTS_TYPE_SITE"		=> self::SITE_TYPE,
		"CONSTS_TYPE_COMMON" 	=> self::COMMON_TYPE,
		"CONSTS_TYPE_ADMIN" 	=> self::ADMIN_TYPE
		);*/
	}
}
?>