<?
class DependencyPhotos extends Dependency
{
	public function __construct(){
		$this->addPackage("Db");
		$this->addPackage("ImageManipulator");
	}
}
?>