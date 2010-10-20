<?
class DependencyImageManager extends Dependency
{
	public function __construct(){
		$this->addPlugin("Image", "ImageManipulator");
	}
}
?>