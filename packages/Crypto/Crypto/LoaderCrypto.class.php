<?
class LoaderCrypto extends Loader{
	
	protected function includes(){
		require_once ('Objects/Crypto.class.php');
		require_once ('Exceptions/CryptoException.class.php');
		require_once ('Exceptions/NoRandomProviderException.class.php');
		require_once ('Exceptions/NotSupportedBigMathLibraryException.class.php');
	}
}
?>