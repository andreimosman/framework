<?

require_once("MConfig.class.php");

class MLicenca {

	protected $cfg;	
	
	/**
	 * Recebe o arquivo de licença
	 */
	public function MLicenca($arquivo) {
		
		
		// Verifica se o arquivo de licença existe
		
		
		// Instancia $cfg
		
		
		if( $this->validaArquivo($arquivo) ) {
			$cfg = new MConfig($arquivo);
		}
	
	
	
	}
	
	
	/**
	 * Verifica o código da licença
	 */
	protected function validaArquivo() {
	
	
	}







}




?>
