<?

	require_once("MException.class.php");

	abstract class MBanco {
	
		/**
		 * Soma()
		 * Realiza a soma de todos os dígitos de uma sequencia
		 */
		public static function soma($p) {
		   $soma = 0;
		   $c=0;
		   
		   //echo "P: $p<br>\n";
		   for($i=strlen($p)-1;$i>=0;--$i) {
			  $c++;
			  //$mul = ($i+1)%2 ? 2 : 1;
			  $mul = ($c)%2 ? 2 : 1;
			  $v = $p[$i] * $mul;
			  if($v>9) $v-=9;
			  $soma += $v;
		   }

		   return($soma);

		}	
	
		public static function Soma11($Partes){ 
			$Quant = strlen($Partes); 
			$Mod11 = '4329876543298765432987654329876543298765432'; 
			$Soma=0;
			
			$ptrMod = strlen($Mod11) - 1;
			
			
			for ($i = $Quant-1; $i >= 0; $i--) { 
				$Y = $Partes[$i]*$Mod11[$ptrMod]; 
				$Soma += $Y; 
				--$ptrMod;
			} 
			return($Soma); 
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
		 * Modulo11()
		 * Calcula do Modulo11 
		 */
		public static function modulo11($soma) {
			$resultado = floor($soma/11);
			$resto     = $soma - ($resultado * 11);
			if( $resto==10 || $resto==1 || $resto==0 ) {
				$dv = 1;
			} else {
				$dv = 11 - $resto;
			}
			return($dv);
		}
		
		/**
		 * Gera o código de barras em HTML
		 * TODO!!!
		 */
		public static function htmlBarcode($CodBarras,$urlPreto,$urlBranco) {

			// Definimos as dimensões das imagens 
			$fino = 1; 
			$largo = 3; 
			$altura = 40; 

			// Criamos um array associativo com os binários 
			$Bar[0] = "00110"; 
			$Bar[1] = "10001"; 
			$Bar[2] = "01001"; 
			$Bar[3] = "11000"; 
			$Bar[4] = "00101"; 
			$Bar[5] = "10100"; 
			$Bar[6] = "01100"; 
			$Bar[7] = "00011" ; 
			$Bar[8] = "10010"; 
			$Bar[9] = "01010"; 
			
			$retorno = "";
			
			/**
			 * Início do Padrão
			 */
			
			$retorno .= "<img src='$urlPreto' width='$fino' height='$altura' border=0>";
			$retorno .= "<img src='$urlBranco' width='$fino' height='$altura' border=0>";
			$retorno .= "<img src='$urlPreto' width='$fino' height='$altura' border=0>";
			$retorno .= "<img src='$urlBranco' width='$fino' height='$altura' border=0>";
			
			// Checando para saber se o conteúdo é impar 
			if ( strlen($CodBarras) % 2 != 0) { 
			    $CodBarras = '0'.$CodBarras; 
			}
			
			for ($a = 0; $a < strlen($CodBarras); $a++){ 
				$Preto  = $CodBarras[$a]; 
				$CodPreto  = $Bar[$Preto]; 

				$a = $a+1; // Sabemos que o Branco é um depois do Preto... 
				$Branco = $CodBarras[$a]; 
				$CodBranco = $Bar[$Branco]; 


				// Encontrado o CodPreto e o CodBranco vamos fazer outro looping dentro do nosso 
				for ($y = 0; $y < 5; $y++) { // O for vai pegar os binários 

					if ($CodPreto[$y] == '0') { // Se o binario for preto e fino ecoa 
						$retorno .= "<img src='$urlPreto'  width=$fino height=$altura border=0>"; 
					} 

					if ($CodPreto[$y] == '1') { // Se o binario for preto e grosso ecoa 
						$retorno .= "<img src='$urlPreto'  width=$largo height=$altura border=0>"; 
					} 

					if ($CodBranco[$y] == '0') { // Se o binario for branco e fino ecoa 
						$retorno .= "<img src='$urlBranco'  width=$fino height=$altura border=0>"; 
					} 

					if($CodBranco[$y] == '1') { // Se o binario for branco e grosso ecoa 
						$retorno .= "<img src='$urlBranco'  width=$largo height=$altura border=0>"; 
					} 
				} 

			} // Fechamos nosso looping maior 

			// Encerramento do código de barras	
			$retorno .= "<img src='$urlPreto' width='$fino' height='$altura' border=0>";
			$retorno .= "<img src='$urlBranco' width='$fino' height='$altura' border=0>";
			$retorno .= "<img src='$urlPreto' width='$fino' height='$altura' border=0>";
		
			return($retorno);
		
		}

		/*******************************************
		 *          FUNCOES DE APOIO               *
		 *******************************************/

		/**
		 * Entra com a variável e o tamanho do campo, 
		 * Preenche o resto com zeros à esquerda
		 */
		public static function padZero($variavel,$tamanho) {
			return( str_pad($variavel, $tamanho, "0", STR_PAD_LEFT) );
		}

		/**
		 * Insere um ponto na posição especificada
		 */
		public static function inserePonto($str,$p) {
		   return( substr($str,0,$p) . "." . substr($str,$p) );
		}

		/**
		 * Obtem o fator da data com base em 07/10/1997 conforme regulamentação Febraban
		 */
		public static function fatorData($data) {
		   list($d,$m,$a) = explode("/",$data);

		   // Constante: 07/10/1997
		   $dt_const = mktime(0,0,0,10,7,1997);

		   // Retorna o valor em dias (e não em segundos)
		   return( (int)((mktime(0,0,0,$m,$d,$a) - $dt_const)/(24*60*60)) );

		}	

	
	}

?>
