<?php

/**
 * 
 * Interface is Generic tool for External Authorization , 
 * It can be used for authorizations to different Social Networks. 
 * For example plugin can authorize against Facebook, OpenID, Twitter, etc.
 * Social User's object is beeing translated to own common externalAuth Object. 
 * externalAuth object then beeing translated to Stingle's standard User object 
 * in order to be able to authenticate on the website. 
 * 
 * @author Aram Gevorgyan
 */
interface ExternalAuth
{
	/**
	 * Function get External User object
	 * 
	 * Function conects to selected site, do some authorization
	 * (for example facebook-connect is implemented with oAuth protocol)
	 * and if auth succeeds, return current social site's user object, after that function 
	 * creates External user object from that object)
	 *
	 * @return ExternalUser
	 */
	public function getExtUser();
	
	/**
	 * Function does mapping for current external user
	 *  
	 * @param integer $userId Stingle User id
	 * @param ExterenalUser $extUser
	 */
	public function addToExtMap($userId, ExternalUser $extUser);
	
	/**
	 * Funciton Gets User Id from Mapping table
	 *
	 * if there is no extUser object in table then function returns false
	 * 
	 * @param ExterenalUser $extUser
	 * @return integer|boolean
	 */
	public function getLocalUserIDFromMap(ExternalUser $extUser);
	
	/**
	 * Function gets External user ID from Mapping table. 
	 * 
	 * If there is no external user Id in table, function returns false
	 * @param interger $userId
	 * @return integer|boolean
	 */
	public function getExternalUserId($userId);
	
	/**
	 * Function Delete Local User Id from mapping table
	 *
	 * @param $userId is a local user Id
	 */
	public function deleteUserIDFromMap($userId);
	
	/**
	 * Function Gets current external plugins name
	 * 
	 * @return string
	 */
	public function getName();
}
