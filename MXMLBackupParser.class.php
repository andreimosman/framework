<?
	require_once("MXMLParser.class.php");
	/**
	 * Parser dos arquivos de dados gerados pelo backup
	 */
	class MXMLBackupParser extends MXMLParser {
		protected $nivel;
		protected $tabela;
		protected $campo;
		protected $valor;
		
		protected $cache;

		/**
		 * Processamento sequencial
		 */
		protected $breakpoint;
		protected $arquivo;
		protected $fd;
		

		public function __construct() {
			parent::__construct();
			$this->nivel = 0;
			$this->tabela = "";
			$this->campo = "";
			$this->initCache();
			$this->arquivo = "";
			$this->fd = 0;
			$this->breakpoint = false;
		}
		
		protected function initCache() {
			$this->cache = array();
		}
		
		protected function addCacheEntry($idx,$val) {
			$this->cache[$idx] = $val;
		}

		function startHandler($xp, $name, $attribs) {
			$this->nivel++;
			$name = strtolower($name);
			
			//echo "nivel: " . $this->nivel . " | name: " . $name . "\n";
			//echo "<$name>\n";
			
			
			switch($this->nivel) {
				case 1:
					// Dados

					break;
				case 2:
					// Tabela

					$this->tabela = $name;
					break;
				case 3:
					// Row

					break;
				case 4:
					// Campo

					$this->campo = $name;
					break;
				case 5:

					$this->valor = null;
					break;
			
			}
			

		}
		
		function endHandler($xp, $name) {
			$name = strtolower($name);
			
			switch($this->nivel) {
				case 1:
					// Dados
					break;
				case 2:
					// Tabela
					$this->tabela = "";
					break;
				case 3:
			 		// Gravar linha no banco
					//echo "GRAVAR: " . $this->tabela . "\n";
					//print_r($this->cache);
					
					$this->breakpoint = true;
					//$this->initCache();

					break;
				case 4:
					// Gravar campo no cache

					$this->addCacheEntry($this->campo,$this->valor);
					
					$this->campo = "";
					$this->valor = "";
					break;
				case 5:
					// Gravar NULL no no cache
					break;
			
			}
			
			--$this->nivel;
			
		}
		
		function cdataHandler($xp, $cdata) {
			// does nothing here, but might e.g. print $cdata
			$this->valor = $cdata;
		}
		
		
		/**
		 * Obtem o próximo registro a ser gravado
		 */
		function getNextRow() {
		
		}


		function processaArquivo($arquivo) {
			$this->arquivo = $arquivo;
			$this->fd = @fopen($this->arquivo,"r");		
		}
		
		function fetch() {
			$retorno = array();
			
			if(!$this->fd || feof($this->fd) ) {
				return null;
			}
			
			
			$texto = "";
			while( ($linha=fgets($this->fd)) && !feof($this->fd) ) {
			
				//echo $this->nivel . "\n";
				//echo "LINHA: $linha\n";
				//$linha = trim($linha);
				//$pattern=
				//$linha="</lalala>";
				
				$linha = trim($linha);
				
				
				//echo "$linha\n";
				
				if(substr($linha,0,2) == "</") {
					//echo $this->tabela . "|" . "$linha\n";
					$linha = str_replace("</","",str_replace(">","",$linha));
					$this->endHandler(null,$linha);				
				} else {
				   $this->setInputString($linha);
				   $this->parse();
				}
				
				
			
				if( $this->breakpoint ) {
					// Desabilita o breakpoint pra compilação continuar.
					$this->breakpoint = false;
					
					//Monta o retorno
					$retorno = array("table" => $this->tabela,"data" => $this->cache);
					
					// Zera o cache
					$this->initCache();
					
					return($retorno);
				
				}
			}
		
		}
		
		
	
	}

?>
