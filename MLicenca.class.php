<?

require_once("MConfig.class.php");

class MLicenca extends MConfig{

	protected $lic;	
	protected $valida;
	protected $arquivo;
	protected $arqCheckSum;
	
	/**
	 * Recebe o arquivo de licença
	 */

	public function MLicenca($arquivo) {
		
		$this->arquivo = $arquivo;
		// Verifica se o arquivo de licença existe
		
		// Instancia $cfg
		
		$lic = array();
		$this->carregaLicenca($arquivo);
		
		//if( $this->valida ) {
		//	$this->cfg = new MConfig($arquivo);
		//}
		
	}
	
	
	/**
	 * Verifica o código da licença
	 */
	protected function carregaLicenca($arquivo) {
		if( !file_exists($arquivo) ) {
			$this->valida = false;
			return false;
		}
		
		$cfg = new MConfig($arquivo);
		$this->arqCheckSum = $this->checkSum($arquivo);
		
		/**
		 * Obtem lista de chaves válidas
		 */
		
		$chaves = $this->obtemChaves();
		
		$chave = $cfg->config["licenca"]["chave"];
		
		//echo "CHARQ: " . $chave . "<br>\n";
		
		for($i=0;$i<count($chaves);$i++) {
			//echo "CHAVE: " . $chaves[$i] . "<br>\n";
			if( trim($chave) == trim($chaves[$i]) ) {
				//echo "CHAVE VALIDA<Br>\n";
				$this->valida = true;
				$this->lic = $cfg->config;
				
				
				while(list($grp,$op) = each($this->lic)) {
				
					while(list($vr,$vl) = each($op)) {
						if( is_array($vl) ) {
							$this->lic[$grp][$vr] = implode(",",$vl);
						}
					}
				
				}
				
				
				
				return true;
			}
		}
		
		//echo "CHAVE INVALIDA<br>\n";
		
		return false;
		
		
		
		/**
		if( $cs != $cfg->config["licenca"]["chave"] ) {
			$this->valida = false;
			return false;
		}
		
		$this->lic = $cfg->config;
		
		$this->valida = true;
		*/
		return true;
	}
	
	public function isValid() {
		return $this->valida;
	}
	
	public function obtemLicenca() {
		//return $this->valida ? array() : $this->cfg->config;
		return $this->lic;
	}
	
	/**
	 * CheckSum texto. 
	 */
	public static function cs($texto) {
		$linhas = explode("\n",$texto);
		$i=0;
		
		$conteudo = "";
		while( $linha = $linhas[$i++] ) {
			if( strstr($linha,"[licenca]") ) {
				break;
			}
			$conteudo .= $linha;

		}

		$conteudo = preg_replace("/[\s\n\r]/","",$conteudo);
		$conteudo = base64_encode($conteudo);
		
		return(md5($conteudo));
	}
	
	/**
	 * Checksum de arquivo
	 */
	public static function checkSum($arquivo) {
		$fd = fopen($arquivo,'r');
		$texto = fread($fd,filesize($arquivo));
		fclose($fd);
		return(MLicenca::cs($texto));
	}
	
	/**
	 * Gera a array de chaves válidas locais
	 */
	public function obtemChaves() {
		
		$hostname = MLicenca::obtemInfoHostname();
		$netinfo = MLicenca::obtemInfoRede();
		
		$chaves = array();
		
		while( list($iface,$dados) = each($netinfo) ) {
			if(@$dados["mac"]) {
				// A interface têm que ter MAC pra gente considerá-la no sistema.
				if(count($dados["ips"])) {
					for($i=0;$i<count($dados["ips"]);$i++) {

					   //echo " -----&gt; " . $dados["ips"][$i] . "(" . MLicenca::localId($hostname,$dados["mac"],$dados["ips"][$i]). " --- " . MLicenca::localIdFormatado($hostname,$dados["mac"],$dados["ips"][$i]) . ") <br>\n";
					   $local_id = MLicenca::localId($hostname,$dados["mac"],$dados["ips"][$i]);
					   //echo "LOCAL ID: $local_id<bR>\n";
					   $chaves[] = MLicenca::geraChave($local_id,$this->arqCheckSum);

					}
				}
			}
		}
		
		
		return($chaves);

	}
	
	/**
	 * Gera a chave com base nos dados enviados
	 */
	public static function geraChave($local_id,$checksum) {
	
		$local_id = str_replace(":","",$local_id);
	
		$base1 = substr($local_id,0,4) . $checksum;
		$p1 = substr(md5($base1),3,4);

		$base2=substr($local_id,4,4) . $p1;
		$p2 = substr(md5($base2),5,4);

		$base3 = $p1 . $p2;
		$p3 = substr( md5($base3), 4,4);
		
		return(strtoupper($p1.":".$p2.":".$p3));
		
	}
	
	/**
	 * Retorna a assinatura para ser utilizada no arquivo de licença
	 */
	public static function signature($chave) {
		$sig  = "[licenca]\n";
		$sig .= "chave=" . $chave . "\n";
		
		return($sig);

	}
	
	
	/**
	 * Retorna a lista de informações sobre os IDs locais. 
	 */
	
	public static function obtemInfoLocalID() {
		$hostname = MLicenca::obtemInfoHostname();
		
		$inforede = MLicenca::obtemInfoRede();
		
		$retorno = array();
		
		while(list($iface,$dados)=each($inforede)){
			if(@$dados["mac"] && count(@$dados["ips"])){
				$i=0;
				while($ip=@$dados["ips"][$i++]) {
					$retorno[] = array( "interface" => $iface, "mac" => $dados["mac"],
					                    "ip" => $ip, "local_id" => MLicenca::localIdFormatado($hostname,$dados["mac"],$ip) );
					
					//
				}
			}
		}
		
		return($retorno);
	}





	/**
	 * Retorna um array com todas as linhas do resultado de uma execução.
	 */
	protected static function executa($comando) {
		$fd = popen($comando,"r");
		$linhas = array();
		while( ($linhas[]=fgets($fd)) && !feof($fd) ) { }
		fclose($fd);
		
		return($linhas);
	}
	
	public static function obtemInfoHostname() {
		return(trim(implode('',MLicenca::executa("/bin/hostname"))));
	}
	
	/**
	 * Retorna uma matriz associativa onde o índice é o nome da interface
	 */
	public static function obtemInfoRede() {
	
		$mac_re = "/([0-9A-Fa-f]{2}\:){5}([0-9A-Fa-f]{2})/";
		$ip_re = "/(?:\d{1,3}\.){3}\d{1,3}/";

		$iflist = array();
		$iface = "";
		$ifmatch = false;

		$linhas = MLicenca::executa("/sbin/ifconfig");
		
		//echo "CL: " . count($linhas);
		$i=0;

		while( $linha = $linhas[$i++] ) {
			//echo $linha;
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

			if( $ifmatch ) {
				$iface = str_replace(":","",trim($iface));
				$iflist[$iface] = array();
				$iflist[$iface]["ips"] = array();
				$ifmatch = false;
			}

			$matches = array();

			preg_match($ip_re,$linha,$matches,PREG_OFFSET_CAPTURE);
			if(count($matches)) {
				$iflist[$iface]["ips"][] = $matches[0][0];
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
	 * retorna a identificação local para o conjunto especificado
	 */
	public static function localId($hostname,$mac,$ip) {
		$base = $ip."/".$mac."/".$hostname;
		 
		//$hash = strtoupper(dechex(sprintf("%u",crc32($base))));
		
		$hash = md5($base);
		
		$local_id = strtoupper(substr($hash,3,4) . substr($hash,7,4));
		
		
		
		return($local_id);

	}
	
	/**
	 * identificação local formatada
	 */
	
	public static function localIdFormatado($hostname,$mac,$ip) {
	
		$local_id = MLicenca::localId($hostname,$mac,$ip);
		$idf = substr($local_id,0,4) . ":" . substr($local_id,4,4);
		
		return($idf);
	
	}



}


?>
