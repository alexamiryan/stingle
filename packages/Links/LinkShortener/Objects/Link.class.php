<?php

class Link {
	public $id;
	public $linkId;
	public $url;
	public $date = null;
	public $expires = null;
	public $isClicked = LinkShortener::STATUS_CLICKED_NO;
	public $dateClicked = null;
	
}
