<?

if(!defined('_M_RETORNO_BANCO')) {
	define('_M_RETORNO_BANCO',true);

	/**
	 * Processamento dos arquivos de retorno dos bancos.
	 *
	 * Define a base das funcionalidades do sistema de processamento de retorno.
	 *
	 */
	 
	abstract class MRetornoBanco {
		protected $_arquivo;

		/**
		 * Registros
		 */
		protected $registros;
		
	
		public function __construct($arquivo) {
			// Passo 1: Inicializar;
			$this->initVars();
			
			// Configurar o arquivo.
			// TODO: Verificar se existe.
			$this->_arquivo = $arquivo;
			
			// Processar o arquivo;
			$this->processa();
		}
		
		/**
		 * Inicialização das propriedades da classe
		 * Chamar este método à partir da classe filho.
		 */
		public function initVars() {
			$this->_arquivo = "";
			$this->registros = array();
		}
		
		/**
		 * 
		 */
		abstract function processa();
		/**
		 * Verifica assinatura do arquivo
		 * Retorna true por padrão, deve ser implementada nas classes de retorno que utilizam checksum.
		 */
		function checkSum() {
			return true;
		}
		
		/**
		 * Obtem lista de registros
		 */
		abstract function obtemRegistros();
	}
}	
?>
