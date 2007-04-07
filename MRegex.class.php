<?


	/**
	 * Classe definindo as regular-expressions comuns e (futuramente) funções úteis de regular expressions.
	 */
	class MRegex {
	
		public static $IP_PATTERN 	= '/^(([0-9]{1,3}\.){1,3}[0-9]{1,3})(\/[0-9]{1,2}){0,1}$/'; // Verifica apenas se tem 4 blocos de até 3 números cada separados com pontos
		public static $MAC_PATTERN	= '/^([0-9A-Fa-f]{1,2}[:\-]){5}([0-9A-Fa-f]{1,2})$/';
		public static $EMAIL_PATTERN	= '/^([^@]+@([^@]+\.)+([^@^\.]+))$/';		// Tá simples, pode melhorar
		
		
		/**
		 * Retorna true caso o texto analizado bata (match) com a regex especificada.
		 */
		
		public static function match($texto,$regex) {
			return( preg_match($regex,$texto) );
		}
		
		/**
		 * Retorna true caso texto analisado seja um endereco IP.
		 */
		public static function ip($texto) {
			return( self::match($texto,self::$IP_PATTERN) );
		}

		/**
		 * Retorna true caso texto analisado seja um endereco ethernet (MAC).
		 */
		public static function mac($texto) {
			return( self::match($texto,self::$MAC_PATTERN) );
		}

		/**
		 * Retorna true caso texto analisado seja um endereco de email.
		 */
		public static function email($texto) {
			return( self::match($texto,self::$EMAIL_PATTERN) );
		}

	
	}
	
	/**
	// TESTES
	
	$ipTesteArr = array("192.168.0.1","200.217.241.66","200.200.290.0","200.300.500.2000");
	
	for($i=0;$i<count($ipTesteArr);$i++) {
		echo $ipTesteArr[$i] . " - " . MRegex::ip($ipTesteArr[$i]) . "\n";
	}

	echo "-------------------\n";
	
	$emailTesteArr = array("consultoria@mosman.com.br", "consult@mosman", "consult@mosman.", "asd@asd.com");
	for($i=0;$i<count($emailTesteArr);$i++) {
		echo $emailTesteArr[$i] . " - " . MRegex::email($emailTesteArr[$i]) . "\n";
	}
	
	*/

?>
