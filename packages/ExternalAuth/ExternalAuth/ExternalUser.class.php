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
	
	public $id = null;
	public $firstName = null;
	public $lastName = null;
	public $email = null;
	public $birthdate = null;
	public $sex = null;
	public $timezone = null;
	public $hometown = null;
	public $location = null;
	public $updatedTime = null;
}

?>