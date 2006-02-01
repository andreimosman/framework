<?

require_once("Smarty.class.php");


class MTemplate {

   protected $__tpl;
   
   
   public function MTemplate($template_dir="./") {
   
      // Configurações do Smarty
      $this->__tpl = new Smarty();
      
      $this->__tpl->template_dir = $template_dir;
      $this->__tpl->compile_dir = '/tmp/templates_c';
      
      if( ! file_exists( $this->__tpl->compile_dir ) ) {
         mkdir($this->__tpl->compile_dir,0770,true);
      }

   }
   
   /**
    * Atribui um valor à uma variável interna no template.
    * @param $variavel Nome da Variável.
    * @param $valor    Valor da variável.
    */
   public function atribui($variavel,$valor) {
      // echo "Atribuindo: $variavel = $valor<br>\n";
      return($this->__tpl->assign($variavel,$valor));
   }
   
   /**
    * Processa e exibe um arquivo de template.
    * @param $arquivo Nome do arquivo que será exibido.
    */
   public function exibe($arquivo) {
      // echo "Exibindo arquivo: $arquivo <br>\n";
      return($this->__tpl->display($arquivo));
   }
   
   
   /**
    * Processa e retorna um arquivo de template.
    * @param $arquivo Nome do arquivo que será processado.
    *
    * @return Conteúdo do arquivo já processado pelo sistema de templates.
    */
   public function obtemPagina($arquivo) {
      // echo "Obtendo página: $arquivo<br>\n";
      return($this->__tpl->fetch($arquivo));
   }


}


?>
