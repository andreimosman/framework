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
			$str = str_replace('"','\"',str_replace('/','\/',str_replace("\n",'\n',str_replace("\r\n","\n",$str))));
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
			/**
			
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
			
			//echo "<pre>"; 
			//echo "ENCODE: [" . implode(",",$retorno) . "]\n"; 
			//echo "</pre>";
			
			return(implode(",",$retorno));
			
			*/
			
			return( function_exists('json_encode') ? json_encode($obj) : self::_json_encode($obj) );
			
			
		}
	
	/** 
	 * Copiado do php.net
	 */
	  protected static function _json_encode($a=false) {
		if (is_null($a)) return 'null';
		if ($a === false) return 'false';
		if ($a === true) return 'true';
		if (is_scalar($a))
		{
		  if (is_float($a))
		  {
			// Always use "." for floats.
			return floatval(str_replace(",", ".", strval($a)));
		  }

		  if (is_string($a))
		  {
			static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
			return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
		  }
		  else
			return $a;
		}
		$isList = true;
		for ($i = 0, reset($a); $i < count($a); $i++, next($a))
		{
		  if (key($a) !== $i)
		  {
			$isList = false;
			break;
		  }
		}
		$result = array();
		if ($isList)
		{
		  foreach ($a as $v) $result[] = self::_json_encode($v);
		  return '[' . join(',', $result) . ']';
		}
		else
		{
		  foreach ($a as $k => $v) $result[] = self::_json_encode($k).':'.self::_json_encode($v);
		  return '{' . join(',', $result) . '}';
		}
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
