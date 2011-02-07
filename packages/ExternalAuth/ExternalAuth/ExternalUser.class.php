<?
/**
 * 
 * Class ErternalUser is common ExternalUser Class for all social sites.
 * Every social site gives own user's object and after 
 * that those objects have to be translated into this class object.  
 * 
 * @author Aram Gevorgyan
 */
class ExternalUser{
	
	const SEX_MALE = 1;
	const SEX_FEMALE = 2;
	
	/**
	 * User ID
	 * @var integer
	 */
	public $id = null;
	
	/**
	 * First name of the user
	 * @var string
	 */
	public $firstName = null;
	
	/**
	 * Last name of the user
	 * @var string
	 */
	public $lastName = null;
	
	/**
	 * Email of the user
	 * @var string
	 */
	public $email = null;
	
	/**
	 * Users's birthdate in format of DEFAULT_DATE_FORMAT
	 * @var string
	 */
	public $birthdate = null;
	
	/**
	 * Sex of the user
	 * Possible values: 1-Male, 2-Female
	 * @var integer
	 */
	public $sex = null;
	
	/**
	 * Other fields that are not 
	 * part of default fields of 
	 * ExternalUser Object
	 * @var array
	 */
	public $otherFields = array();
}

?>