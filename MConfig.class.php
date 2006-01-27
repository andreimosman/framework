<?


require_once("Config.php");


class MConfig {

   protected $__cfg;
   
   protected $config;	// Variável p/ acesso externo;
   
   public function MConfig($arquivo) {
   
      $__cfg = new Config();
      $root =& $__cfg->parseConfig($arquivo, 'inifile');
   
      if (PEAR::isError($root)) {
         die('Error while reading configuration: ' . $root->getMessage());
      }
      
      $tmp = $root->toArray();
      $this->config = $tmp["root"];

   }
   



}



?>
