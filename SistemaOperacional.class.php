<?


	class SistemaOperacional {
	
		public static $FW_SUB_BASERULE			= 2000;
		public static $FW_IP_BASERULE			= 10000;
		public static $FW_IP_BASEPIPE_IN		= 18000;
		public static $FW_IP_BASEPIPE_OUT		= 26000;

		public static $FW_PPPoE_BASERULE		= 34000;
		public static $FW_PPPoE_BASEPIPE_IN		= 42000;
		public static $FW_PPPoE_BASEPIPE_OUT	= 50000;

	
		public static $IFCONFIG	= "/sbin/ifconfig";
		public static $HOSTNAME	= "/bin/hostname";
		public static $ARP		= "/sbin/arp";
		public static $ROUTE	= "/sbin/route";
		
		public static $INSTALL	= "/usr/bin/install";
		public static $DEFAULT_NTP_SERVER = "146.164.48.5";
		
		/**
		 * Construtor
		 */
		public function __construct() {
		
		}
		
		/**
		 * Interfaces
		 */
		public static function getInterfaces() {
			$so = self::getSO();
			
			if( $so == "Linux" ) {
				$comando = self::$IFCONFIG . " | /bin/cut -f 1 -d ' '|/bin/grep -vE '^$|:|ppp|tun|lo'";
				$l = chop(self::executa($comando));
				$interfaces = explode("\n",$l);				
			} else {
				// FreeBSD
				$comando = self::$IFCONFIG . " -l";
				$l = chop(self::executa($comando));
				
				$tmp = explode(" ",$l);
				
				$interfaces = array();
				
				for($i=0;$i<count($tmp);$i++) {
					if(trim($tmp[$i]) && !strstr($tmp[$i],"plip") && !strstr($tmp[$i],"slip") && !strstr($tmp[$i],"ppp") && !strstr($tmp[$i],"tun") && $tmp[$i] != "lo0" ) {
						$interfaces[] = $tmp[$i];
					}
				}
				
				
			}
			
			return($interfaces);
			
		}
		
		/**
		 * Hostname
		 */
		public static function getHostname() {
			$comando = self::$HOSTNAME;			
			return(trim(chop(self::executa($comando))));
		}
		
		/**
		 * Network Info
		 */
		public static function getNetworkInfo() {
		
			$so = self::getSO();

			$mac_re = "/([0-9A-Fa-f]{2}\:){5}([0-9A-Fa-f]{2})/";
			$ip_re = "/(?:\d{1,3}\.){3}\d{1,3}/";

			$iflist = array();
			$iface = "";
			$ifmatch = false;

			// $linhas = preg_split('/\n/',self::executa("/sbin/ifconfig"));
			$linhas = self::executa(self::$IFCONFIG,NULL,"",true);
			
			//echo "<pre>";
			//print_r($linhas);
			//echo "</pre>";

			//echo "CL: " . count($linhas);
			$i=0;

			while( $linha = $linhas[$i++] ) {
				//$linha .= "\n";
				// echo $linha . "<br>\n";
				$matches = array();
				$ifaceinfo = array();
				//$linha = preg_replace("/^[\s\t]/","",$linha);

				/**
				 * Linux Match
			 	 */
				preg_match("/^[A-Za-z0-9]+[\s\t]/",$linha,$ifaceinfo,PREG_OFFSET_CAPTURE);
				if(@$ifaceinfo[0][0]) {
					$iface = @$ifaceinfo[0][0];
					$ifmatch = true;
				}

				/**
				 * FreeBSD Match
			 	 */
				if( !$ifmatch ) {
					preg_match("/^[A-Za-z0-9]+\:\s/",$linha,$ifaceinfo,PREG_OFFSET_CAPTURE);
					if(@$ifaceinfo[0][0]) {
						$iface = @$ifaceinfo[0][0];
						$ifmatch = true;
					}
				}
				
				//echo "IFACE: $iface<br>\n";

				if( $ifmatch ) {
					$iface = str_replace(":","",trim($iface));
					$iflist[$iface] = array();
					// $iflist[$iface]["ips"] = array();
					$iflist[$iface]["inet"] = array();
					$ifmatch = false;
				}

				$matches = array();
				
				preg_match($ip_re,$linha,$matches,PREG_OFFSET_CAPTURE);
				if(count($matches)) {
					// $iflist[$iface]["ips"][] = $matches[0][0];

					$ipaddr = $matches[0][0];

					if( $so == "Linux" ) {
						// LINUX
						$re_netmask = "/(Mask:)((\d{1,3}\.){3}\d{1,3})/";
					} else {
						// FREEBSD
						//$linha = "       inet 200.217.241.68 netmask 0xfffffff8 broadcast 200.217.241.71";
						$re_netmask = "/(netmask 0x)([0-9a-fA-F]{8})/";
					}
					
					$matches = array();
					preg_match($re_netmask,$linha,$matches,PREG_OFFSET_CAPTURE);

					$netmask = @$matches[2][0];

					if( $so == "FreeBSD" ) {
						$netmask = MInet::hex2netmask($netmask);
					}

					$iflist[$iface]["inet"][] = MInet::calculadora($ipaddr,$netmask);

				}

				$matches = array();

				preg_match($mac_re,$linha,$matches,PREG_OFFSET_CAPTURE);
				$linha = strtoupper($linha);

				$mac = @$matches[0][0];
					if($mac) {
					$iflist[$iface]["mac"] = strtoupper($mac);
				}      
			}

			return($iflist);


		}
		
		/**
		 * uname
		 */
		public static function getSO() {
			$uname = is_executable("/usr/bin/uname") ? "/usr/bin/uname" : "/bin/uname";
			$so = trim(chop(self::executa($uname)));
			return($so);
		}

		/**
		 * Tenta identificar a interface externa pela rota padrão.
		 */
		public static function getExtIf() {
			$ns = self::getNetstat();
			
			$extIf = "";
			
			for($i=0;$i<count($ns);$i++) {
				if( $ns[$i]["destination"] == "0.0.0.0/0" ) {
					$extIf = $ns[$i]["interface"];
					break;
				}
			}
			
			return($extIf);
		}

		/**
		 * Tenta identificar a rota padrão.
		 */
		public static function getDefaultRoute() {
			$ns = self::getNetstat();
			
			$defGw = "";
			
			for($i=0;$i<count($ns);$i++) {
				if( $ns[$i]["destination"] == "0.0.0.0/0" ) {
					$defGw = $ns[$i]["gateway"];
					break;
				}
			}
			
			return($defGw);
		}

		/**
		 * Tenta identificar a rede de saída.
		 */
		public static function getOutNetwork($interface) {
			$ns = self::getNetstat();
			
			$defGw = "";
			
			for($i=0;$i<count($ns);$i++) {
				if( $ns[$i]["destination"] == "0.0.0.0/0" ) {
					$defGw = $ns[$i]["gateway"];
					break;
				}
			}
			
			return($defGw);
		}
		
		/**
		 * getNetstat
		 */
		public static function getNetstat() {
		
			$so = self::getSO();
		
			if( $so == "Linux" ) {
				$grep = "/bin/grep";
				$netstat = "/bin/netstat -rn --inet| $grep -vE 'Kernel IP routing table'";
				$sed = "/bin/sed";
				$sed_flag = "-r";
				
			} else {
				$grep = "/usr/bin/grep";
				$netstat = "/usr/bin/netstat -rn -f inet | $grep -vE '^$|Routing tables|Internet:'";
				$sed_flag = "-E";
				$sed = "/usr/bin/sed";
			}
			
			// $comando = "$netstat -rn|$grep -E '^(0.0.0.0|default)'|$sed $sed_flag 's/[ ]+/\:/g'";
			$comando = "$netstat|$sed $sed_flag 's/[ ]+/\::/g'";
			
			$ns = self::executa($comando);
			
			$rotas = explode("\n",$ns);
			
			$campos = array();
			$tmp = array();
			
			$iR = 0;
			
			for($i=0;$i<count($rotas);$i++) {
			
				if( !strlen($rotas[$i]) ) {
					continue;
				}
			
				$dados = explode("::",$rotas[$i]);

				if( $i == 0 ) {
					$campos = $dados;
					continue;
				}
				
				$tmp[$iR] = array();
				
				for($x=0;$x<count($campos);$x++) {
					$tmp[$iR][strtolower($campos[$x])] = @$dados[$x];
				}
				
				$iR++;

			}
			
			
			$retorno = array();
			
			
			// NORMALIZACAO
			for($i=0;$i<count($tmp);$i++) {
				$retorno[$i] = array();
				if( $so == "Linux" ) {
					$rede = MInet::calculadora($tmp[$i]["destination"],$tmp[$i]["genmask"]);
					$retorno[$i]["destination"] = $tmp[$i]["destination"] . "/" . $rede->obtemBitmask();
				} else {
					if( $tmp[$i]["destination"] == "default" ) {
						$retorno[$i]["destination"] = "0.0.0.0/0";
					}
				}

				$retorno[$i]["gateway"] = (($so == "Linux" && $tmp[$i]["flags"] == "U") || ($so == "FreeBSD" && $tmp[$i]["flags"] == "UC")) ? "" : $tmp[$i]["gateway"];
				if( (($so == "Linux" && $tmp[$i]["flags"] == "U") || ($so == "FreeBSD" && $tmp[$i]["flags"] == "UC")) ) {
					$retorno[$i]["flags"] = "localnet";
				} else {
					$retorno[$i]["flags"] = "";
				}
				
				$retorno[$i]["interface"] = $so == "Linux" ? $tmp[$i]["iface"] : $tmp[$i]["netif"];

			}
						
			return($retorno);
						
		}
		
		/**
		 * pgDump 
		 */
		public static function pgDump($host,$usuario,$senha,$banco,$arquivoOutput,$opcoes="") {
			$possibilidades = array();
			$possibilidades[] = "/usr/local/bin/pg_dump";
			$possibilidades[] = "/usr/bin/pg_dump";
			
			$pg_dump = "";
			
			for($i=0;$i<count($possibilidades);$i++) {
				if( file_exists($possibilidades[$i]) && is_executable($possibilidades[$i])) {
					$pg_dump = $possibilidades[$i];
					break;
				}
			}
			
			$param = "";
			
			if( $host ) $param .= " -h $host";
			if( $usuario ) $param .= " -U $usuario";
			
			$param .= " " . $opcoes;			
			$param .= " " . $banco;
			
			$comando = $pg_dump . " " . trim($param) . " 2>&1";
			self::executa($comando,NULL,$arquivoOutput);
			
		}
		
		public static function obtemPHP() {
			$possibilidades = array();
			$possibilidades[] = "/usr/bin/php";
			$possibilidades[] = "/usr/local/bin/php";
			$possibilidades[] = "/bin/php";
			
			for($i=0;$i<count($possibilidades);$i++) {
				if( file_exists($possibilidades[$i]) && is_executable($possibilidades[$i])) {
					return($possibilidades[$i]);
				}
			}
			
			return "";
			
			
		}
		
		public static function tar($arquivoTar,$fileList) {
			$possibilidades = array();
			$possibilidades[] = "/bin/tar";
			$possibilidades[] = "/usr/bin/tar";
			$possibilidades[] = "/usr/local/bin/tar";
			
			$tar = "";
			
			for($i=0;$i<count($possibilidades);$i++) {
				if( file_exists($possibilidades[$i]) && is_executable($possibilidades[$i])) {
					$tar = $possibilidades[$i];
					break;
				}
			}
			
			if( is_array($fileList) ) {
				$fileList = implode(" ", $fileList);
			}
			
			$comando = $tar . " cf " . $arquivoTar . " " . $fileList;
			
			return(self::executa($comando));

		}
		
		public static function gzip($arquivo) {
			$possibilidades = array();
			$possibilidades[] = "/bin/gzip";
			$possibilidades[] = "/usr/bin/gzip";
			$possibilidades[] = "/usr/local/bin/gzip";
			
			$gzip = "";
			
			for($i=0;$i<count($possibilidades);$i++) {
				if( file_exists($possibilidades[$i]) && is_executable($possibilidades[$i])) {
					$gzip = $possibilidades[$i];
					break;
				}
			}
			
			$comando = $gzip . " " . $arquivo;
			
			return(self::executa($comando));
		}
		
		
		
		
		/**
		 * Executa um comando no sistema operacional
		 *
		 * retorna o resultado da execução deste comando.
		 */
		public static function executa($comando,$post=NULL,$outputFile="",$retArray=false) {
		
			// Comportamento diferente para retorno em array();
			if( $retArray ) {
				$fd = popen($comando,"r");
				$linhas = array();
				while( ($linhas[]=fgets($fd)) && !feof($fd) ) { }
				fclose($fd);
				return($linhas);			
			}
		
		
			$retorno = "";
			
			// echo $comando . "\n";
			// echo "EXECUTA: $comando\n";
			
			$fd = popen($comando, ($post ? 'w' : 'r'));
			if($post) {
				fputs($fd,$post);
			}
			$now = time();
			
			if( $outputFile ) {
				$outFD = fopen($outputFile,"w");
			}
			
			
			while(!feof($fd) && ($linha = fgets($fd)) ) {
				if( $outputFile ) {
					fwrite($outFD,$linha,strlen($linha));
				} else {
					$retorno .= $linha;
				}
			}
			
			if( $outputFile ) {
				fclose($outFD);
			}
			
			
			pclose($fd);
			
			//$retorno = shell_exec($comando);
			
			return($retorno);

		}
		
		
		/**
		 * ifConfig / ifUnConfig
		 *
		 * Configura / Remove IP da interface
		 */
		
		public static function ifConfig($iface,$ip,$mascara) {
		
		}
		
		public static function ifUnConfig($iface,$ip) {
		
		}
		
		
		/**
		 * adcionaRegraBW / removeRegraBW
		 *
		 * Adiciona e remove regra com gerenciamento de banda no firewall.
		 */
		public static function adicionaRegraBW($id,$baserule,$basepipe_in,$basepipe_out,$int_iface,$ext_iface,$ip,$mac,$upload_kbps,$download_kbps,$username) {
		
		}
		
		public static function deletaRegraBW($id,$baserule, $basepipe_in,$basepipe_out) {
		
		}
	
		/**
		 * adicionaRegraSP / removeRegraSP
		 *
		 * Adiciona e remove regra de suporte e infra-estrutura
		 */
		
		public static function adicionaRegraSP($id,$baserule,$rede,$ext_iface) {
		
		}
		
		public static function deletaRegraSP($id,$baserule) {
		
		}
		
		/**
		 * Obtem as estatísticas de utilização
		 */
		public static function obtemEstatisticas() {
		
		}
		
		
		/**
		 * setNAT / unsetNAT
		 *
		 * Habilita e desabilita o NAT na interface
		 */
	
		public static function setNAT($iface) {
		
		}
		
		public static function unsetNAT($iface) {
		
		}
		
		/**
		 * routeAdd / routeDelete
		 *
		 * Cria e exclui rotas.
		 */
		public static function routeAdd($rede,$destino) {
		
		}
		
		public static function routeDelete($rede) {
		
		}

		/**
		 * removeARP / atribuiARP
		 * Cria e remove entradas na tabela arp.
		 */

		public static function removeARP($ip) {

		}
		
		public static function atribuiARP($ip,$mac) {
		
		}



		/**
		 * installDir
		 *
		 * Cria um diretório
		 */
		public static function installDir($target,$mode=755,$owner="") {

		}

		/**
		 * fping
		 */
		
		public static function fping($ip,$num_pacotes=2,$tamanho="",$timeout=1200) {
			return( array() );
		}
		
		/**
		 * ntpUpdate
		 *
		 * Atualiza a data via NTP
		 */
		public static function ntpDate($server="") {
		
		}
		
		/**
		 * mailDirMake
		 * Cria um diretório de maildir
		 */
		public static function mailDirMake($target,$uid,$gid) {
		
			self::installDir($target . "/cur","700",$uid,$gid);
			self::installDir($target . "/new","700",$uid,$gid);
			self::installDir($target . "/tmp","700",$uid,$gid);
		
		}

	
	}




?>
