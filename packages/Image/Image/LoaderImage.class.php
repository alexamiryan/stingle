<?php
class LoaderImage extends Loader{
	protected function includes(){
		require_once ('Image.class.php');
		require_once ('ImageException.class.php');
	}
}
