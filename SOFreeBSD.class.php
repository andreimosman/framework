<?

	require_once("SistemaOperacional.class.php");

	class SOFreeBSD extends SistemaOperacional {
	
		public static $IPFW 	= "/sbin/ipfw -q";
		public static $PFCTL	= "/sbin/pfctl";
		
		/****************************************************
		 *                                                  *
		 * FUNCOES DA INTERFACE                             *
		 *                                                  *
		 ****************************************************/
		
		// Configura IP na interface
		public static function ifConfig($iface,$ip,$mascara) {
			$comando = SistemaOperacional::$IFCONFIG . " " . $iface . " inet alias " . $ip . " netmask " . $mascara;
			return(SistemaOperacional::executa($comando));
		}
		
		// Tira o IP da interface
		public static function ifUnConfig($iface,$ip) {

			$comando = SistemaOperacional::$IFCONFIG . " " . $iface . " delete " . $ip;
			return(SistemaOperacional::executa($comando));

		}


		/****************************************************
		 *                                                  *
		 * FUNCOES DO FIREWALL                              *
		 *                                                  *
		 ****************************************************/

		public static function adicionaRegraBW($id,$baserule,$basepipe_in,$basepipe_out,$int_iface,$ext_iface,$ip,$mac,$upload_kbps,$download_kbps,$username) {
			$ipfw = SoFreeBSD::$IPFW;

			$rule     = (int)($baserule + $id);
			$pipe_in  = (int)($basepipe_in + $id);
			$pipe_out = (int)($basepipe_out + $id);

			$upload    = (int)$upload_kbps;
			$download  = (int)$download_kbps;

			
			///////////////////////////////////////////
			// VERIFICACAO DE MAC                    //
			///////////////////////////////////////////

			if( $mac ) {
				$comando = $ipfw . " add " . $rule . " deny layer2 src-ip " . $ip . " not MAC any " . $mac . " via " . $int_iface;
				SistemaOperacional::executa($comando);
			}

			///////////////////////////////////////////
			// ADICIONA AS REGRAS                    //
			///////////////////////////////////////////

			// upload
			if($upload) {
				$comando = $ipfw . " add " . $rule . " pipe " . $pipe_in . " ip from " . $ip . " to any // " .$username."::up";
			} else {
				$comando = "$ipfw add $rule permit ip from $ip to any // $username::up";
			}
			SistemaOperacional::executa($comando);
			
			// download
			if($download) {
				$comando = $ipfw . " add " . $rule . " pipe " . $pipe_out . " ip from any to " . $ip . " // ".$username."::down";
			} else {
				$comando = "$ipfw add $rule permit ip from any to $ip // $username::down";
			}
			SistemaOperacional::executa($comando);

			///////////////////////////////////////////
			// CONFIGURA OS PIPES                    //
			///////////////////////////////////////////
			if($upload){
			   $slots_out = SOFreeBSD::obtemNumSlotsIdeal($upload_kbps);
			   $comando = $ipfw . " pipe " . $pipe_out . " config bw " . $upload . "Kbit/s queue " . $slots_out;
			   SistemaOperacional::executa($comando);
			}

			if($download){
				$slots_in  = SOFreeBSD::obtemNumSlotsIdeal($download_kbps);
				$comando = $ipfw . " pipe " . $pipe_in . " config bw " . $download . "Kbit/s queue " . $slots_in;
				SistemaOperacional::executa($comando);
			}

		}

		public static function deletaRegraBW($id,$baserule, $basepipe_in,$basepipe_out) {
			$ipfw = SoFreeBSD::$IPFW;

			$rule     = (int)($baserule + $id);
			$pipe_in  = (int)($basepipe_in + $id);
			$pipe_out = (int)($basepipe_out + $id);

			// Apaga regras
			$comando = $ipfw . " delete " . $rule;
			SistemaOperacional::executa($comando);

			// Apaga os pipes
			$comando = $ipfw . " pipe " . $pipe_in . " delete";
			SistemaOperacional::executa($comando);

			$comando = $ipfw . " pipe " . $pipe_out . " delete";
			SistemaOperacional::executa($comando);

		}

		public static function adicionaRegraSP($id,$baserule,$rede,$ext_iface) {
			$ipfw = SoFreeBSD::$IPFW;

			$rule     = (int)($baserule + $id);

			$comando = $ipfw . " add " . $rule . " allow ip from " . $rede . " to any keep-state";
			SistemaOperacional::executa($comando);
			$comando = $ipfw . " add " . $rule . " allow ip from any to " . $rede . " recv " . $ext_iface . " keep-state ";
			SistemaOperacional::executa($comando);
		}

		public static function deletaRegraSP($id,$baserule) {
			$ipfw = SoFreeBSD::$IPFW;
			$rule     = (int)($baserule + $id);

			$comando = $ipfw . " delete " . $rule;
			SistemaOperacional::executa($comando);
		}

		public static function obtemEstatisticas() {
			$ipfw = SoFreeBSD::$IPFW;
			$comando = $ipfw . " -b show ";
			
			
			$fd = fopen("/home/mosman/cvs/virtex/teste.ipfw","r");
			
			$retorno = array();
			
			while(($linha=fgets($fd)) && !feof($fd) ) {
				$linha = str_replace("\n","",$linha);
				
				@list($id,$pacotes,$bytes,$rule,$resto) = split("[ ]+",$linha,5);
				$tmp = split("[ ]+",$resto);
				$comentario = $tmp[ count($tmp)-1 ];
				
				//if($id) {
				//	echo "ID: $id / RULE: $rule / COMENTARIO: $comentario\n";
				//}
				
				if( $rule == "pipe" ) {
					//echo "PIPE: $comentario\n";
					@list($usuario,$tipo) = explode("::",$comentario);
					//echo $usuario . "/" . $tipo . "\n";
					$retorno[$usuario][$tipo] = $bytes;
				}
				
				
			}
			
			while(list($usuario,$dados) = each($retorno)) {
				if(!@$retorno[$usuario]["up"])		$retorno[$usuario]["up"] = "0";
				if(!@$retorno[$usuario]["down"])	$retorno[$usuario]["down"] = "0";
			}
			
			fclose($fd);
			reset($retorno);
			return($retorno);
			
		}


		/****************************************************
		 *                                                  *
		 * FUNCOES DE NAT                                   *
		 *                                                  *
		 ****************************************************/

		public static function setNAT($iface){
			$pfctl = SoFreeBSD::$PFCTL;
			$comando = $pfctl . " -Nf- ";

			//FILE *p = popen(comando.c_str(),"w");

			$op = "";
			$op .= "no nat on " . $iface . " from " . $iface . " to any ";
			$op .= "nat on " . $iface . " from {10.0.0.0/8,172.16.0.0/12,192.168.0.0/16} to any -> (" . $iface . ") ";
			
			SistemaOperacional::executa($comando,$op);

		}

		public static function unsetNAT($iface){
			$pfctl = SoFreeBSD::$PFCTL;

			// Flush
			$comando = $pfctl . " -F nat ";
			SistemaOperacional::executa($comando);
		}


		/****************************************************
		 *                                                  *
		 * FUNCOES DE ROTEAMENTO                            *
		 *                                                  *
		 ****************************************************/

		public static function routeAdd($rede,$destino) {
			$route = SistemaOperacional::$ROUTE;

			$comando = $route . " add -net " . $rede . " " . $destino;
			SistemaOperacional::executa($comando);

		}

		public static function routeDelete($rede) {
			$route = SistemaOperacional::$ROUTE;

			$comando = $route . " delete -net " . $rede . " 2>&1 > /dev/null ";
			SistemaOperacional::executa($comando);

		}


		/****************************************************
		 *                                                  *
		 * FUNCOES GERAIS                                   *
		 *                                                  *
		 ****************************************************/

		public static function installDir($target) {
			$install = SistemaOperacional::$INSTALL;

			$comando = $install . " -d " . $target;
			SistemaOperacional::executa($comando);
		}
		

		public static function obtemARP($ip="") {
			$arp = array();
			
			$comando = "/usr/sbin/arp -an";
			
			if( $ip != "-a" ) {
				// Ping para assegurar que o host está ok
				$cmd = "/sbin/ping -c 1 '" . $ip . "' 2>&1 > /dev/null";
				SOFreeBSD::executa($cmd);
				
				$comando .= "|grep '(" . $ip . ")' 2>&1 ";
			}
			
			$arptable = SOFreeBSD::executa($comando);
			
			$linhas = explode("\n",$arptable);
			
			for($i=0;$i<count($linhas);$i++) {
				if( trim($linhas[$i]) ) {
					//@list($shit,$addr,$at,$mac,$on,$on,$iface) = preg_split('/[\s]+/',$linhas[$i]);
					@list($shit,$addr,$at,$mac,$on,$iface,$on) = preg_split('/[\s]+/',$linhas[$i]);
					if( strstr($mac,"incomplete")) {
						$mac = "ARP Não Enviado";
						$iface = "N/A";
					}
					$arp[] = array("addr" => $addr, "mac" => $mac , "iface" => $iface);
				}

			}
			
			
			return($arp);

		}


















		/****************************************************
		 *                                                  *
		 * FUNCOES ESPECIFICAS DO FREEBSD                   *
		 *                                                  *
		 ****************************************************/
		
		// Cálculo do número de slots
		private static function obtemNumSlotsIdeal($banda) {
		   $kbps_geral = 600.00;
		   $pipes = 50.00;
		   $b = $banda;

		   if($banda>$kbps_geral) return $pipes;

		   return( (int)round($pipes/($kbps_geral/$b)) );
		}
	
	
	}

?>
