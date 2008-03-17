<?

	require_once("MRetorno.class.php");
	
	class MRetornoItau extends MRetorno {

		public function processaLinha($linha) {
			// echo "PROCESSA LINHA: $linha\n";
			switch(@$linha[0]) {
			
				case '0':
					// HEADER
					$codigo_retorno = substr($linha,1,1);
					$literal_retorno = substr($linha,2,7);
					$codigo_servico  = substr($linha,9,2);
					$literal_servico = substr($linha,11,15);
					$agencia         = substr($linha,26,4);
					$zeros			 = substr($linha,30,2);
					$conta			 = substr($linha,32,5);
					$dac			 = substr($linha,37,1);
					$brancos 		 = substr($linha,38,8);
					$nome_empresa	 = substr($linha,46,30);
					$codigo_banco	 = substr($linha,76,3);
					$nome_banco		 = substr($linha,79,15);
					$this->data_geracao	 = $this->formataData(substr($linha,94,6),"bd");
					$densidade		 = substr($linha,100,5);
					$unidade_densid  = substr($linha,105,3);
					$seq_arquivo	 = substr($linha,108,5);
					$data_credito	 = substr($linha,113,6);
					$brancos2		 = substr($linha,119,275);
					$sequencial		 = substr($linha,394,6);
					
					
					/**
					echo "HEADER!!!\n";
					echo "--------------------------\n";
					echo "COD RETORNO: [$codigo_retorno]\n";
					echo "LIT RETORNO: [$literal_retorno]\n";
					echo "COD SERVICO: [$codigo_servico]\n";
					echo "LIT SERVICO: [$literal_servico]\n";
					echo "AGENCIA....: [$agencia]\n";
					echo "ZEROS......: [$zeros]\n";
					echo "CONTA......: [$conta]\n";
					echo "DAC........: [$dac]\n";
					echo "BRANCOS....: [$brancos]\n";
					echo "NOME EMPRE.: [$nome_empresa]\n";
					echo "COD BANCO..: [$codigo_banco]\n";
					echo "NOME BANCO.: [$nome_banco]\n";
					echo "DATA GERAC.: [$data_geracao]\n";
					echo "DENSIDADE..: [$densidade]\n";
					echo "UN DENSID..: [$unidade_densid]\n";
					echo "SEQ ARQUIVO: [$seq_arquivo]\n";
					echo "DATA CREDIT: [$data_credito]\n";
					echo "BRANCOS....: [$brancos2]\n";
					echo "SEQUENCIAL.: [$sequencial]\n";
					echo "--------------------------\n";
					
					*/
					
					
					break;
					
				case '1':
					// REGISTRO DE TRANSAÇÃO
					$codigo_inscricao 	= substr($linha,1,2);
					$numero_inscricao 	= substr($linha,3,14);
					$agencia		  	= substr($linha,17,4);
					$zeros			  	= substr($linha,21,2);
					$conta			  	= substr($linha,23,5); 
					$dac			  	= substr($linha,28,1);
					$brancos		  	= substr($linha,29,8);
					$uso_empresa	  	= substr($linha,37,25);
					$nosso_numero	  	= substr($linha,62,8);
					$brancos2		  	= substr($linha,70,12);
					$numero_carteira  	= substr($linha,82,3);
					$nosso_numero2	  	= substr($linha,85,8); // 
					$dac_nosso_numero	= substr($linha,93,1);
					$brancos3			= substr($linha,94,13);
					$codigo_carteira	= substr($linha,107,1);
					$cod_ocorrencia		= substr($linha,108,2);
					$data_ocorrencia	= $this->formataData(substr($linha,110,6),"bd");
					$numero_documento	= substr($linha,116,10);
					$conf_nosso_numero	= substr($linha,126,8);
					$brancos4			= substr($linha,134,12);
					$vencimento			= $this->formataData(substr($linha,146,6),"bd");
					$valor_titulo		= $this->formataValor(substr($linha,152,13),"bd"); // 9(11)V9(2)
					$codigo_banco		= substr($linha,165,3);
					$agencia_cobradora	= substr($linha,168,4);
					$dac_ag_cobradora	= substr($linha,172,1);
					$especie			= substr($linha,173,2);
					$tarifa_cobranca	= $this->formataValor(substr($linha,175,13),"bd"); // 9(11)V9(2)
					$brancos5			= substr($linha,188,26);
					$valor_iof			= $this->formataValor(substr($linha,214,13),"bd"); // 9(11)V9(2)
					$valor_abatimento	= $this->formataValor(substr($linha,227,13),"bd"); // 9(11)V9(2)
					$descontos			= $this->formataValor(substr($linha,240,13),"bd"); // 9(11)V9(2)
					$valor_principal	= $this->formataValor(substr($linha,253,13),"bd"); // 9(11)V9(2)
					$juros_multa_mora	= $this->formataValor(substr($linha,266,13),"bd"); // 9(11)V9(2)
					$outros_creditos	= $this->formataValor(substr($linha,279,13),"bd"); // 9(11)V9(2)
					$brancos6			= substr($linha,292,3);
					$data_credito		= $this->formataData(substr($linha,295,6),"bd");
					$instr_cancelada	= substr($linha,301,4);
					$brancos7			= substr($linha,305,19);
					$nome_sacado		= substr($linha,324,30);
					$brancos8			= substr($linha,354,23);
					$erros				= substr($linha,377,8);
					$brancos9			= substr($linha,385,7);
					$cod_liquidacao		= trim(substr($linha,392,2));
					$numero_sequencial	= (int)substr($linha,394,6);
					
					$idx = count($this->registros);
					
					$this->registros[$idx] = array (
													"agencia" => $agencia,
													"conta" => $conta,
													"dac" => $dac,	
													"nossonumero" => $nosso_numero,
													"dac_nossonumero" => $dac_nosso_numero,
													"uso_empresa" => $uso_empresa,
													"carteira" => $numero_carteira,
													"codigo_carteira" => $codigo_carteira,
													"codigo_ocorrencia" => $cod_ocorrencia,
													"data_ocorrencia" => $data_ocorrencia,
													"numero_documento" => $numero_documento,
													"vencimento" => $vencimento,
													"valor_titulo" => $valor_titulo,
													"agencia_cobradora" => $agencia_cobradora,
													"dac_agencia_cobradora" => $dac_ag_cobradora,
													"especie" => $especie,
													"tarifa_cobranca" => $tarifa_cobranca,
													"valor_iof" => $valor_iof,
													"valor_abatimento" => $valor_abatimento,
													"descontos" => $descontos,
													"valor_principal" => $valor_principal,
													"juros_multa_mora" => $juros_multa_mora,
													"outros_creditos" => $outros_creditos,
													"data_credito" => $data_credito,
													"instrucao_cancelada" => $instr_cancelada,
													"nome_sacado" => $nome_sacado,
													"codigo_liquidacao" => $cod_liquidacao,
													"erros" => $erros,
													"numero_sequencial" => $numero_sequencial
												);
					// 
					/**
					print_r($this->registros[$idx]);
					echo "TRANSACAO!!!\n";
					echo "--------------------------\n";
					echo "COD INSCR..............: [$codigo_inscricao]\n";
					echo "NUM INSCR..............: [$numero_inscricao]\n";
					echo "AGENCIA................: [$agencia]\n";
					echo "ZEROS..................: [$zeros]\n";
					echo "CONTA..................: [$conta]\n";
					echo "DAC....................: [$dac]\n";
					echo "BRANCOS................: [$brancos]\n";
					echo "USO EMPRESA............: [$uso_empresa]\n";
					echo "NOSSO NUMERO...........: [$nosso_numero]\n";
					echo "BRANCOS2...............: [$brancos2]\n";
					echo "NUMERO CARTEIRA........: [$numero_carteira]\n";
					echo "NOSSO NUMERO2..........: [$nosso_numero2]\n";
					echo "DAC NOSSO NUMERO.......: [$dac_nosso_numero]\n";
					echo "BRANCOS3...............: [$brancos3]\n";
					echo "CODIGO CARTEIRA........: [$codigo_carteira]\n";
					echo "COD OCORRENCIA.........: [$cod_ocorrencia]\n";
					echo "DATA OCORRENCIA........: [$data_ocorrencia]\n";
					echo "NUMERO DOCUMENTO.......: [$numero_documento]\n";
					echo "NOSSO NUMERO3..........: [$conf_nosso_numero]\n";
					echo "BRANCOS4...............: [$brancos4]\n";
					echo "VENCIMENTO.............: [$vencimento]\n";
					echo "VALOR TITULO...........: [$valor_titulo]\n";
					echo "COD BANCO..............: [$codigo_banco]\n";
					echo "AGENCIA COBRADORA......: [$agencia_cobradora]\n";
					echo "DAC AG COBRADORA.......: [$dac_ag_cobradora]\n";
					echo "ESPECIE................: [$especie]\n";
					echo "TARIFA COBRANCA........: [$tarifa_cobranca]\n";
					echo "BRANCOS5...............: [$brancos5]\n";
					echo "VALOR IOF..............: [$valor_iof]\n";
					echo "VALOR ABATIMENTO.......: [$valor_abatimento]\n";
					echo "DESCONTOS..............: [$descontos]\n";
					echo "VALOR PRINCIPAL........: [$valor_principal]\n";
					echo "JUROS/MULTA/MORA.......: [$juros_multa_mora]\n";
					echo "OUTROS CREDITOS........: [$outros_creditos]\n";
					echo "BRANCOS6...............: [$brancos6]\n";
					echo "DATA CREDITO...........: [$data_credito]\n";
					echo "INSTR CANCELADA........: [$instr_cancelada]\n";
					echo "BRANCOS7...............: [$brancos7]\n";
					echo "NOME SACADO............: [$nome_sacado]\n";
					echo "BRANCOS8...............: [$brancos8]\n";
					echo "ERROS..................: [$erros]\n";
					echo "BRANCOS9...............: [$brancos9]\n";
					echo "COD LIQUIDACAO.........: [$cod_liquidacao]\n";
					echo "NUMERO SEQUENCIAL......: [$numero_sequencial]\n";					
					
					*/
					break;
					
				case '9':
					// TRAILER
					$codigo_retorno 		= substr($linha,1,1);
					$codigo_servico 		= substr($linha,2,2);
					$codigo_banco	 		= substr($linha,4,3);
					$brancos		 		= substr($linha,7,10);
					$qtde_titulos	 		= substr($linha,17,8);
					$valor_total	 		= substr($linha,25,12.2);
					$aviso_bancario	 		= substr($linha,39,8);
					$brancos		 		= substr($linha,47,10);
					$qtde_titulos	 		= substr($linha,57,8);
					$valor_total			= substr($linha,65,12.2);
					$aviso_bancario			= substr($linha,79,8);
					$brancos2				= substr($linha,87,90);
					$qtde_titulos2			= substr($linha,177,12.2);
					$valor_total2			= substr($linha,185,8);
					$aviso_bancario			= substr($linha,199,8);
					$controle_arquivo		= substr($linha,207,5);
					$qtde_detalhes			= substr($linha,212,8);
					$vlr_total_informado	= substr($linha,220,12.2);					
					$brancos3				= substr($linha,234,160);
					$numero_sequencial		= substr($linha,394,6);
					
					
					break;
					
				default:
					break;

			}
		
		}
	
	}
	
	
	/**
	 * TESTE
	 */
	 
	//echo "<pre>";
	//$retorno = MRetorno::factory('ITAU',"teste-retorno-itau-03.txt");
	//print_r($retorno->obtemRegistros());
	//echo "</pre>";

?>
