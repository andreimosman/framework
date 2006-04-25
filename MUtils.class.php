<?

/**
 * Classe com funcoes genericas e facilidades.
 * 
 * Todas as funcoes de uso geral que n�o pertencem a nenhuma classe 
 * especificamente dever�o estar presentes nesta classe.
 *
 * Esta classe dever� conter apenas m�todos est�ticos.
 *
 */


if(!defined('_M_UTILS')) {
	define('_M_UTILS',1);

	class MUtils {
		static function getPwd() {
			$tmp = $_SERVER["REQUEST_URI"];
			$p = 0;
			for($i=0;$i<strlen($tmp);$i++) {
			   if( $tmp[$i] == '/' ) {
				  $p = $i;
			   }
			}

			return(substr($tmp,0,$p));
		}
	}
}

//echo MUtils::getPwd();

?>