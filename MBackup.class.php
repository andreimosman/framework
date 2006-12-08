<?

	require_once("MDatabase.class.php");
	require_once("MXMLUtils.class.php");
	require_once("MXMLBackupParser.class.php");

	/**
	 * Fornece funcionalidades de backup
	 */
	class MBackup {
		protected $bd;
	
		public function __construct($bd=null) {
			$this->bd = $bd;
			
			if($this->bd) {
				$this->bd->preparaReverso();
			}
		
		}
		
		/**
		 * Especifica o arquivo de destino e opcionalmente a estrutura
		 */
		public function bdStructDump($arquivo,$estrutura=null) {
		
			if( !$arquivo ) {
				return;
			}

			$tmpArq=tempnam("/tmp","mbkp-");
			$fd=fopen($tmpArq,"w");
			if(!$fd) {
				echo "NAO FOI POSSIVEL ABRIR O ARQUIVO TEMPORARIO '$tmpArq' PRA GRAVACAO\n";
				return;
			}
			
			if( !$estrutura ) {
				$estrutura = $this->bd->obtemEstrutura();
			}
			
			$xml = new MXMLUtils();

			fputs($fd,$xml->a2x($estrutura,"database"));
			fclose($fd);
			
			copy($tmpArq,$arquivo);
			unlink($tmpArq);
		}
		
		public function bdDataDump($tabelas=array(),$arquivo) {
			$xml = new MXMLUtils();
			
			$tmpArq=tempnam("/tmp","mbkp-");
			$fd=fopen($tmpArq,"w");
			if(!$fd) {
				echo "NAO FOI POSSIVEL ABRIR O ARQUIVO TEMPORARIO '$tmpArq' PRA GRAVACAO\n";
				return;
			}

			/**
			 * Varre as tabelas
			 */
			
			fputs($fd,$xml->headerTag()."\n");
			fputs($fd,$xml->beginTag("dados")."\n");
			foreach($tabelas as $tabela) {
				//echo "DUMP: " . $tabela . "\n";
				//echo "-----------------------------\n";
				
				$sSQL = "SELECT * FROM $tabela";
				$r = $this->bd->obtemResultado($sSQL);
				
				fputs($fd,$xml->beginTag($tabela)."\n");
				
				$rownum = 0;
				while($linha=$r->fetch()) {
					fputs($fd,$xml->a2x($linha,$xml->intBeginTag($rownum++,false),MXMLUTILS_PRIMEIRO_NIVEL,false));
				}
				
				fputs($fd,$xml->endTag($tabela)."\n");
				
				
				//$dados[$tabela] = $r;
				//unset($r);
				
				
				
			}
			fputs($fd,$xml->endTag("dados")."\n");
			
			fclose($fd);
			
			copy($tmpArq,$arquivo);
			unlink($tmpArq);
		
		}
		
		/**
		 * Pega um XML e gera um script SQL
		 */
		public function bdDump2Script($arquivo_xml,$arquivo_script) {
			$p = &new MXMLBackupParser();

			//$result = $p->setInputFile($arquivo_xml);
			//$result = $p->parse();
			
			
			$tmpArq=tempnam("/tmp","mbkp-");
			$fd=fopen($tmpArq,"w");
			if(!$fd) {
				echo "NAO FOI POSSIVEL ABRIR O ARQUIVO TEMPORARIO '$tmpArq' PRA GRAVACAO\n";
				return;
			}
			
			
			$p->processaArquivo($arquivo_xml);
			
			while( $dado = $p->fetch() ) {
				//echo "FETCHED!!!!\n";
				//print_r($dado);
				//break;
				
				$sql  = "INSERT INTO " . $dado["table"] . "(";
				$keys = array_keys($dado["data"]);
				
				$fields = array();
				$values = array();
				
				foreach($keys as $k) {
					$fields[] = $k;
					$vl = $dado["data"][$k];
					$values[] = is_null($vl) ? 'NULL' : "'" . $this->bd->escape($vl) . "'";
				}
				
				$sql .= implode(",",$fields) . ") VALUES ( " . implode(',',$values) . ") ";
				
				
				fputs($fd,$sql.";\n");
							
			}
			
			fclose($fd);

			copy($tmpArq,$arquivo_script);
			unlink($tmpArq);
			
		}
		
		/**
		 * Cria um script completo p/ restore, com estrutura e dados, na ordem correta pra execucao
		 */
		public function criaScriptRestore($arq_xml_struct,$arq_dados_xml,$arq_destino) {

			$tmpArqScript=tempnam("/tmp","mbkp-");
			$fdS=fopen($tmpArqScript,"w");
			if(!$fdS) {
				echo "NAO FOI POSSIVEL ABRIR O ARQUIVO TEMPORARIO '$tmpArqScript' PRA GRAVACAO\n";
				return;
			}




			$xml = new MXMLUtils();
			$fd=fopen("bd_novo.xml","r");
			$x=fread($fd,filesize("bd_novo.xml"));
			fclose($fd);	
			$estrutura = $xml->x2a($x,"database");
			unset($x);
			$script = $this->bd->scriptCriacao($estrutura);
			
			foreach($script["begin"] as $sql) {
				fputs($fdS,$sql .";\n");
			}
			unset($script["begin"]);

			foreach($script["struct"] as $sql) {
				fputs($fdS,$sql .";\n");
			}
			unset($script["struct"]);
			
			
			// Inserir dados.
			
			$tmpArqDados=tempnam("/tmp","mbkp-");
			$this->bdDump2Script($arq_dados_xml,$tmpArqDados);
			
			$fdD = fopen($tmpArqDados,"r");
			
			if( !$fdD ) {
				echo "NAO FOI POSSIVEL ABRIR O ARQUIVO TEMPORARIO '$tmpArqDados' PRA LEITURA\n";
				return;
			}

			while( ($linha=fgets($fdD)) && !feof($fdD) ) {
				fputs($fdS,$linha);
			}
			
			fclose($fdD);
			unlink($tmpArqDados);
			
			
						

			// Fim dos dados.

			foreach($script["end"] as $sql) {
				fputs($fdS,$sql .";\n");
			}
			unset($script["end"]);
			
			fclose($fdS);
			copy($tmpArqScript,$arq_destino);
			unlink($tmpArqScript);
			
			
			
		}

	}

?>
