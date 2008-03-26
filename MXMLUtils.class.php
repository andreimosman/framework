<?

	define('MXMLUTILS_PRIMEIRO_NIVEL',0);

	class MXMLUtils {
		protected $ptr;

		public function __construct() {
			$this->ptr=0;
		}
		
		/**
		 * Funcoes pra criação de TAG
		 */
		public function beginTag($tag) {
			return("<$tag>");
		}
		
		public function endTag($tag) {
			return("</$tag>");
		}
		
		public function intBeginTag($num,$comDelimitadores=true) {
			$texto = 'int i="'.((int)$num).'"';
			if($comDelimitadores) {
				$texto = '<' . $texto . '>';
			}
			
			return($texto);
		}
		
		public function intEndTag() {
			return('</int>');
		}

		public static function htmlnumericentities($str){
		  return preg_replace('/[^!-%\x27-;=?-~ ]/e', '"&#".ord("$0").chr(59)', $str);
		}

		public static function numericentitieshtml($str){
		  return utf8_encode(preg_replace('/&#(\d+);/e', 'chr(str_replace(";","",str_replace("&#","","$0")))', $str));
		}
		
		
		public function headerTag($encoding="ISO-8859-1") {
			return('<?xml version="1.0" encoding="'.$encoding.'" ?>');
		}
		
		/**
		 * Converte um Array para um XML
		 * Funcao recursiva
		 */
		public function a2x($arr,$majortag,$nivel=MXMLUTILS_PRIMEIRO_NIVEL,$exibe_header=true) {
			$output = "";
			if( is_array($arr) ) {
				if($nivel==MXMLUTILS_PRIMEIRO_NIVEL) {
					if( $exibe_header ) {
						$output .= $this->headerTag() . "\n";
					}

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
								//$vl=htmlentities($vl);
								$vl=MXMLUtils::htmlnumericentities($vl);
							}
						}
						$output .= str_repeat(" ",$nivel)."<$vr $att>$vl</$vr>\n";
					}
				}
				if($nivel==MXMLUTILS_PRIMEIRO_NIVEL && $majortag) {
					$tmp = explode(" ",$majortag);
					$output .= "</".$tmp[0].">\n";
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
			
			//echo "MACHINE\n";

			while($this->ptr<count($instructs)) {
				//echo "INSTRUCT: \n";
				//print_r($instructs[$this->ptr]);
				//echo "\n-----------------------\n";
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
								//$vl=html_entity_decode($vl);
								$vl=MXMLUtils::numericentitieshtml($vl);
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
		public function old_x2a($xml,$majortag="") {
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
				//echo "TOK: $tok\n";
				//$tok=chop($tok);

				$nl = false;
				
				//echo "TOK: [$tok]\n";
				//echo "----------------\n";
				
				if( strstr($tok,"\n") ) {
					$nl = true;
					$tok=str_replace("\n","",$tok);
					//$tok=str_replace("\m","",$tok);
					$tok=str_replace("\r","",$tok);
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
			
			//echo "ARR:\n";
			//print_r($instructs);
			

			$arr = $this->machine($instructs);
			
			//echo "ARR: \n";
			//print_r($arr);
			//echo "-----------\n";
			
			
			return($majortag?@$arr[$majortag]:$arr);
		
		}
		
		protected function procX2A($arr) {
			//echo "Processando: \n";
			//print_r($arr);
			//echo "---------------\n";
		
			$retorno = array();
			while(list($vr,$vl)=each($arr)) {
				if( $vr == "value" ) {
					return($vl);
				} else {
					if( is_array($vl) ) {
						if( !count($vl) ) 
							$retorno[$vr] = "";
						else
							$retorno[$vr] = $this->procX2A($vl);
					}
				}
			}
			
			return($retorno);
		}
		
		public function x2a($xml,$majortag="") {		
			$x = $this->xml2array($xml);
			$arr = $this->procX2A($x);
			return($majortag?@$arr[$majortag]:$arr);					
		}

		/** 
		 * xml2array() will convert the given XML text to an array in the XML structure. 
		 * Link: http://www.bin-co.com/php/scripts/xml2array/ 
		 * Arguments : $contents - The XML text 
		 *                $get_attributes - 1 or 0. If this is 1 the function will get the attributes as well as the tag values - this results in a different array structure in the return value. 
		 * Return: The parsed XML in an array form. 
		 */ 
		public function xml2array($contents, $get_attributes=1) { 
			if(!$contents) return array(); 

			if(!function_exists('xml_parser_create')) { 
				//print "'xml_parser_create()' function not found!"; 
				return array(); 
			} 
			//Get the XML parser of PHP - PHP must have this module for the parser to work 
			$parser = xml_parser_create(); 
			xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0 ); 
			xml_parser_set_option( $parser, XML_OPTION_SKIP_WHITE, 1 ); 
			xml_parse_into_struct( $parser, $contents, $xml_values ); 
			xml_parser_free( $parser ); 

			if(!$xml_values) return;//Hmm... 

			//Initializations 
			$xml_array = array(); 
			$parents = array(); 
			$opened_tags = array(); 
			$arr = array(); 

			$current = &$xml_array; 

			//Go through the tags. 
			foreach($xml_values as $data) { 
				unset($attributes,$value);//Remove existing values, or there will be trouble 

				//This command will extract these variables into the foreach scope 
				// tag(string), type(string), level(int), attributes(array). 
				extract($data);//We could use the array by itself, but this cooler. 

				$result = ''; 
				if($get_attributes) {//The second argument of the function decides this. 
					$result = array(); 
					if(isset($value)) $result['value'] = $value; 

					//Set the attributes too. 
					if(isset($attributes)) { 
						foreach($attributes as $attr => $val) { 
							if($get_attributes == 1) $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr' 
							/**  :TODO: should we change the key name to '_attr'? Someone may use the tagname 'attr'. Same goes for 'value' too */ 
						} 
					} 
				} elseif(isset($value)) { 
					$result = $value; 
				} 

				//See tag status and do the needed. 
				if($type == "open") {//The starting of the tag '<tag>' 
					$parent[$level-1] = &$current; 

					if(!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag 
						$current[$tag] = $result; 
						$current = &$current[$tag]; 

					} else { //There was another element with the same tag name 
						if(isset($current[$tag][0])) { 
							array_push($current[$tag], $result); 
						} else { 
							$current[$tag] = array($current[$tag],$result); 
						} 
						$last = count($current[$tag]) - 1; 
						$current = &$current[$tag][$last]; 
					} 

				} elseif($type == "complete") { //Tags that ends in 1 line '<tag />' 
					//See if the key is already taken. 
					if(!isset($current[$tag])) { //New Key 
						$current[$tag] = $result; 

					} else { //If taken, put all things inside a list(array) 
						if((is_array($current[$tag]) and $get_attributes == 0)//If it is already an array... 
								or (isset($current[$tag][0]) and is_array($current[$tag][0]) and $get_attributes == 1)) { 
							array_push($current[$tag],$result); // ...push the new element into that array. 
						} else { //If it is not an array... 
							$current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value 
						} 
					} 

				} elseif($type == 'close') { //End of tag '</tag>' 
					$current = &$parent[$level-1]; 
				} 
			} 

			return($xml_array); 
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
