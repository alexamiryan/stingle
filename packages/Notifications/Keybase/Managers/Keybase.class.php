<?php

class Keybase extends Model {

	protected $config;
	
	public function __construct(Config $config) {
		$this->config = $config;
	}
	
	
	public function send($message, $configName = null){
	    $url = '';
	    if($configName != null){
            $url = $this->config->urls->$configName;
        }
        else{
            $url = $this->config->urls->default;
        }
	    
        // Get cURL resource
        $curl = curl_init();
        // Set some options - we are passing in a useragent too here
        
        $config = [
            
            CURLOPT_URL => $url,
            CURLOPT_USERAGENT => 'Stingle Photos API',
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $message
        ];
        
        curl_setopt_array($curl, $config);
        // Send the request & save response to $resp
        $resp = curl_exec($curl);
        // Close request to clear up some resources
        
        curl_close($curl);
	    
	    return $resp;
    }

    
}
