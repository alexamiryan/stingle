<?
/**
 * Generate image cache and return resulting 
 * file path to show in HTML
 *
 * @param string $fileName
 * @param string $sizeName
 * @return string
 */
function smarty_modifier_imgGeno(Photo $photo, $sizeName){
	
	if(empty($sizeName)){
		return;
	}
	exit;

	$resultingFilePath = Reg::get('photoCache')->generateImageCache($sizeName, $photo);

	return SITE_PATH . $resultingFilePath;
}
?>