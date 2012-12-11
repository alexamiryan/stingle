<?
class User{
	
	public $id = null;
	public $login;
	public $password;
	public $salt;
	public $creationDate;
	public $creationTime;
	public $enabled = 1;
	public $email;
	public $emailConfirmed = 0;
	public $lastLoginDate;
	public $lastLoginIP;
	/**
	 * @var UserProperties
	 */
	public $props = null;
	/**
	 * @var UserPermissions
	 */
	public $perms = null;
}
?>