<?
   //require_once("MDatabase.class.php");
   require_once("MTemplate.class.php");
   require_once("MConfig.class.php");
   require_once("MWebApp.class.php");
   


   class MTesteApp extends MWebApp {
   
       public function MTesteApp($arqConfig) {
       
          parent::MWebApp($arqConfig);
          
          $this->arquivoTemplate = "teste.html";
       
       
       }
   
       public function processa() {
       
          $this->tpl->atribui("ze", "Teste Muito Louco");
       
          echo "Faz qquer coisa";
       
       }
   }
   











   $app = new MTesteApp("teste.ini");
   $app->executa();


   
?>
