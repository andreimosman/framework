<?

   require_once("DB.php");
   
   class MDatabaseResult {
   
      protected $__res;
      
      protected $erro;
      
      
      
      
      
      public function MDatabaseResult(&$res) {
         $this->__res = $res;
         
         $this->erro = false;
         
         if(PEAR::isError($this->__res)) {
            $this->erro = true;
         }

      }
      
      
      
      
   
   
   }


   class MDatabase {
      
      protected $db;
      
      
      protected $dsn;		// String de conexão
      
      
      
      
      
      
      
      public function MDatabase($dsn) {
      
         $options = array(
                          'debug' => 0,
                          'portability' => DB_PORTABILITY_ALL
                         );
      
         $this->db =& DB::connect($dsn,$options);
         
         if(PEAR::isError($this->db)) {
            // Não foi possível se conectar ao banco de dados blablabla
            echo "PAU!!!: " . $this->db->getMessage();
         }
      
      
      }
      
      
      
      /**
       * Executa uma consulta no banco de dados.
       */
      public function consulta() {
      
      
      
      
      }
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      /**
       * Desconecta do banco de dados se 
       */
      function __destruct() {
         //if(PEAR::isError($this->db)) {
         //   $this->db->disconnect();
         //}
      }
      
      
      
      
      
   
   
   }

?>
