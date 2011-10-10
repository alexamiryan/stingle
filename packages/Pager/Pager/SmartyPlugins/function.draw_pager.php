<?
/**
 * Draw Pager
 *
 * @param $id
 * @param $visualPagesCount
 * @return string
 */
function smarty_function_draw_pager($params, Smarty_Internal_Template &$smarty){
	$id = null;
	$visualPagesCount = null;
	
	extract($params);
	
	if(empty($visualPagesCount)){
		$visualPagesCount = ConfigManager::getConfig("Pager","Pager")->AuxConfig->defaultVidualPagesCount;
	}
	if(empty($id)){
		$id = null;
	}
	
	$pager = Pager::getPager($id);
	
	if($pager instanceof  Pager){
		$link = RewriteURL::generateCleanBaseLink(
                                Reg::get('nav')->module,
                                Reg::get('nav')->page,
                                ConfigManager::getConfig("SiteNavigation")->AuxConfig->firstLevelDefaultValue) . 
                                	get_all_get_params(array($pager->getUrlParam())
                              );
		$urlParam = $pager->getUrlParam();
		$currentPageNumber = $pager->getCurrentPageNumber();
		$pagesCount = $pager->getTotalPagesCount();
		
		
		if($pagesCount > 1){
			$pageNumStart = $currentPageNumber - floor($visualPagesCount / 2);
			if($pageNumStart < 1){
				$pageNumStart = 1;
			}
			
			$pageNumEnd = $pageNumStart + $visualPagesCount - 1;
			if($pageNumEnd > $pagesCount){
				$pageNumEnd = $pagesCount;
				$pageNumStart = $pageNumEnd - $visualPagesCount + 1;
				if($pageNumStart < 1){
					$pageNumStart = 1;
				}
			}
			
			if($currentPageNumber > 1){
				$prevPageLink = Reg::get(ConfigManager::getConfig("RewriteURL")->Objects->rewriteURL)->glink($link . $pager->getUrlParam() . ':' . ($currentPageNumber - 1));
				$smarty->assign('pagerPreviousPageLink', $prevPageLink);
			}
			
			$pagerNumbersArray = array();
			for($pgNum = $pageNumStart; $pgNum <= $pageNumEnd; $pgNum++){
				$isCurrent = false;
				if($pgNum == $currentPageNumber){
					$isCurrent = true;
				}
				$pageLink = Reg::get(ConfigManager::getConfig("RewriteURL")->Objects->rewriteURL)->glink($link . $pager->getUrlParam() . ':' . $pgNum);
				
				array_push($pagerNumbersArray, array("pageNum" => $pgNum, "pageLink" => $pageLink, "isCurrent" => $isCurrent));
			}
			
			if($currentPageNumber < $pagesCount){
				$nextPageLink = Reg::get(ConfigManager::getConfig("RewriteURL")->Objects->rewriteURL)->glink($link . $pager->getUrlParam() . ':' . ($currentPageNumber + 1));
				$smarty->assign('pagerNextPageLink', $nextPageLink);
			}
			
			$smarty->assign("pagerPageNumStart", $pageNumStart);
			$smarty->assign("pagerPageNumEnd", $pageNumEnd);
			$smarty->assign("pagerCurrentPageNumber", $currentPageNumber);
			$smarty->assign("pagerTotalPagesCount", $pagesCount);
			$smarty->assign("pagerNumbersArray", $pagerNumbersArray);
		}
		
		$pagerSnippetFileName = ConfigManager::getConfig("Pager","Pager")->AuxConfig->pagerSnippetFileName;
		
		return $smarty->fetch(Reg::get('smarty')->getFilePathFromTemplate(Reg::get('smarty')->snippetsPath . $pagerSnippetFileName));
	}
}
?>