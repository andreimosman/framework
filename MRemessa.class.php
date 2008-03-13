<?

	require_once("MBanco.class.php");

	/**
	 * Classe base para geração de arquivos de remessa
	 */
	class MRemessa extends MBanco {
	
		public function __construct() {
		
		}


		/**
		 * Retorna a instância do objeto de retorno que trabalha com o formato especificado.
		 *
		 */
		public function &factory($tipo) {
			switch($tipo) {
			
				case 'ITAU':
				case '341':
				case '0341':
					return new MRemessaItau();
					break;
					
				default:
					throw new Exception("Formato de Remessa Não Encontrado");
			
			
			}
		
		}
		
		



	
	}


?>
