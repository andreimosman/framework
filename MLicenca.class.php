<?

require_once("MConfig.class.php");

class MLicenca extends MConfig{

	protected $lic;	
	protected $valida;
	protected $arquivo;
	protected $arqCheckSum;
	
	protected $extraOpt;	// Utilizado para a string extra para a geração do localid
	
	/**
	 * Recebe o arquivo de licença
	 */

	public function MLicenca($arquivo="",$extraopt="") {
		
		$this->arquivo = $arquivo;
		$this->extraOpt = $extraopt;
		// Verifica se o arquivo de licença existe
		
		// Instancia $cfg
		
		$lic = array();
		$this->carregaLicenca();
		
		//if( $this->valida ) {
		//	$this->cfg = new MConfig($arquivo);
		//}
		
	}
	
	/**
	 * Retorna a chave criptografica
	 */
	protected function getKey() {
		return("mfw".$this->extraOpt);
	}
	
	/**
	 * Gera a licença em um formato especifico
	 */
	protected function encode($conteudo) {
		$chave = $this->getKey();
		
		srand();
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		
		$cripto = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $chave, $conteudo, MCRYPT_MODE_ECB, $iv);

		return(base64_encode($cripto));

	}
	
	/**
	 * Verifica se o arquivo é valido
	 */
	
	function verificaConteudo($conteudo) {
		if( !strstr($conteudo,"[licenca]") || !strstr($conteudo,"chave") ) {
			return false;
		}
		
		return true;
	
	}

	/**
	 * Recupera a licença.
	 */
	protected function decode($conteudo) {
		$chave = $this->getKey();
		
		srand();
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		
		$conteudo = base64_decode($conteudo);
		$decripto = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $chave, $conteudo, MCRYPT_MODE_ECB, $iv);

		return($decripto);
	}
	
	/**
	 * Gera um arquivo temporário com a licenca "decodada"
	 * Retorna o nome do arquivo temporário
	 */
	protected function decodedFile($arquivo) {
		$fd = @fopen($arquivo,"r");
		if( !$fd ) return "";
		
		$conteudo = fread($fd,filesize($arquivo));
		$dec_cont = $this->decode($conteudo);
		
		if( !$this->verificaConteudo($dec_cont) ) return "";
		
		$arqTmp = tempnam("/tmp","vlf");
		$fdtmp = fopen($arqTmp,"w");
		fwrite($fdtmp,$dec_cont);
		fclose($fdtmp);
		return($arqTmp);
	}
	
	/**
	 * Encoda o arquivo especificado em e salva em um arquivo temporario
	 * Retorna o nome do arquivo temporario
	 */
	public function encodedFile($arquivo) {
		$fd = @fopen($arquivo,"r");
		if( !$fd ) return "";

		$conteudo = fread($fd,filesize($arquivo));
		
		$enc_cont = $this->encode($conteudo);

		$arqTmp = tempnam("/tmp","vlf");
		$fdtmp = fopen($arqTmp,"w");
		fwrite($fdtmp,$enc_cont);
		fclose($fdtmp);
		return($arqTmp);
		
	}
	
	
	/**
	 * Verifica o código da licença
	 */
	protected function carregaLicenca() {
		//$arquivo = $this->arquivo;
		if( !$this->arquivo || !file_exists($this->arquivo) ) {
			$this->valida = false;
			return false;
		}
		
		$arquivo = $this->decodedFile($this->arquivo);
		
		if( !$arquivo ) {
			$this->valida = false;
			return false;
		}
		
		$cfg = new MConfig($arquivo);
		$this->arqCheckSum = $this->checkSum($arquivo);
		
		@unlink($arquivo);
		
		/**
		 * Obtem lista de chaves válidas
		 */
		
		$chaves = $this->obtemChaves();
		
		$chave = $cfg->config["licenca"]["chave"];
		
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
	public function cs($texto) {
		$linhas = explode("\n",$texto);
		$i=0;
		
		$conteudo = "";
		while( $linha = $linhas[$i++] ) {
			if( strstr($linha,"[licenca]") ) {
				break;
			}
			$conteudo .= $linha;

		}

		$conteudo = preg_replace('/[ \t\s\n\r]/',"",trim($conteudo));
		$conteudo = base64_encode($conteudo);
		
		return(md5($conteudo.$this->extraOpt)); // Checksum do arquivo agora considera extraOpt
	}
	
	/**
	 * Checksum de arquivo
	 */
	public function checkSum($arquivo) {
		$fd = fopen($arquivo,'r');
		$texto = fread($fd,filesize($arquivo));
		fclose($fd);
		return($this->cs($texto));
	}
	
	/**
	 * Gera a array de chaves válidas locais
	 */
	public function obtemChaves() {
		
		$hostname = $this->obtemInfoHostname();
		$netinfo = $this->obtemInfoRede();
		
		$chaves = array();
		
		while( list($iface,$dados) = each($netinfo) ) {
			if(@$dados["mac"]) {
				// A interface têm que ter MAC pra gente considerá-la no sistema.
				if(count($dados["ips"])) {
					for($i=0;$i<count($dados["ips"]);$i++) {

					   //echo " -----&gt; " . $dados["ips"][$i] . "(" . $this->localId($hostname,$dados["mac"],$dados["ips"][$i]). " --- " . $this->localIdFormatado($hostname,$dados["mac"],$dados["ips"][$i]) . ") <br>\n";
					   $local_id = $this->localId($hostname,$dados["mac"],$dados["ips"][$i]);
					   //echo "LOCAL ID: $local_id<bR>\n";
					   $chaves[] = $this->geraChave($local_id,$this->arqCheckSum);

					}
				}
			}
		}
		
		
		return($chaves);

	}
	
	/**
	 * Gera a chave com base nos dados enviados
	 */
	public function geraChave($local_id,$checksum) {
	
		$local_id = str_replace(":","",$local_id);
		$local_id = strtoupper($local_id);
	
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
	public function signature($chave) {
		$sig  = "[licenca]\n";
		$sig .= "chave=" . $chave . "\n";
		
		return($sig);

	}
	
	
	/**
	 * Retorna a lista de informações sobre os IDs locais. 
	 */
	
	public function obtemInfoLocalID() {
		$hostname = $this->obtemInfoHostname();
		$inforede = $this->obtemInfoRede();
		
		$retorno = array();
		
		while(list($iface,$dados)=each($inforede)){
			if(@$dados["mac"] && count(@$dados["ips"])){
				$i=0;
				while($ip=@$dados["ips"][$i++]) {
					$retorno[] = array( "interface" => $iface, "mac" => $dados["mac"],
					                    "ip" => $ip, "local_id" => $this->localIdFormatado($hostname,$dados["mac"],$ip) );
					
					//
				}
			}
		}
		
		return($retorno);
	}


	/**
	 * Retorna um array com todas as linhas do resultado de uma execução.
	 */
	protected function executa($comando) {
		$fd = popen($comando,"r");
		$linhas = array();
		while( ($linhas[]=fgets($fd)) && !feof($fd) ) { }
		fclose($fd);
		
		return($linhas);
	}
	
	public function obtemInfoHostname() {
		return(trim(implode('',$this->executa("/bin/hostname"))));
	}
	
	/**
	 * Retorna uma matriz associativa onde o índice é o nome da interface
	 */
	public function obtemInfoRede() {
	
		$mac_re = "/([0-9A-Fa-f]{2}\:){5}([0-9A-Fa-f]{2})/";
		$ip_re = "/(?:\d{1,3}\.){3}\d{1,3}/";

		$iflist = array();
		$iface = "";
		$ifmatch = false;

		$linhas = $this->executa("/sbin/ifconfig");
		
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
	public function localId($hostname,$mac,$ip) {
		// Hostname não mais utilizado
		$hostname="";
		$base = $ip."/".$mac."/".$hostname."/".$this->extraOpt;
		 
		//$hash = strtoupper(dechex(sprintf("%u",crc32($base))));
		
		$hash = md5($base);
		
		$local_id = strtoupper(substr($hash,3,4) . substr($hash,7,4));
		
		
		
		return($local_id);

	}
	
	/**
	 * identificação local formatada
	 */
	
	public function localIdFormatado($hostname,$mac,$ip) {
	
		$local_id = $this->localId($hostname,$mac,$ip);
		$idf = substr($local_id,0,4) . ":" . substr($local_id,4,4);
		
		return($idf);
	
	}



}


?>
