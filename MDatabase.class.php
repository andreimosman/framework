<?

   require_once("DB.php");
   
   //define('DEBUG',0);
   
   define('MDATABASE_OK',0);
   define('MDATABASE_ERRO_DSN',1);
   define('MDATABASE_ERRO_NAO_CONECTADO',2);
   
   define('MDATABASE_ERRO',255);
   define('MDATABASE_ERRO_QUERY_INVALIDA',254);
   
   
   
   
   
   define('MDATABASE_RETORNA_NREGS',0);
   define('MDATABASE_RETORNA_UMA', 1);
   define('MDATABASE_RETORNA_TODAS',2);
   
   class MDatabase {
      
      protected $bd;		// Objeto de conex�o com o banco de dados
      protected $dsn;		// String de conex�o. Utilizada geralmente para reconex�es perdidas.
      
      protected $erro;		// C�digo do erro.
      protected $erroMSG;	// Mensagem do erro.
      
      protected $debug;		// DEBUG HABILITADO
      protected $arquivoDebug;
      
      
      protected $estaConectado;	// Indica se o objeto est� conectado ao banco de dados.
      

      /**
       * Construtor.
       *
       * Zera a informa��o de erros.
       * Instancia o banco de dados caso tenha recebido o DSN.
       */
      public function MDatabase($dsn=null,$debug=0) {
      	$this->debug = $debug;
      	$this->arquivoDebug = "/tmp/debug.framework.log";
        $this->zeraErro();
        if( $dsn ) {
           $this->conecta($dsn);
        }
         
         
         
      }
      
      /**
       * Destrutor: Fecha o banco de dados.
       */
      function __destruct() {
         $this->desconecta();
      }
      
      /**
       * Debug
       */
      
      public function debug($mensagem) {
      	if( $this->debug ) {
			$fd = fopen($this->arquivoDebug,'a');
			fputs($fd,$mensagem."\n");
			fclose($fd);
		}
      }


      /**
       * Conecta no banco de dados.
       * @param $dsn String DSN para conex�o com o banco de dados.
       */
      public function conecta($dsn=null) {
      
         $this->estaConectado = false;		// N�o est� conectado ainda.
         
         
         if( !$dsn ) {
            // Se n�o recebeu o DSN tenta pegar o valor setado no pr�prio objeto.
            $dsn = $this->dsn;
         } else {
            // Se recebeu atualiza na classe.
            $this->dsn = $dsn;
         }
         
         // Se aindassim n�o conseguiu pegar o DSN seta um erro e retorna.
         if( !$dsn ) {
            atribuiErro(MTEMPLATE_ERRO_DSN,"conecta() n�o recebeu o DSN");
            return(MTEMPLATE_ERRO_DSN);
         }
         

         $options = array(
                          'debug' => 0,
                          'portability' => DB_PORTABILITY_NONE,
                          'seqname_format' => '%s'
                         );

         $this->bd =& DB::connect($dsn,$options);
         
         if(PEAR::isError($this->bd)) {
            // N�o foi poss�vel se conectar ao banco de dados blablabla
            $this->erro    = $this->bd->getCode();
            $this->erroMSG = $this->bd->getMessage();
         } else {
            // Seta o modo de fetch para matriz associativa
            $this->bd->setFetchMode(DB_FETCHMODE_ASSOC);
            $this->estaConectado = true;
            
            $this->erro     = MDATABASE_OK;
            $this->erroMSG = "";
         }

      }
      
      /**
       * Desconecta do banco de dados.
       */
      public function desconecta() {
         if(!PEAR::isError($this->bd)) {
            $this->bd->disconnect();
         }
         $this->estaConectado = false;
      }
      
      /**
       * Indica se o banco de dados est� conectado.
       */
      public function estaConectado() {
         return $this->estaConectado;
      }
      
      
      
      /**
       * Obtem o pr�ximo valor de uma sequence. Caso a sequence n�o exista o sistema ir� cri�-l�.
       */
      
      public function proximoID($nomesequence) {
         return($this->bd->nextID($nomesequence));
      }
      
      
      
      
      
      /**
       * Implementa internamente as fun�oes de consulta(), obtemRegistros() e obtemUnicoRegistro() 
       * com base no tipo de retorno especificado.
       *
       * @param $query Query SQL.
       * @param $tipo_retorno 
       *		- 0 para retorno com o n�mero de linhas afetadas (em inserts e updates).
       *		- 1 para retorno de apenas uma linha (a primeira) de um select
       *		- 2 para retorno de m�ltipas linhas de um select.
       */
      protected function __query($query,$tipo_retorno=MDATABASE_RETORNA_NREGS) {
      
         
         /**
          * Executa apenas se o banco de dados estiver conectado.
          */
         if( !$this->estaConectado() ) {
            atribuiErro(MDATABASE_ERRO_NAO_CONECTADO,"Banco de dados desconectado.");
            return(MDATABASE_ERRO_NAO_CONECTADO);
         }
         
         
         $this->debug("QUERY: " . $query . "\n");

         $res =& $this->bd->query($query);
         
         if(PEAR::isError($res)) {
            $codigo   = MDATABASE_ERRO;
            //$mensagem = "Erro ao processar a query";
            $mensagem = $res->getMessage();
            switch ($res->getCode()) {
               case DB_ERROR_INVALID:
                  $codigo   = MDATABASE_ERRO_QUERY_INVALIDA;
                  //$mensagem = "Query Invalida.";
                  $mensagem = $res->getMessage();
                  break;
            
            }
            $this->atribuiErro($codigo,$mensagem);
            return($codigo);
         }
         
         // N�o deu erro, processa o retorno de acordo com o tipo solicitado.
         
         switch($tipo_retorno) {
            case MDATABASE_RETORNA_NREGS:
               return($res);
               break;		// Neste caso � DB_OK.
            
            case MDATABASE_RETORNA_UMA:
               $linha = $res->fetchRow();
               return($linha ? $linha : array()); // Se $linha n�o for nulo retorna $linha, sen�o retorna um array vazio.
               break;

            case MDATABASE_RETORNA_TODAS:
               $retorno = array();
               while($linha=$res->fetchRow()) {
                  $retorno[]=$linha;
               }
               return($retorno);
         }
         
      }
      

      /**
       * Executa uma consulta no banco de dados.
       */
      public function consulta($query) {
         return( $this->__query($query) );
      }

      
      /**
       * Retorna um array contendo TODOS os registros retornados por uma query SQL
       */
      public function obtemRegistros($query) {
         return( $this->__query($query,MDATABASE_RETORNA_TODAS) );
      }
      
      /**
       * Retorna um array contendo um �NICO registro retornado pela query (por padroniza��o � o primeiro registro).
       */
      public function obtemUnicoRegistro($query) {
         return( $this->__query($query,MDATABASE_RETORNA_UMA) );
      }
      
      
      
      
      /**
       * Atribui um erro ao objeto. Este m�todo s� deve ser usado internamente ou pelas classes-filhas.
       * @param $codigo		C�digo do erro.
       * @param $mensagem	Mensagem de erro.
       */
      protected function atribuiErro($codigo,$mensagem=null) {
         $this->erro    = $codigo;
         $this->erroMSG = $mensagem;
         
         $this->debug("ERRO $codigo: $mensagem");
      }
      
      /**
       * Retorna o c�digo do erro da �ltima opera��o executada.
       */
      public function obtemErro() {
         return($this->erro);
      }
      
      /**
       * Retorna a mensagem de erro da �ltima opera��o executada.
       */
      public function obtemMensagemErro() {
         return($this->erroMSG);
      }
      
      /**
       * Zera as vari�veis internas de erro.
       */
      public function zeraErro() {
         $this->erro    = MDATABASE_OK;
         $this->erroMSG = null;
      }
      
      
      
      public function escape($valor) {
         return($this->bd->escapeSimple($valor));
      }
      
      
   
   
   }

?>
