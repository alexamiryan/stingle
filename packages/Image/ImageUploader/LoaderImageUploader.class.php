<?php
class LoaderImageUploader extends Loader{
	protected function includes(){
		require_once ('Exceptions/ImageUploaderException.class.php');
		require_once ('Managers/ImageUploader.class.php');
	}
}
