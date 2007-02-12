<?

  /**
   * Classe para tratamento de excessoes
   */
  
  class MException extends Exception {


    public function __construct($motivo="") {
      parent::__construct($motivo);
    }  
  
  
  }
?>
