<?

require_once("MConfig.class.php");
require_once("MTemplate.class.php");


abstract class MWebApp {

   protected $cfg;
   protected $tpl;
   
   
   
   protected $arquivoTemplate=null;
   
   
   
   protected $_arqConfig;
   
   
   public function MWebApp($arqConfig,$pathTemplate='./') {
      $this->_arqConfig = $arqConfig;

      $this->cfg = new MConfig($arqConfig);
      $this->tpl = new MTemplate($pathTemplate);
      $this->tpl->atribui("pathTemplate", $pathTemplate);
   }
   
   
   
   abstract public function processa($op=null);
   
   
   
   /**
    * Exibe o arquivo de template definido em $this->arquivoTemplate
    */
   public function exibe($arquivo=null) {
      if( $arquivo != null || $this->arquivoTemplate != null ) {
         $this->tpl->exibe(($arquivo != null ? $arquivo : $this->arquivoTemplate));
      }
   }
   
   
   
   public function executa() {
      
      $this->processa();
      $this->exibe();

   }


}

?>
