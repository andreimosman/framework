<?

	/**
	 * Classe base para processamento de arquivos de retorno.
	 */
	abstract class MRetorno {

		/**
		 * Arquivo que será processado
		 */
		protected $arquivo;
		
		/**
		 * Registros do arquivo.
		 */
		protected $registros;
		
		/**
		 * Indica se o arquivo processado é válido.
		 */
		protected $valido;
		
		
		/**
		 * Construtor da classe.
		 * @param $arquivo Arquivo que será processado.
		 */
		protected function __construct($arquivo) {
			// Inicializações
			$this->init();
			
			// Setar arquivo que será utilizado.
			$this->arquivo = $arquivo;
		
			// Processar arquivo
			$this->processaArquivo();
		
		}
		
		/**
		 * Retorna a instância do objeto de retorno que trabalha com o formato especificado.
		 *
		 */
		public static function &factory($formato,$arquivo) {
			switch($formato) {
				case 'PAGCONTAS':
					return new MRetornoPAGCONTAS($arquivo);
					
				case 'BBCBR643':
					return new MRetornoBBCBR643($arquivo);
					
				default:
					throw new MException("Formato de Retorno Desconhecido");
					
			
			}
		
		}
		
		
		
		
		/**
		 * Inicializações
		 */
		public function init() {
			$this->arquivo = "";
			$this->registros = array();
			$this->valido = true;
		}
		
		/**
		 * Processa o arquivo.
		 */
		public function processaArquivo() {
			// Abre o arquivo.
			$fd = fopen($this->arquivo,"r");
			if( !$fd ) return;
			
			while(!feof($fd) && $this->valido) {
				$linha = fgets($fd,40960);
				$this->processaLinha($linha);
			}
			
			fclose(fd);
			
			return;

		}
		
		/**
		 * Processa a linha lida e alimenta $this->registros.
		 * Implementação individual de cada formato.
		 * Em caso de problemas seta $this->valid como false.
		 */
		abstract function processaLinha($linha);
		
		/**
		 * Retorna se o arquivo processado é válido.
		 */
		function checkSum() {
			return($this->valido);
		}


		/**
		 * Formata o valor de acordo com o tipo e número de casas decimais.
		 * @param $valor 		Valor a ser formatado.
		 * @param $tipo 		Atualmente somente "bd", indicando que a origem dos dados é um banco de dados.
		 * @param $decimais 	Número de casas decimais.
		 * @return 				Retorna o valor formatado de acordo com as especificações.
		 */
		public function formataValor($valor,$tipo="pt_BR",$decimais=2) {
			$vl = (float) $valor;
			$tamanho = strlen($vl);
			$inteiro = substr($vl,0,$tamanho - $decimais);
			$decimal = substr($vl,-$decimais);
			
			$num = "$inteiro.$decimal";
			
			return($tipo == "bd" ? (float)$num : number_format($num,$decimais,",","."));
		}
		
		/**
		 * Retorna os registros processados do arquivo de retorno.
		 */
		public function obtemRegistros() {
			return($this->registros);
		}
	
	}

?>
