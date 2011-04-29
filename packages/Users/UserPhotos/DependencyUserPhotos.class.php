<?
class DependencyUserPhotos extends Dependency
{
	public function __construct(){
		$this->addPlugin("Filter", "Filter");
		$this->addPlugin("Image", "ImageUploader");
		$this->addPlugin("Image", "Photo");
	}
}
?>