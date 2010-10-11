<?
function smarty_modifier_zodiak($date){
	$sign='';
	list($year,$month,$day) = explode("-",$date);
	if (($month == 1 and $day <=19) or ($month == 12 and $day >=21)){
		$sign = SIGN_CAPRICORN;
	}
	elseif (($month == 1 and $day >=20) or ($month == 2 and $day <=18)){
		$sign = SIGN_AQUARIUS;
	}
	elseif (($month == 2 and $day >=19) or ($month == 3 and $day <=20)){
		$sign = SIGN_PISCES;
	}
	elseif (($month == 3 and $day >=21) or ($month == 4 and $day <=20)){
		$sign = SIGN_ARIES;
	}
	elseif (($month == 4 and $day >=21) or ($month == 5 and $day <=20)){
		$sign = SIGN_TAURUS;
	}
	elseif (($month == 5 and $day >=21) or ($month == 6 and $day <=20)){
		$sign = SIGN_GEMINI;
	}
	elseif (($month == 6 and $day >=21) or ($month == 7 and $day <=21)){
		$sign = SIGN_CANCER;
	}
	elseif (($month == 7 and $day >=22) or ($month == 8 and $day <=21)){
		$sign = SIGN_LEO;
	}
	elseif (($month == 8 and $day >=22) or ($month == 9 and $day <=21)){
		$sign = SIGN_VIRGO;
	}
	elseif (($month == 9 and $day >=22) or ($month == 10 and $day <=21)){
		$sign = SIGN_LIBRA;
	}
	elseif (($month == 10 and $day >=22) or ($month == 11 and $day <=21)){
		$sign = SIGN_SCORPIO;
	}
	elseif (($month == 11 and $day >=22) or ($month == 12 and $day <=20)){
		$sign = SIGN_SAGITTARIUS;
	}
	if(empty($sign)){
		
	}
	return $sign;
}
?>