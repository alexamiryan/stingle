<?
/**
 * Return src code for img tag for png images
 * &lt;img &lt;?=png_img('img/preved.png')?&gt; alt=""&gt;
 * &lt;img src="img/no.gif" style="filter: progid:DXImageTransform.Microsoft.AlphaImageLoader (src='img/preved.png');" alt=""&gt;
 *
 * @param string $src
 * @return string
 */
function smarty_modifier_png($src)
{
	if (preg_match ("/msie/i", $_SERVER{'HTTP_USER_AGENT'})){
		return 'src="img/no.gif" style="filter: progid:DXImageTransform.Microsoft.AlphaImageLoader (src=\''.$src.'\');"';
	}
	else{
		return 'src="'. $src .'"';
	}
}
?>