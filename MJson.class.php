<?


if(!defined('_M_JSON')) {
	define('_M_JSON',1);

	/**
	 * Classe para geração de JSon
	 */
	
	class MJson {
	
		protected static function typeArray($obj) {
			$keys = array_keys($obj);
			
			//print_r($keys);
			
			for($i=0;$i<count($keys);$i++) {
				if( !is_numeric($keys[$i]) ) {
					return("ASSOC");
				}
			}
			
			return("ARRAY");
			
		}
		
		protected static function escape($str) {
			$str = str_replace('"','\"',str_replace('/','\/',str_replace("\n",'\n',$str)));
			return(addcslashes($str,"\r\n"));
		}
		
		protected static function quoteString($str) {
		
			$retorno="";
			
			if( is_null($str) ) {
				$retorno = "null";
			} else {
				$retorno = '"' . self::escape($str) . '"';
			}
			
			return($retorno);
		
		
			
		}
	
		public static function encode($obj) {
			$retorno = array();
			if( is_array($obj) ) {
				// echo "TA: " . self::typeArray($obj);
				$ta = self::typeArray($obj);
				
				// Varre os elementos
				$ret = $ta == "ASSOC"?"{":"[";
				$elementos = array();
				while(list($vr,$vl)=each($obj)) {
					$el = "";
					if(is_array($vl)) {
						$el = (is_numeric($vr)?"":self::quoteString($vr).":") . self::encode($vl);
					} else {
						$el = (is_numeric($vr)?"":self::quoteString($vr).":") . self::quoteString($vl);
					}
					$elementos[] = $el;
				}
				$ret .= implode(",",$elementos);
				$ret .= $ta == "ASSOC"?"}":"]";
				
				$retorno[] = $ret;
			}
			
			return(implode(",",$retorno));
		}
	
	}


}
	/**
	$arr = array(
					array("nome" => "José da Silva", "doc" => "12345"),
					array("nome" => "Manuel Pereira", "doc" => "54321"),
					array("nome" => "José Trovão", "doc" => "15234", "lista" => array(1,2,3,4,5))
				);

	print_r(MJson::encode($arr));
	echo "\n";
	print_r(json_encode($arr));
	echo "\n";
	*/	


?>
