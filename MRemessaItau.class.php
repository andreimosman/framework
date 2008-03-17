<?

	require_once("MRemessa.class.php");

	/**
	 * Classe para geração de remessa do banco itaú.
	 */
	class MRemessaItau extends MBanco {
	
		protected $_init;
		protected $_agencia;
		protected $_conta;
		protected $_dv_conta;
		protected $_carteira;
		
		protected $_nome_empresa;
		protected $_cnpj_empresa;
		
		protected $juros;
		
		protected $_sequencia;
		


		public function __construct() {
			$this->_init = 0;	// Indica se o init foi chamado.
		}
		
		public function init($agencia,$conta,$dv_conta,$carteira,$convenio,$nome_empresa,$cnpj_empresa,$juros) {
			$this->_banco = 341;
			$this->_nome_banco = "BANCO ITAU SA";
			
			$this->_agencia = $agencia;
			$this->_conta = $conta;
			$this->_dv_conta = $dv_conta;
			$this->_carteira = $carteira;
			$this->_convenio = $convenio;
			$this->_nome_empresa = $nome_empresa;
			$this->_cnpj_empresa = str_replace(".", "", str_replace("/","", str_replace("-","",$cnpj_empresa)));
			
			$this->_juros = $juros; // Em porcentagem
			
			$this->_sequencia = 0;
			
			$this->_init = 1;
		}
				
		protected function obtemCabecalho() {
			$cabecalho  = "01REMESSA01COBRANCA       " . $this->padZero($this->_agencia,4) . "00".$this->padZero($this->_conta,5) . $this->padZero($this->_dv_conta,1);
			$cabecalho .= str_repeat(" ",8) . str_pad($this->_nome_empresa, 30, " ", STR_PAD_RIGHT) . $this->padZero($this->_banco,3) . str_pad($this->_nome_banco, 15, " ", STR_PAD_RIGHT);
			$cabecalho .= date("dmy").str_repeat(" ",294).$this->padZero($this->obtemProximoSequencial(),6);
			
			return($cabecalho);
		}
		
		protected function obtemRodape() {
			$rodape = '9' . str_repeat(" ", 393) . $this->padZero($this->obtemProximoSequencial(),6);
			return($rodape);
		}
		
		protected function registroDetalhe($instrucao_alegacao,$identificacao_titulo,$id,$vencimento,$valor,
										$tipo_pessoa,$cpf_cnpj_sacado,$nome_razao_sacado, $logradouro_sacado,
										$bairro_sacado, $cep_sacado, $cidade_sacado, $uf_sacado, $nome_produto) {
		
			// TIPO DE REGISTRO: 1
			$registro  = "1";
			
			// CODIGO DE INSCRICAO
			// 01 - CPF CEDENTE / 02 - CNPJ CEDENTE / 03 - CPF SACADO / 04 - CNPJ SACADO
			$registro .= "02"; 
								
			// NUMERO DE INSCRICAO
			$registro .= $this->padZero($this->_cnpj_empresa,14);
			
			// AGENCIA ZEROS CONTA DAC
			$registro .= $this->padZero($this->_agencia,4) . "00".$this->padZero($this->_conta,5) . $this->padZero($this->_dv_conta,1);
			
			// BRANCOS (COMPLEMENTO DE REGISTRO
			$registro .= str_repeat(" ",4);
			
			// ESTRANHO: FALTAM ESSES ESPAÇOS:
			// $registro .= str_repeat(" ", 25);
			
			// INSTRUCAO/ALEGACAO (NOTA 27)
			if( !$instrucao_alegacao ) {
				$registro .= str_repeat(" ",4);
			} else {
				$registro .= $this->padZero($instrucao_alegacao,4);	// Código da Instrução ou da alegação de cancelamento. VER NOTA 27
			}
			
			// IDENTIFICAÇÃO DO TITULO NA EMPRESA (NOTA 2)
			$registro .= str_pad($identificacao_titulo,25, ' ', STR_PAD_LEFT); // Identificação do título na empresa. VER NOTA 2
			
			// NOSSO NUMERO
			if( $this->_carteira == 112 ) {
				// Cobrança registrada.
				$nosso_numero = 0;
			} else {
				// NOSSO NUMERO GERADO PELA EMPRESA.
				$nosso_numero = $id;
			}			
			$registro .= $this->padZero($nosso_numero,8);	// Identificação do título no banco. VER NOTA 3
			
			// QUANTIDADE DE MOEDA VARIAVEL
			$registro .= $this->padZero(0,13);				// Quantidade de moeda variável
			
			// NÚMERO DA CARTEIRA NO BANCO
			$registro .= $this->padZero($this->_carteira,3);	// Carteira

			// IDENTIFICAÇÃO DA OPERAÇÃO NO BANCO
			$registro .= str_repeat(" ",21);	// USO DO BANCO
			
			// CARTERIA (CÓDIGO DA CARTEIRA)
			$registro .= $this->obtemCodigoCarteira();		// NOTA 5
			
			// TODO: CÓDIGO DE OCORRÊNCIA (NOTA 06)
			$registro .= $this->padZero(0,2);
			
			// NUMERO DO DOCUMENTO DE COBRANÇA X(10)
			$registro .= $this->padZero($id, 10);
			
			// VENCIMENTO.
			// USAR 999999 p/ pagamento à vista (contra apresentação);
			$registro .= $this->padZero($this->obtemData($vencimento), 6); // VENCIMENTO 
			
			// VALOR NOMINAL DO TÍTULO (NOTA 9)
			$vl = number_format($valor,2,"","");
			$registro .= $this->padZero($vl,13);
			
			// CÓDIGO DO BANCO NA CÂMARA DE COMPENSAÇÃO
			$registro .= $this->padZero($this->_banco,3);
			
			// AGENCIA COBRADORA 9(05) NOTA 9
			$registro .= $this->padZero(0,5);
			
			// ESPECIE 9(02) NOTA 10
			// OBSERVAÇÃO: ESPECIE 17 não consta na documentaçào, contudo o arquivo de remessa do rogério gera toda especie como 17
			$registro .= $this->padZero(17,2);
			
			// ACEITE X(01) A=SIM N=NAO
			$registro .= 'N';
			
			// DATA DA EMISSAO 9(06) NOTA 31
			$registro .= $this->obtemData();
			
			// INSTRUCAO 01 X(02) NOTA 11			
			$instrucao1 = '93';	// MENSAGEM DOS BOLETOS COM 30 POSIÇOES (p/ 40 utilize 94)
			$registro .= str_pad($instrucao1, 2, " ", STR_PAD_RIGHT);
			
			// INSTRUCAO 02 X(02) NOTA 11
			$instrucao2 = '00'; // Sem instrução adicional.
			$registro .= str_pad($instrucao2, 2, " ", STR_PAD_RIGHT);
			
			// TODO: VERIFICAR SE ESTÁ CORRETO: JUROS DE 1 DIA 9(11)V9(2) NOTA 12 (OS JUROS ESTÃO EM REAIS).
			//$juros = $valor / 100 * $this->_juros;
			//$juros = number_format($juros,2,"","");
			$juros = 0;
			$registro .= $this->padZero($juros,13);
			
			// DESCONTO ATE DDMMAA
			$registro .= $this->padZero(0,6);
			
			// VALOR DO DESCONTO 9(11)V9(2) NOTA 13
			$registro .= $this->padZero(0,13);
			
			// VALOR DO IOF 9(11)V9(2) NOTA 14
			$registro .= $this->padZero(0,13);
			
			// ABATIMENTO 9(11)V9(2) NOTA 13
			$registro .= $this->padZero(0,13);
			
			// CODIGO DE INSCRICAO DO SACADO: 01=CPF / 02=CNPJ
			$codigo_inscricao = $tipo_pessoa == 'F' ? '01' : '02';
			$registro .= $codigo_inscricao;
			
			// NUMERO DE INSCRICAO
			$inscricao_sacado = str_replace(".","",str_replace("-","",str_replace("/","",$cpf_cnpj_sacado)));
			$registro .= $this->padZero($inscricao_sacado,14);
			
			// NOME DO SACADO
			$registro .= str_pad(strtoupper(substr($nome_razao_sacado,0,30)),30," ",STR_PAD_RIGHT);
			
			// BRANCOS
			$registro .= str_repeat(" ",10);
			
			// LOGRADOURO
			$registro .= str_pad(strtoupper(substr($logradouro_sacado,0,40)),40," ",STR_PAD_RIGHT);
			
			// BAIRRO
			$registro .= str_pad(strtoupper(substr($bairro_sacado,0,12)),12," ",STR_PAD_RIGHT);
			
			// CEP
			$cep = str_replace(".","",str_replace("-","",$cep_sacado));
			$registro .= $this->padZero($cep,8);
			
			// CIDADE
			$cidade = str_pad(strtoupper(substr($cidade_sacado,0,15)),15," ",STR_PAD_RIGHT);
			$registro .= $cidade;
			
			// UF
			$registro .= str_pad(strtoupper(substr($uf_sacado,0,2)),2," ",STR_PAD_RIGHT);
			
			// SACADOR/AVALISTA X(30)
			$registro .= str_pad(strtoupper(substr($nome_produto,0,30)),30," ",STR_PAD_RIGHT);
			
			// BRANCOS X(4)
			$registro .= str_repeat(" ", 4);
			
			// DATA DE MORA 9(6)
			$data_mora = 0;
			$registro .= $this->padZero($data_mora,6);
			
			// PRAZO 9(2) NOTA 11(A)
			$registro .= $this->padZero(0,2);
						
			// BRANCOS X(1)
			$registro .= " ";
			
			// NUMERO SEQUENCIAL 9(6)
			$registro .= $this->padZero($this->obtemProximoSequencial(),6);
			
			return($registro);
		}
		
		protected function obtemProximoSequencial() {
			return(++$this->_sequencial);
		}
		
		protected function obtemData($data="") {
			if( !$data ) {
				return(date("dmy"));
			}
			
			if(strstr($data,"-")) {
				// Data no formato do BD
				list($ano,$mes,$dia) = explode("-",$data);
			} else if(strstr($data,"/")) {
				// Data no formato pt_BR
				list($dia,$mes,$ano) = explode("/",$data);
			} else {
				return($data);
			}
			
			if(strlen($ano) == 4 ) {
				$ano = substr($ano,2,2);
			}
			
			$dia = $this->padZero($dia,2);
			$mes = $this->padZero($mes,2);
			$ano = $this->padZero($ano,2);
			
			return($dia.$mes.$ano);
			
		}
		
		
		protected function obtemCodigoCarteira($carteira = "") {
			$codigos = array();
			
			if( !$carteira ) {
				$carteira = $this->_carteira;
			}
			
			// TODO: Código para todas as carteiras.
			$codigos["112"] = "I";
			
			return($codigos[$carteira]);
			
		}

		public function geraArquivoRemessa($arquivo,$faturas) {
			if( !$this->_init ) {
				throw new Exception("Remessa não inicializada.");
			}
			
			$temparq = tempnam("/tmp","varb");
			$fd = fopen($temparq,"w");
			fputs($fd,$this->obtemCabecalho() . "\n");
			
			for($i=0;$i<count($faturas);$i++) {
				$cliente = $faturas[$i]["cliente"];
				$contrato = $faturas[$i]["contrato"];
				$endereco_cobranca = $faturas[$i]["endereco_cobranca"];
				
				// Chamar $this->registroDetalhe()
				
				$instrucao_alegacao = 0;
				$identificacao_titulo = "";
				$id = $faturas[$i]["id_cobranca"];
				$vencimento = $faturas[$i]["data"];
				$valor = $faturas[$i]["valor"];
				$tipo_pessoa = $cliente["tipo_pessoa"];
				$cpf_cnpj_sacado = $cliente["cpf_cnpj"];
				$nome_razao_sacado = $cliente["nome_razao"];
				
				$logradouro_sacado = $endereco_cobranca["endereco"];
				$bairro_sacado = $endereco_cobranca["bairro"];
				$cep_sacado = $endereco_cobranca["cep"];
				$cidade_sacado = $endereco_cobranca["cidade"]["cidade"];
				$uf_sacado = $endereco_cobranca["cidade"]["uf"];
				
				$nome_produto = $contrato["nome_produto"];

				fputs($fd,$this->registroDetalhe($instrucao_alegacao,$identificacao_titulo,$id,$vencimento,$valor,
										$tipo_pessoa,$cpf_cnpj_sacado,$nome_razao_sacado, $logradouro_sacado,
										$bairro_sacado, $cep_sacado, $cidade_sacado, $uf_sacado, $nome_produto) . "\n" );
				
				
				// LIMPEZA
				unset($cliente);
				unset($contrato);
				unset($endereco_cobranca);
				
			}
			
			fputs($fd, $this->obtemRodape() . "\n");
			fclose($fd);
			
			copy($temparq, $arquivo);
			
			unlink($temparq);
			
		}

	}
	
	
	/**
	 * TESTES
	 */
	
	/**
	
	$remessa = new MRemessaItau();
	$remessa->init(210,71130,5,112,"","KZ Com. e Serv. Ltda","01.928.751/0001-35",2);
	$remessa->geraArquivoRemessa($arquivo,$faturas);

	*/


?>
