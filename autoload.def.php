<?
/**
 * Autoload
 * definicao do autoload (para carregar arquivo com definicao de classes)
 */



/**
 * Carrega dinamicamente os arquivos de acordo com o nome da classe
 * Verifica de acordo com algumas notações.
 * Caso o aplicativo tenha definido PATH_LIB utiliza ele como base de procura também.
 */

function __autoload($class_name) {

	$possibilidades = array();
	if( defined('PATH_LIB') ) {
		$possibilidades[] = PATH_LIB . "/" . $class_name . ".class.php";
		$possibilidades[] = PATH_LIB . "/" . $class_name . ".php";
	}
	$possibilidades[] = $class_name . ".class.php";
	$possibilidades[] = $class_name . ".php";
	$possibilidades[] = str_replace("_","/",$class_name) . ".php";

	$encontrado = 0;
	for($i=0;$i<count($possibilidades);$i++) {
		if( @include_once($possibilidades[$i]) ) {
			$encontrado = 1;
			break;
		}
	}
	if( !$encontrado ) {
		//die("Classe nao encontrada: $class_name \n");
	}
}


?>
