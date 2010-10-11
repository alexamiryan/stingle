<?
function smarty_modifier_smartcut ($string, $length, $trailing_chars="..."){
	return smart_cut($string,$length,$trailing_chars);
}
?>