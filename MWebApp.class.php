<?

require_once("MConfig.class.php");
require_once("MTemplate.class.php");


class MWebApp {

   protected $cfg;
   protected $tpl;
   
   
   
   protected $_arqConfig;
   
   
   public function MWebApp($arqConfig) {
      $this->_arqConfig = $arqConfig;

      $cfg = new MConfig($arqConfig);
      $tpl = new MTemplate();




   }
   
   
   public function exibe() {
   
   }




}

?>
