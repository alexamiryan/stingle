<?php
class LoaderLinkShortener extends Loader{
	
	protected function includes(){
		stingleInclude ('Filters/LinkShortenerFilter.class.php');
		stingleInclude ('Managers/LinkShortener.class.php');
		stingleInclude ('Objects/Link.class.php');
		stingleInclude ('Helpers/helpers.inc.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('LinkShortener');
	}
	
	protected function loadLinkShortener(){
		$this->register(new LinkShortener($this->config->AuxConfig));
	}
	
	public function hookParseLinks(){
		if(!empty($_SERVER['REQUEST_URI'])){
			$uri = rawurldecode($_SERVER['REQUEST_URI']);
			RewriteURL::ensureLastSlash($uri);

			$matches = array();
			if(preg_match("/".$this->config->AuxConfig->shortenerUrlRegex."/", $uri, $matches)){
				if(!empty($matches[1])){
					$_SERVER['REQUEST_URI'] = glink(parse($this->config->AuxConfig->shortenerHandlerPath, array('linkId' => $matches[1])));
				}
			}
		}
	}
}
