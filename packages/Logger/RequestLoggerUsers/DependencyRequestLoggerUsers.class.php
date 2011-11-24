<?
class DependencyRequestLoggerUsers extends Dependency
{
	public function __construct(){
		$this->addPlugin("Logger", "DBLogger");
		$this->addPlugin("Users", "Users");
	}
}
?>