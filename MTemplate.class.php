<?


if( !defined('_MTEMPLATE_CLASS_PHP') ) {
	define('_MTEMPLATE_CLASS_PHP', 1);

	require_once("MSmarty.class.php");
	require_once("MUtils.class.php");

	class MTemplate {
	   protected static $instancia = array();
	   protected static $template_dir_padrao;
	   protected $__tpl;

	   // Ou usa instancias individuais ou singleton, por isso o __construct eh public.
	   public function __construct($template_dir="./") {

		  // Configura��es do Smarty
		  $this->__tpl = new MSmarty();

		  $wd = MUtils::getPwd();

		  //echo "WD: " . $wd . "<br>\n";
		  //echo "MD: " . md5($wd) . "<br>\n";

		  $this->__tpl->template_dir = $template_dir;
		  $this->__tpl->compile_dir = '/tmp/templates_c/' . md5($wd) . "/" . md5($template_dir);

		  // Tenta criar o diret�rio de compile no sistema

		  system("/usr/bin/install -d " . $this->__tpl->compile_dir);

		  if( ! file_exists( $this->__tpl->compile_dir ) ) {
			 mkdir($this->__tpl->compile_dir,0770,true);
		  }

	   }

	   /**
		* Singleton
		*/
	   public static function &getInstance($template_dir=null) {
		   if( $template_dir == null ) {
			 if( isset(self::$template_dir_padrao) ) {
			   $template_dir = self::$template_dir_padrao;
			 }
		   } else {
			 if( !isset($template_dir_padrao) ) {
			   self::$template_dir_padrao = $template_dir;
			 }
		   }

		   if( $template_dir == null ) {
			 // Retorna erro
		   }

		   if( !isset(self::$instancia[$template_dir]) ) {
			 self::$instancia[$template_dir] = new MTemplate($template_dir);
		   }

		   return self::$instancia[$template_dir];
	   }

	   /**
		* Atribui um valor � uma vari�vel interna no template.
		* @param $variavel Nome da Vari�vel.
		* @param $valor    Valor da vari�vel.
		*/
	   public function atribui($variavel,$valor) {
		  // echo "Atribuindo: $variavel = $valor<br>\n";
		  return($this->__tpl->assign($variavel,$valor));
	   }

	   /**
		* Processa e exibe um arquivo de template.
		* @param $arquivo Nome do arquivo que ser� exibido.
		*/
	   public function exibe($arquivo) {
		  // echo "Exibindo arquivo: $arquivo <br>\n";
		  echo($this->__tpl->fetch($arquivo));
	   }


	   /**
		* Processa e retorna um arquivo de template.
		* @param $arquivo Nome do arquivo que ser� processado.
		*
		* @return Conte�do do arquivo j� processado pelo sistema de templates.
		*/
	   public function obtemPagina($arquivo) {
		  // echo "Obtendo p�gina: $arquivo<br>\n";
		  return($this->__tpl->fetch($arquivo));
	   }


	}
}

?>
