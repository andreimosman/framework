<?

	/**
	 * Classe para tratamento e c�lculos com datas.
	 * Esta classe considera o formato pt_BR (dd/mm/aaaa).
	 */
	class MData {

		public static function ptBR_to_ISO($data_ptBR) {
			list($d,$m,$a)=explode("/",$data_ptBR);
			return("$a-$m-$d");
		}

		public static function _diff($date_ini, $date_end, $round = 1) {
		    $date_ini = strtotime($date_ini);
		    $date_end = strtotime($date_end);

		    $date_diff = ($date_end - $date_ini) / 86400;

		    if($round != 0) {
				return floor($date_diff);
		    } else {
				return $date_diff;
		    }
		}
		
		public static function diff($data1,$data2) {
			//echo "DATA1: $data1\n";
			//echo "DATA2: $data2\n";
			
			return(self::_diff(self::ptBR_to_ISO($data1),self::ptBR_to_ISO($data2)));
			
			
			//list($d1,$m1,$a1) = explode($data1);
			//$diaMes
			//list($d2,$m2,$a2) = explode($data2);
			
		
			
			
			
		}
		
		
		public static function obtemDiasMes($ano="") {
			if( !$ano ) {
				list($d,$m,$ano)=explode("/",date("d/m/Y"));
			}

			$dias = array();
			for($i=1;$i<=12;$i++) {
				
				if( $i == 2 ) {
					// Fevereiro, verificar se ano � bissexto					
					$numDias = $ano % 4 ? 28 : 29;					
				} else {
					$numDias = in_array($i,array(1,3,5,7,8,10,12))?31:30;
				}
				
				$dias[$i] = $numDias;
				
			}
			
			return($dias);

		}
		
		
		/**
		 * Adiciona um m�s em uma data.
		 *
		 * Considera��es.
		 *   - Tratamento para meses com menores dias.
		 *   - Formato: DD/MM/AAAA
		 */
		public static function adicionaMes($data,$meses) {
			list($dia,$mes,$ano) = explode("/",$data);

			if( $meses >= 12 ) {
				$anos = floor($meses/12);
				$ano += $anos;
				$meses -= 12 * $anos;
			}

			$mes += $meses;
			
			if( $mes > 12 ) {
				$mes -= 12;
				$ano++;
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
				$dataBase = date("d/m/Y");
			}

			list($d,$m,$a) = explode("/",$dataBase);

			if( $dia > $d ) {
				// O pr�ximo dia est� neste m�s
				$d = $dia;
			} else if( $dia < $d ) {
				// o pr�ximo dia est� no m�s que vem.
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

		
	}
	
	
	// TESTE
	// echo(MData::adicionaMes("31/05/2005", 23));
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
