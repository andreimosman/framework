<?

/**
 * Classe para tratamento e cálculos com datas.
 * Esta classe considera o formato pt_BR (dd/mm/aaaa).
 */
class MData {

	public static function ptBR_to_ISO($data_ptBR) {
	
		// Se a data já for ISO
		if( !strstr($data_ptBR,"/") ) {
			return($data_ptBR);
		}
	
		list($d,$m,$a)=explode("/",$data_ptBR);
		return("$a-$m-$d");
	}
	
	public static function ISO_to_ptBR($data_ISO) {
		$data_ISO = self::ptBR_to_ISO($data_ISO); // Assegura que a data está realmente em ISO.

		if( !strstr($data_ISO,"-") ) {
			return($data_ISO);
		}
		
		list($a,$m,$d)=explode("-",$data_ISO);
		return("$d/$m/$a");
		
	}

	public static function _diff($date_ini, $date_end, $round = 1) {

		//echo "DI: $date_ini\n"; 
		//echo "DE: $date_end\n"; 

		$date_ini = strtotime($date_ini);
		$date_end = strtotime($date_end);

		$date_diff = ($date_end - $date_ini) / 86400;

		if($round != 0) {
			return floor($date_diff);
		} else {
			return $date_diff;
		}
	}

	public static function diff($data1,$data2,$round=1) {			
		return(self::_diff(self::ptBR_to_ISO($data1),self::ptBR_to_ISO($data2),$round));			
	}


	public static function obtemDiasMes($ano="") {
		if( !$ano ) {
			list($d,$m,$ano)=explode("/",date("d/m/Y"));
		}

		$dias = array();
		for($i=1;$i<=12;$i++) {

			if( $i == 2 ) {
				// Fevereiro, verificar se ano é bissexto
				$numDias = $ano % 4 ? 28 : 29;
			} else {
				$numDias = in_array($i,array(1,3,5,7,8,10,12))?31:30;
			}

			$dias[$i] = $numDias;

		}
			
		return($dias);

	}


	/**
	 * Adiciona um mês em uma data.
	 *
	 * Considerações.
	 *   - Tratamento para meses com menores dias.
	 *   - Formato: DD/MM/AAAA
	 */
	public static function adicionaMes($data,$meses) {
		$data = self::ptBR_to_ISO($data);
		list($ano,$mes,$dia) = explode("-",$data);

		if( $meses >= 12 ) {
			$anos = floor($meses/12);
			$ano += $anos;
			$meses -= 12 * $anos;
		} 

		$mes += $meses;
			
		if( $mes > 12 ) {
			$mes -= 12;
			$ano++;
		} elseif ( $mes < 1 ) {
			// Subtracao
			
			$anos = -1 * floor($mes/12);
			$ano -= $anos;
			
			$mes = $mes + ($anos*12);
			
			if( $mes == 0 ) {
				--$ano;
				$mes=12;
			}
			
			//echo "MES: $mes\n" ;
			
			//echo "ANOS: $anos\n" ;
			
			
			
			
		}
			
		$dias = self::obtemDiasMes($ano);
			
		$mes = (int)$mes;
			
		if( $dia > $dias[$mes] ) {
			$dia = $dias[$mes];
		}
			
		if( $dia < 10 ) $dia = "0" . ((int)$dia);
		if( $mes < 10 ) $mes = "0" . ((int)$mes);
			

		$retorno = "$dia/$mes/$ano";
			
		return($retorno);

	}

	public static function proximoDia($dia,$dataBase="") {
		if( !$dataBase ) {
			$dataBase = date("Y/m/d");
		} else {
			$dataBase = self::ptBR_to_ISO($dataBase);
		}

		list($a,$m,$d) = explode("-",$dataBase);

		if( $dia > $d ) {
			// O próximo dia está neste mês
			$d = $dia;
		} else if( $dia < $d ) {
			// o próximo dia está no mês que vem.
			$d = $dia;
			$m++;
			if( $m > 12 ) {
				$m -= 12;
				$a++;
			}
		} else {
			$d = $dia;
		}
			
		$m = (int)$m;

		$dias = self::obtemDiasMes($a);
		if( $d > $dias[$m] ) $d = $dias[$m];

		if( $d < 10 ) $d = "0".((int)$d);
		if( $m < 10 ) $m = "0".((int)$m);

		return("$d/$m/$a");

	}
	
	/**
	 * Retorna o início ou fim de um período 
	 * baseado em uma data
	 *
	 * @param integer $data timestamp da data base
	 * @param interger $periodo periodo para o cálculo se for negativo irá calcular o início se for positivo irá calcular o fim
	 * @param mixed $formato Se formato é false retorna em timestamp se formato é uma 'string format' da função date retorna nesse formato
	 */
	public static function calculaPeriodo($data, $periodo, $formato = false){
		$data_timestamp = mktime (0, 0, 0, date("m", $data) + $periodo, date("d", $data),  date("Y", $data));
		if($formato){
			return date($formato, $data_timestamp);			 
		} else {
			return $data_timestamp;
		}
	}


}


// TESTE
// echo(MData::adicionaMes("01/12/2007", -13));
// echo MData::proximoDia("31","30/06/2007") . "\n";

//$dataContrato = "20/08/2007";
//$diaVencimento = "15";

//$dataCobranca = MData::proximoDia($diaVencimento,$dataContrato);
//$diferenca = MData::diff($dataContrato,$dataCobranca);

//echo "DATA CONTRATO...: $dataContrato\n";
//echo "DIA VENCIMENTO..: $diaVencimento\n";
//echo "DATA COBRANCA...: $dataCobranca\n";
//echo "DIFERENCA: $diferenca\n";





?>
