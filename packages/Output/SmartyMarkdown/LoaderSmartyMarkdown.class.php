<?php
class LoaderSmartyMarkdown extends Loader{
	protected function includes(){
        stingleInclude('MarkdownLib/Parsedown.php');
	}
	
}
