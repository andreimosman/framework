<?

	require_once("MBanco.class.php");

	class MCarne extends MBanco {
		protected $valor;
		protected $contrato;
		protected $nosso_numero;
		protected $data;
	
		public function __construct( $valor, $contrato, $nosso_numero, $data) {
			$this->valor = $valor;
			$this->contrato = $contrato;
			$this->nosso_numero = $nosso_numero;
			$this->data = $data;
		}
		
		public function obtemCodigoBarras() {
			$codigo_barras = self::padZero($this->contrato,7);
			$codigo_barras .= self::fatorData($this->data);
			$codigo_barras .= self::padZero($this->nosso_numero,10);
			$codigo_barras .= self::padZero(number_format($this->valor,2,"",""),10);
			
			return($codigo_barras);
		}
		
		public function obtemLinhaDigitavel() {
			$cb = $this->obtemCodigoBarras();
			$ld = $this->inserePonto($cb,8);
			$ld = $this->inserePonto($ld,17);
			$ld = $this->inserePonto($ld,26);
			return($ld);
		}
	
		static function padZero($variavel,$tamanho) {
			//echo "VAR: $variavel<br>\n";
			//echo "T: $tamanho<br>\n";
			//echo "Zrs: " . (
			return( str_pad($variavel, $tamanho, "0", STR_PAD_LEFT) );
		}

		static function fatorData($data) {
		   list($d,$m,$a) = explode("/",$data);

		   // Constante: 07/10/1997
		   $dt_const = mktime(0,0,0,10,7,1997);
		   // Retorna o valor em dias (e não em segundos)
		   return( (int)((mktime(0,0,0,$m,$d,$a) - $dt_const)/(24*60*60)) );

		}	
	
		static function inserePonto($str,$p) {
		   return( substr($str,0,$p) . "." . substr($str,$p) );
		}
	
	}
	/**
	$valor = "10.50";
	$contrato = "18";
	$nosso_numero = 5;
	$data = "10/10/2007";
	
	$t = new MCarne($valor,$contrato,$nosso_numero,$data);
	
	echo "CB: " . $t->obtemCodigoBarras() . "\n";
	echo "LD: " . $t->obtemLinhaDigitavel() . "\n";
	*/
?>
