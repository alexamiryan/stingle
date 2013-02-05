<?php
class LoaderFileUploader extends Loader{
	protected function includes(){
		require_once ('Managers/FileUploader.class.php');
	}
}
