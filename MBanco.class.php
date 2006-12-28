<?

	// Geração do código de barras
	require_once("mimage_barcode_int25.class.php");


	/**
	 * Módulo base para os sistemas bancários de boleto e arrecadação
	 *
	 */

	class MBanco {

		/**
		 * CONSTRUTOR
		 */
		public function __construct() {
		
		}

		/**
		 * Soma()
		 * Realiza a soma de todos os dígitos de uma sequencia
		 */
		protected static function soma($p) {
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
	
		public static function Soma11($Partes){ 
			$Quant = strlen($Partes); 
			$Mod11 = '4329876543298765432987654329876543298765432'; 
			$Soma=0;
			
			$ptrMod = strlen($Mod11) - 1;
			
			
			for ($i = $Quant-1; $i >= 0; $i--) { 
				$Y = $Partes[$i]*$Mod11[$ptrMod]; 
				//echo $Partes[$i] . "*" . $Mod11[$ptrMod] . " = $Y\n";
				$Soma += $Y; 
				--$ptrMod;
			} 
			return($Soma); 
		}

		/**
		 * Modulo10()
		 * Calcula o Modulo10 (utilizado no digito verificador)
		 */
		protected static function modulo10($soma) {
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
		 * Imprime a imagem do codigo de barras
		 */
		public static function barCode($cod,$target='') {
		   // Imprime o código de barras.
		   $bc = new MImage_Barcode_int25();
		   $bc->draw($cod, "png", $target);
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


	}









?>
