<?

/**
 * Classe com funcoes genericas e facilidades.
 * 
 * Todas as funcoes de uso geral que não pertencem a nenhuma classe 
 * especificamente deverão estar presentes nesta classe.
 *
 * Esta classe deverá conter apenas métodos estáticos.
 *
 */


if(!defined('_M_UTILS')) {
	define('_M_UTILS',1);

	class MUtils {
		static function getPwd() {
			$tmp = @$_SERVER["REQUEST_URI"];
			if(!$tmp) {
				$tmp = @$_SERVER["PHP_SELF"];

				if( $tmp[0] != '/' ) { 
					$tmp =  @$_SERVER["PWD"] ."/".$tmp;
					// Tratando ".."
					$t = explode("/",$tmp);

					$pt = array();
					for($i=0;$i<count($t);$i++) {
						if( $t[$i] == ".." ) {
							array_pop($pt);
						} else {
							array_push($pt,$t[$i]);
						}
					}
					
					$tmp = implode("/",$pt);
					
				}
			}
			
			
			$p = 0;
			for($i=0;$i<strlen($tmp);$i++) {
			   if( $tmp[$i] == '/' ) {
				  $p = $i;
			   }
			}

			return(substr($tmp,0,$p));
		}
		
		/**
		 * Configura o path
		 */
		static function setIncludePath() {
			$incpath = get_include_path();
			set_include_path($incpath.':'.MUtils::getPwd());
		}



	}


}



//echo MUtils::getPwd();

?>
