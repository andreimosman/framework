<?

	class MXMLUtils {
		protected $ptr;

		public function __construct() {
			$this->ptr=0;
		}
		
		/**
		 * Converte um Array para um XML
		 * Funcao recursiva
		 */
		public function a2x($arr,$majortag,$nivel=0) {
			$output = "";
			if( is_array($arr) ) {
				if($nivel==0 ) {
					$output .= '<?xml version="1.0" encoding="ISO-8859-1" ?>' . "\n";

					if( $majortag ) {
						$output .= "<$majortag>\n";
					}
				}
				while(list($vr,$vl)=each($arr)) {
					$att="";
					if((int)($vr) || $vr===0) {
						$att = "i=\"{$vr}\"";
						$vr  = "int";
					}
					
					if(is_array($vl)) {
						$output .= str_repeat(" ",$nivel)."<$vr $att>\n";
						$output .= $this->a2x($vl,$majortag,$nivel+1);
						$output .= str_repeat(" ",$nivel)."</$vr>\n";
					} else {
						if(is_string($vl) ) {
							if( ($vl==""||$vl==null)){
								$vl="<null/>";
							} else {
								$vl=htmlentities($vl);
							}
						}
						$output .= str_repeat(" ",$nivel)."<$vr $att>$vl</$vr>\n";
					}
				}
				if($nivel==0 && $majortag) {
					$output .= "</$majortag>\n";
				}

			}
			
			return($output);
		}
		
		/**
		 * Machine
		 * Processa uma série de instruções e retorna um array
		 */
		protected function machine($instructs,$ptr=0) {
			$arr = array();
			
			$this->ptr=$ptr;
			$brk=false;
			$vl=null;
			
			while($this->ptr<count($instructs)) {
				//echo $instructs[$this->ptr]["tipo"] . ":[".$instructs[$this->ptr]["valor"] ."]:[" . $instructs[$this->ptr]["newline"] . "]\n";
				//echo "--------------------------------------------------\n";
				switch($instructs[$this->ptr]["tipo"]) {
					case 'BEG':
						// Inicio de Valor

						// Pega o nome
						$nome = $instructs[$this->ptr]["valor"];
						$nome = trim(str_replace("<","",str_replace(">","",$nome)));
						
						if( strstr($nome,"int i=") ) {
							$nome=trim(str_replace('"',"",str_replace("int i=","",$nome)));
						}
						
						// Pega o newline
						$nl = $instructs[$this->ptr]["newline"];
						// Pega o valor e armazena para retorno
						$vl = $this->machine($instructs,$this->ptr+1);
						
						/**
						 * Se tem newline eh array
						 */
						if( $nl ) { 
							// ALGUMA COISA ESPECIFICA
							
						} else {
							// Deve ser algum null/vazio tratado errado
							if( is_array($vl) || $vl=="<null/>" ) {
								$vl = "";
							} else {
								$vl=html_entity_decode($vl);
							}
						}
						
						
						$arr[$nome]=$vl;

						break;

					case 'END':
						// Fim de Valor
						$brk=true;
						break;
						
					case 'STR':
						$arr=$instructs[$this->ptr]["valor"];
						break;
					case 'NUL':
						$arr="<null/>";
						break;
						// String
				}

				
				
				if($brk) {
					break;
				}
				$this->ptr++;


			}
			return($arr);
		}
		
		/**
		 * Caminho inverso
		 */
		public function x2a($xml,$majortag="") {
			/**
			 * Pattern: Ou é tag ou é conteudo
			 */
			
			$limpa_pattern = '/^([ ]*)/m';
			$xml=preg_replace($limpa_pattern,"",$xml);
			$tok_pattern = '/<([^>]*)>[\n]?|([^<^>]+)/';
			preg_match_all($tok_pattern,$xml,$matches,PREG_OFFSET_CAPTURE);

			//print_r($matches);
			$instructs=array();
			
			$lasttag="";
			for($i=0;$i<count(@$matches[0]);$i++) {
				$tok=@$matches[0][$i][0];
				//$tok=chop($tok);

				$nl = false;
				
				//echo "TOK: [$tok]\n";
				//echo "----------------\n";
				
				if( strstr($tok,"\n") ) {
					$nl = true;
					$tok=str_replace("\n","",$tok);
				}
				

				$tok=str_replace("\n","",$tok);
				$tipo="STR";
				$ign=false;
				if($tok) {
					if($tok == "<null/>") {
						$tipo="NUL";
					} else {
						if($tok[0] == '<') {
							// TAG
							if( $tok[1] == '?' ) {
								// IGNORAR
								$ign=true;
							} else if($tok[1] == '/') {
								$tipo="END";
								$lasttag="END";
								// NL interessa apenas para BEG
								$nl=false;
							} else {
								$tipo="BEG";
								$lasttag="BEG";
							}
						}
					}
				}
				
				if( !$ign ) {
					if($lasttag=="BEG" || strlen(trim($tok))) {
						$instructs[] = array("tipo" => $tipo, "valor" => $tok, "newline" => $nl);
					} else {

					}
				}
			}

			$arr = $this->machine($instructs);
			return($majortag?@$arr[$majortag]:$arr);
		
		}
	
	}
/**	
	
	$arr = array();
	$arr[] = "Teste";
	$arr[] = "Coisa";
	$arr[] = null;
	$arr[] = array("Fruta" => "abacate", "Coisas" => array("violao","sabonete","treco"));
	$arr[] = array();
	$arr[] = array("coisa feia","coisa bunita", "coisa maomeno");
	//print_r($arr);
					
	$xml = new MXMLUtils();
	$x=$xml->a2x($arr,"lalala");
	//echo($x);
	$a=$xml->x2a($x);
	print_r($a);
	
*/	
		
	


	
	
	
	
?>
