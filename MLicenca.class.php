<?

require_once("MConfig.class.php");

class MLicenca {

	protected $cfg;	
	
	/**
	 * Recebe o arquivo de licen�a
	 */
	public function MLicenca($arquivo) {
		
		
		// Verifica se o arquivo de licen�a existe
		
		
		// Instancia $cfg
		
		
		if( $this->validaArquivo($arquivo) ) {
			$cfg = new MConfig($arquivo);
		}
	
	
	
	}
	
	
	/**
	 * Verifica o c�digo da licen�a
	 */
	protected function validaArquivo() {
	
	
	}







}




?>
