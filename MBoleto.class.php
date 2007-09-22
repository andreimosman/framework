<?

	require_once("MBanco.class.php");

	/**
	 * Classe base p/ geração de boletos.
	 */
	abstract class MBoleto extends MBanco {
	
		/**
		 * Informações Básicas
		 */
		protected $banco;		// Código do banco
		protected $moeda;	 	// Código da moeda (real = 9)
		protected $carteira; 		// Número fornecido pelo banco
		protected $agencia;		// Sem o dígito
		protected $conta;		// Sem o dígito
		protected $convenio;		// Fornecido pelo banco
		protected $vencimento;		// Data de vencimento
		protected $valor;		// Valor do documento
		protected $id;			// identificação única do boleto (gerada pelo sistema mosman)
		
		protected $cnpj_ag_cedente;	// CNPJ da Agencia Cedente (utilizado pela CEF)
		protected $codigo_cedente;	// Código do Cedente (CEF)
		protected $operacao_cedente;	// Operação código cedente
		
		// Variáveis para cache
		protected $nosso_numero;
		protected $linha_digitavel;
		protected $codigo_boleto;
		
		// Nome do Banco
		protected $nome_banco;
		
		
		public function __construct() {
			$this->init();		
		}
		
		public function geraBoleto($agencia,$conta,$carteira,$convenio,$vencimento,$valor,$id,$moeda=9,$cnpj_ag_cedente="",$codigo_cedente="",$operacao_cedente="") {
			$this->carteira    = $carteira;
			$this->agencia     = $agencia;
			$this->conta       = $conta;
			$this->convenio    = $convenio;
			$this->vencimento  = $vencimento;
			$this->valor       = $valor;
			$this->id          = $id; // Seu numero

			$this->moeda       = $moeda;	// 9 por padrão
			
			// CEF
			$this->cnpj_ag_cedente 	= $cnpj_ag_cedente;
			$this->codigo_cedente  	= $codigo_cedente;
			$this->operacao_cedente	= $operacao_cedente;
			$linha_digitavel   = "";
			$codigo_barras     = "";
			
			$this->processa();
			
		}
		
		public static function &factory($banco,$agencia,$conta,$carteira,$convenio,$vencimento,$valor,$id,$moeda=9,$cnpj_ag_cedente="",$codigo_cedente="",$operacao_cedente="") {
			$retorno = null;
			
			$bco = self::padZero($banco,3);
			$classe = "MBoleto".$bco;
			$include_file = $classe.".class.php";			
			if(! @include_once($include_file) ) {
				throw new MException("Banco não encontrado.");
			}
			
			$retorno = new $classe;
			$retorno->geraBoleto($agencia,$conta,$carteira,$convenio,$vencimento,$valor,$id,$moeda,$cnpj_ag_cedente,$codigo_cedente,$operacao_cedente);
			
			return($retorno);
		}
		
		abstract protected function init();
		
		abstract protected function obtemCampoLivre();
		abstract protected function obtemNossoNumero();


		/**
		 * Gera a linha digitável e o código de barras
		 */
		public function processa() {
			$this->nosso_numero = $this->obtemNossoNumero();
			$campoLivre = $this->obtemCampoLivre();
			
			//echo "Campo Livre: $campoLivre<br>\n";
		
			// Base
			$base = "";

			/**
			 * Primeiro Campo
			 *  - Código do Banco (3 digitos)
			 *  - Código da Moeda (1 digito)
			 *  - Cinco primeiras posições do campo livre
			 *  - Dígito Verificador
			 */
			
			$partes = $this->padZero($this->banco,3) . $this->padZero($this->moeda,1) . substr($campoLivre, 0,5); 
			$base .= $partes;
			$dv = $this->modulo10($this->soma($partes));
			$campo1 = $this->inserePonto($partes.$dv,5);
			//echo "C1: $campo1<br>\n";
			
			
			/**
			 * Segundo Campo
			 * - Posições de 6 a 15 do campo livre (10 dígitos)
			 * - Dígito verificador
			 */

			$partes = substr($campoLivre, 5,10);
			$base .= $partes;
			$dv = $this->modulo10($this->soma($partes));
			$campo2 = $this->inserePonto($partes,5) . $dv;
			//echo "C2: $campo2<br>\n";
			
			/**
			 * Terceiro Campo
			 * - Posições de 16 a 25 do campo livre
			 * - Dígito Verificador
			 */

			$partes = substr($campoLivre, 15,10);
			$base .= $partes;
			$dv = $this->modulo10($this->soma($partes));
			$campo3 = $this->inserePonto($partes,5) . $dv;

			//echo "C3: $campo3<br>\n";
			
			/**
			 * Quarto Campo
			 * - Dígito Verificador Geral do código de Barras
			 * Feito abaixo.
			 */
			
			
			
			/**
			 * Quinto Campo (O quarto é o DV geral, será feito posteriormente)
			 * - Fator do vencimento (4 dígitos)
			 * - Valor Nominal (10 dígitos)
			 */


			$vl = str_replace(',','', $this->valor );
			$vl = str_replace('.','', $vl );

			$valordoc = $this->padZero($vl,10);
			$fatvenc  = $this->fatorData($this->vencimento);

			$partes = $fatvenc . $valordoc;
			$base .= $partes;
			$campo5 = $partes;

			//echo "C5: $campo5<br>\n";

			/**
			 * Base do código de barras (para calculo do DC (quarto campo da linha digitável)
			 * ERRO DO DC
			 */
			$cb_base = $this->padZero($this->banco,3).$this->moeda.$fatvenc.$valordoc.$campoLivre;
			$dc = $this->modulo11( $this->soma11( $cb_base ) );	// Quarto Campo.


			$this->linha_digitavel = $campo1." ".$campo2." ".$campo3." ".$dc." ".$campo5;
			$this->codigo_boleto      = $this->padZero($this->banco,3).$this->moeda.$dc.$fatvenc.$valordoc.$campoLivre;
			
			//echo "LD: " . $this->linha_digitavel . "<br>\n";
			//echo "CB: " . $this->codigo_boleto . "<br>\n";

		
		}
		
		public function obtemLinhaDigitavel() {
			return $this->linha_digitavel;
		}
		
		public function obtemCodigoBoleto() {
			return $this->codigo_boleto;
		}














		/*******************************************
		 *          FUNCOES DE APOIO               *
		 *******************************************/

		/**
		 * Entra com a variável e o tamanho do campo, 
		 * Preenche o resto com zeros à esquerda
		 */
		static function padZero($variavel,$tamanho) {
			//echo "VAR: $variavel<br>\n";
			//echo "T: $tamanho<br>\n";
			//echo "Zrs: " . (
			return( str_pad($variavel, $tamanho, "0", STR_PAD_LEFT) );
		}

		/**
		 * Insere um ponto na posição especificada
		 */
		protected function inserePonto($str,$p) {
		   return( substr($str,0,$p) . "." . substr($str,$p) );
		}

		/**
		 * Obtem o fator da data com base em 07/10/1997 conforme regulamentação Febraban
		 */
		function fatorData($data) {
		   list($d,$m,$a) = explode("/",$data);

		   // Constante: 07/10/1997
		   $dt_const = mktime(0,0,0,10,7,1997);
		   // Retorna o valor em dias (e não em segundos)
		   return( (int)((mktime(0,0,0,$m,$d,$a) - $dt_const)/(24*60*60)) );

		}	

		
	}
	
	/**

	$banco   	= '001';
	$agencia 	= '0254';		// Sem o DV
	$conta   	= '26272';		// Sem o DV
	$carteira	= '18';
	$convenio   = '1276130';
	$vencimento = '14/08/2006';	// Formato brasileiro
	$valor		= '1000,00';		// Tanto faz ponto ou virgula
	$id			= '0011';
	
	$moeda = "9";

	$boleto = MBoleto::factory($banco,$agencia,$conta,$carteira,$convenio,$vencimento,$valor,$id,$moeda,$cnpj_ag_cedente,$codigo_cedente,$operacao_cedente);
	
	echo "LD: " . $boleto->obtemLinhaDigitavel() . "<BR>\n";
	echo "CB: " . $boleto->obtemCodigoBoleto() . "<BR>\n";
	
	*/

?>
