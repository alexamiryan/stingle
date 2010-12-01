<?
class DependencyChatUsers extends Dependency
{
	public function __construct(){
		$this->addPlugin("Chat", "Chat");
		$this->addPlugin("Users", "Users");
	}
}
?>