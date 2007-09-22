<?

	require_once("MRetorno.class.php");
	
	class MRetornoBBCBR643 extends MRetorno {

		/**
		 * Header
		 */
		protected $agencia;
		protected $dv_agencia;
		protected $cedente;
		protected $dv_cedente;
		protected $convenente;
		protected $nome_empresa;
		protected $data;
		protected $seq_retorno;


		/**
		 * Trailer (Simulado)
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
		
			$this->agencia		= "";
			$this->dv_agencia 	= "";
			$this->cedente		= ""; // Conta
			$this->dv_cedente	= "";
			$this->convenente	= ""; // convenio
			$this->nome_empresa	= "";
			$this->data			= "";
			$this->seq_retorno	= "";
		
		}
		
		public function processaLinha($linha) {
		
			if( strlen(trim($linha)) ) {

				// Sequencial de registro (todos os registros têm
				$seq_registro	= substr($linha,394,6);

				switch( substr($linha,0,2) ) {

					case '02':
						// HEADER

						// VERIFICAR DISPLAY DO HEADER (CBR643)
						$displayPadrao = "02RETORNO01COBRANCA       ";
						$display = substr($linha,0,26);

						if( $display != $displayPadrao ) {
							// Exibir erro;
							$this->valido = false;
							return;
						}

						// analizar o resto do header
						$agencia 		= substr($linha,26,4);	// Agência
						$dv_agencia		= substr($linha,30,1);	// DV da Agência

						$cedente 		= substr($linha,31,8);	// Cod do Cedente
						$dv_cedente		= substr($linha,39,1);	// Dv do Cedente

						$convenente		= substr($linha,40,6);	// Cod do Convenente Lider (convenio)
						$nome_empresa	= substr($linha,46,30);	// Nome da empresa

						$displayPadrao = "001BANCO DO BRASIL";
						$display = substr($linha,76,18);

						if( $display != $displayPadrao ) {
							$this->valido = false;
							return;
						}

						$data 			= substr($linha,94,6); // DDMMAA
						//$data 			= $this->formataData($data);

						$seq_retorno	= substr($linha,100,7); // Número sequencial de retorno

						if( $convenente == "000000" ) {
							// Usar Convenio com 17 digitos
							$convenente = substr($linha,149,7);
						}

						//$brancos		= substr($linha,107,287);
						//echo "BRANCOS:[" . $brancos . "]\n";


						/**
						echo "AGENCIA......: [" . $agencia . "]\n";
						echo "DV AGENCIA...: [" . $dv_agencia . "]\n";
						echo "CEDENTE......: [" . $cedente . "]\n";
						echo "DV CEDENTE...: [" . $dv_cedente . "]\n";
						echo "CONVENENTE...: [" . $convenente . "]\n";
						echo "NOME EMPRESA.: [" . $nome_empresa . "]\n";
						echo "DATA.........: [" . $data . "]\n";
						echo "SEQ RETORNO..: [" . $seq_retorno . "]\n";
						*/

						$this->agencia 			= $agencia;
						$this->dv_agencia		= $dv_agencia;
						$this->cedente			= $cedente;
						$this->dv_cedente		= $dv_cedente;
						$this->convenente		= $convenente;
						$this->nome_empresa		= $nome_empresa;
						$this->data				= $data;
						$this->seq_retorno		= $seq_retorno;

						break;

					case '92':
						// TRAILER (FOOTER)
						$displayPadrao = "9201001          ";
						$display = substr($linha,0,17);

						// Valores estáticos
						if( $display != $displayPadrao ) {
							$this->valido = false;
							return;
							
						}


						// COBRANCA SIMPLES
						$nada01 = substr($linha,17,8);	// zeros
						$nada02 = substr($linha,25,14);	// zeros
						$nada03 = substr($linha,39,8);	// zeros
						$nada04 = substr($linha,47,10);	// Brancos

						// COBRANCA VINCULADA
						$nada05 = substr($linha,57,8);	// zeros
						$nada06 = substr($linha,65,14);	// zeros
						$nada07 = substr($linha,79,8);	// zeros
						$nada08 = substr($linha,87,10);	// Brancos

						// COBRANCA CAUCIONADA
						$nada09 = substr($linha,97,8);	// zeros
						$nada10 = substr($linha,105,14);	// zeros
						$nada11 = substr($linha,119,8);	// zeros
						$nada12 = substr($linha,127,10);	// Brancos

						// COBRANCA DESCONTADA
						$nada13 = substr($linha,137,8);	// zeros
						$nada14 = substr($linha,145,14);	// zeros
						$nada15 = substr($linha,159,8);	// zeros
						$nada16 = substr($linha,167,227);	// Brancos (mas tem uns zeros no meio, vai entender)

						/**

						echo "NADA 01......: [" . $nada01 . "]\n";
						echo "NADA 02......: [" . $nada02 . "]\n";
						echo "NADA 03......: [" . $nada03 . "]\n";
						echo "NADA 04......: [" . $nada04 . "]\n";
						echo "NADA 05......: [" . $nada05 . "]\n";
						echo "NADA 06......: [" . $nada06 . "]\n";
						echo "NADA 07......: [" . $nada07 . "]\n";
						echo "NADA 08......: [" . $nada08 . "]\n";
						echo "NADA 09......: [" . $nada09 . "]\n";
						echo "NADA 10......: [" . $nada10 . "]\n";
						echo "NADA 11......: [" . $nada11 . "]\n";
						echo "NADA 12......: [" . $nada12 . "]\n";
						echo "NADA 13......: [" . $nada13 . "]\n";
						echo "NADA 14......: [" . $nada14 . "]\n";
						echo "NADA 15......: [" . $nada15 . "]\n";
						echo "NADA 16......: [" . $nada16 . "]\n";

						*/



						// TODO: Contagens


						break;

					default:
						// DETALHE

						$tipo_detalhe = $linha[0];		// 1 (registro p/ convenios com 6 digitos) 7 (p/ convenios com 7 digitos)
						$tipo_inscr		= substr($linha,1,2);	// Tipo da inscricao (01 - cpf; 02 - cnpj, 00 - no arquivo de retorno)
						$cpf_cgc		= substr($linha,3,14);	// cpf/cgc do cedente (com zeros no arquivo de retorno)
						$agencia		= substr($linha,17,4);	// prefixo da agencia
						$dv_agencia		= substr($linha,21,1);	// DV da agencia
						$cedente		= substr($linha,22,8); 	// Cedente
						$dv_cedente		= substr($linha,30,1);	// DV do cedente

						///////////////
						//
						// VALORES ESPECIFICOS
						//
						////////////////////////////////////////


						if( $tipo_detalhe == "7" ) {
							// Convenio com 7 digitos (nosso numero com 17)
							$convenio		= substr($linha,31,7);	// Convenio
							$nr_cnt_part	= substr($linha,38,25);	// Numero de controle do participante
							$nossonumero	= substr($linha,63,17);	// Nosso numero
							$tipo_cobranca	= substr($linha,80,1);	// Tipo da cobrança:
																	// 1 (simples) 
																	// 2 (vinculada) 
																	// 4 (descontada) 
																	// 7 (direta) 
																	// 8 (vendor)

							$tp_cobra_ant	= substr($linha,81,1);	// Tipo da cobranca anterior (Especifico para comando 72
																	// 0 (caso não haja alteracao no tipo de cobranca)
																	// 1 (simples) 
																	// 2 (vinculada) 
																	// 4 (descontada) 
																	// 7 (direta) 
																	// 8 (vendor)

							$dias_para_calc	= substr($linha,82,4);	// Dias para calculo
																	// Carteiras- 11,12,15,16,17,18 e 31, vide observações
																	// Carteiras- 51.. igual ao numero de dias sobre os quais foram
																	//                 calculados o Desconto e o IOF
							$nat_mot		= substr($linha,86,2);	// Natureza do recebimento/Motivo da entrada/baixa/recusa indicativos
																	//NATUREZA DO RECEBIMENTO /comando
																	//05,06,07,08 ou 15 /pos. 109/110/
																	//				01-liquidacao normal
																	//				02-por conta
																	//				03-liquidacao por saldo
																	//				04-liquidacao com cheque a compensar
																	//				05-liquidacao de titulo sem registro/cart.17, tipo 4/
																	//				07-liquidacao na apresentacao
																	//				09-liquidacao em cartorio
																	//ENTRADA/comdo 02- pos. 109/110/..
																	//				00-Por meio magnetico
																	//				11-Por via convencional
																	//				16-Por alteracao do codigo do cedente
																	//				17-Por alteracao da variacao
																	//				18-por alteracao da carteira
																	//BAIXA/comdo 09/10/20-pos 109/110/..
																	//				 00-Solicitada pelo cliente
																	//				 15-Protestado
																	//				 18-Por alteracao da carteira
																	//				 19-Debito automatico
																	//				 31-Liquidado anteriormente
																	//				 32-Habilitado em processo
																	//				 33-Incobravel por nosso intermedio
																	//				 34-Transferido para creditos em liquidação
																	//				 46-Por alteracao do codigo do cedente
																	//				 47-Por alteracao da variacao
																	//				 51-Acerto
																	//				 90-Baixa automatica
																	//RECUSA/comdo 03-pos 109/110/..
																	//				 01-Identificacao invalida
																	//				 02-Variacao da carteira invalida
																	//				 03-Valor dos juros por um dia inválido
																	//				 04-Valor do desconto invalido
																	//				 05-Especie de titulo invalida para carteira/variação
																	//				 06-Especie de valor variavel inválido
																	//				 07-Prefixo da agencia usuaria inválido
																	//				 08-Valor do titulo/apolice invalido
																	//				 09-Data de vencimento invalida
																	//				 10-Fora do prazo /Soh admissivel na carteira 11/
																	//				 11-Inexistencia de margem para desconto
																	//				 12-O Banco nao tem agencia na praça do sacado
																	//				 13-Razoes cadastrais
																	//				 14-Sacado interligado com o sacador/Só admissível
																	//					  em cobranca simples- cart. 11 e 17/
																	//				 15-Titulo sacado contra orgao do Poder Público
																	//					  /Soh admissivel na cart.11 e sem ordem de 
																	//					  protesto/
																	//				 16-Titulo preenchido de forma irregular
																	//				 17-Titulo rasurado
																	//				 18-Ender.do sacado não localizado ou incompleto
																	//				 19-Codigo do cedente invalido
																	//				 20-Nome/endereco do cliente não informado/ECT/
																	//				 21-Carteira invalida
																	//				 22-Quantidade de valor variavel inválida
																	//				 23-Faixa nosso-numero excedida
																	//				 24-Valor do abatimento invalido
																	//				 25-Novo numero do tit. dado pelo cedente inválido 
																	//				 26-Valor do IOF de seguro invalido
																	//				 27-Nome do sacado/cedente invalido ou não 
																	//					  informado
																	//				 28-Data do novo vencimento invalida
																	//				 29-Endereco nao informado
																	//				 30-Registro de titulo jah liquidado/cart.17-tipo 4/
																	//				 31-Numero do bordero invalido
																	//				 32-Nome da pessoa autorizada inválido
																	//				 33-Nosso numero jah existente
																	//				 34-Numero da prestacao do contrato inválido
																	//				 35-percentual de desconto invalido
																	//				 36-Dias para fichamento de protesto inválido
																	//				 37-Data de emissao do titulo inválida
																	//				 38-Data do vencimento anterior aa data da 
																	//					  emissao do titulo
																	//				 39-Comando de alteracao indevido para a carteira
																	//				 40-Tipo de moeda invalido
																	//				 41-Abatimento nao permitido
																	//				 42-CEP/UF invalido/nao compativeis /ECT/
																	//				 43-Codigo de unidade variavel incompatível com a
																	//					   data de emissão do título
																	//				 44-Dados para debito ao sacado inválidos
																	//				 45-Carteira/variacao encerrada
																	//				 46-Convenio encerrado
																	//				 47-Titulo tem valor diverso do informado
																	//				 48-Motivo de baixa invalido para a carteira
																	//				 49-Abatimento a cancelar nao consta do título
																	//				 50-Comando incompativel com a carteira
																	//				 51-Codigo do convenente invalido
																	//				 52-Abatimento igual ou maior que o valor do título
																	//				 53-Titulo jah se encontra na situação pretendida
																	//				 54-Titulo fora do prazo admitido para a conta 1
																	//				 55-Novo vencimento fora dos limites da carteira
																	//				 56-Titulo nao pertence ao convenente
																	//				 57-Variacao incompativel com a carteira
																	//				 58-Impossivel a transferencia para a cart. indicada
																	//				 59-Titulo vencido em transferência para a cart.51
																	//				 60-Titulo com prazo superior a 179 dias em 
																	//					   transferencia para carteira 51
																	//				 61-Titulo jah foi fichado para protesto
																	//				 62-Alteracao da situacao de debito inválida para o
																	//					   código de responsabilidade
																	//				 63-DV do nosso numero invalido
																	//				 64-Titulo nao passivel de débito/baixa-situação
																	//					   anormal
																	//				 65-Titulo com ordem de não protestar-não pode
																	//					   ser encaminhado a cartório
																	//				 67-Titulo/carne rejeitado
																	//				 80-Nosso numero invalido
																	//				 81-Data para concessao do desconto inválida.
																	//					  Gerada nos seguintes casos..
																	//					  a/ erro na data do desconto.,
																	//					  b/ data do desconto anterior aa data de emissão
																	//				 82-CEP do sacado invalido
																	//				 83-Carteira/variacao nao localizada no cedente
																	//				 84-Titulo nao localizado na existência
																	//				 99-Outros motivos
																	//ALTERACAO DE TIPO DE COBRANCA/
																	//				 Comando 72/pos. 109/110..
																	//				 00-Transferencia de titulo de Cobrança Simples 
																	//					  para Descontada
																	//				 52-Reembolso de titulo Vendor ou Descontado

							$pref_tit		= substr($linha,88,3);	// Prefixo do título
							$var_carteira	= substr($linha,91,3);	// Variacao da carteira
							$conta_caucao	= substr($linha,94,1);	// Conta caucao 
																	//- para carteira 31..
																	//  0 - comandos 28, 96, 97 e 98
																	//  1 - Conta "1"
																	//  2 - Conta "2"
																	//  4 - Conta "2" /Titulos a vencer a mais de 180 dias/
																	//- para tipo de cobranca 8-Vendor, vide observações
							$taxa_desconto	= substr($linha,95,5);	// Taxa de desconto 999v99
																	// - para tipo de cobranca 8-Vendor, vide observações
							$taxa_desconto	= $this->formataValor($taxa_desconto,"bd");

							$taxa_iof		= substr($linha,100,5);	// Taxa IOF
																	// - para tipo de cobranca 8-Vendor, vide observações
							$taxa_iof 		= $this->formataValor($taxa_iof,"bd",1);

							$branco 		= substr($linha,105,1);	// Branco (pra variar)

							// INFORMACOES COMUNS FORA DO LOOP

							$numero_cedente = substr($linha,116,10);// Numero do título fornecido pelo cedente
							$data_vencimento= substr($linha,146,6);	// Data do vencimento DDMMAA

							//$data_vencimento= $this->formataData($data_vencimento);

							$especie_tit	= substr($linha,173,2); // Espécie do título
																	// 00-informado nos registros com comando /97-DESPESAS DE
																	// 	 SUSTACAO DE PROTESTO/nas posicoes 109/110 desde
																	// 	 que o titulo nao conste mais da existência.
																	// 01-duplicata mercantil
																	// 02-nota promissoria
																	// 03-nota de seguro
																	// 05-recibo
																	// 08-letra de cambio
																	// 09-warrant
																	// 10-cheque
																	// 12-duplicata de servico
																	// 13-nota de debito
																	// 15-apolice de seguro
																	// 25-divida ativa da Uniao
																	// 26-divida ativa de Estado
																	// 27-divida ativa de Municipio
							$juros_desconto	= substr($linha,201,13);// Juros do Desconto 99999999999v99
							$juros_desconto = $this->formataValor($juros_desconto,"bd");

							$iof_desconto	= substr($linha,214,13);// IOF do Desconto 99999999999v99
							$iof_desconto 	= $this->formataValor($iof_desconto,"bd");

							$abat_nao_aprov	= substr($linha,292,13);// Abatimento nao aproveitado pelo sacado
							$abat_nao_aprov = $this->formataValor($abat_nao_aprov,"bd");

							$lancamento		= substr($linha,305,13);// valor do lancamento
							$lancamento 	= $this->formataValor($lancamento,"bd");

							$debito_credito	= substr($linha,318,1);	// Indicativo de débito/crédito
																	// 0-Sem lancamento
																	// 1-Debito
																	// 2-Credito

							$indica_valor	= substr($linha,319,1);	// Indicador de Valor
																	// 1-Ajuste a debito
																	// 2-Ajuste a credito
																	//    - para tipo de cobranca 8-Vendor, vide observações


							$valor_ajuste	= substr($linha,320,12);// Valor do ajuste
																	// - para tipo de cobranca 8-Vendor, vide observações
							$valor_ajuste = $this->formataValor($valor_ajuste,"bd");

							//
							// OPCOES PARA CONVENIO COMPARTILHADO
							//

							$convenio_comp	= substr($linha,332,1);	// Brancos
																	// - Para cobranca compartilhada será apresentado o indicativo
																	//   de compartilhamento..
																	//   2-Convenio Compartilhador
																	//   3-Convenio Compartilhado

							$vl_orig_tit	= substr($linha,333,9);	// Brancos
																	// - Na cobranca compartilhada.. Será informado o valor original
																	//  do título pago pelo sacado.

							$vl_orig_tit	= $this->formataValor($vl_orig_tit,"bd");

							$num_conv_comp1	= substr($linha,342,7);	// Número do convênio compartilhado - 1
																	// - Na cobranca compartilhada..
																	//   Posicao 333 = 2.. Numero do  primeiro conv.compartilhado,
																	//   Posicao 333 = 3.. Numero do  conv.compartilhador.

							$valor_compart1	= substr($linha,349,9);	// Valor compartilhado
																	// - Na cobranca compartilhada..
																	//   Posicao 333 = 2.. Valor repassado para o primeiro conv.
																	//   compartilhado.
																	//   Posicao 333 = 3.. Valor  recebido no conv.compartilhador.

							$valor_compart1	= $this->formataValor($valor_compart1,"bd");

							$num_conv_comp2	= substr($linha,358,7);	// Número do convênio compartilhado - 2
																	// - Na cobranca compartilhada..
																	//   Posicao 333 = 2.. Numero do  segundo conv.compartilhado,
																	//   Posicao 333 = 3.. Numero do  conv.compartilhador.

							$valor_compart2	= substr($linha,365,9);	// Valor compartilhado
																	// - Na cobranca compartilhada..
																	//   Posicao 333 = 2.. Valor repassado para o segungo conv.
																	//   compartilhado.
																	//   Posicao 333 = 3.. Valor  recebido no conv.compartilhador.

							$valor_compart2	= $this->formataValor($valor_compart2,"bd");

							$num_conv_comp3	= substr($linha,374,7);	// Número do convênio compartilhado - 3
																	// - Na cobranca compartilhada..
																	//   Posicao 333 = 2.. Numero do  primeiro conv.compartilhado,
																	//   Posicao 333 = 3.. Numero do  conv.compartilhador.

							$valor_compart3	= substr($linha,381,9);	// Valor compartilhado
																	// - Na cobranca compartilhada..
																	//   Posicao 333 = 2.. Valor repassado para o terceiro conv.
																	//   compartilhado.
																	//   Posicao 333 = 3.. Valor  recebido no conv.compartilhador.

							$valor_compart3	= $this->formataValor($valor_compart3,"bd");


							$brancos		= substr($linha,390,4);	// Brancos (pra variar)


						} else {
							// Convenio com 6 digitos (nosso numero com 11)
							$convenio		= substr($linha,31,6);	// Convenio
							$brancos		= substr($linha,37,25);	// Brancos (pra variar)
							$nossonumero	= substr($linha,62,11);	// Nosso numero
							$dv_nossonumero	= substr($linha,73,1);	// DV nosso numero
							$zeros			= substr($linna,74,2);	//
							$zeros			= substr($linna,76,4);	//
							$zeros			= substr($linna,80,2);	//
							$brancos		= substr($linna,82,3);	//
							$var_carteira	= substr($linha,85,3);	// Variacao da carteira
							$zeros			= substr($linna,88,1);	//
							$zeros			= substr($linna,89,5);	//
							$brancos		= substr($linna,94,1);	//
							$zeros			= substr($linna,95,5);	//
							$zeros			= substr($linna,100,5);	//
							$brancos		= substr($linna,105,1);	//
							$zeros			= substr($linha,146,6);	// Zeros

						}

						///////////////
						//
						// VALORES COMUNS
						//
						////////////////////////////////////////


						$carteira		= substr($linha,106,2);	// Carteira

						$comando		= substr($linha,108,2); // Comando
																// 02-Confirmacao de Entrada de Título
																// 03-Comando recusado /ver posicao 081/082/
																// 05-Liquidado sem registro /carteira 17-tipo4 /
																// 06-Liquidacao Normal
																// 07-Liquidacao por Conta
																// 08-Liquidacao por Saldo
																// 09-Baixa de Titulo
																// 10-Baixa Solicitada
																// 11-Titulos em Ser /constara somente do arquivo de existência
																// 	de cobranca, fornecido mediante solicita~]ao do cliente/
																// 12-Abatimento Concedido
																// 13-Abatimento Cancelado
																// 14-Alteracao de Vencimento do título
																// 15-Liquidacao em Cartorio
																// 19-Confirmacao de recebimento de instruções para protesto
																// 20-Debito em Conta
																// 21-Alteracao do Nome do Sacado
																// 22-Alteracao do Endereco do Sacado
																// 23-Indicacao de encaminhamento a cartório
																// 24-Sustar Protesto
																// 25-Dispensar Juros
																// 28-Manutencao de titulo vencido
																// 31-Conceder desconto
																// 32-Nao conceder desconto
																// 33-Retificar desconto
																// 34-Alterar data para desconto
																// 35 - Cobrar Multa
																// 36 - Dispensar Multa
																// 37 - Dispensar Indexador
																// 38 - Dispensar prazo limite para recebimento
																// 39 - Alterar prazo limite para recebimento
																// 72-Alteracao de tipo de cobranca/específico para títulos das
																// 	carteiras 11 e 17/.
																// 96-Despesas de Protesto
																// 97-Despesas de Sustacao de Protesto
																// 98-Debito de Custas Antecipadas

						$data_entrada	= substr($linha,110,6);	// Data da entrada/liquidacao /DDMMAA/
						//$data_entrada 	= $this->formataData($data_entrada);

						$brancos		= substr($linha,126,20);// Pra variar

						$valor_titulo	= substr($linha,152,13);// Valor do título
						$valor_titulo	= $this->formataValor($valor_titulo,"bd");



						$cod_banco_receb= substr($linha,165,3);	// Código do banco recebedor
						$agencia_receb	= substr($linha,168,4);	// Código da agência recebedora (vide observaões)
						$dv_agencia_rec = substr($linha,172,1);	// dv da agência ou do prefixo de compensacao COMPE-SP

						$data_credito	= substr($linha,175,6);	// Data do crédito

						$valor_tarifa	= substr($linha,181,7);	// Valor da tarifa 99999v99
						$valor_tarifa	= $this->formataValor($valor_tarifa,"bd");

						$outras_despesas= substr($linha,188,13);// Outras despesas 99999999999v99


						$abatimento		= substr($linha,227,13);// Valor do Abatimento 99999999999v99
						$abatimento		= $this->formataValor($abatimento,"bd");

						$desconto		= substr($linha,240,13);// Desconto concedido 99999999999v99
						$desconto 		= $this->formataValor($desconto,"bd");

						$valor_recebido = substr($linha,253,13);// Valor Recebido 99999999999v99
						$valor_recebido = $this->formataValor($valor_recebido,"bd");

						$juros_mora		= substr($linha,266,13);// Juros de Mora 99999999999v99
						$juros_mora		= $this->formataValor($juros_mora,"bd");

						$outros_receb	= substr($linha,279,13);// Outros recebimentos 99999999999v99
						$outros_receb	= $this->formataValor($outros_receb,"bd");

						$lancamento		= substr($linha,305,13);// valor do lancamento
						$lancamento 	= $this->formataValor($lancamento,"bd");

						$debito_credito	= substr($linha,318,1);	// Indicativo de débito/crédito
																// 0-Sem lancamento
																// 1-Debito
																// 2-Credito

						$idx = count($this->registros);
						$this->registros[$idx] = array (
														"agencia" 					=> @$agencia,
														"dv_agencia" 				=> @$dv_agencia,
														"cedente"					=> @$cedente,
														"dv_cedente"				=> @$dv_cedente,
														"convenio"					=> @$convenio,
														"nossonumero"				=> @$nossonumero,
														"tipo_cobranca" 			=> @$tipo_cobranca,
														"nr_cnt_part"				=> @$nr_cnt_part,
														"dias_para_calculo"			=> @$dias_para_calc,
														"natureza_motivo"			=> @$natureza_motivo,
														"prefixo_titulo"			=> @$pref_tit,
														"variacao_carteira"			=> @$var_carteira,
														"conta_caucao"				=> @$conta_caucao,
														"taxa_desconto"				=> @$taxa_desconto,
														"taxa_iof"					=> @$taxa_iof,
														"carteira"					=> @$carteira,
														"comando"					=> @$comando,
														"data_entrada"				=> @$data_entrada,
														"numero_cedente"			=> @$numero_cedente,
														"data_vencimento"			=> @$data_vencimento,
														"valor_titulo" 				=> @$valor_titulo,
														"banco_recebedor"			=> @$cod_banco_receb,
														"agencia_recebedora"		=> @$agencia_receb,
														"dv_ag_recebedora"			=> @$dv_agencia_rec,
														"especie_titulo"			=> @$especie_tit,
														"data_credito"				=> @$data_credito,
														"valor_tarifa"				=> @$valor_tarifa,
														"outras_despesas"			=> @$outras_despesas,
														"juros_desconto"			=> @$juros_desconto,
														"iof_desconto"				=> @$iof_desconto,
														"abatimento"				=> @$abatimento,
														"valor_recebido"			=> @$valor_recebido,
														"juros_mora"				=> @$juros_mora,
														"outros_recebimentos" 		=> @$outros_receb,
														"abatimentos_nao_aprovados"	=> @$abat_nao_aprov,
														"lancamento"				=> @$lancamento,
														"debito_cretido"			=> @$debito_credito,
														"indicador_valor"			=> @$indica_valor,
														"valor_ajuste"				=> @$valor_ajuste,
														"convenio_compartilhado"	=> @$convenio_comp,
														"valor_original_titulo"		=> @$vl_orig_tit,
														"num_convenio_comp_1"		=> @$num_conv_comp1,
														"valor_compartilhado_1"		=> @$valor_compart1,
														"num_convenio_comp_2"		=> @$num_conv_comp2,
														"valor_compartilhado_2"		=> @$valor_compart2,
														"num_convenio_comp_3"		=> @$num_conv_comp3,
														"valor_compartilhado_3"		=> @$valor_compart3,
														"sequencial_registro"		=> @$seq_registro
														);
						$this->vl_total_processado += $this->registros[$idx]["valor_recebido"];
						$this->registros_processados++;							

						/**
						echo "TIPO INSCR...: [" . $tipo_inscr . "]\n";
						echo "CPF/CGC......: [" . $cpf_cgc . "]\n";
						echo "AGENCIA......: [" . $agencia . "]\n";
						echo "DV AGENCIA...: [" . $dv_agencia . "]\n";
						echo "CEDENTE......: [" . $cedente . "]\n";
						echo "DV CEDENTE...: [" . $dv_cedente . "]\n";
						echo "CONVENIO.....: [" . $convenio . "]\n";
						echo "NOSSO NUMERO.: [" . $nossonumero . "]\n";
						//echo "DV/NOSSONUM..: [" . $dv_nossonumero . "]\n";
						echo "TIPO COBRAN..: [" . $tipo_cobranca . "]\n";
						echo "TP COBR ANT..: [" . $tp_cobra_ant . "]\n";
						echo "DIAS P CALC..: [" . $dias_para_calc . "]\n";
						echo "NAT/MOT......: [" . $nat_mot . "]\n";
						echo "PREF TIT.....: [" . $pref_tit . "]\n";
						echo "VAR CARTEIRA.: [" . $var_carteira . "]\n";
						echo "CONTA CAUCAO.: [" . $conta_caucao . "]\n";
						echo "TX DESC......: [" . $taxa_desconto . "]\n";
						echo "TX IOF.......: [" . $taxa_iof . "]\n";
						echo "CARTEIRA.....: [" . $carteira . "]\n";
						echo "COMANDO......: [" . $comando . "]\n";
						echo "DT ENTRADA...: [" . $data_entrada . "]\n";
						echo "NR CEDENTE...: [" . $numero_cedente . "]\n";
						echo "DT VENCI.....: [" . $data_vencimento . "]\n";
						echo "VL TITULO....: [" . $valor_titulo . "]\n";
						echo "BANCO RECEB..: [" . $cod_banco_receb . "]\n";
						echo "AG RECEB.....: [" . $agencia_receb . "]\n";
						echo "DV AG RECEB..: [" . $dv_agencia_rec . "]\n";
						echo "ESPECIE TIT..: [" . $especie_tit . "]\n";
						echo "DT CREDITO...: [" . $data_credito . "]\n";
						echo "VL TARIFA....: [" . $valor_tarifa . "]\n";
						echo "OUTRAS DESP..: [" . $outras_despesas . "]\n";
						echo "JUROS DESC...: [" . $juros_desconto . "]\n";
						echo "IOF DESC.....: [" . $iof_desconto . "]\n";
						echo "ABATIMENTO...: [" . $abatimento . "]\n";
						echo "DESCONTO.....: [" . $desconto . "]\n";
						echo "VL RECEBIDO..: [" . $valor_recebido . "]\n";
						echo "JUROS MORA...: [" . $juros_mora . "]\n";
						echo "OUTOS RECEB..: [" . $outros_receb . "]\n";
						echo "ABAT NAO APR.: [" . $abat_nao_aprov . "]\n";
						echo "LANCAMENTO...: [" . $lancamento . "]\n";
						echo "DEB/CRED.....: [" . $debito_credito . "]\n";
						echo "INDICADOR VL.: [" . $indica_valor . "]\n";
						echo "VL AJUSTE....: [" . $valor_ajuste . "]\n";
						echo "CONVEN COMP..: [" . $convenio_comp . "]\n";
						echo "VL ORIG TIT..: [" . $vl_orig_tit . "]\n";
						echo "N CONV COMP 1: [" . $num_conv_comp1 . "]\n";
						echo "VL COMP 1....: [" . $valor_compart1 . "]\n";
						echo "N CONV COMP 2: [" . $num_conv_comp2 . "]\n";
						echo "VL COMP 2....: [" . $valor_compart2 . "]\n";
						echo "N CONV COMP 3: [" . $num_conv_comp3 . "]\n";
						echo "VL COMP 3....: [" . $valor_compart3 . "]\n";
						*/





				}


			}

		}
	
	
	
	}


?>
