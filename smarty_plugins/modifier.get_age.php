<?
function smarty_modifier_get_age ($birthday){
	list($year,$month,$day) = explode("-",$birthday);
	$year_diff  = date("Y") - $year;
	$month_diff = date("m") - $month;
	$day_diff   = date("d") - $day;
	if ($day_diff < 0 || $month_diff < 0){
		$year_diff--;
	}
	return $year_diff;
}
?>