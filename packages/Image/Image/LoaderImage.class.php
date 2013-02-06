<?php
class LoaderImage extends Loader{
	protected function includes(){
		require_once ('Objects/Image.class.php');
		require_once ('Exceptions/ImageException.class.php');
	}
}
