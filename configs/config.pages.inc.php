<?
/*
 postfix
 prefixes(
	 module(
			page(
				hostExt(
					_title,
					_keywords,
					_description,
					[_show_postfix = true]
					)
				)
			)
	 )
 )
 */
$CONFIG['pageConfig'] = array (
	'postfix' => TITLE_SITE_NAME,
	'prefixes' => array(
		'*' => array (
			'*' => array (
				'*' => array (
					'_title' => LOSUNG,
					'_keywords' => KEYWORDS,
					'_description' => DESCRIPTION,
					'_show_postfix' => true
				)
			)
		)
	)
);
?>