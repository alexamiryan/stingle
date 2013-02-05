<?php
class LoaderImageUploader extends Loader{
	protected function includes(){
		require_once ('ImageUploaderException.class.php');
		require_once ('ImageUploader.class.php');
	}
}
