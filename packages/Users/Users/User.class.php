<?php
/**
 * ###################################################
 * #                   IMPORTANT!!!                  #
 * ###################################################
 * # Requires sequrity_quotes function to be called  #
 * # before new object definition from this class    #
 * ###################################################
 */

class User
{
	private $id=0;
	private $login = "";
	private $creation_date = "";
	private $status = 0;
	private $primary_group = "";
	private $groups = array();
	private $permissions = array();

	public function getLogin(){
		return $this->login;
	}

	public function getId(){
		return $this->id;
	}

	public function getCreationDate(){
		return $this->creation_date;
	}
	
	public function isEnabled(){
		if($this->status){
			return  true;
		}
		else{
			return false;
		}
	}
	
	public function setId($id){
		if(($id = intval($id))){
			$this->id = $id;
		}
		else{
			return false;
		}
	}

	public function setLogin($login){
		if(empty($login)){
			return false;
		}
		$this->login = $login;
		return true;
	}
	
	public function setCreationDate($date){
		if(empty($date)){
			return false;
		}
		$this->creation_date = $date;
		return true;
	}
	
	public function setStatus($status){
		if($status){
			$this->status = 1;
		}
		else{
			$this->status = 0;
		}
	}
	
	public function enable(){
		$this->status = 1;
	}
	
	public function disable(){
		$this->status = 0;
	}
	
	public function getPermissions(){
		return $this->permissions;
	}

	public function getGroups(){
		return $this->groups;
	}
	
	public function getPrimaryGroup(){
		return $this->primary_group;
	}

	public function setPermissions($permissions_list){
		unset($this->permissions);
		$this->permissions = $permissions_list;
	}

	public function setGroups($groups_list){
		unset($this->groups);
		if(!is_array($groups_list)){
			$groups_list=array($groups_list);
		}
		$this->groups = $groups_list;
	}
	
	public function setPrimaryGroup($group_name){
		$this->primary_group=$group_name;
	}

	public function clearPermissions(){
		unset($this->permissions);
		$this->permissions = array();
	}

	public function clearGroups(){
		unset($this->groups);
		$this->groups = array();
	}

	public function addPermissions($permissions_list){
		if(is_null($this->permissions)){
			$this->permissions=$permissions_list;
		}
		elseif(is_array($permissions_list)){
			$this->permissions=array_merge($this->permissions, $permissions_list);
		}
		elseif(is_string($permissions_list)){
			$this->permissions[] = $permissions_list;
		}
		else{
			return false;
		}

		return true;
	}

	public function addGroups($groups_list){
		if(is_array($groups_list)){
			$this->groups = array_merge($this->groups, $groups_list);
		}
		elseif(is_string($groups_list)){
			$this->groups[] = $groups_list;
		}
		else{
			return false;
		}

		return true;
	}

	public function removePermissions($permissions_list){
		if(is_array($permissions_list))	{
			$this->permissions = array_diff($this->permissions, $permissions_list);
		}
		elseif(is_string($permissions_list)){
			$this->permissions = array_diff($this->permissions, array($permissions_list));
		}
		else{
			return false;
		}

		return true;
	}

	public function removeGroups($groups_list){
		if(is_array($groups_list)){
			$this->groups = array_diff($this->groups, $groups_list);
		}
		elseif(is_string($groups_list)){
			$this->groups = array_diff($this->groups, array($groups_list));
		}
		else{
			return false;
		}

		return true;
	}

	public function hasPermission($permission){
		if(in_array($permission, $this->permissions)){
			return true;
		}
		else{
			return false;
		}
	}

	public function hasGroup($group){
		if(in_array($group, $this->groups)){
			return true;
		}
		else{
			return false;
		}
	}
	
	public function isPrimaryGroup($group){
		if($group == $this->primary_group){
			return true;
		}
		else{
			return false;
		}
	}

	public function isAuthorized(){
		if(empty($this->login)){
			return false;
		}
		else{
			return true;
		}
	}

}
?>