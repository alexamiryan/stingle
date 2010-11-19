<?
$CONFIG['Users']['login_cokie'] =  $CONFIG['site']['site_id'] . "-login";

$reg_max_count_in_minute = 5;

$CONFIG['users']['onlinePingInterval'] =  7000;
$CONFIG['users']['missedPingsCount'] =  20;
$CONFIG['users']['username_pattern'] =  "/^([^\,\/\\\"\[\]\{\}\~\@\#\$\%\^\&\*\(\)\:\;\?<>\s\+\=]{3,20})$/isu";

/**
 * Yubikey Configuration
 */
$CONFIG['Users']['Yubikey']['yubico_id'] =  4264;
$CONFIG['Users']['Yubikey']['yubico_key'] =  'ETbmajX8ozu1h/cqvRvBD28G6A4=';
?>