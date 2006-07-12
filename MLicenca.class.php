<?

require_once("MConfig.class.php");

class MLicenca extends MConfig{

	protected $lic;	
	protected $valida;
	protected $arquivo;
	
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
		
		$cs = $this->checkSum($arquivo);
		
		
		if( $cs != $cfg->config["licenca"]["chave"] ) {
			$this->valida = false;
			return false;
		}
		
		$this->lic = $cfg->config;
		
		$this->valida = true;
		return true;
	}
	
	public function isValid() {
		return $this->valida;
	}
	
	public function obtemLicenca() {
		//return $this->valida ? array() : $this->cfg->config;
		return $this->lic;
	}
	
	protected function checkSum($arquivo) {
		$fd = fopen($this->arquivo,'r');
		
		$conteudo = "";
		while(($linha=fgets($fd)) && !feof($fd)) {
			//echo $linha;
			if( strstr($linha,"[licenca]") ) {
				break;
			}
			$conteudo .= $linha;
		}
		
		// Prepara o conteudo pra checksum
		//$conteudo = str_replace(" ","",$conteudo);
		//$conteudo = str_replace("\n","",$conteudo);
		$conteudo = preg_replace("/[\s\n\r]/","",$conteudo);
		$conteudo = base64_encode($conteudo);
		
		//echo md5($conteudo)."<br>\n";
		
		return(md5($conteudo));
		
		
		
		//echo nl2br(base64_decode($conteudo));
	}



}


?>
