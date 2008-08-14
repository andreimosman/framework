<?



	class MFormata {
	
		public static function valorExtenso($valor=0, $maiusculas=false) { 

			$rt = null;

			// verifica se tem virgula decimal 
			if (strpos($valor,",") > 0) { 
				// retira o ponto de milhar, se tiver 
				$valor = str_replace(".","",$valor); 

				// troca a virgula decimal por ponto decimal 
				$valor = str_replace(",",".",$valor); 
			} 

			$singular = array("centavo", "real", "mil", "milh�o", "bilh�o", "trilh�o", "quatrilh�o"); 
			$plural = array("centavos", "reais", "mil", "milh�es", "bilh�es", "trilh�es", "quatrilh�es"); 

			$c = array("", "cem", "duzentos", "trezentos", "quatrocentos", "quinhentos", "seiscentos", "setecentos", "oitocentos", "novecentos"); 
			$d = array("", "dez", "vinte", "trinta", "quarenta", "cinquenta", "sessenta", "setenta", "oitenta", "noventa"); 
			$d10 = array("dez", "onze", "doze", "treze", "quatorze", "quinze", "dezesseis", "dezesete", "dezoito", "dezenove"); 
			$u = array("", "um", "dois", "tr�s", "quatro", "cinco", "seis", "sete", "oito", "nove"); 

			$z=0; 

			$valor = number_format($valor, 2, ".", "."); 
			$inteiro = explode(".", $valor); 

			for($i=0;$i<count($inteiro);$i++) 
				for($ii=strlen($inteiro[$i]);$ii<3;$ii++) 
					$inteiro[$i] = "0".$inteiro[$i]; 

			$fim = count($inteiro) - ($inteiro[count($inteiro)-1] > 0 ? 1 : 2); 
			for ($i=0;$i<count($inteiro);$i++) { 
				$valor = $inteiro[$i]; 
				$rc = (($valor > 100) && ($valor < 200)) ? "cento" : $c[$valor[0]]; 
				$rd = ($valor[1] < 2) ? "" : $d[$valor[1]]; 
				$ru = ($valor > 0) ? (($valor[1] == 1) ? $d10[$valor[2]] : $u[$valor[2]]) : ""; 

				$r = $rc.(($rc && ($rd || $ru)) ? " e " : "").$rd.(($rd && $ru) ? " e " : "").$ru; 
				$t = count($inteiro)-1-$i; 
				$r .= $r ? " ".($valor > 1 ? $plural[$t] : $singular[$t]) : ""; 
				if ($valor == "000")$z++; elseif ($z > 0) $z--; 
				if (($t==1) && ($z>0) && ($inteiro[0] > 0)) $r .= (($z>1) ? " de " : "").$plural[$t]; 
				if ($r) $rt = $rt . ((($i > 0) && ($i <= $fim) && ($inteiro[0] > 0) && ($z < 1)) ? ( ($i < $fim) ? ", " : " e ") : " ") . $r; 
			} 
			
			$rt = trim($rt);

			if(!$maiusculas){ 
				return($rt ? $rt : "zero"); 
			} elseif($maiusculas == "2") { 
				return (strtoupper($rt) ? strtoupper($rt) : "Zero"); 
			} else { 
				return (ucwords($rt) ? ucwords($rt) : "Zero"); 
			} 

		}
	
		public static function escreveData($data)  { 
			$data = MData::ptBR_to_ISO($data);
			list($ano,$mes,$dia) = explode("-",$data);
			$mes_array = array("janeiro", "fevereiro", "mar�o", "abril", "maio", "junho", "julho", "agosto", "setembro", "outubro", "novembro", "dezembro"); 
			return $dia ." de ". $mes_array[(int)$mes-1] ." de ". $ano;
		}




	}


?>
