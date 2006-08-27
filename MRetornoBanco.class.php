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
		//abstract function obtemRegistros();





		public function formataValor($valor,$tipo="pt_BR",$decimais=2) {
			$vl = (float) $valor;
			$tamanho = strlen($vl);
			$inteiro = substr($vl,0,$tamanho - $decimais);
			$decimal = substr($vl,-$decimais);
			
			$num = "$inteiro.$decimal";
			
			/**
			echo "VALOR: $valor<br>\n";
			echo "VL: $vl<br>\n";
			echo "TAM: $tamanho<br>\n";
			echo "INT: $inteiro<br>\n";
			echo "DEC: $decimal<br>\n";
			echo "NUM: $num<br>\n";
			echo "<hr>\n";
			*/
			
			

			return($tipo == "bd" ? (float)$num : number_format($num,$decimais,",","."));
		}
		
		public function obtemRegistros() {
			return($this->registros);
		}









	}
}	
?>
