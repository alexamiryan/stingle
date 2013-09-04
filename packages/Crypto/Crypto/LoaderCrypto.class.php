<?php
class LoaderCrypto extends Loader{
	
	protected function includes(){
		stingleInclude ('Objects/Crypto.class.php');
		stingleInclude ('Exceptions/CryptoException.class.php');
		stingleInclude ('Exceptions/NoRandomProviderException.class.php');
		stingleInclude ('Exceptions/NotSupportedBigMathLibraryException.class.php');
		stingleInclude ('Helpers/helpers.inc.php');
	}
}
