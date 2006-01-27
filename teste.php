<?

   require_once("MTemplate.class.php");
   require_once("MConfig.class.php");
   
   
   $cfg = new MConfig("teste.ini");
   
   //echo $cfg->cfg["root"]["global"]["nome"] . "<br>\n";
   

   $tpl = new MTemplate();

   $tpl->atribui("ze",$cfg->config["global"]["nome"]);
   //$tpl->exibe("teste.html");
   
   $pg = $tpl->obtemPagina("teste.html");
   
   
   
   echo "<br>PG (ini):<br>\n-----------------------<br>\n";
   echo $pg;
   echo "<br>PG (fim):<br>\n-----------------------<br>\n";

   
   
?>
