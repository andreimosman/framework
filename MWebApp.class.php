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

   }
   
   
   
   abstract public function processa();
   
   
   
   /**
    * Exibe o arquivo de template definido em $this->arquivoTemplate
    */
   public function exibe() {
      if( $this->arquivoTemplate != null ) {
         $this->tpl->exibe($this->arquivoTemplate);
      }
   }
   
   
   
   public function executa() {
      
      $this->processa();
      $this->exibe();

   }


}

?>
