<?
class Crypto
{
	
	#random seed
	private static $RSeed = 0;


	/*
	 * PBKDF2 key derivation function as defined by RSA's PKCS #5: https://www.ietf.org/rfc/rfc2898.txt
	* $algorithm - The hash algorithm to use. Recommended: SHA256
	* $password - The password.
	* $salt - A salt that is unique to the password.
	* $count - Iteration count. Higher is better, but slower. Recommended: At least 1024.
	* $key_length - The length of the derived key in BYTES.
	* Returns: A $key_length-byte key derived from the password and salt (in binary).
	*
	* Test vectors can be found here: https://www.ietf.org/rfc/rfc6070.txt
	*/
	public static function pbkdf2($algorithm, $password, $salt, $count, $key_length){
		$algorithm = strtolower($algorithm);
		if(!in_array($algorithm, hash_algos(), true)){
			die('PBKDF2 ERROR: Invalid hash algorithm.');
		}
		if($count <= 0 || $key_length <= 0){
			die('PBKDF2 ERROR: Invalid parameters.');
		}

		// number of blocks = ceil(key length / hash length)
		$hash_length = strlen(hash($algorithm, "", true));
		$block_count = $key_length / $hash_length;
		if($key_length % $hash_length != 0){
			$block_count += 1;
		}

		$output = "";
		for($i = 1; $i <= $block_count; $i++){
			$output .= self::pbkdf2_f($password, $salt, $count, $i, $algorithm, $hash_length);
		}

		return substr($output, 0, $key_length);
	}

	/*
	 * The pseudorandom function used by PBKDF2.
	* Definition: https://www.ietf.org/rfc/rfc2898.txt
	*/
	private static function pbkdf2_f($password, $salt, $count, $i, $algorithm, $hash_length){
		//$i encoded as 4 bytes, big endian.
		$last = $salt . chr(($i >> 24) % 256) . chr(($i >> 16) % 256) . chr(($i >> 8) % 256) . chr($i % 256);
		$xorsum = "";
		for($r = 0; $r < $count; $r++){
			$u = hash_hmac($algorithm, $last, $password, true);
			$last = $u;
			if(empty($xorsum)){
				$xorsum = $u;
			}
			else{
				for($c = 0; $c < $hash_length; $c++){
					$xorsum[$c] = chr(ord(substr($xorsum, $c, 1)) ^ ord(substr($u, $c, 1)));
				}
			}
		}
		return $xorsum;
	}

	public static function isHex($input = ''){
		for($i=0; $i<strlen( $input ); $i++){
			$digit  = ord( $input[$i] );
			if( $digit < 48 || $digit > 102 ){
				return false;
			}
			if( $digit < 96 && $digit > 57  ){
				return false;
			}
		}
		return true;
	}

	public static function hex2dec($hex = ''){
		if( !self::isHex( $hex ) ){
			throw new InvalidArgumentException("Provided values is not hex");
			return false;
		}
		if(function_exists('bcmul') && function_exists('bcadd')){
			$dec = '0';
			$pow = '1';
			for( $i = strlen( $hex ) - 1; $i >= 0; $i--){
				$digit  = ord( $hex[$i] );
				$digit  = ( ( $digit>96 )? ( $digit ^ 96 ) + 9: $digit ^ 48 );
				$dec    = bcadd( $dec, bcmul( $pow, $digit ) );
				$pow    = bcmul( $pow, '16' );
			}
			return $dec;
		}
		else if( function_exists( 'gmp_init' ) && function_exists( 'gmp_strval' ) ){
			$hex = gmp_init(   $hex, 16 );
			return gmp_strval( $hex, 10 );
		}
		else{
			throw new NotSupportedBigMathLibraryException();
		}
		return false;
	}

	public static function dec2hex($dec = ''){
		if( function_exists( 'bcmod' ) && function_exists( 'bcdiv' ) && function_exists( 'bccomp' ) ){
			$digits = array('0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f');
			$hex = '';
			do{
				$digit  = bcmod( $dec, '16' );
				$dec    = bcdiv( $dec, '16' );
				$hex    = $digits[$digit].$hex;
			}while( bccomp( $dec, '0' ) );
			return $hex;
		}else if( function_exists( 'gmp_init' ) && function_exists( 'gmp_strval' ) ){
			$hex = gmp_init(   $dec, 10 );
			return gmp_strval( $dec, 16 );
		}else{
			throw new NotSupportedBigMathLibraryException();
		}
		return false;
	}

	public static function byte2hex($bytes = ''){
		$digits = array('0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f');
		$hex    = '';
		$len    = strlen( $bytes );
		for( $i = 0; $i < $len; $i++ ){
			$b      = ord( $bytes[$i] ) & 0xFF;
			$hex    = $hex . $digits[( $b & 0xF0 ) >> 4];
			$hex    = $hex . $digits[$b & 0x0F];
		}
		return $hex;
	}

	public static function secureRandom($bits = 16){

		$bytes = $bits/8;
		$result = '';
		$digits = array('0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f');

		$pr_bits    = '';

		#Unix/Linux platform
		$fp = @fopen( '/dev/urandom', 'rb' );
		if( $fp !== false ){
			$pr_bits    .= @fread( $fp, $bytes );
			@fclose( $fp );
		}

		#MS-Windows platform before CAPICOM discontinued
		if( @class_exists( 'COM' ) ){
			#http://msdn.microsoft.com/en-us/library/aa388176(VS.85).aspx
			try{
				$CAPI_Util  = new COM( 'CAPICOM.Utilities.1' );
				$pr_bits    .= $CAPI_Util->GetRandom( $bytes, 0 );
	
				#ask for binary data PHP munges it, so we
				#request base64 return value.  We squeeze out the
				#redundancy and useless ==CRLF by hashing...
				if( $pr_bits ){
					$pr_bits    = md5( $pr_bits, true );
				}
			}
			catch(Exception $e){ }
		}

		#nothing has worked yet so lets make an outside connection
		if( strlen( $pr_bits ) < $bytes ){
			try{
				$pr_bits = file_get_contents( "http://www.random.org/cgi-bin/randbyte?nbytes=$bytes&format=h" ); #connect to random.org for the random stuff
				$pr_bits = preg_replace( '/[^0-9A-z]/', '', trim( $pr_bits ) ); #git rid of the spaces, only leaves the 16 bits or 32 characters
				$pr_bits = pack( "H*", $pr_bits ); #pack it down into the 16 byte string
			}
			catch(Exception $e){}
		}

		#failed to get any random source to work
		if( strlen( $pr_bits ) < $bytes ){
			throw new NoRandomProviderException();
			return false;
		}


		$len = strlen($pr_bits);
		$b = 0;

		for( $i = 0; $i < $len; $i++ ){
			$b      = ord( $pr_bits[$i] );
			$result = $result . $digits[ ($b & 0xF0) >> 4 ];
			$result = $result . $digits[$b & 0x0F];
		}
		return $result;
	}

	#set random seed
	protected static function seed($s = 0){
		self::$RSeed = abs(intval($s)) % 9999999 + 1;
		self::s_rand();
	}

	#replaces php native random functions
	#efficent, uses secure seeding and repeatable sequencing if the same seed is provided
	public static function s_rand($min = 0, $max = 9999999){
		if (self::$RSeed == 0){
			self::seed(self::hex2dec(self::secureRandom()));
		}
		self::$RSeed = (self::$RSeed * 125) % 2796203;
		return self::$RSeed % ($max - $min + 1) + $min;
	}
}
?>