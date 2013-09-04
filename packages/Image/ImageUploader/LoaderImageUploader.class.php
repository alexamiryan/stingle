<?php
class LoaderImageUploader extends Loader{
	protected function includes(){
		stingleInclude ('Exceptions/ImageUploaderException.class.php');
		stingleInclude ('Managers/ImageUploader.class.php');
	}
}
