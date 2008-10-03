<?

	require_once("Net/IPv4.php");
	require_once("MException.class.php");

	/**
	 * Classe de manipulação de endereços de rede.
	 */
	
	class MInet {
		protected $ipv4;
		protected $isValid;
		protected $baseNetwork; 

		/**
		 * Recebe o ip na notação IP/BITS
		 */
		public function __construct($ip,$baseNetwork="") {
			$this->isValid = false;
			if( $ip ) {
				$this->ipv4 = Net_IPv4::parseAddress($ip);
				
				if( PEAR::isError($this->ipv4) ) {
					$this->ipv4 = "";
					throw new MException("Dados Inválidos");
				} else {
					$this->isValid = true;
					if( !$this->obtemMaxHosts() ) {
						$this->isValid = false;
						throw new MException("Redes /31 não existem.");
					}
				}
				
				// Utilizado pelos iteradores p/ identificar se o endereço pertence a rede especificada.
				$this->baseNetwork = $baseNetwork;

				//echo "IP: $ip\n";
				//echo "BN: $baseNetwork\n";
				//echo "R: " . $this->obtemRede() . "\n";

				if( $baseNetwork ) {
					@list($ipAddr,$bitsAddr) = explode("/",$ip);
					@list($ipBase,$bitsBase) = explode("/",$baseNetwork);

					if( !$bitsAddr  ) $bitsAddr = 32;

					//echo "IA: $ipAddr\n";
					//echo "BA: $bitsAddr\n";
					//echo "IB: $ipBase\n";
					//echo "BB: $bitsBase\n";
					

					
					if( $bitsAddr < $bitsBase ) {
						throw new MException("Rede base menor que endereço indicado");
					}
										
					if( !Net_IPv4::ipInNetwork($this->obtemRede(),$baseNetwork) ) {
						throw new MException("Rede não pertence à base.");
					}
				}
				
			}
		}
		
		protected static function somaIP($ip,$incremento) {
			return(long2ip(Net_IPv4::ip2double($ip)+$incremento));
		}
		
		public function valido() {
			return($this->isValid);
		}
		
		public function obtemIP() {
			return($this->isValid?$this->ipv4->ip:"");
		}
		
		public function obtemRede() {
			return($this->isValid?$this->ipv4->network:"");
		}
		
		public function obtemBitmask() {
			return($this->isValid?$this->ipv4->bitmask:"");
		}
		
		public function obtemBroadcast() {
			return($this->isValid?$this->ipv4->broadcast:"");
		}
		
		public function obtemPrimeiroIP() {
			if($this->isValid) {
				if( $this->obtemBitmask() == 31 ) {
					return("Invalido");
				} else if( $this->obtemBitmask() == 32) {
					return($this->obtemIP());
				} else {
					return(self::somaIP($this->obtemRede(),1));
				}
			}
		}
		
		public function obtemUltimoIP() {
			if($this->isValid) {
				if( $this->obtemBitmask() == 31 ) {
					return("Invalido");
				} else if( $this->obtemBitmask() == 32) {
					return($this->obtemIP());
				} else {
					return(self::somaIP($this->obtemBroadcast(),-1));
				}
			}
			return("");
		}
		
		public function obtemMascara() {
			return($this->isValid?$this->ipv4->netmask:"");
		}
		
		public function obtemWildcard() {
			if( $this->isValid ) {
				$mask = $this->ipv4->bitmask == 0 ? 0 : (~0 << (32 - $this->ipv4->bitmask));
				$mask = ~$mask;
				return(long2ip($mask));
			}
		}
		
		public static function calculadora($ip,$netmask) {

			$bits = -1;

			if(strlen($netmask) > 0 && strlen($netmask) <= 3 && ((int)$netmask <= 32) ) {
				$bits = (int)$netmask;
			} else {

				$ip_calc = new Net_IPv4();
				$ip_calc->ip = $ip;
				if( !$ip_calc->validateNetmask($netmask) ) {
					throw new Exception("Mascara Invalida");
				} else {
					$ip_calc->netmask = $netmask;				
					$error = $ip_calc->calculate();
					if( !is_object($error) ) {
						$bits = $ip_calc->bitmask;
					}
				}
			}
			
			if( $bits < 0 ) {
				return new MInet("");
			}

			return new MInet($ip."/".$bits);
			
		}
		
		public function obtemMaxHosts() {
			$retorno=0;
			if( $this->isValid ) {
				if( $this->obtemBitmask() == 31 ) {
					$retorno=0;
				} else if( $this->obtemBitmask() == 32 ) {
					$retorno=1;
				} else {
					$retorno = pow(2,32-$this->obtemBitmask()) - 2;
				}
			}
			return($retorno);
		}
		
		/**
		 * Obtem o próximo endereço de rede com o mesmo bitmask
		 */
		public function proximaRede() {
			try {
				$proximaRede = new MInet(long2ip(Net_IPv4::ip2double($this->obtemBroadcast())+1) . "/" . $this->obtemBitmask(),$this->baseNetwork);
				if( !$this->baseNetwork && !$this->obtemMaxHosts() || $proximaRede->obtemRede() == $this->obtemRede() ) {
					// Chegou ao fim
					$proximaRede = new MInet("");
				}

			} catch( MException $e ) {
				$proximaRede = new MInet("");
			}
			
			
			return($proximaRede);
		
		}
		
		public function teste() {
			print_r($this->ipv4);
		}
		
		public function toArray() {
			return(array(
							"ip" => $this->obtemIP(),
							"rede" => $this->obtemRede(),
							"mascara" => $this->obtemMascara(),
							"bitmask" => $this->obtemBitmask(),
							"broadcast" => $this->obtemBroadcast(),
							"primeiro_host" => $this->obtemPrimeiroIP(),
							"ultimo_host" => $this->obtemUltimoIP(),
							"wildcard" => $this->obtemWildcard(),
							"maxhosts" => $this->obtemMaxHosts()
						));
		}
		
		public static function hex2netmask($hexmask) {
			$hexmask = str_replace("0x","",$hexmask);
			
			// $hexmask = "12345678";
			
			$parte01 = substr($hexmask,0,2);
			$parte02 = substr($hexmask,2,2);
			$parte03 = substr($hexmask,4,2);
			$parte04 = substr($hexmask,6,2);
			
			//echo "<pre>\n$parte01.$parte02.$parte03.$parte04</pre>\n";
			
			
			return( hexdec($parte01) . "." . hexdec($parte02) . "." . hexdec($parte03) . "." . hexdec($parte04) );
			
		}
		
		public function obtemLong() {
			return($this->ipv4->long);
		}
		
		/**
		 * Retorna true se o objeto rede contem o ip especificado.
		 */
		public function contem($ip) {
			$nm = $this->obtemBitmask();
			
			if( $nm < 24 ) {
				return false;
			}
			
			$rd = $this->obtemRede();

			$tmp = new MInet($this->obtemPrimeiroIP() . "/" . $nm);
			$ini = $tmp->obtemLong();
			
			$tmp = new MInet($this->obtemUltimoIP() . "/" . $nm);
			$fim = $tmp->obtemLong();
			
			$tmp = new MInet($ip."/32");
			$l = $tmp ->obtemLong();
			
			unset($tmp);
			
			return( $l >= $ini && $l <= $fim );		
		}
	
	}
	
	
	//for($ip = new MInet("192.168.0.0/25","192.168.0.0/24"); $ip->obtemRede() != "" ; $ip = $ip->proximaRede()) {
	//	echo $ip->obtemRede() . "/" . $ip->obtemBitmask()	 . "\n";
	//}
	
	
	
	//$ip = MInet::calculadora("192.168.0.1","255.255.255.192");
	
	//$ip->teste();
	


	// Teste
	/**	
	$addr = "192.168.0.0/32";
	$ip = new MInet($addr);
	echo "VALIDO...: " . $ip->valido() . "\n";
	echo "BITS.....: " . $ip->obtemBitmask() . "\n";
	echo "REDE.....: " . $ip->obtemRede() . "\n";
	echo "NMASK....: " . $ip->obtemMascara() . "\n";
	echo "BCAST....: " . $ip->obtemBroadcast() . "\n";
	echo "PRIMEIRO.: " . $ip->obtemPrimeiroIP() . "\n";
	echo "ULTIMO...: " . $ip->obtemUltimoIP() . "\n";
	echo "WCARD....: " . $ip->obtemWildcard() . "\n";
	echo "MAX......: " . $ip->obtemMaxHosts() . "\n";
	echo "--------------------------------------------\n";
	$ip = $ip->proximaRede();
	echo "VALIDO...: " . $ip->valido() . "\n";
	echo "BITS.....: " . $ip->obtemBitmask() . "\n";
	echo "REDE.....: " . $ip->obtemRede() . "\n";
	echo "NMASK....: " . $ip->obtemMascara() . "\n";
	echo "BCAST....: " . $ip->obtemBroadcast() . "\n";
	echo "PRIMEIRO.: " . $ip->obtemPrimeiroIP() . "\n";
	echo "ULTIMO...: " . $ip->obtemUltimoIP() . "\n";
	echo "WCARD....: " . $ip->obtemWildcard() . "\n";
	echo "MAX......: " . $ip->obtemMaxHosts() . "\n";
	echo "--------------------------------------------\n";
	*/	

?>
