<?php
/**
 * Mail Sender class
 * @author Aram Gevorgyan
 *
 */
class MailSender
{
	/**
	 * @access private 
	 * @var array
	 */
	private $_headers = array();
	
	/**
	 * 
	 * @access protected
	 * @var Config
	 */
	protected $config;
	
	/**
	 * Constructor
	 * @param Config $config
	 */
	public function __construct(Config $config){
		$this->config = $config;
	}
	
	/**
	 * Send mail by current Mail object,
	 * also if given DKIM config, then send mail by signing with DKIM and DomainKeys signatures
	 * @param Mail $mail
	 * @param DKIMConfig $dkimConfig
	 * @throws MailException
	 * @throws DKIMConfigException
	 * @access public
	 * @return bool true if the mail was successfully accepted for delivery, false otherwise.
	 */
	public function send(Mail $mail, DKIMConfig $dkimConfig = null){
		if(empty($mail)){
			throw new MailException("Mail object is empty");
		}
		if($dkimConfig !== null){
			if(empty($dkimConfig->publicKey)){
				throw new DKIMConfigException("DKIM public key is empty!");		
			}
			if(empty($dkimConfig->privateKey)){
				throw new DKIMConfigException("DKIM private key is empty!");
			}
			if(empty($dkimConfig->selector)){
				throw new DKIMConfigException("DKIM selector is empty");
			}
			if(empty($dkimConfig->domain)){
				throw new DKIMConfigException("DKIM domain is empty");
			}
		}
		
		$to = $this->getToAddressesString($mail);
		$from = $this->addressFormat(array($mail->from, $mail->fromName));
		$dkimHeader = '';
		$domainKeysHeader = '';
		if($dkimConfig !== null){
			$headers =  $this->createHeaders($mail);
			if(isset($this->config->dkimEnabled) and $this->config->dkimEnabled !== false){ 
				$dkimHeader 		= $this->addDKIM($from, $to, $mail->subject, $mail->body, $dkimConfig);
			}
			if(isset($this->config->domainKeysEnabled) and $this->config->domainKeysEnabled !== false){
				$domainKeysHeader 	= $this->addDomainKey($mail, $dkimConfig);
			}
			$headers =  $dkimHeader . $domainKeysHeader . $headers;
		}
		else{
			$headers = $this->createHeaders($mail);
		}
		return @mail($to, $mail->subject, $mail->body, $headers, "-f$mail->from") ;
	}
	
	/**
	 * Create DNS TEXT record. for creating dns record you must have public and private keys
	 * Generate public  key: openssl genrsa -out key.priv 384
	 * Generate private key: openssl rsa -in key.priv -out key.pub -pubout -outform PEM
	 * @param DKIMConfig $dkimConfig
	 * @throws DKIMConfigException
	 * @return String
	 */
	public function buildDNSTXTRR(DKIMConfig $dkimConfig) {
		if(empty($dkimConfig)){
			throw new DKIMConfigException("DKIM config object is empty!");
		}
		if(empty($dkimConfig->publicKey)){
			throw new DKIMConfigException("DKIM public key is empty!");		
		}
		if(empty($dkimConfig->privateKey)){
			throw new DKIMConfigException("DKIM private key is empty!");
		}
		if(empty($dkimConfig->selector)){
			throw new DKIMConfigException("DKIM selector is empty");
		}
	
		$pub_lines=explode("\n",$dkimConfig->publicKey) ;
		$txt_record="{$dkimConfig->selector}._domainkey\tIN\tTXT\t\"v=DKIM1\\; k=rsa\\; g=*\\; s=email\; h=sha1\\; t=s\\; p=" ;
		foreach($pub_lines as $pub_line){
			if (strpos($pub_line,'-----') !== 0){
				$txt_record.=$pub_line ;
			}
		}
		return $txt_record . "\;\""; 
	}
	
	/**
	 * Sign mail by DKIM (DomainKeys Identified Mail) and return by header
	 * @param String $from_header
	 * @param String $to_header
	 * @param String $subject
	 * @param String $body
	 * @param DKIMConfig $dkimConfig
	 * @access public
	 * @return String DKIM signature header
	 */	
	public function addDKIM($from_header, $to_header, $subject, $body, DKIMConfig $dkimConfig) {
		$DKIM_s = $dkimConfig->selector;
		$DKIM_d = $dkimConfig->domain;
		$DKIM_i = $dkimConfig->identity;
		
		$DKIM_a='rsa-sha1'; // Signature & hash algorithms
		$DKIM_c='relaxed/simple'; // Canonicalization of header/body
		$DKIM_q='dns/txt'; // Query method
		$DKIM_t=time() ; // Signature Timestamp = number of seconds since 00:00:00 on January 1, 1970 in the UTC time zone
		$from_header="From: $from_header" ;
		$to_header="To: $to_header" ;
		$subject_header="Subject: $subject" ;
		
		$from = str_replace('|','=7C', $this->DKIMQuotedPrintable($from_header)) ;
		$to = str_replace('|','=7C', $this->DKIMQuotedPrintable($to_header)) ;
		$subject = str_replace('|','=7C', $this->DKIMQuotedPrintable($subject_header)) ; // Copied header fields (dkim-quoted-printable
		$body = $this->simpleBodyCanonicalization($body);
		$DKIM_l=strlen($body) ; // Length of body (in case MTA adds something afterwards)
		$DKIM_bh=base64_encode(pack("H*", sha1($body))) ; // Base64 of packed binary SHA-1 hash of body
		$i_part=($DKIM_i == '')? '' : " i=$DKIM_i;" ;
		$b='' ; // Base64 encoded signature
		$dkim="DKIM-Signature: v=1; a=$DKIM_a; q=$DKIM_q; l=$DKIM_l; s=$DKIM_s;\r\n".
			"\tt=$DKIM_t; c=$DKIM_c;\r\n".
			"\th=From:To:Subject;\r\n".
			"\td=$DKIM_d;$i_part\r\n".
			"\tz=$from\r\n".
			"\t|$to\r\n".
			"\t|$subject;\r\n".
			"\tbh=$DKIM_bh;\r\n".
			"\tb=";
		$to_be_signed = $this->relaxedHeaderCanonicalization("$from_header\r\n$to_header\r\n$subject_header\r\n$dkim") ;
		$b = $this->DKIMBlackMagic($to_be_signed, $dkimConfig) ;
		if($b != false){
			return "X-DKIM: php-dkim.sourceforge.net\r\n".$dkim.$b."\r\n" ;
		}
		return false;
	}
	
	/**
	 * Sign mail by DomainKeys (Email authentication) and return by header 
	 * @param Mail $mail
	 * @param DKIMConfig $dkimConfig
	 * @throws MailException
	 * @access public
	 * @return String DomainKeys Signature header
	 */
	public function addDomainKey(Mail $mail, DKIMConfig $dkimConfig){
		
		// Creating DomainKey-Signature
		$domainkeys = 	"DomainKey-Signature: " . "a=rsa-sha1; " . // The algorithm used to generate the signature "rsa-sha1"
						"c=nofws; " . // Canonicalization Alghoritm "nofws"
						"d={$dkimConfig->domain}; " . // The domain of the signing entity
						"s={$dkimConfig->selector}; ";
		if(empty($this->_headers)){
			$this->createHeaders($mail);
		}
		if(empty($this->_headers)){
			throw new MailException("Cant create DomainKeys Headers are not set!");
		}
		$h = '';
		$h_values = array();
		foreach($this->_headers as $header){
			$h .= $header["header"] . ":";
			$h_values[] = $header["header"] . ": " . $header["value"];
		}
		$h = trim($h, ":");
		$domainkeys .= "h={$h};\r\n";
		
		$body = quoted_printable_encode($mail->body);
		$_unsigned = $this->nofwsCanonicalization($h_values, $body);
		
		$b = $this->DKIMBlackMagic($_unsigned, $dkimConfig);
		
		$domainkeys .= "\tb=$b\r\n";
		return $domainkeys;
	}
	
  	private function DKIMQuotedPrintable($txt){
		$tmp = "";
		$line = "";
		for($i = 0; $i < strlen($txt); $i++){
			$ord = ord($txt[$i]);
			if(((0x21 <= $ord) && ($ord <= 0x3A)) || $ord == 0x3C || ((0x3E <= $ord) && ($ord <= 0x7E))){
				$line .= $txt[$i];
			}
			else{
				$line .= "=" . sprintf("%02X", $ord);
			}
		}
		return $line;
	}
	
	private function DKIMBlackMagic($s, DKIMConfig $dkimConfig){
		if(empty($dkimConfig)){
			throw new DKIMConfigException("DKIM config object is empty!");
		}
		if(empty($dkimConfig->privateKey)){
			throw new DKIMConfigException("DKIM private key is empty!");
		}
	 	if ($dkimConfig->password !== null) {
     	 	$privKey = openssl_pkey_get_private($dkimConfig->privateKey, $dkimConfig->password);
    	} else {
      		$privKey = $dkimConfig->privateKey;
    	}
		if(openssl_sign($s, $signature, $privKey)){
			return base64_encode($signature);
		}
		else{
			return false;
		}
	}
	
	private function simpleHeaderCanonicalization($string){
		return $string;
	}
	
	private function relaxedHeaderCanonicalization($string){
		$string = preg_replace("/\r\n\s+/", " ", $string);
		$lines = explode("\r\n", $string);
		foreach($lines as $key => $line){
			list($heading, $value) = explode(":", $line, 2);
			$heading = strtolower($heading);
			$value = preg_replace("/\s+/", " ", $value); // Compress useless spaces
			$lines[$key] = $heading . ":" . trim($value); // Don't forget to remove WSP around the value
		}
		$string = implode("\r\n", $lines);
		return $string;
	}
	
	private function simpleBodyCanonicalization($body){
		if($body == ''){
			return "\r\n";
		}
		
		// Just in case the body comes from Windows, replace all \r\n by the Unix \n
		$body = str_replace("\r\n", "\n", $body);
		// Replace all \n by \r\n
		$body = str_replace("\n", "\r\n", $body);
		// Should remove trailing empty lines... I.e. even a trailing \r\n\r\n
		while(substr($body, strlen($body) - 4, 4) == "\r\n\r\n"){
			$body = substr($body, 0, strlen($body) - 2);
		}
		return $body;
	}
  
  
	private function nofwsCanonicalization($raw_headers, $raw_body){

		$headers = array();
		foreach($raw_headers as $header){
			$headers[] = preg_replace('/[\r\t\n ]++/', '', $header);
		}
		$data = implode("\n", $headers) . "\n";
		foreach(explode("\n", "\n" . str_replace("\r", "", $raw_body)) as $line){
			$data .= preg_replace('/[\t\n ]++/', '', $line) . "\n";
		}
		$data = explode("\n", rtrim($data, "\n"));
		$data = implode("\r\n", $data) . "\r\n";
		
		return $data;
	}
	
	private function getToAddressesString(Mail $mail){
		if(empty($mail)){
			throw new MailException("Mail object is empty");
		}
		$to = $mail->getToAddress();
		if(empty($to) || !is_array($to)){
			throw new MailException("To addresses is empty");
		}
		$toArr = array();
    	foreach($to as $t) {
    	  $toArr[] = $this->addressFormat($t);
    	}
    	return implode(', ', $toArr);
	}
	
	private function addressFormat($addr){
		if(!isset($addr[1]) or empty($addr[1])){
			return "{$addr[0]}";
		}
		else{
			return "{$addr[1]}" . " <{$addr[0]}>";
		}
	}
	
	private function createHeaders(Mail $mail){
		if(empty($mail)){
			throw new MailException("Mail object is empty");
		}
		if(empty($mail->from)){
			throw new MailException("from mail is empty");
		}
		$from = null;
		if(!empty($mail->fromName)){
			$from = $this->addressFormat(array($mail->from, $mail->fromName));
		}
		else{
			$from = $mail->from;
		}
		$headers="From: ".$from."\r\n";
		$this->_headers[] = array("header"=>"From", "value"=>$from);
		
		$headers .= "Subject: {$mail->subject}\r\n";
		$this->_headers[] = array("header"=>"Subject", "value"=>$mail->subject);
		$headers .= "Content-Type: {$mail->contentType}\r\n";
		$this->_headers[] = array("header"=>"Content-Type", "value"=>$mail->contentType);
		
		$replyTos = $mail->getReplyTos();
		if(!empty($replyTos)){
			$replyToArr = array();
    		foreach($replyTos as $t) {
    		  $replyToArr[] = $this->addressFormat($t);
    		}
    		$replyTo = implode(', ', $replyToArr);
    		$this->_headers[] = array("header"=>"Reply-To", "value"=>$replyTo);
			$headers .= "Reply-To: $replyTo\r\n";
		}
		$headers .= "Return-Path: <{$mail->from}>\r\n";
		$this->_headers[] = array("header"=>"Return-Path", "value"=>$mail->from);
		
		return $headers;
	}
}
