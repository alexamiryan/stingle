<?php
class DependencyFileUploader extends Dependency
{
	public function __construct(){
		$this->addPlugin("File", "S3Transport");
	}
}
