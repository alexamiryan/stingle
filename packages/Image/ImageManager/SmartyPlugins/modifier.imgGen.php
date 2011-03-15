<?
/**
 * Generate image cache and return resulting 
 * file path to show in HTML
 *
 * @param string $fileName
 * @param string $sizeName
 * @return string
 */
function smarty_modifier_imgGen($fileName, $sizeName){
	if(empty($fileName) or empty($sizeName)){
		return;
	}
	
	$resultingFilePath = Reg::get('imageMgr')->generateImageCache($sizeName, $fileName);

	return SITE_PATH . $resultingFilePath;
}
?>