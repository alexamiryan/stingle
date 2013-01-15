<?
/**
 * Draw Pager
 *
 * @param $id
 * @param $visualPagesCount
 * @param $exclude
 * @param $additionalParams
 * @param $linkClass
 * @return string
 */
function smarty_function_draw_pager($params, Smarty_Internal_Template &$smarty){
	$id = null;
	$visualPagesCount = null;
	$excludedGetsArray = array();
	
	extract($params);
	
	if(empty($visualPagesCount)){
		$visualPagesCount = ConfigManager::getConfig("Pager","Pager")->AuxConfig->defaultVidualPagesCount;
	}
	if(empty($id)){
		$id = null;
	}
	
	if(isset($exclude) and !empty($exclude)){
		$excludedGetsArray = explode(",", str_replace(" ", "", $exclude));
	}
	
	$pager = Pager::getPager($id);
	
	if($pager instanceof  Pager){
		if(isset($baseLink) and !empty($baseLink)){
			// Remove heading slash if present
			$link = ltrim($baseLink, "/");
			$link = Reg::get(ConfigManager::getConfig("RewriteURL")->Objects->rewriteURL)->glink($link);
		}
		else{
			$link = getCurrentUrl(array_merge(array($pager->getUrlParam()), $excludedGetsArray));
		}
		
		if(isset($additionalParams) and !empty($additionalParams)){
			RewriteURL::ensureLastSlash($additionalParams);
			$urlParam = $additionalParams . $pager->getUrlParam();
		}
		else{
			$urlParam = $pager->getUrlParam();
		}
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
			
			if($pageNumStart > 1){
				$pagerFirstPageLink = $link . $urlParam . ':1';
				$smarty->assign('pagerFirstPageLink', $pagerFirstPageLink);
			}
			
			if($pageNumEnd < $pagesCount){
				$pagerLastPageLink = $link . $urlParam . ':' .$pagesCount;
				$smarty->assign('pagerLastPageLink', $pagerLastPageLink);
			}
			
			if($currentPageNumber > 1){
				$prevPageLink = $link . $urlParam . ':' . ($currentPageNumber - 1);
				$smarty->assign('pagerPreviousPageLink', $prevPageLink);
			}
			
			$pagerNumbersArray = array();
			for($pgNum = $pageNumStart; $pgNum <= $pageNumEnd; $pgNum++){
				$isCurrent = false;
				if($pgNum == $currentPageNumber){
					$isCurrent = true;
				}
				$pageLink = $link . $urlParam . ':' . $pgNum;
				
				array_push($pagerNumbersArray, array("pageNum" => $pgNum, "pageLink" => $pageLink, "isCurrent" => $isCurrent));
			}
			
			if($currentPageNumber < $pagesCount){
				$nextPageLink = $link . $urlParam . ':' . ($currentPageNumber + 1);
				$smarty->assign('pagerNextPageLink', $nextPageLink);
			}
			
			if(isset($linkClass) and !empty($linkClass)){
				$smarty->assign("linkClass", $linkClass);
			}
			
			$smarty->assign("pagerPageNumStart", $pageNumStart);
			$smarty->assign("pagerPageNumEnd", $pageNumEnd);
			$smarty->assign("pagerCurrentPageNumber", $currentPageNumber);
			$smarty->assign("pagerTotalPagesCount", $pagesCount);
			$smarty->assign("pagerNumbersArray", $pagerNumbersArray);
		}
		
		if(isset($tplChunkFile)){
			$pagerChunkFileName = $tplChunkFile;
		}
		else{
			$pagerChunkFileName = ConfigManager::getConfig("Pager","Pager")->AuxConfig->pagerChunkFileName;
		}
		
		return $smarty->fetch($smarty->getChunkPath($pagerChunkFileName));
	}
}
?>