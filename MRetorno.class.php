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
		 * Indica a data de geração do arquivo.
		 */
		protected $data_geracao;
		
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
					
				case 'ITAU':
					return new MRetornoItau($arquivo);
					
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
			$this->data_geracao = "";
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
			
			fclose($fd);
			
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
		
		public function formataData($data,$formatoRetorno="pt_BR") {
			if( strstr($data,"/") ) {
				// Formato DD/MM/AAAA
				list($dia,$mes,$ano) = explode("/",$data);
				
			} else if( strstr($data,"-") ) {
				// Formato AAAA-MM-DD
				list($ano,$mes,$dia) = explode("-",$data);
				
			} else {
			
				if( !(int)$data ) {
					//echo "NOT DATA $data\n";
					return($data);
				}
			
				$dia = substr($data,0,2);
				$mes = substr($data,2,2);
				$ano = substr($data,4);
			}
			
			$ano4d = $ano;
			
			//echo "STRLEN: " . strlen($ano) . "\n";
			
			if( strlen($ano) == 2 ) {
				if( $ano >= 80 ) {
					$ano4d = '19'.$ano;
				} else {
					$ano4d = '20'.$ano;
				}
				
			}
			
			$retorno = "";
			
			switch($formatoRetorno) {
				case 'pt_BR':
					$retorno = "$dia/$mes/$ano4d";
					break;
				case 'bd':
					$retorno = "$ano4d-$mes-$dia";
					break;
				case '6d':
					$retorno = $dia.$mes.$ano;
					break;
				
			}
			
			return $retorno;
			
			
		}
		
		public static function obtemFormatosRetorno() {
			return( array (
							"BBCBR643" => "Banco do Brasil CBR 643",
							"PAGCONTAS" => "Sistema Pag Contas",
							"ITAU" => "Itau CNAB 400"
						) );
		}
		
		/**
		 * Retorna os registros processados do arquivo de retorno.
		 */
		public function obtemRegistros() {
			return($this->registros);
		}

		/**
		 * Retorna a data de geração do arquivo.
		 */
		public function obtemDataGeracao() {
			return($this->data_geracao);
		}

	
	}

?>
