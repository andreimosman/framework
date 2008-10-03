<?

	require_once("SistemaOperacional.class.php");

	class SOFreeBSD extends SistemaOperacional {
	
		public static $IPFW 	= "/sbin/ipfw -q";
		public static $PFCTL	= "/sbin/pfctl";
		public static $NTPDATE  = "/usr/sbin/ntpdate";
		public static $ARP      = "/usr/sbin/arp";
		
		// mapa de tabelas (p/ ipfw)
		public static $tablemap = array("desconhecidos" => "1","bloqueados" => "2","suspensos" => "3");
		
		
		protected static $cacheTabela;
		
		
		/****************************************************
		 *                                                  *
		 * FUNCOES DA INTERFACE                             *
		 *                                                  *
		 ****************************************************/
		
		/**
		 * Configura IP na interface
		 */
		public static function ifConfig($iface,$ip="",$mascara="") {
			$comando = SistemaOperacional::$IFCONFIG . " " . $iface . " " . ($ip && $mascara ? "inet alias " . $ip . " netmask " . $mascara : "up");
			return(SistemaOperacional::executa($comando));
		}
		
		/**
		 * Tira o IP da interface
		 */
		public static function ifUnConfig($iface,$ip) {
			if (trim($ip)){
				$comando = SistemaOperacional::$IFCONFIG . " " . $iface . " delete " . $ip;
				return(SistemaOperacional::executa($comando));
			}
		}


		/****************************************************
		 *                                                  *
		 * FUNCOES DO FIREWALL                              *
		 *                                                  *
		 ****************************************************/
		
		/**
		 * Retorna o timestamp caso o registro exista em cache ou vazio caso não exista.
		 */
		public static function cacheTabela($ip="",$oldtime=120) {
			if(!is_array(self::$cacheTabela)) {
				self::$cacheTabela = array();
			}
			
			if( !$ip ) return;
			
			if( @self::$cacheTabela[$ip] && time() > self::$cacheTabela[$ip] + $oldtime ) {
				self::removeCacheTabela($ip);
			}
			
			return(@self::$cacheTabela[$ip]);
		}
		
		public static function adicionaCacheTabela($ip) {
			self::$cacheTabela[$ip] = time();
		}
		
		public static function removeCacheTabela($ip) {
			if(@isset(self::$cacheTabela[$ip])) {
				unset(self::$cacheTabela[$ip]);
			}
		}
		
		public static function adicionaEnderecoTabela($tabela,$ip) {
			
			if( @self::$tablemap[$tabela] ) {
				$ipfw = self::$IPFW;
				$comando = $ipfw . " -q table " . self::$tablemap[$tabela] . " add $ip";
				SistemaOperacional::executa($comando);
			}

			$pfctl = SOFreeBSD::$PFCTL;
			$comando = $pfctl . " -qt $tabela -T add $ip";
			return(SistemaOperacional::executa($comando));
						
		}
		
		public static function removeEnderecoTabela($tabela,$ip) {
			if( @self::$tablemap[$tabela] ) {
				$ipfw = self::$IPFW;
				$comando = $ipfw . " -q table " . self::$tablemap[$tabela] . " delete $ip";
				SistemaOperacional::executa($comando);
			}

			$pfctl = SOFreeBSD::$PFCTL;
			$comando = $pfctl . " -qt $tabela -T delete $ip";
			return(SistemaOperacional::executa($comando));		
		}
		
		public static function listaEnderecosTabela($tabela) {
			$pfctl = SOFreeBSD::$PFCTL;
			$comando = "$pfctl -qt $tabela -T show 2>&1 |/usr/bin/grep -vi altq|/usr/bin/grep -vi pfctl|/usr/bin/sed -E 's/ //g'";
			//echo "CMD: $comando\n\n";
			$dados = SistemaOperacional::executa($comando);
			//$dados = system($comando);
			$dados = trim(chop($dados));
			if( $dados )
				$retorno = explode("\n",$dados);
			else
				$retorno = array();
			
			return($retorno);
		}
		
		public static function verificaEnderecoTabela($tabela,$ip,$oldtime=120) {
			$cache = self::cacheTabela($ip,$oldtime);
			
			if( $cache ) return true;

			$retorno = 0;
			system("/sbin/pfctl -qt $tabela -T test $ip",$retorno);
			
			if( $retorno != 0 ) return false;
			self::adicionaCacheTabela($ip);
			
			return(true);
			
		}

		/**
		 * Adiciona regra com gerenciamento de banda.
		 * Usado pra liberar acesso de clientes banda larga.
		 */
		public static function adicionaRegraBW($id,$baserule,$basepipe_in,$basepipe_out,$int_iface,$ext_iface,$ip,$mac,$upload_kbps,$download_kbps,$username) {
			$ipfw = SOFreeBSD::$IPFW;
			$arp = SOFreeBSD::$ARP;

			$rule     = (int)($baserule + $id);
			$pipe_in  = (int)($basepipe_in + $id);
			$pipe_out = (int)($basepipe_out + $id);

			$upload    = (int)$upload_kbps;
			$download  = (int)$download_kbps;

			
			///////////////////////////////////////////
			// VERIFICACAO DE MAC                    //
			///////////////////////////////////////////

			// Remove o MAC
			self::removeARP($ip);
			
			$mac = trim($mac);

			if( $mac ) {
				// $comando = $ipfw . " add " . $rule . " deny layer2 src-ip " . $ip . " not MAC any " . $mac . " via " . $int_iface;
				self::atribuiARP($ip,$mac);				
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
			if($download){
			   $slots_out = SOFreeBSD::obtemNumSlotsIdeal($download_kbps);
			   $comando = $ipfw . " pipe " . $pipe_out . " config bw " . $download . "Kbit/s queue " . $slots_out;
			   SistemaOperacional::executa($comando);
			}

			if($upload){
				$slots_in  = SOFreeBSD::obtemNumSlotsIdeal($upload_kbps);
				$comando = $ipfw . " pipe " . $pipe_in . " config bw " . $upload . "Kbit/s queue " . $slots_in;
				SistemaOperacional::executa($comando);
			}

		}

		public static function deletaRegraBW($id,$baserule, $basepipe_in,$basepipe_out) {
			$ipfw = SOFreeBSD::$IPFW;

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
			$ipfw = SOFreeBSD::$IPFW;

			$rule     = (int)($baserule + $id);
			
			// Bloquear conexões pela interface externa
			$comando = $ipfw . " add " . $rule . " deny ip from " . $rede . " to any via " . $ext_iface;
			SistemaOperacional::executa($comando);
			$comando = $ipfw . " add " . $rule . " deny ip from any to " . $rede . " via " . $ext_iface;
			SistemaOperacional::executa($comando);
			
			// Liberar acessos partindo da rede de infra
			//$comando = $ipfw . " add " . $rule . " allow ip from " . $rede . " to any keep-state";
			$comando = $ipfw . " add " . $rule . " allow ip from " . $rede . " to any keep-state";
			SistemaOperacional::executa($comando);
			
			// Não estamos liberando mais de qquer cliente pra rede de infra. REGRAS COMENTADAS:
			//$comando = $ipfw . " add " . $rule . " allow ip from any to " . $rede ;
			//SistemaOperacional::executa($comando);
		}

		public static function deletaRegraSP($id,$baserule) {
			$ipfw = SOFreeBSD::$IPFW;
			$rule     = (int)($baserule + $id);

			$comando = $ipfw . " delete " . $rule;
			SistemaOperacional::executa($comando);
		}

		public static function obtemEstatisticas() {
			$ipfw = SOFreeBSD::$IPFW;
			$comando = $ipfw . " -b show ";
			
			
			//$fd = fopen("/home/mosman/cvs/virtex/teste.ipfw","r");
			$fd = popen($comando,"r");
			
			$retorno = array();
			
			while(($linha=fgets($fd)) && !feof($fd) ) {
				$linha = str_replace("\n","",$linha);
				
				@list($id,$pacotes,$bytes,$rule,$resto) = split("[ ]+",$linha,5);
				$tmp = split("[ ]+",$resto);
				$comentario = $tmp[ count($tmp)-1 ];
				
				//if($id) {
				//	echo "ID: $id / RULE: $rule / COMENTARIO: $comentario\n";
				//}
				
				if( $rule == "pipe" || $rule == "allow") {
					//echo "PIPE: $comentario\n";
					@list($usuario,$tipo) = explode("::",$comentario);
					if( $usuario && $tipo ) {
					//echo $usuario . "/" . $tipo . "\n";
						$retorno[$usuario][$tipo] = $bytes;
					}
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
			$pfctl = SOFreeBSD::$PFCTL;
			$comando = $pfctl . " -Nf - ";

			//FILE *p = popen(comando.c_str(),"w");

			$op = "";
			$op .= "no nat on " . $iface . " from " . $iface . " to any ";
			$op .= "nat on " . $iface . " from {10.0.0.0/8,172.16.0.0/12,192.168.0.0/16} to any -> (" . $iface . ") ";
			
			SistemaOperacional::executa($comando,$op);

		}

		public static function unsetNAT($iface){
			$pfctl = SOFreeBSD::$PFCTL;

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

		public static function installDir($target,$mode=755,$uid="",$gid="") {
			$install = SistemaOperacional::$INSTALL;
			
			$op = "";
			
			if( $uid ) {
				$op  .= " -o $uid";
			}
			
			if( $gid ) {
				$op .= " -g $gid";
			}

			$comando = $install . $op . " -m $mode" . " -d " . $target;
			SistemaOperacional::executa($comando);
		}
		
		public static function removeARP($ip) {
			$comando = SOFreeBSD::$ARP . " -dn " . $ip . " 2>&1 > /dev/null ";
			return(SistemaOperacional::executa($comando));		
		}
		
		public static function atribuiARP($ip,$mac) {
			$comando = SOFreeBSD::$ARP . " -Sn " . $ip . " "  . $mac . " only";
			return(SistemaOperacional::executa($comando));
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
					@list($shit,$addr,$at,$mac,$on,$iface,$resto) = preg_split('/[ ]+/',$linhas[$i]);
					if( strstr($mac,"incomplete")) {
						$mac = "ARP Não Enviado";
						$iface = "N/A";
					}
					
					$extra = "";
					if(strstr($resto,"perm")) $extra = "permanente";
					
					$arp[] = array("addr" => $addr, "mac" => $mac , "iface" => $iface,"extra" => $extra);
				}

			}
			
			
			return($arp);

		}


		/**
		 * ntpUpdate
		 *
		 * Atualiza a data via NTP
		 */
		public static function ntpDate($server="") {
			$serverlist = array();
			if( $server ) $serverlist[] = escapeshellcmd( $server );
			$serverlist[] = escapeshellcmd( SistemaOperacional::$DEFAULT_NTP_SERVER );
			$cmd = SoFreeBSD::$NTPDATE . " " . implode(" ", $serverlist) . "> /dev/null 2>&1 ";
			SOFreeBSD::executa($cmd);
		}
		
		/**
		 * fping
		 */
		
		public static function fping($ip,$num_pacotes=2,$tamanho="",$timeout=1200) {
			//$result = exec("/usr/local/sbin/fping -C $num_pacotes -t $timeout -q $ip 2>&1");
			
			$r=array();
			
			$comando = "/sbin/ping -c $num_pacotes ";
			if( $tamanho ) $comando .= " -s $tamanho ";
			$comando .= " $ip ";
			$comando .= " 2>&1 ";
			
			$result = SistemaOperacional::executa($comando);
			
			$linhas = explode("\n",$result);
			
			$lastseq=-1;
			
			for($i=0;$i<count($linhas);$i++) {
				if( strstr($linhas[$i],":") && strstr($linhas[$i],"=") ) {
					// retono de ping
					list($lixo,$info) = explode(":",$linhas[$i]);
					$info=trim($info);

					$tmp = explode(" ",$info);
					for($x=0;$x<count($tmp);$x++) {
						if( strstr($tmp[$x],"=") ) {
							list($vr,$vl) = explode("=",$tmp[$x]);
							$$vr=$vl;
						}
					}

					if( ($lastseq+1) != $icmp_seq ) {
						$num = ($lastseq == -1 ? $icmp_seq : $icmp_seq - 1 - $lastseq);
						for($y=0;$y<$num;$y++) {
							$r[]="-"; // Perda de pacotes
						}
					}

					$r[] = $time;
					$lastseq = $icmp_seq;
				}
			}
			
			$num = ($lastseq == -1 ? $num_pacotes - 1 : $num_pacotes - 1 - $lastseq);

			for($y=0;$y<($num_pacotes - 1 -$lastseq);$y++) {
				$r[]="-"; // Perda de pacotes
			}
			
			
			/**
			 * Tratar resultado
			 */
			//echo $result;
			
			//for($i=0;$i<count($r);$i++) {
			//	echo $r[$i]."\n";
			//}
			//echo "---------------\n";
			
			//list($host,$info) = explode(":",$result,2);
			//$host=trim($host);
			//$info=trim($info);
			
			//$r = explode(" ",$info);
			
			return($r);
		
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
		
		/**
		 * homeDirMake
		 * Cria o diretório home do usuário.
		 */
		public static function homeDirMake($tipo_hospedagem,$base_dir,$dominio,$username,$uid,$gid) {
		
			//echo "TIPO: $tipo_hospedagem\n";
			//echo "BASE: $base_dir\n";
			//echo "DOM.: $dominio\n";
			//echo "USER: $username\n";
			//echo "UID.: $uid\n";
			//echo "GID.: $gid\n";
		
			$diretorios = array();
			$home_dir = "";
		
			if( $tipo_hospedagem == "D" ) {
				
				$home_dir = $base_dir . "/" . $dominio;				
				$diretorios[] = $home_dir;
				$diretorios[] = $home_dir . "/www";
				$diretorios[] = $home_dir . "/log";
			
			} else {
				$home_dir = $base_dir . "/" . $dominio . "/usuarios/" . $username;
				$diretorios[] = $home_dir;
			}
			
			for($i=0;$i<count($diretorios);$i++) {
				// echo "CRIAR: " . $diretorios[$i] . "\n";
				self::installDir($diretorios[$i],"755",$uid,$gid);
			}
			
			return($home_dir);
		
		
		}
		
		public static function apachectl($op="start") {
			self::executa("/usr/local/sbin/apachectl $op");
		}





		/****************************************************
		 *                                                  *
		 * FUNCOES ESPECIFICAS DO FREEBSD                   *
		 *                                                  *
		 ****************************************************/
		
		// Cálculo do número de slots
		private static function obtemNumSlotsIdeal($banda) {
		   //return(50);
		   //$kbps_geral = 600.00;
		   //$pipes = 50.00;
		   //$b = $banda;

		   //if($banda>$kbps_geral) return $pipes;

		   //return( (int)round($pipes/($kbps_geral/$b)) );
		   return( 8 );
		}
	
	
	}

?>
