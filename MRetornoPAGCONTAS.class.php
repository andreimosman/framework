<?


	require_once("MRetorno.class.php");

	class MRetornoPAGCONTAS extends MRetorno {
		/**
		 * Cabecalho
		 */
		protected $codigo_remessa;
		protected $codigo_convenio;
		protected $nome_empresa;
		protected $codigo_banco;
		protected $nsa;
		protected $versao_layout;
		
		/**
		 * Rodape
		 */
		protected $total_registros;
		protected $valor_total_rec;
		
		/**
		 * Valores processados (para o checksum)
		 */
		
		protected $registros_processados;
		protected $vl_total_processado;		
	
		public function init() {
			parent::init();

			$this->codigo_remessa = "";
			$this->codigo_convenio = "";
			$this->nome_empresa = "";
			$this->codigo_banco = "";
			$this->nsa = 0;
			$this->versao_layout = "";

			/**
			 * Rodape
			 */
			$this->total_registros = 0;
			$this->vl_total_recebido = 0;
			
			/**
			 * Valores processados
			 */
			$this->registros_processados = 0;
			$this->vl_total_processado = 0;
		
		}
		
		public function checkSum() {
			if( 
				$this->total_registros /** rodape */ == $this->registros_processados &&
				$this->vl_total_recebido /** rodape */ == $this->vl_total_processado
				) {
				
				return true;
				
			}
			
			return false;		
		}
		
		public function processaLinha($linha) {

			// Identifica o tipo do registro
			switch($linha[0]) {
				case 'A':
					// Cabecalho

					$this->codigo_remessa  = substr($linha,1,1);	 // Retorno = 2
					$this->codigo_convenio = substr($linha,2,20);
					$this->nome_empresa    = substr($linha,22,20);
					$this->codigo_banco    = substr($linha,42,3);
					$this->nome_banco      = substr($linha,45,20); 
					$this->data_geracao    = $this->formataData(substr($linha,65,8),"bd");
					$this->nsa             = substr($linha,73,6);  // Numero sequencial do arquivo
					$this->versao_layout   = substr($linha,79,2);  // Versao do layout = 3
					//$reservado       = substr($linha,81,69); // Reservado para o futuro

					/**
					if( $this->__DEBUG ) {

						echo "Codigo Remessa: $codigo_remessa <br>\n";
						echo "Código Convenio: $codigo_convenio <br>\n";
						echo "Nome Empresa: $nome_empresa <br>\n";
						echo "Codigo Banco: $codigo_banco <br>\n";
						echo "Nome Banco: $nome_banco<br>\n";
						echo "Data Geracao: $data_geracao<br>\n";
						echo "Numero Seq: $nsa <br>\n";
						echo "Versao Layout: $versao_layout<br>\n";
						echo "reservado: $reservado<br>\n";
						echo "<hr>\n";
					}
					*/

					break;

				case 'Z':
					// Rodape
					$this->total_registros = ((int)substr($linha,1,6)) - 2;	  // Tirando cabeçalho e rodapé
					$this->vl_total_recebido = (int)substr($linha,7,17);	  // 
					//$reservado       = substr($linha,24,126); // Reservado para o futuro
					break;

				case 'G':
					// Registro de Retorno

					// TODO: Jogas para $this->registros;

					$idx = count($this->registros);

					$this->registros[$idx] = array(
												"id_ag_cc_dig"   => substr($linha,1,20),	 
												"data_pagamento" => $this->formataData(substr($linha,21,8),"bd"),
												"data_credito"   => $this->formataData(substr($linha,29,8),"bd"),
												"codigo_barras"  => substr($linha,37,44),
												"valor_recebido" => $this->formataValor(substr($linha,81,12),"bd"),
												"valor_tarifa"   => $this->formataValor(substr($linha,93,7),"bd"),
												"nsr"            => substr($linha,100,8),	/** Numero sequencial do registro */
												"cod_agencia_arrecadadora" => substr($linha,108,8),	/** Codigo da agencia arrecadadora (código do posto) */
												"forma_arrecadacao"  => substr($linha,116,1)	/** Forma de arrecadacao (1) */
											);
					// $reservado    = substr($linha,117,33);	// Reservado para o futuro





					// Dados para a checksum
					$this->vl_total_processado += $this->registros[$idx]["valor_recebido"];
					$this->registros_processados++;

					//echo "Linha: $linha <br>\n";
					//echo "RP: " . $this->registros_processados . "<br>\n";

					break;

			}
			
		}

		public function formataData($data,$tipo="pt_BR") {
			$ano = substr($data,0,4);
			$mes = substr($data,4,2);
			$dia = substr($data,6,2);
			
			return( $tipo == "bd" ? "$ano-$mes-dia" : "$dia/$mes/$ano" );
			
		}


	}


?>
