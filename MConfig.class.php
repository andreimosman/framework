<?


require_once("Config.php");


class MConfig {

   protected $__cfg;
   
   public $config;	// Variável p/ acesso externo;
   
   /**
    * Singleton
    */
  
  protected static $instancia = array();
  protected static $lastConfig = "";
  
  /**
   * public constructor (retrocompatibilidade)
   */
   
   
   public function __construct($arquivo) {
   
      $__cfg = new Config();
      $root =& $__cfg->parseConfig($arquivo, 'inifile');
   
      if (PEAR::isError($root)) {
         die('Error while reading configuration: ' . $root->getMessage());
      }
      
      $tmp = $root->toArray();
      $this->config = $tmp["root"];

   }
   
   /**
    * Singleton
    */
   public static function &getInstance($arquivo="") {
    if( !$arquivo ) 
      $arquivo = self::$lastConfig;
    else
      self::$lastConfig = $arquivo;
    
    if( !isset(self::$instancia[$arquivo]) ) {
      self::$instancia[$arquivo] = new MConfig($arquivo);
    }
    return self::$instancia[$arquivo];

   }
   



}



?>
