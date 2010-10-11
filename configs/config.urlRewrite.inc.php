<?
/**
 * Handler script for whole site
 * @var string
 */
$CONFIG['RewriteURL']['RewriteURL']['handler_script'] = "index.php";
/**
 * mod_rewrite configurations
 * ON/OFF
 */
$CONFIG['RewriteURL']['RewriteURL']['enable_url_rewrite'] = "ON";

/**
 * Style of links used in templates. 
 * "nice" or "default".
 */
$CONFIG['RewriteURL']['RewriteURL']['source_link_style'] = 'nice';

/**
 * Style of links to be outputed. "nice" or "default".
 * If use nice ENABLE_URL_REWRITE is ON.
 */
$CONFIG['RewriteURL']['RewriteURL']['output_link_style'] = 'nice';

/**
 * Set the name of the project folder www.mysite.com/[SITE_PATH]
 * This constant particularly used by RewriteUrl class.
 * NOTE: Change "RewriteBase" in .httaccess to [SITE_PATH]
 */
$CONFIG['RewriteURL']['RewriteURL']['site_path'] = "/";
?>