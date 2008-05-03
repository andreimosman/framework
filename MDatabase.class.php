<?

	if( !defined('_MDATABASE_CLASS_PHP') ) {
		define('_MDATABASE_CLASS_PHP', 1);

		require_once("MDB2.php");
		require_once("MDatabaseResultado.class.php");

		//define('DEBUG',0);

		define('MDATABASE_OK',0);
		define('MDATABASE_ERRO_DSN',1);
		define('MDATABASE_ERRO_NAO_CONECTADO',2);

		define('MDATABASE_ERRO',255);
		define('MDATABASE_ERRO_QUERY_INVALIDA',254);

		define('MDATABASE_RETORNA_NREGS',0);
		define('MDATABASE_RETORNA_UMA', 1);
		define('MDATABASE_RETORNA_TODAS',2);
		define('MDATABASE_RETORNA_RESULTADO',3);




		class MDatabase {
			protected static $instancia = array();	// Guarda a instancia do objeto para uso em singleton
			protected static $dsn_padrao; // Na ausencia da especificacao do DNS usar o padrao

			protected $bd;		// Objeto de conexão com o banco de dados
			protected $dsn;		// String de conexão. Utilizada geralmente para reconexões perdidas.

			protected $erro;		// Código do erro.
			protected $erroMSG;	// Mensagem do erro.

			protected $debug;		// DEBUG HABILITADO
			protected $arquivoDebug;

			protected $listaSQL;	// Armazena todas as instruções SQL executadas.


			protected $estaConectado;	// Indica se o objeto está conectado ao banco de dados.

			protected $schema;
			protected $options;

			protected static $instructPointer;	// Ponteiro pra processamento de instruções;
			
			protected $cacheTypes;
			
			/**
			 * Construtor.
			 *
			 * Zera a informação de erros.
			 * Instancia o banco de dados caso tenha recebido o DSN.
			 */
			public function __construct($dsn=null,$debug=0) {
				$this->fetch=array();
				//echo "TESTÃO";
				$this->debug = $debug;
				$this->arquivoDebug = "/tmp/debug.framework.log";
				$this->zeraErro();

				if( $dsn ) {
					$this->conecta($dsn);
				}

				$this->zeraListaSQL();
				
				$this->cacheTypes = array();
			}
			
			public static function parseDSN($dsn) {
				return(MDB2::parseDSN($dsn));
			}

			protected static function processArrayInstructs($instructs,$pointer=0) {
					$arrayRetorno = array();
					//$arrayPointer = 0;

					for(self::$instructPointer=$pointer;self::$instructPointer<count($instructs);self::$instructPointer++) {
						//echo $instructs[self::$instructPointer] . "\n";
						switch(substr($instructs[self::$instructPointer],0,3)) {
							case 'BEG':
								//echo "BEG\n";
								$arrayRetorno[] = self::processArrayInstructs($instructs,self::$instructPointer+1);
								break;
							case 'END':
								//echo "END\n";
								return($arrayRetorno);
							case 'TXT':
								$arrayRetorno[] = substr($instructs[self::$instructPointer],4);
								break;
							default:
								break;							
						}
					}
					//echo "FIM";
					return(@$arrayRetorno[0]);

			}


			/**
			 * Trabalha com arrays no estilo do pgsql
			 */
			public static function parseArray($valor) {
				// Tira o {} (pra entrar dentro do array)
				//$valor = substr($valor,1,strlen($valor)-2);
				//echo "Valor: $valor\n";

				$instructs = array();
				$buffer = "";

				$coma="";

				for($i=0;$i<strlen($valor);$i++) {
					switch($valor[$i]) {
						case ',':
							if( $buffer ) {
								$instructs[] = "TXT:" . $buffer;
								$buffer="";
							}
							$instructs[] = "SEP";
							break;
						case '{':
							$buffer = "";
							$instructs[] = 'BEG';
							break;
						case '}':
							if( $buffer ) {
								$instructs[] = "TXT:".$buffer;
								$buffer = "";
							}
							$instructs[] = 'END';
							break;

						// COMAS
						case '"':
						case "'":
							if( trim($buffer) == "" ) {
								$coma = $valor[$i];
								while( ($c=$valor[++$i]) && $i<strlen($valor)) {
									if( $c == $coma ) {
										$coma = "";
										$instructs[] = "TXT:".$buffer;
										$buffer = "";
										break;
									} else {
										$buffer .= $c;
									}

								}
							}
							break;
						default:
							$buffer .= $valor[$i];
							break;

					}

				}

				if( $buffer ) $instructs[] = "TXT:".$buffer;
				$buffer = "";

				return(self::processArrayInstructs($instructs));

			}

			/**
			 * Singleton
			 */
			public static function &getInstance($dsn=null,$debug=0) {
				if( $dsn == null ) {
					// Pegar o DSN padrao
					if( isset(self::$dsn_padrao) ) {
						$dsn = self::$dsn_padrao;
					}
				} else {
					if( !isset(self::$dsn_padrao) ) {
						self::$dsn_padrao = $dsn;
					}
				}

				if( $dsn == null ) {
					// Retorna erro
				}

				if( !isset(self::$instancia[$dsn]) ) {
					self::$instancia[$dsn] = new MDatabase($dsn,$debug);
				}

				return self::$instancia[$dsn];
			}

			public function zeraListaSQL() {
				$this->listaSQL = array();
			}

			public function obtemListaSQL() {
				return($this->listaSQL);
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
			 * @param $dsn String DSN para conexão com o banco de dados.
			 */
			public function conecta($dsn=null) {
				if( self::$dsn_padrao == null ) {
					$this->dsn_padrao = $dsn;
				}

				$this->estaConectado = false;		// Não está conectado ainda.


				if( !$dsn ) {
					// Se não recebeu o DSN tenta pegar o valor setado no próprio objeto.
					$dsn = $this->dsn;
				} else {
					// Se recebeu atualiza na classe.
					$this->dsn = $dsn;
				}

				// Se aindassim não conseguiu pegar o DSN seta um erro e retorna.
				if( !$dsn ) {
					atribuiErro(MTEMPLATE_ERRO_DSN,"conecta() não recebeu o DSN");
					return(MTEMPLATE_ERRO_DSN);
				}



				$options = array(
									  'debug' => 0,
									  'portability' => MDB2_PORTABILITY_NONE,
									  'seqname_format' => '%s',
									  'idxname_format' => '%s'
									 );

				$this->bd =& MDB2::factory($dsn,$options);
				$this->options = $options;

				if(PEAR::isError($this->bd)) {
					// Não foi possível se conectar ao banco de dados blablabla
					$this->erro	 = $this->bd->getCode();
					$this->erroMSG = $this->bd->getMessage();
				} else {
					// Seta o modo de fetch para matriz associativa
					$this->bd->setFetchMode(MDB2_FETCHMODE_ASSOC);
					$this->estaConectado = true;

					$this->erro	  = MDATABASE_OK;
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
			 * Indica se o banco de dados está conectado.
			 */
			public function estaConectado() {
				return $this->estaConectado;
			}



			/**
			 * Obtem o próximo valor de uma sequence. Caso a sequence não exista o sistema irá criá-lá.
			 */

			public function proximoID($nomesequence) {
				return($this->bd->nextID($nomesequence));
			}





			/**
			 * Implementa internamente as funçoes de consulta(), obtemRegistros() e obtemUnicoRegistro() 
			 * com base no tipo de retorno especificado.
			 *
			 * @param $query Query SQL.
			 * @param $tipo_retorno 
			 *		- 0 para retorno com o número de linhas afetadas (em inserts e updates).
			 *		- 1 para retorno de apenas uma linha (a primeira) de um select
			 *		- 2 para retorno de múltipas linhas de um select.
			 */
			protected function __query($query,$tipo_retorno=MDATABASE_RETORNA_NREGS) {
				$this->listaSQL[] = $query;

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
					$codigo	= MDATABASE_ERRO;
					//$mensagem = "Erro ao processar a query";
					$mensagem = $res->getMessage();
					switch ($res->getCode()) {
						case MDB2_ERROR_INVALID:
							$codigo	= MDATABASE_ERRO_QUERY_INVALIDA;
							//$mensagem = "Query Invalida.";
							$mensagem = $res->getMessage();
							break;

					}
					$this->atribuiErro($codigo,$mensagem);
					return($codigo);
				}

				// Não deu erro, processa o retorno de acordo com o tipo solicitado.

				switch($tipo_retorno) {
					case MDATABASE_RETORNA_NREGS:
						return($res);
						break;		// Neste caso é DB_OK.

					case MDATABASE_RETORNA_UMA:
						$linha = $res->fetchRow();
						return($linha ? $linha : array()); // Se $linha não for nulo retorna $linha, senão retorna um array vazio.
						break;

					case MDATABASE_RETORNA_TODAS:
						$retorno = array();
						while($linha=$res->fetchRow()) {
							while(list($campo,$valor) = each($linha)) {
								if( @$valor[0] == '{' && @$valor[ strlen($valor) - 1 ] == '}' ) {
									// É array();
									$linha[$campo] = self::parseArray($valor);								
								}
							}
							$retorno[]=$linha;
						}
						return($retorno);
						break;
					case MDATABASE_RETORNA_RESULTADO:
						return(new MDatabaseResultado($res));
						break;
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
			 * Retorna um resultado (resultset)
			 */
			public function obtemResultado($query) {
				return( $this->__query($query,MDATABASE_RETORNA_RESULTADO) );
			}

			/**
			 * Retorna um array contendo um ÚNICO registro retornado pela query (por padronização é o primeiro registro).
			 */
			public function obtemUnicoRegistro($query) {
				return( $this->__query($query,MDATABASE_RETORNA_UMA) );
			}




			/**
			 * Atribui um erro ao objeto. Este método só deve ser usado internamente ou pelas classes-filhas.
			 * @param $codigo		Código do erro.
			 * @param $mensagem	Mensagem de erro.
			 */
			protected function atribuiErro($codigo,$mensagem=null) {
				$this->erro	 = $codigo;
				$this->erroMSG = $mensagem;

				$this->debug("ERRO $codigo: $mensagem");
			}

			/**
			 * Retorna o código do erro da última operação executada.
			 */
			public function obtemErro() {
				return($this->erro);
			}

			/**
			 * Retorna a mensagem de erro da última operação executada.
			 */
			public function obtemMensagemErro() {
				return($this->erroMSG);
			}

			/**
			 * Zera as variáveis internas de erro.
			 */
			public function zeraErro() {
				$this->erro	 = MDATABASE_OK;
				$this->erroMSG = null;
			}



			public function escape($valor) {
				//return($this->bd->escapeSimple($valor));
				return($this->bd->escape($valor));
			}






			/**
			 * Prepara para operacao reversa
			 */
			public function preparaReverso() {
				$this->bd->loadModule('Reverse');
				$this->bd->loadModule('Manager');
			}

			/**
			 * Obtem a lista de tabelas
			 */
			public function obtemListaTabelas() {
				return($this->bd->manager->listTables());
			}

			/**
			 * Obtem a lista de campos de uma tabela
			 */
			public function obtemListaCampos($tabela) {
				return($this->bd->manager->listTableFields($tabela));
			}

			/**
			 * Obtem a definicão do camp
			 */
			public function obtemDefinicaoCampo($tabela,$campo) {
				$info = $this->bd->reverse->getTableFieldDefinition($tabela, $campo);

				if($info[0]["nativetype"] == "numeric"){
					// Calcular o camanho
					$len = $info[0]["length"];
					if( $len != "-1" ) {
						$sSQL = "SELECT (int4($len)) >> 16 AS inteiro, (int4($len)) & int4('65535') AS decimal";
						$ilen = $this->obtemUnicoRegistro($sSQL);
						$info[0]["length"] = $ilen["inteiro"] . "," . $ilen["decimal"];
					}
				}
				return($info);
			}

			/**
			 * Obtem a lista de sequences
			 */
			public function obtemListaSequencias() {
				return($this->bd->manager->listSequences());
			}

			public function obtemDefinicaoSequencia($sequencia) {
				$sSQL = "SELECT last_value + 1 as start, increment_by as increment FROM $sequencia";
				$def = $this->obtemUnicoRegistro($sSQL);
				return($def);

				//return($this->bd->reverse->getSequenceDefinition($sequencia));
			}

			/**
			 * Integridade referencial. Atualmente só trabalha com chave primária
			 * Obtem as constraints de uma tabela.
			 */
			public function obtemListaConstraints($tabela) {
				//$sSQL = "SELECT conname FROM pg_constraint WHERE type_in
				//return($this->bd->manager->listTableConstraints($tabela));

				$sSQL  = "SELECT ";
				$sSQL .= "   cn.conname ";
				$sSQL .= "FROM ";
				$sSQL .= "   pg_constraint cn INNER JOIN pg_class tl ON( tl.oid = cn.conrelid ) ";
				$sSQL .= "WHERE ";
				$sSQL .= "   tl.relname = '".$this->escape($tabela)."' ";
				$l = $this->obtemRegistros($sSQL);
				$lista = array();
				for($i=0;$i<count($l);$i++) {
					$lista[] = $l[$i]["conname"];	
				}

				return($lista);


			}

			public function obtemAttrInfo($oidTabela,$campo) {
				if( !$campo ) return array();
				$sSQL  = "SELECT ";
				$sSQL .= "   a.attname,t.typname,a.atttypmod ";
				$sSQL .= "FROM ";
				$sSQL .= "   pg_attribute a INNER JOIN pg_type t ON( t.oid = a.atttypid ) ";
				$sSQL .= "WHERE ";
				$sSQL .= "   attrelid='".$this->escape($oidTabela)."'";
				//if( is_numeric($campo) ) {
					$sSQL .= "   AND attnum = '".$this->escape($campo)."'";
				//} else {
				//	$sSQL .= "   AND attname = '".$this->escape($campo)."'";
				//}
				$info = $this->obtemUnicoRegistro($sSQL);
				return(@$info);
			}

			public function obtemDefinicaoConstraint($tabela,$constraint) {
				$sSQL  = "SELECT  ";
				$sSQL .= "   contype as type, ";
				$sSQL .= "   cn.conrelid as table_oid, tl.relname as table,  ";
				$sSQL .= "   cn.confrelid as foreign_oid, tr.relname as foreign_table, ";
				$sSQL .= "   cn.conkey, cn.confkey, ";
				$sSQL .= "   cn.confupdtype as update_action, cn.confdeltype as delete_action, ";
				$sSQL .= "   cn.confmatchtype as match ";
				$sSQL .= "FROM ";
				$sSQL .= "   pg_class tl  ";
				$sSQL .= "   RIGHT OUTER JOIN pg_constraint cn ON(tl.oid = cn.conrelid) ";
				$sSQL .= "   LEFT OUTER JOIN pg_class tr ON(tr.oid = cn.confrelid) ";
				$sSQL .= "WHERE ";
				$sSQL .= "   cn.conname = '".$this->escape($constraint)."' ";
				$info = $this->obtemUnicoRegistro($sSQL);

				/**
				 * Traduz os campos da tabela local
				 */
				$campos = explode(",",str_replace("{","",str_replace("}","",$info["conkey"])));
				$fields = array();
				for($i=0;$i<count($campos);$i++) {
					$infoCampo=$this->obtemAttrInfo($info["table_oid"],$campos[$i]);
					$nomeCampo = @$infoCampo["attname"];

					if(trim($nomeCampo)) {
						$fields[]=$nomeCampo;
					}
				}

				$info["fields"] = $fields;


				/**
				 * Traduz os campos da tabela estrangeira
				 */
				$campos = explode(",",str_replace("{","",str_replace("}","",$info["confkey"])));
				$ffields = array();
				for($i=0;$i<count($campos);$i++) {
					$infoCampo = $this->obtemAttrInfo($info["foreign_oid"],$campos[$i]);
					$nomeCampo = @$infoCampo["attname"];
					if(trim($nomeCampo)) {
						$ffields[]=$nomeCampo;
					}
				}			

				$info["foreign_fields"] = $ffields;
				return($info);

			}

			public function obtemListaIndices($tabela) {
				return($this->bd->manager->listTableIndexes($tabela));
			}

			public function obtemDefinicaoIndice($tabela,$indice) {
				return($this->bd->reverse->getTableIndexDefinition($tabela, $indice));
			}

			public function obtemDefinicaoTabela($tabela) {
				return($this->bd->reverse->tableInfo($tabela));
			}

			/**
			 * Retorna a tabela pai
			 */
			public function obtemHeranca($tabela) {
				$sql = "SELECT  ";
				$sql .= "   cl.oid as id_filha, cl.relname as tabela_filha, inh.inhparent as id_pai, pai.relname as tabela_pai ";
				$sql .= "FROM ";
				$sql .= "   pg_class cl INNER JOIN pg_inherits inh ON(cl.oid = inh.inhrelid)  ";
				$sql .= "   INNER JOIN pg_class pai ON (inh.inhparent = pai.oid) ";
				$sql .= "WHERE ";
				$sql .= "   cl.relname = '$tabela' ";

				//echo $sql."\n\n" ;

				$tabs = $this->obtemUnicoRegistro($sql);

				return(@$tabs["tabela_pai"]);


			}

			/**
			 * Obtem a estrutura do Banco de Dados na representação interna.
			 */
			public function obtemEstrutura() {
				$estrutura = array(
								   "tables" => array(),
								   "sequences" => array(),
								   "languages" => array(),
								   "procedures" => array()
								  );
								  
				$lista_tabelas = $this->obtemListaTabelas();
				// Varre as tabelas
				for($i=0;$i<count($lista_tabelas);$i++) {
					$tabela = array(); // Informações da tabela
					$lista_campos = $this->obtemListaCampos($lista_tabelas[$i]);
					$lista_consts = $this->obtemListaConstraints($lista_tabelas[$i]);
					$lista_index  = $this->obtemListaIndices($lista_tabelas[$i]);

					$heranca      = $this->obtemHeranca($lista_tabelas[$i]);



					//echo "TABELA: ".$lista_tabelas[$i]."\n";
					$tabela["inherits"] = $heranca;

					if( $heranca ) {
						// Excluir os campos herdados.

						$campos_parent = $this->obtemListaCampos($heranca);
						$tmp_campos = array();

						for($x=0;$x<count($lista_campos);$x++) {
							if( !in_array($lista_campos[$x],$campos_parent) ) {
								$tmp_campos[] = $lista_campos[$x];
							}
						}

						$lista_campos = $tmp_campos;

					}


					$campos = array();
					
					for($x=0;$x<count($lista_campos);$x++) {
						$campo = $this->obtemDefinicaoCampo($lista_tabelas[$i],$lista_campos[$x]);
						$campos[$lista_campos[$x]] = $campo[0];
					}
					$tabela["fields"] = $campos;

					$indices = array();
					for($x=0;$x<count($lista_index);$x++) {
						$indice = $this->obtemDefinicaoIndice($lista_tabelas[$i],$lista_index[$x]);
						$indices[$lista_index[$x]] = $indice;
					}
					$tabela["indexes"] = $indices;

					$consts = array();
					for($x=0;$x<count($lista_consts);$x++) {
						$constr = $this->obtemDefinicaoConstraint($lista_tabelas[$i],$lista_consts[$x]);
						$consts[$lista_consts[$x]] = $constr;
					}
					$tabela["constraints"] = $consts;


					// Joga na estrutura
					$estrutura["tables"][$lista_tabelas[$i]] = $tabela;
				}

				$lista_sequencias = $this->obtemListaSequencias();
				for($i=0;$i<count($lista_sequencias);$i++) {
					$seq = $this->obtemDefinicaoSequencia($lista_sequencias[$i]);
					$estrutura["sequences"][$lista_sequencias[$i]] = $seq;
				}
				
				$estrutura["languages"] = $this->externalLanguageList();
				$estrutura["procedures"] = $this->userProcedureList();

				return($estrutura);

			}

			/**
			 * Converte um array (estrutura) em um XML
			 */
			/**
			public function array2XML($estrutura) {
				$tmpArq = tempnam("/tmp","a2x-");
				$fd=fopen($tmpArq,"w");
				if(!$fd) return;

				fputs($fd,'<?xml version="1.0" encoding="ISO-8859-1" ?>'."\n");
				//fputs($fd,"<rss version='2.0'>\n");
				//fputs($fd,"<item>\n");
				fputs($fd,"<database>\n");

				// Varre a tabela
				while(list($table,$tableinfo)=each($estrutura["tables"])) {
					fputs($fd," <table>\n");
					fputs($fd,"   <name>$table</name>\n");
					fputs($fd,"   <declaration>\n");


					// Varre os campos
					while(list($field,$fieldinfo)=each($tableinfo["fields"])) {
						fputs($fd,"     <field>\n");
						fputs($fd,"      <name>$field</name>\n");
						while(list($vr,$vl)=each($fieldinfo)){
							fputs($fd,"      <$vr>$vl</$vr>\n");
						}
						fputs($fd,"     </field>\n");
					}

					// Varre as constrains
					while(list($constr,$constinfo)=each($tableinfo["constraints"])) {
						fputs($fd,"     <constraint>\n");
						fputs($fd,"      <name>$constr</name>\n");
						while(list($vr,$vl)=each($constinfo)){
							if($vr == "fields" || $vr == "foreign_fields") {
								fputs($fd,"      <$vr>\n");
								$nm = substr($vr,0,strlen($vr)-1);
								for($i=0;$i<count($vl);$i++) {
									fputs($fd,"        <$nm>".$vl[$i]."</$nm>\n");	
								}
								fputs($fd,"      </$vr>\n");
							} else {
								fputs($fd,"      <$vr>$vl</$vr>\n");
							}
						}
						fputs($fd,"     </constraint>\n");
					}



					fputs($fd,"   </declaration>\n");
					fputs($fd," </table>\n\n");
				}

				fputs($fd,"</database>\n");
				//fputs($fd,"</item>\n");
				//fputs($fd,"</rss>\n");
				fclose($fd);

				// Exibe
				$fd=fopen($tmpArq,"r");
				while(!feof($fd)) {
					echo fgets($fd);
				}
				fclose($fd);
			}
			*/

			protected function sqlFieldDefinition($fieldinfo) {
				$sql="";
				switch(trim(strtolower($fieldinfo["nativetype"]))) {
					case 'smallint':
					case 'int2':
					case 'integer':
					case 'int':
					case 'int4':
					case 'bigint':
					case 'int8':
					case 'inet':
					case 'cidr':
					case 'macaddr':
					case 'date':
					case 'timestamp':
					case 'timestamp with time zone':
					case 'timestamptz':
					case 'bpchar':
					case 'text':
					case 'bool':
					case 'boolean':
					case 'real':
					case 'float4':
					case 'double precision':
					case 'float8':
					case 'serial':
					case 'serial4':
					case 'bigserial':
					case 'serial8':	
					case 'money':
					case 'box':
					case 'bytea':
					case 'line':
					case 'lseg':
					case 'path':
					case 'point':
					case 'polygon':
					case 'time':
					case 'time with time zone':
					case 'timetz':


						/**
						 * Campos que não recebem o tamanho
						 */
						$sql .= $fieldinfo["nativetype"];
						break;

					case 'char':
					case 'character':
					case 'varchar':
					case 'character varying':
					case 'numeric':
					case 'decimal':
					case 'bit':
					case 'bit varying':
					case 'varbit':
						/**
						 * Campos que recebem um único parametro (tamanho)
						 */
						$sql .= $fieldinfo["nativetype"] . "(" . $fieldinfo["length"] . ")";
						break;


				}		

				if( $fieldinfo["notnull"] ) {
					$sql .= " NOT NULL";
				}

				if( trim($fieldinfo["default"]) ) {
					$sql .= " DEFAULT " . $fieldinfo["default"];
				}

				return($sql);

			}

			/**
			 * 
			 */
			protected function sqlAlterTableAddColumn($table,$field,$fieldinfo) {
				$sql = "ALTER TABLE $table ADD COLUMN $field ";
				$sql .= $this->sqlFieldDefinition($fieldinfo);
				return($sql);
			}

			/**
			 * sqlCreateTable() 
			 */
			public function sqlCreateTable($table,$fields,$heranca="") {
				$sql = "CREATE TABLE $table (\n";

				$cnt=1;

				while(list($field,$fieldinfo)=each($fields)) {
					$sql .= "   " . $field . " ";
					$sql .= $this->sqlFieldDefinition($fieldinfo);

					if( $cnt++ < count($fields) ) {
						$sql .= ",";
					}


					$sql .= "\n";
				}

				$sql .= ")";
				
				if( $heranca ) {
					// echo "\n\nTabela $table herda de $heranca\n\n";
					$sql .= " INHERITS (" . $heranca . ")";
				}
				
				return($sql);
			}

			/**
			 * SQL para adicionar uma constraint.
			 */
			public function sqlAddConstraint($table,$constr,$constinfo) {
				$sql  = "ALTER TABLE ONLY $table ADD CONSTRAINT $constr";
				$PG_ON_FLAGS = array("c" => "cascade", "r" => "restrict");

				switch($constinfo["type"]) {

					case 'p':
						/**
						 * Primary key
						 */
						$sql .= " PRIMARY KEY(" . implode(',',$constinfo["fields"]) .")";
						
						break;
					case 'u':
						$sql .= " UNIQUE(" . implode(',',$constinfo["fields"]) . ")";
						break;
					case 'f':
						$delayed=1;	// Integridade referencial irá rodar depois.
						$sql .= " FOREIGN KEY(" . implode(',',$constinfo["fields"]) . ")";
						$sql .= " REFERENCES " . $constinfo["foreign_table"] . "(" . implode(',',$constinfo["foreign_fields"]) . ")";

						if( @$PG_ON_FLAGS[$constinfo["update_action"]] ) {
							$sql .= " on update " . $PG_ON_FLAGS[$constinfo["update_action"]];
						}

						if( @$PG_ON_FLAGS[$constinfo["delete_action"]] ) {
							$sql .= " on delete " . $PG_ON_FLAGS[$constinfo["delete_action"]];
						}
						break;
				}
				return($sql);
			}
			
			public function sqlCreateIndex($indexName,$indexTable,$indexFields) {
				$sql = "";
				if( $indexName && $indexTable && count($indexFields) ) {
					$sql = 'CREATE INDEX "' . $indexName . '" ON ' . $indexTable . "(" . implode(",",$indexFields) . ")";
				}
				return($sql);
			}
			
			/**
			 * Cria uma language.
			 */
			protected function sqlCreateLanguage($langname) {
				$sql = "CREATE LANGUAGE $langname";
				return($sql);
			}
			
			/**
			 * Cria uma function
			 */
			protected function sqlCreateFunction($funcname,$language,$returns,$argcount, $arglist,$argtypes,$src) {
				$sql = "CREATE FUNCTION $funcname(";
				$params = array();
				
				for($i=0;$i<count($arglist) && $i<count($argtypes) && $i<$argcount;$i++) {
					$params[] = $arglist[$i] . " " . $argtypes[$i];
				}
				
				$sql .= implode(",",$params) . ") RETURNS " . $returns . " AS $$\n";
				$sql .= $src;
				$sql .= "\n$$ LANGUAGE $language";
				
				return($sql);
			}
			
			/**
			 * Apaga uma function
			 */
			protected function sqlDropFunction($funcname, $argcount, $arglist, $argtypes) {
				$sql = "DROP FUNCTION $funcname(";

				$params = array();
				
				for($i=0;$i<count($arglist) && $i<count($argtypes) && $i<$argcount;$i++) {
					$params[] = $arglist[$i] . " " . $argtypes[$i];
				}
				
				$sql .= implode(",",$params) . ")";

				return($sql);
			}
			
			

			/**
			 * Comentarios SQL
			 */
			protected function sqlComment($texto="",$size=70) {
				if(!$texto){
					$texto = "\n";
					return($texto);
				}
				if($texto=="-"){ 
					$texto = str_repeat("-",$size);
				} else if($texto=="=") { 
					$texto = str_repeat("=",$size);
				} else {
					$texto = " " . $texto;
				}

				$retorno = "--" . $texto;
				if( strlen($texto) < $size ) {
					$retorno .= str_repeat(" ",70 - strlen($texto));
				}
				$retorno .= "--\n";

				return($retorno);

			}

			/**
			 * Cria um SQL para insert
			 * @param	$tabela		Nome da tabela
			 * @param	$dados		Matriz associativa "campo" => $dado.
			 */

			public function sqlInsert($tabela,$dados) {
				$sql  = "INSERT INTO $tabela (";
				$keys = array_keys($dados);
				$fields = array();
				$values = array();

				foreach($keys as $k) {
					$fields[] = $k;
					$vl = $dados[$k];
					$values[] = is_null($vl) ? 'NULL' : "'" . $this->bd->escape($vl) . "'";
				}

				$sql .= implode(",",$fields) . ") VALUES ( " . implode(',',$values) . ") ";

				return($sql);

			}

			/**
			 *SQL PARA DELETE BY 'AMARRA'
			 */
			public function sqlDelete($tabela,$condicao) {

				$sql = "DELETE FROM $tabela ";
				$sql .= $this->sqlWhere($condicao);

				return($sql);

			}

			/**
			 * Montagem de clausula WHERE
			 */
			public function sqlWhere($condicao) {
				$sql = "";
				$cnt=0;
				while(list($campo,$valor) = each($condicao)) {
					$cnt++;
					if( $cnt > 1 ) {
						$sql .= " AND";
					}
					$sql .= " $campo " . (is_null($valor)? "is NULL" : "= '" . $this->bd->escape($valor) . "'");
				}

				if( trim($sql) ) {
					$sql = " WHERE $sql";
				}

				return($sql);
			}

			/**
			 * Cria um SQL para update
			 */
			public function sqlUpdate($tabela,$dados,$condicao) {
				$sql = "UPDATE $tabela SET ";

				$cnt=0;
				while(list($campo,$valor) = each($dados) ) {
					$sql .= " $campo = " . (is_null($valor)? "NULL" : "'" . $this->bd->escape($valor) . "'");
					$cnt++;
					if($cnt < count($dados)) {
						$sql .=", ";
					}

				}

				$sql .= $this->sqlWhere($condicao);

				return($sql);

			}
			
			public function sqlAlterTableColumnType($tabela,$campo,$tipoNovo) {
				$sql = "ALTER TABLE $tabela ALTER COLUMN $campo TYPE $tipoNovo";
				return($sql);
			}
			
			public function sqlAlterTAbleColumnDefault($tabela,$campo,$defaultNovo) {
				$sql = "ALTER TABLE $tabela ALTER COLUMN $campo SET DEFAULT $defaultNovo";
				return($sql);
			}

			/**
			 * Cria um SQL para select
			 */
			public function sqlSelect($tabela,$campos,$condicao) {
				$sql = "SELECT " . implode(",",$campos) . " FROM " . $tabela;
				$sql .= $this->sqlWhere($condicao);
				return($sql);
			}
			
			/**
			 * Cria um script de criação do banco
			 *
			 * Retorna um array:
			 *     "begin"	=> Queries para rodar no começo; (geralmente a criacao das sequences).
			 *     "struct"	=> Estrutura (geralmente create table e alter p/ primary e unique).
			 *     "end"	=> Queries para rodar no fim. (geralmente os alter tables de integridade referencial).
			 */
			public function scriptCriacao($estrutura) {

				$script = array("begin" => array(), "struct" => array(), "dados" => array(), "end" => array(), "comments" => array() );
				$delayed_tables = array(); // Tabelas para criar por último (geralmente depois da criação das tabelas).
				$delayed_constraints = array();		// Constraints para executar posterormente (ao termino do script).
				
				/**
				 * SEQUENCES
				 */

				while(list($seq,$seqinfo)=each($estrutura["sequences"])) {
					$sql = "CREATE SEQUENCE $seq INCREMENT BY " . $seqinfo["increment"];
					$script["begin"][] = $sql;
				}

				/**
				 * Tables
				 */
				while(list($table,$tableinfo)=each($estrutura["tables"])) {
					// CREATE TABLE
					
					$sql = $this->sqlCreateTable($table,$tableinfo["fields"],$tableinfo["inherits"]);
					
					if( $tableinfo["inherits"] ) {
						$delayed_tables[] = $sql;
					} else {
						$script["struct"][] = $sql;
					}
					
					if( !@$tableinfo["constraints"] ) $tableinfo["constraints"] = array();

					/**
					 * Constraints
					 * Varre as constraints e joga as foreign keys para o final
					 */
					while(list($constr,$constinfo)=each($tableinfo["constraints"])){
						$sql  = $this->sqlAddConstraint($table,$constr,$constinfo);

						//print_r($constinfo);
						$delayed=0; // Indica se a query será executada agora ou posteriormente

						switch($constinfo["type"]) {
							case 'p':
								break;
							case 'u':
								break;
							case 'f':
								$delayed=1;	// Integridade referencial irá rodar no final.
								break;
						}

						if($delayed) {
							$script["end"][] = $sql;
						} else {
							$script["struct"][] = $sql;
						}

					}

				}
				
				$script["struct"] = array_merge($script["struct"],$delayed_tables);


				//$script = array_merge($script,$delayed_constraints);

				return($script);

			}
			
			protected function _listaIndices($tabName,$tabDef) {
				
				$retorno = array();
				
				if( !@$tabDef["indexes"] ) $tabDef["indexes"] = array();
				
				$indices = array_keys($tabDef["indexes"]);

				for($x=0;$x<count($indices);$x++) {
					// Varre os índices
					$idx = $indices[$x];						
					$fields = array_keys( $tabDef["indexes"][ $idx ]["fields"] );						
					if( !$fields ) continue;						
					$retorno[$idx] = array("table" => $tabName, "fields" => $fields);						
					unset($fields);
					unset($idx);

				}
				unset($tab);
				
				return($retorno);
			
			}

			/**
			 * Gera script de modificação da estrutura
			 * Considera o primeiro parametro como estrutura atual e o segundo como nova estrutura
			 */
			public function scriptModificacao($original,$novo) {
			
				if( !@$original["tables"] ) $original["tables"] = array();
				if( !@$novo["tables"] ) $novo["tables"] = array();
				
				if( !@$original["sequences"] ) $original["sequences"] = array();
				if( !@$novo["sequences"] ) $novo["sequences"] = array();
				
				if( !@$original["languages"] ) $original["languages"] = array();
				if( !@$novo["languages"] ) $novo["languages"] = array();
				
				if( !@$original["procedures"] ) $original["procedures"] = array();
				if( !@$novo["procedures"] ) $novo["procedures"] = array();
				
				//if( !@$original["indexes"] ) $original["indexes"] = array();
				//if( !@$novo["indexes"] ) $novo["indexes"] = array();

				// lista de tabelas
				$tabelasOriginal 	= array_keys(@$original["tables"]);
				$tabelasNovo		= array_keys(@$novo["tables"]);	
				
				/**
				 * Gera a lista de indices
				 */
				
				$original["indexes"] = array();
				for($i=0;$i<count($tabelasOriginal);$i++) {
					$indices = $this->_listaIndices($tabelasOriginal[$i],$original["tables"][$tabelasOriginal[$i]]);
					$original["indexes"] = array_merge($original["indexes"],$indices);
				}

				$indicesOriginal 	= array_keys(@$original["indexes"]);
				
				$novo["indexes"] = array();
				for($i=0;$i<count($tabelasNovo);$i++) {
					$indices = $this->_listaIndices($tabelasNovo[$i],$novo["tables"][$tabelasNovo[$i]]);
					$novo["indexes"] = array_merge($novo["indexes"],$indices);
				}
				
				$indicesNovo		= array_keys(@$novo["indexes"]);	
							
				/**
				 * Fim da lista de indices
				 */
				
				


				// Lista de sequences
				$seqOriginal		= array_keys(@$original["sequences"]);
				$seqNovo			= array_keys(@$novo["sequences"]);

				// Tabelas faltando/sobrando
				$tabelasFaltando = array_diff($tabelasNovo,$tabelasOriginal);
				$tabelasSobrando = array_diff($tabelasOriginal,$tabelasNovo);

				// Sequences faltando/sobrando
				$seqFaltando = array_diff($seqNovo,$seqOriginal);
				$seqSobrando = array_diff($seqOriginal,$seqNovo);
				
				// Lista de Languages 
				$langOriginal		= @$original["languages"];
				$langNovo			= @$novo["languages"];
				
				// Linguagens faltando/sobrando
				$langFaltando = array_diff($langNovo,$langOriginal);
				$langSobrando = array_diff($langOriginal,$langNovo);
				
				// Lista de Procedures
				$procOriginal		= array_keys(@$original["procedures"]);
				$procNovo			= array_keys(@$novo["procedures"]);
				
				// Procedures faltando/sobrando
				$procFaltando = array_diff($procNovo,$procOriginal);
				$procSobrando = array_diff($procOriginal,$procNovo);

				// Define uma array de estrutura

				$arr = array( "tables" => array(), "sequences" => array() );

				$script = array();

				// Armazena informação das tabelas faltando
				while(list($vr,$vl)=each($tabelasFaltando)) {
					$arr["tables"][$vl] = $novo["tables"][$vl];
				}

				// Armazena informação das sequences faltando
				while(list($vr,$vl)=each($seqFaltando)) {
					$arr["sequences"][$vl] = $novo["sequences"][$vl];
				}

				$script = $this->scriptCriacao($arr);


				// Verificar campos faltando
				// Verificar constraints faltando
				// Pegando baseado na tabela original pois as tabelas faltando e sequences já foram resolvidas.
				foreach($tabelasOriginal as $tabela) {
					// CAMPOS
					if( @count($original["tables"][$tabela]["fields"] ) ) {
						$camposOriginal	= array_keys($original["tables"][$tabela]["fields"]);
					} else {
						$camposOriginal = array();
					}
					
					if( @count($novo["tables"][$tabela]["fields"] ) ) {					
						$camposNovo		= array_keys($novo["tables"][$tabela]["fields"]);
					} else {
						$campoNovo = array();
					}

					$camposFaltando = array_diff($camposNovo,$camposOriginal);
					$camposSobrando = array_diff($camposOriginal,$camposNovo);

					//echo "VERIFICANDO: $tabela\n";

					foreach($camposFaltando as $campo) {
						if( @count($novo["tables"][$tabela]["fields"][$campo]) ) {
							$script["struct"][] = $this->sqlAlterTableAddColumn($tabela,$campo,$novo["tables"][$tabela]["fields"][$campo]);
						}
					}

					// INFO -- CAMPOS DIFERENTES

					$comments = array();
					$altertable = array();
					
					$_CHAR = array("varchar", "character varying", "char", "character");
					$_NUM  = array("numeric", "decimal", "money");
					
					foreach($camposOriginal as $campo) {
						//echo "CN: " . $novo["tables"][$tabela]["fields"][$campo] . "\n";
						if( !@$novo["tables"][$tabela]["fields"][$campo] ) {
							continue;
						}
						$diff = array_diff($novo["tables"][$tabela]["fields"][$campo],$original["tables"][$tabela]["fields"][$campo]);

						if( count($diff) ) {
						
							$_novo  = $novo["tables"][$tabela]["fields"][$campo];
							$_atual = $original["tables"][$tabela]["fields"][$campo];
							
							if( $_novo["length"] > $_atual["length"] ) {
								$tipoNovo = "";
								if( $_novo["nativetype"] == $_atual["nativetype"] ) {
									// Permite troca somente para tipos caracter
									if( in_array($_novo["nativetype"], $_CHAR) ) {
										$tipoNovo = $_novo["nativetype"] . "(" . $_novo["length"] . ")";
									} elseif( in_array($_novo["nativetype"],$_NUM) ) {
										$tipoNovo = $_novo["nativetype"] . "(" . $_novo["length"] . ")";
									}
									
									if( $tipoNovo ) {
										$altertable[] = $this->sqlAlterTableColumnType($tabela,$campo,$tipoNovo);
									}
								} else {
									if( substr($_novo["nativetype"],0,3) == "int" ) {
										$altertable[] = $this->sqlAlterTableColumnType($tabela,$campo,$_novo["nativetype"]);
									}
								}
							} elseif( $_atual["default"] != $_novo["default"] ) {
								/**
							
								//print_r($_atual);
								//print_r($_novo);
							
								$novoDefault = $_novo["default"];
								$novoDefault = preg_replace("/::[^)]+/",'$1',$novoDefault);
								$novoDefault = str_replace("((","(",$novoDefault);
								$novoDefault = str_replace("))",")",$novoDefault);

								$atualDefault = $_atual["default"];
								$atualDefault = preg_replace("/::[^)]+/",'$1',$atualDefault);
								$atualDefault = str_replace("((","(",$atualDefault);
								$atualDefault = str_replace("))",")",$atualDefault);
								
								echo "-- " ."DEF: \n";
								echo "-- " .$atualDefault . "\n";
								echo "-- " .$novoDefault . "\n";
								
								echo "-- " .$_atual["default"] . "\n";
								echo "-- " .$_novo["default"] . "\n";
								
								$altertable[] = $this->sqlAlterTAbleColumnDefault($tabela,$campo,$novoDefault);
								*/
							} else {
								$comments[]  = "=";
								$comments[] .= "DIFERENCAS DETECTADAS";
								$comments[] .= "TABELA: $tabela";
								$comments[] .= "CAMPO: $campo";
								$comments[] .= "-";

								while(list($vr,$vl)=each($diff)) {
									$co = $original["tables"][$tabela]["fields"][$campo];
									$cn = $novo["tables"][$tabela]["fields"][$campo];
									//$comments[] .= "-- $vr    -- \n";
									$comments[] .= $vr . "...:   ATUAL: " . $co[$vr] . " | NOVO: " . $cn[$vr];


								}

								$comments[]  = "=";
							}
						}
					}
					
					// print_r($altertable);
					if( count($altertable) ) $script["struct"] = array_merge($script["struct"],$altertable);

					$script["comments"] = array_merge($script["comments"],$comments);


					// CONSTS
					
					//echo "CONSTRAINT: \n";
					//echo " - ORIG: [" . $original["tables"][$tabela]["constraints"] . "]\n";
					//echo " - NOVO: [" . $novo["tables"][$tabela]["constraints"] . "]\n";
					
					$constsOriginal	= (!$original["tables"][$tabela]["constraints"]?array():array_keys($original["tables"][$tabela]["constraints"]));
					$constsNovo		= (!$novo["tables"][$tabela]["constraints"]?array():array_keys($novo["tables"][$tabela]["constraints"]));

					$constsFaltando = array_diff($constsNovo,$constsOriginal);
					$constsSobrando = array_diff($constsOriginal,$constsNovo);

					foreach($constsFaltando as $constr) {
						$sql = $this->sqlAddConstraint($tabela,$constr,$novo["tables"][$tabela]["constraints"][$constr]);
						if($novo["tables"][$tabela]["constraints"][$constr]["type"]=="f") {
							$script["end"][] = $sql;
						} else {
							$script["struct"][] = $sql;
						}
					}
					
					
				}

				// INDICES
				$indicesFaltando = array_diff($indicesNovo,$indicesOriginal);
				$indicesSobrando = array_diff($indicesOriginal,$indicesNovo);

				for($i=0;$i<count($indicesFaltando);$i++) {
					$idx = $indicesFaltando[$i];
					$tabela = $novo["indexes"][ $idx ]["table"];
					$fields = $novo["indexes"][ $idx ]["fields"];
					$sql = $this->sqlCreateIndex($idx,$tabela,$fields);
					$script["end"][] = $sql;
				}
				
				// LANGUAGES
				for($i=0;$i<count($langFaltando);$i++) {
					$sql = $this->sqlCreateLanguage($langFaltando[$i]);
					$script["end"][] = $sql;
				}
				
				// PROCEDURES
				for($i=0;$i<count($procNovo);$i++) {
					$idx = $procNovo[$i];
					$funcname = $idx;
					$returns = $novo["procedures"][ $idx ]["returns"];
					$funclang = $novo["procedures"][ $idx ]["language"];
					$argcount = $novo["procedures"][ $idx ]["argcount"];
					$arglist = $novo["procedures"][ $idx ]["arglist"];
					$argtypes = $novo["procedures"][ $idx ]["argtypes"];
					$src = $novo["procedures"][ $idx ]["src"];
					
					if( in_array($idx,$procOriginal) ) {
						$script["end"][] = $this->sqlDropFunction($funcname, $argcount, $arglist, $argtypes);
					}

					$sql = $this->sqlCreateFunction($funcname, $funclang, $returns, $argcount, $arglist, $argtypes, $src);
					$script["end"][] = $sql;
				}





				return($script);

			}

			/**
			 * Gera o texto de um script.
			 * Na ordem correta.
			 */
			public function script2text($script) {
				$retorno = "";
				$retorno .= $this->sqlComment();
				$retorno .= $this->sqlComment("-");			
				$retorno .= $this->sqlComment("BEGIN");
				$retorno .= $this->sqlComment("-");
				$retorno .= $this->sqlComment();

				for($i=0;$i<count(@$script["begin"]);$i++){
					$retorno .= $script["begin"][$i].";\n";
				}

				$retorno .= $this->sqlComment();
				$retorno .= $this->sqlComment("-");
				$retorno .= $this->sqlComment("STRUCT");
				$retorno .= $this->sqlComment("-");
				$retorno .= $this->sqlComment();

				for($i=0;$i<count($script["struct"]);$i++){
					$retorno .= $script["struct"][$i].";\n";
				}

				$retorno .= $this->sqlComment();
				$retorno .= $this->sqlComment("-");
				$retorno .= $this->sqlComment("END");
				$retorno .= $this->sqlComment("-");
				$retorno .= $this->sqlComment();
				for($i=0;$i<count($script["end"]);$i++){
					$retorno .= $script["end"][$i].";\n";
				}


				$retorno .= $this->sqlComment();
				$retorno .= $this->sqlComment("-");
				$retorno .= $this->sqlComment("COMENTARIOS");
				$retorno .= $this->sqlComment("-");
				$retorno .= $this->sqlComment();
				for($i=0;$i<count($script["comments"]);$i++){
					$retorno .= $this->sqlComment($script["comments"][$i]);
				}

				return($retorno);


			}


			/**
			 * Obtem informações sobre uma tabela.
			 */
			public function tableInfo($tabela) {

				if( PEAR::isError($tabela) ) {
					// Erro !!!
					echo $tabela->getMessage . "\n";
				}


				$rev =& $this->bd->loadModule('Reverse');
				$this->bd->loadModule('Manager');


				$info = $this->bd->reverse->tableInfo($tabela);

				if( PEAR::isError($info) ) {
					echo $info->getMessage() . "\n";
					return;
				}

				$campos = array();
				for($x=0;$x<count($info);$x++) {

					//$k = array_keys($info[$x]);
					//for($i=0;$i<count($k);$i++) {
					//	echo $k[$i] ."=".$info[$x][$k[$i]]. "\n";
					//}
					//echo "-----\n";

					$campos[$x]["nome"] 	  = $info[$x]["name"];
					$campos[$x]["tipo"] 	  = $info[$x]["nativetype"]; // No lugar de type
					$campos[$x]["tamanho"] 	  = @$info[$x]["length"];
					$campos[$x]["flags"]	  = str_replace("public.","",$info[$x]["flags"]);
					//$campos[$x]["notnull"]    = $info[$x]["notnull"];
					//$campos[$x]["nativetype"] = $info[$x]["nativetype"];


				}
				return($campos);
			}

			/**
			 * Inicia a transação.
			 */
			public function begin() {
				//return($this->consulta("BEGIN"));
				return($this->bd->beginTransaction());
			}

			/**
			 * Finaliza e persiste a transação.
			 */
			public function commit() {
				//return($this->consulta("COMMIT"));
				return($this->bd->commit());
			}

			/**
			 * Finaliza de descarta a transação.
			 */
			public function rollback() {
				//return($this->consulta("ROLLBACK");
				return($this->bd->rollback());
			}





			/**************************************************************
			 *                                                            *
			 * ROTINAS DE DUMP (para criação de backups do banco de dados *
			 *                                                            *
			 **************************************************************/
			
			public function dumpDados($tabela) {
				$q = "SELECT * FROM $tabela";
				$registros = $this->obtemRegistros($q);
				
				$retorno = "";
				
				for($i=0;$i<count($registros);$i++) {
					$reg = $registros[$i];
					
					$campos = array();
					$dados = array();
					
					while(list($campo,$valor) = each($reg)) {
						// echo "$campo = $valor\n";
						
						$campos[] = $campo;
						$dados[] = (is_null($valor) ? 'NULL' : "'" . $this->escape($valor) . "'" );
						
					}
					// echo "----\n";
					
					$retorno .= 'INSERT INTO "' . $tabela . '" ( ' . implode(",",$campos) . ") VALUES (" . implode(",",$dados) . ");\n";
					
				}
				
				return($retorno);

			}
			
			public function dumpSequence($sequence) {
			
			}
			
			public function executeSQLScript($arquivo) {
				if( !file_exists($arquivo) || !is_readable($arquivo) ) {
					return false;
				}
				
				$fd = @fopen($arquivo,"r");
				
				if( !$fd ) return false;
				
				$instrucao = "";
				
				$retorno = true;
				
				while(!feof($fd)) {
					$linha = fgets($fd,4096);
					
					$linha = preg_replace("/^(--).*/","",$linha);
					$linha = preg_replace("/^SET.*/","",$linha);
					$matches = array();
					preg_match('/\;$/',$linha,$matches);
					//print_r($matches);
					
					$instrucao .= $linha;
					
					$this->begin();
					
					if( preg_match('/\;$/',$linha ) ) {
						$instrucao = preg_replace('/\;$/',"",$instrucao);
						$instrucao = trim(chop($instrucao));
						
						//echo "[".$instrucao."]\n";
						$this->consulta($instrucao,false); 
						
						//if( $this->consulta($instrucao,false) == 255 ) {
						//	$retorno = false;
						//	$this->rollback();
						//	echo "ERRO\n";
						//	break;
						//}
						
						$instrucao = "";
						
					}
					
					$this->commit();
					
					if( $linha ) {
						//echo "$linha";
					}
					
				}
				fclose($fd);
				
				return($retorno);
			
			}
			
			/**
			 * Obtem o tipo de dados pelo ID
			 */
			public function getTypeDefinition($id) {
			
				if( !@$this->cacheTypes[$id] ) {
					$sql = "SELECT oid, typname, typnamespace, typowner, typlen, typbyval, typtype, typisdefined, typdelim, typrelid, typelem, typinput, typoutput, typreceive, typsend, typanalyze, typalign, typstorage, typnotnull, typbasetype, typtypmod, typndims, typdefaultbin, typdefault FROM pg_type";
					$tipos = $this->obtemRegistros($sql);
					for($i=0;$i<count($tipos);$i++) {
						$this->cacheTypes[ $tipos[$i]["oid"] ] = $tipos[$i]["typname"];
					}
					
				}
				
				return(@$this->cacheTypes[$id]);
			
			}
			
			public function externalLanguageList() {
				$sql = "SELECT lanname, lanispl, lanpltrusted, lanplcallfoid, lanvalidator, lanacl FROM pg_language WHERE lanispl is true";
				$languages = $this->obtemRegistros($sql);
				
				$retorno = array();
				
				for($i=0;$i<count($languages);$i++) {
					$retorno[] = $languages[$i]["lanname"];
				}
				return($retorno);
			}
			
			public function userProcedureList() {
				$sql  = "SELECT ";
				$sql .= "  p.proname, p.prorettype,  ";
				$sql .= "    p.pronamespace, p.proowner, p.prolang, l.lanname, p.proisagg, p.prosecdef,  ";
				$sql .= "    p.proisstrict, p.proretset, p.provolatile, p.pronargs, p.prorettype, t.typname as rettypename, p.proargtypes, p.probin, p.prosrc, p.proacl, ";
				$sql .= "    p.proargnames, u.usename, u.usesysid, u.usecreatedb, u.usesuper, u.usecatupd, u.passwd, ";
				$sql .= "    u.valuntil, u.useconfig ";
				$sql .= "FROM ";
				$sql .= "    pg_proc p INNER JOIN pg_user u ON (p.proowner = u.usesysid) ";
				$sql .= "    INNER JOIN pg_language l on (p.prolang = l.oid) ";
				$sql .= "    INNER JOIN pg_type t on (p.prorettype = t.oid) ";				
				$sql .= "WHERE ";
				$sql .= "   u.usename not in ('pgsql', 'postgresql', 'postgres', '_postgres', '_pgsql', '_postgresql') ";
				
				$procs = $this->obtemRegistros($sql);
				
				$retorno = array();
				
				for($i=0;$i<count($procs);$i++) {
					$tipos = explode(" ",trim($procs[$i]["proargtypes"]));

					for($x=0;$x<count($tipos);$x++) {
						$tipos[$x] = $this->getTypeDefinition($tipos[$x]);
					}
					
					$procs[$i]["proargtypes"] = $tipos;
					unset($tipos);
					
					$retorno[ $procs[$i]["proname"] ] = array(
											"name" => $procs[$i]["proname"],
											"language" => $procs[$i]["lanname"],
											"returns" => $procs[$i]["rettypename"],
											"argcount" => $procs[$i]["pronargs"],
											"arglist" => $procs[$i]["proargnames"],
											"argtypes" => $procs[$i]["proargtypes"],
											"src" => $procs[$i]["prosrc"]
										);
					
					//
					
					
					
				
				}

				return($retorno);

			}
			
		}
		
	}

//$dsn="pgsql://virtex:vtx123@192.168.0.1/virtex";
//$dsn="pgsql://postgres:xingling@192.168.0.1/teste";

//$tmp = MDatabase::getInstance($dsn);


//$tmp->userProceduresList();
//$tmp->externalLanguageList();

//echo "<pre>"; 
//$tmp->preparaReverso();
//$struct = $tmp->obtemEstrutura();
//print_r($struct);
//$tmp->scriptModificacao(array(),$struct);
//print_r($tmp->script2text($tmp->scriptModificacao($struct,$struct)));

//echo "</pre>"; 

//$struct = $tmp->obtemEstrutura();

//$tmp->scriptModificacao(array(),$struct);

//echo "<pre>";
//echo $tmp->script2text($tmp->scriptModificacao(array(),$struct));
//echo "</pre>"; 
// print_r($tmp);
?>
