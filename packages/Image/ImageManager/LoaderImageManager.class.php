<?php
class LoaderImageManager extends Loader{
	protected function includes(){
		stingleInclude ('Exceptions/ImageManagerException.class.php');
		stingleInclude ('Managers/ImageManager.class.php');
	}
}
