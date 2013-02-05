<?php
/**
 * DKIM or DomainKeys config class 
 * @author user
 *
 */
class DKIMConfig extends Config
{
	/**
	 * DKIM private key 
	 * Generate private key: openssl rsa -in key.priv -out key.pub -pubout -outform PEM
	 * @access public
	 * @var String 
	 */
	public $privateKey;
	
	/**
	 * DKIM public key
	 * Generate public  key: openssl genrsa -out key.priv 384
	 * @access public
	 * @var String
	 */
	public $publicKey;
	
	/**
	 * Domain name
	 * @example yourDomain
	 * @access public
	 * @var String
	 */
	public $domain;
	
	/**
	 * Selector name of dkim signature
	 * @example "stingle"
	 * @access public
	 * @var String
	 */
	public $selector;
	
	/**
	 * Password for private key. 
	 * openssl_pkey_get_private($dkimConfig->privateKey, $dkimConfig->password);
	 * @example "somePassword"
	 * @access public
	 * @var String
	 */
	public $password = null;
	
	/**
	 * Optional parameter.
	 * @var string 
	 * @example: "mail@domain.com"
	 */
	public $identity = '';
}
