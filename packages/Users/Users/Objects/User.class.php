<?
class User{
	
	public $id = null;
	public $login;
	public $password;
	public $salt;
	public $creationDate;
	public $enabled = 1;
	public $email;
	public $emailConfirmed = 0;
	public $lastLoginDate;
	public $lastLoginIP;
	public $properties = null;
	public $permissions = null;
}
?>