<?
interface ExternalAuth
{
	/**
	 * Function get External User object
	 * 
	 * Function Coonect to selected site, do some authorization
	 * (for example facebook-connect is implemented with oauth protocol)
	 * and if auth is success, return current social site user object, after that function 
	 * create External user object from that object)
	 * 
	 * param is polimorf, Its  given param from current connected site
	 * (For facebook have to give GET['code'] parameter) 
	 */
	public function getExtUser(/*polimorf*/);
}
?>