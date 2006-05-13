<?

if(!defined('_M_ARRECADACAO')) {
	define('_M_ARRECADACAO',1);

	//require_once("Image/Barcode.php");
	require_once("mimage_barcode_int25.class.php");


	/**
	 *
	 * Classe MArrecadacao
	 *
	 * Utilizado por sistema de cobranca atraves de arrecadacao bancaria (como o sistema PagContas);
	 *
	 */
	
	class MArrecadacao {
	
		/**
		 * Soma()
		 * Realiza a soma de todos os d�gitos de uma sequencia
		 */
		public static function soma($p) {
		   $soma = 0;
		   $c=0;
		   for($i=strlen($p)-1;$i>=0;--$i) {
			  $c++;
			  //$mul = ($i+1)%2 ? 2 : 1;
			  $mul = ($c)%2 ? 2 : 1;
			  $v = $p[$i] * $mul;
			  if($v>9) $v-=9;
			  //echo $p[$i] . " x " . $mul . " = " . $v . "<br>\n";
			  $soma += $v;
		   }

		   return($soma);

		}

		/**
		 * Modulo10()
		 * Calcula o Modulo10 (utilizado no digito verificador)
		 */
		public static function modulo10($soma) {
		   $dv = 10 - ($soma % 10);
		   if( $dv==10 ) $dv = 0;
		   return($dv);
		}
	
	
		/**
		 * digitoVerificador
		 * Retorna o digito verificador de uma determinada sequencia num�rica
		 */
		public static function digitoVerificador($val) {
			return(MArrecadacao::modulo10(MArrecadacao::soma($val)));
		}
		
		/**
		 * linhaDigitavel()
		 * Retorna a linha digitavel
		 * $codigo_barras Codigo de barras.
		 */
		function linhaDigitavel($codigo_barras) {
		   $A = substr($codigo_barras,0,11);
		   $B = substr($codigo_barras,11,11);
		   $C = substr($codigo_barras,22,11);
		   $D = substr($codigo_barras,33,11);

		   $cA = MArrecadacao::digitoVerificador($A);
		   $cB = MArrecadacao::digitoVerificador($B);
		   $cC = MArrecadacao::digitoVerificador($C);
		   $cD = MArrecadacao::digitoVerificador($D);

		   return "$A-$cA $B-$cB $C-$cC $D-$cD";
		}



		/**
		 * Gera um codigo de barras com base nas informa��es.
		 *
		 *	$id_produto Identifica��o do produto:
		 *		8 = Arrecada��o;
		 *
		 *	$id_segmento Identifica��o do segmento:
		 * 		1 - Prefeituras
		 * 		2 - Saneamento
		 * 		3 - Energia El�trica
		 * 		4 - Telecomunica��es
		 * 		5 - Org�os Governamentais
		 * 		6 - Carnes e Assemelhados ou demais Empresas / Org�os que ser�o 
		 * 		    identificados atrav�s de CGC
		 * 		7 - Multas de tr�nsito
		 * 		9 - Uso interno do banco
		 *
		 * 	$codigo_moeda Identificador de Valor Efetivo ou Referencia (codigo de moeda)
		 * 		6 - Valor a ser cobrado efetivamente em reais
		 * 		    com digito verificador na quarta posicao do codigo de barras e valor com 11 posicoes
		 * 		7 - Quantidade de moeda
		 * 		    Zeros - somente na indisponibilidade de utilizar o valor
		 * 		    Valor a ser reajustado por um indice.
		 * 		    com digito verificador na quarta posicao do c�digo de barras e valor com 11 posicoes.		 
		 *
		 * 	$valor Valor do documento. Formato: 99999.99 (ponto como separador decimal, sem separador de milhar).
		 * 		Se $codigo_moeda indicar valor efetivo este campo devera conter o valor a ser cobrado.
		 * 		Se $codigo_moeda indicar um valor de referencia, neste campo poder� conver uma quantidade 
		 * 		de moeda, zeros, ou um valor a ser reajustado por um indice, etc.
		 *
		 * 	$id_empresa Identifica��o da empresa
		 * 		Se a identifica��o do segmento (id_segmento) for igual a 6 ser� utilizado o CGC (apenas as 
		 *      8 primeiras posicoes) pra identificar a empresa e o campo livre (seguinte � identifica��o) 
		 * 		ir� conter apenas 21 posi��es. Nos outros casos ser� utilizado um c�digo de quatro d�gitos 
		 * 		atribu�do e controlado pela Febraban.
		 *
		 * 	$nosso_numero Identifica��o �nica da cobran�a. Faz parte da composi��o do "Campo livre".
		 *	$vencimento Data de vencimetno do documento. Formato: AAAAMMDD. Faz parte da composi��o do "Campo livre".
		 *
		 *	Campo Livre:
		 * 		Caso haja data de vencimento esta ir� nos 8 primeiros digitos do campo livre (AAAAMMDD).
		 * 		Vamos usar o NOSSONUMERO no restante do campo (13 posi��es qdo usado CGC pra identificar a empresa ou
		 * 		17 posi��es no caso da id emitido pela Febraban)
		 */
		public static function obtemCodigoBarras($id_produto,$id_segmento,$codigo_moeda,$valor,$id_empresa,$nosso_numero,$vencimento) {

			/**
			 * Gerando o c�digo de barras (inicialmente sem o DV)
			 */


			// Valor com 11 posicoes, usando duas casas decimais e sem separador de casas decimais
			$b_valor = str_pad(number_format($valor,2,"",""),11,"0",STR_PAD_LEFT);

			/**
			 * Identifica��o da empresa
			 * Se o segmento for 6 a identifica��o � usando 8 d�gitos do CPF, caso contr�rio ser� usando 4.
			 */

			// Numero de digitos que identificar� a empresa
			$num_digitos_empresa = ($id_segmento==6?8:4);

			// Limpa a sujeira do id_empresa
			$b_id_empresa = str_replace("/","",str_replace("-","",str_replace(".","",$id_empresa)));

			// Monta o campo
			$b_empresa = str_pad(substr($b_id_empresa,0,$num_digitos_empresa),$num_digitos_empresa, "0", STR_PAD_LEFT);

			/**
			 * Campo Livre
			 * Tamanho vari�vel de acordo com a forma de identifica��o do segmento.
			 */
			$num_digitos_campolivre = ($id_segmento==6?21:25);
			$b_campolivre = $vencimento . str_pad($nosso_numero,$num_digitos_campolivre - strlen($vencimento),"0",STR_PAD_LEFT);

			/**
			 * Gera o c�digo de barras
			 */

			// Sem o DV
			$codigo_barras = $id_produto . $id_segmento . $codigo_moeda . $b_valor . $b_empresa . $b_campolivre;

			// Coloca o DV
			$codigo_barras = substr($codigo_barras,0,3) . MArrecadacao::digitoVerificador($codigo_barras) . substr($codigo_barras,3);
			
			return($codigo_barras);

		}
		
		/**
		 * Gera um codigo de barras para o PagContas;
		 */
		public static function codigoBarrasPagContas($valor,$id_empresa,$nosso_numero,$vencimento) {
			$id_produto = 8;
			$id_segmento = 6;
			$codigo_moeda = 7;
			return(MArrecadacao::obtemCodigoBarras($id_produto,$id_segmento,$codigo_moeda,$valor,$id_empresa,$nosso_numero,$vencimento));
		}
		
		/**
		 * Imprime a imagem do codigo de barras
		 */
		public static function barCode($cod,$target='') {
		   // Imprime o c�digo de barras.
		   $bc = new MImage_Barcode_int25();
		   $bc->draw($cod, "png", $target);
		}
		
	
	}

}

	/**
	 * Teste
	 */
/**	
	$id_produto = 8;
	$id_segmento = 6;
	$codigo_moeda = 7;
	$valor = 40.00;
	$id_empresa = '05.125.818/0001-54';
	$nosso_numero = 1;
	$vencimento = '20030101';
	
	//$codigo_barras = MArrecadacao::obtemCodigoBarras($id_produto,$id_segmento,$codigo_moeda,$valor,$id_empresa,$nosso_numero,$vencimento);
	$codigo_barras = MArrecadacao::codigoBarrasPagContas($valor,$id_empresa,$nosso_numero,$vencimento);
	//echo "CB: $codigo_barras<br>\n";
	
	MArrecadacao::barCode($codigo_barras);
	
	$linha_digitavel = MArrecadacao::linhaDigitavel($codigo_barras);
	//echo "LD: $linha_digitavel<br>\n";
*/
?>
