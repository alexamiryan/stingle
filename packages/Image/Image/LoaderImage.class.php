<?php
class LoaderImage extends Loader{
	protected function includes(){
		stingleInclude ('Objects/Image.class.php');
		stingleInclude ('Exceptions/ImageException.class.php');
	}
}
