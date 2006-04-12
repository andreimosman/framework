<?
// M�dulo PEAR gerador de c�digo de barras
require_once("Image/Barcode.php");
require_once("MTemplate.class.php");

/**
 * Linha digit�vel: 4 campos mais um d�gito contador;
 *
 * 00000.00000 00000.000000 00000.000000 0 00000000000000 
 *   Campo1      Campo2       Campo3    DC     Campo4 
 *
 * Campo1:
 *    C�digo do Banco  ( 3 d�gitos )
 *    C�digo da Moeda   ( 1 digito )
 *    Nosso N�mero  ( 5 d�gitos ) 
 *    Digito Verificador ( 1 d�gito )
 *
 *
 * Campo2:
 *    Nosso  N�mero ( 6 d�gitos ) Usamos os 6 �ltimos d�gitos do Nosso N�mero.
 *    Ag�ncia ( 4 d�gitos ) 
 *    Digito Verificador ( 1 d�gito ) 
 *
 * Campo3:
 *    Numero da Conta Corrente ( 8 d�gitos )
 *    Carteira ( 2 d�gitos ) 
 *    Digito Verificador ( 1 d�gito ) 
 *
 *
 * Digito Controlador:
 *    DC ( 1 d�gito ) 
 *
 * Campo4:
 *    Fator de Vencimento ( 4 d�gitos )
 *    Valor do documento (10 d�gitos ) Valor do documento, mais adi��o de zeros a esquerda e sem edi��o, pontos ou v�rgulas.
 *
 */


class MBoleto {
    /**
     * Informa��es B�sicas
     */
	protected $banco;		// C�digo do banco
	protected $moeda;	 	// C�digo da moeda (real = 9)
	protected $carteira; 	// N�mero fornecido pelo banco
	protected $agencia;		// Sem o d�gito
	protected $conta;		// Sem o d�gito
	protected $convenio;	// Fornecido pelo banco
	protected $vencimento;	// Data de vencimento
	protected $valor;		// Valor do documento
	protected $id;			// identifica��o �nica do boleto (gerada pelo sistema mosman)
	protected $sacado;      // Nome do sacado
	protected $scpf;		// CPF/CNPJ Sacado
	protected $sendereco;	// Endere�o Sacado
	protected $cedente;		// Nome do Cedente
	protected $ccpf;		// CPF/CNPJ do Cedente
	protected $tx_juros;	// Taxa de Juros
	protected $multa;		// Multa
	protected $observacoes;	// Observa��es gerais (ex: N�o receber ap�s vencimento). Mandar observa��es ja tabulado em html
	
	
	/**
	 * Valores Pr�-Definidos
	 */
	
	/**
	 * Valores Processados
	 */
	
	protected $nossoNumero;	// convenio + id 
	protected $campo1;
	protected $campo2;
	protected $campo3;
	protected $campo4;
	protected $dc;
	protected $linha_digitavel;
	protected $codigo_boleto;
	
	
	// Vari�veis extras
	protected $tpl;
	protected $tplPath;
	protected $imgPath;
	

	function MBoleto($banco,$carteira,$agencia,$conta,$convenio,$vencimento,$valor,$id,$sacado,$scpf,$cedente,$ccpf,$tx_juros,$multa,$sendereco,$observacoes)  {
				
		$this->banco       = $banco;
		
		$this->carteira    = $carteira;
		$this->agencia     = $agencia;
		$this->conta       = $conta;
		$this->convenio    = $convenio;
		$this->vencimento  = $vencimento;
		$this->valor       = $valor;
		$this->id          = $id; // Seu numero
		$this->sacado	   = $sacado;
		$this->scpf		   = $scpf;
		$this->cedente     = $cedente;
		$this->ccpf		   = $ccpf;
		$this->tx_juros    = $tx_juros;
		$this->multa	   = $multa;
		$this->sendereco   = $sendereco;
		$this->observacoes = $observacoes;
		
		
		
		
		// Valores est�ticos pr�-definidos
		$this->moeda       = "9"; 		// Real
		
		// Valores Processados
		$this->nossoNumero = $this->obtemNossoNumero();
		
		//$this->linha_digitavel = $this->obtemLinhaDigitavel();
		$this->processa();

	}
	
	function obtemNossoNumero() {
		return( $this->convenio . $this->id );
	}
	
	function processa() {
	    // Base
	    $base = "";
	
		// Campo1
		$partes = $this->banco . $this->moeda . substr($this->nossoNumero, 0,5); 
		$base .= $partes;
		$dv = $this->modulo10($this->soma($partes));
		$campo1 = $this->inserePonto($partes.$dv,5);
		//echo "CAMPO1: $campo1 <br>\n";
		
		// Campo2
		$partes = substr($this->nossoNumero, 5) . $this->agencia;
		$base .= $partes;
		$dv = $this->modulo10($this->soma($partes));
		$campo2 = $this->inserePonto($partes,5) . $dv;
		//echo "PARTES2: $partes <br>\n";		
		//echo "CAMPO2: $campo2 <br>\n";
		
		$conta = $this->padZero( $this->conta, 8);
		$partes = $conta . $this->carteira; //$this->padZero( $this->conta . $this->carteira, 10);
		
		$base .= $partes;
		$dv = $this->modulo10($this->soma($partes));
		$campo3 = $this->inserePonto($partes,5) . $dv;
		//echo "PARTES3: $partes <br>\n";		
		//echo "CAMPO3: $campo3 <br>\n";
		
		$vl = str_replace(',','', $this->valor );
		$vl = str_replace('.','', $vl );
		
		$valordoc = $this->padZero($vl,10);
		$fatvenc  = $this->fatorData($this->vencimento);
		
		$partes = $fatvenc . $valordoc;
		$base .= $partes;
		$campo4 = $partes;
		//echo "PARTES4: $partes <br>\n";		
		//echo "CAMPO4: $campo4 <br>\n";
		
		//echo "<hr>\n";
		//echo "BASE: $base<br>\n";
		
		// Codigo de barras sem o DC
		$cb_base = $this->banco.$this->moeda.$fatvenc.$valordoc.$this->nossoNumero.$this->agencia.$conta.$this->carteira;
		//echo "BASE: $cb_base<br>\n";
		
		$dc = $this->modulo11( $this->soma11( $cb_base ) );
		
		//echo "DC: " . $dc . "<br>\n";
		
		$this->linha_digitavel = $campo1." ".$campo2." ".$campo3." ".$dc." ".$campo4;
		$this->codigo_boleto      = $this->banco.$this->moeda.$dc.$fatvenc.$valordoc.$this->nossoNumero.$this->agencia.$conta.$this->carteira;
		
		//echo "<hr>\n";
		//echo "Linha digit�vel: " . $this->linha_digitavel . "<br>\n";
		//echo "Linha digit�vel: " . $this->codigo_boleto . "<br>\n";
		
		
		
	}
	
	function barCode($cod) {
	   // Imprime o c�digo de barras.
	   
	   Image_Barcode::draw($cod,"int25", "png");
	   
	}
	
	function fatorData($data) {
	   list($d,$m,$a) = explode("/",$data);
	   
	   // Constante: 07/10/1997
	   $dt_const = mktime(0,0,0,10,7,1997);
	   // Retorna o valor em dias (e n�o em segundos)
	   return( (int)((mktime(0,0,0,$m,$d,$a) - $dt_const)/(24*60*60)) );
	   
	}
	
	/**
	 * Entra com a vari�vel e o tamanho do campo
	 */
	function padZero($variavel,$tamanho) {
		//echo "VAR: $variavel<br>\n";
		//echo "T: $tamanho<br>\n";
		//echo "Zrs: " . (
		return( str_pad($variavel, $tamanho, "0", STR_PAD_LEFT) );
	}
	
	// Insere um campo na posicao especificada;
	function inserePonto($str,$p) {
	   return( substr($str,0,$p) . "." . substr($str,$p) );
	}
	
	function soma($p) {
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
	
	
	protected function modulo10($soma) {
	   $dv = 10 - ($soma % 10);
	   if( $dv==10 ) $dv = 0;
	   return($dv);
	}

	protected function Soma11($Partes){ 
		$Quant = strlen($Partes); 
		$Mod11 = '4329876543298765432987654329876543298765432'; 
		$Soma=0;
		for ($i = $Quant-1; $i >= 0; $i--) { 
			$Y = $Partes[$i]*$Mod11[$i]; 
			$Soma += $Y; 
		} 
		return $Soma; 
	} 
	
	protected function modulo11($soma) {
	   $dv = 11 - ($soma % 11);
	   if( $dv==10 || $dv==1 || $dv==0 ) $dv = 1;
	   return($dv);
	}
	
	
	
	
	/**
	 * Metodos gerais de acesso aos valores
	 */
	public function obtemCodigoBoleto() {
	   return($this->codigo_boleto);
	}
	
	public function obtemLinhaDigitavel() {
	   return($this->linha_digitavel);
	}
	
	public function setTplPath($path) {
		$this->tplPath = $path;
		$this->tpl = new MTemplate($path);
	}
	
	public function setImgPath($path) {
		$this->imgPath = $path;
		$this->tpl->atribui("imagens",$this->imgPath);
	}	
	
	public function exibe($banco) {
		$mapa = array(
						"001" => "layout-bb.html"
		);
		
		$this->tpl->atribui("codigo_boleto",$this->codigo_boleto);
		$this->tpl->atribui("linha_digitavel",$this->linha_digitavel);
		$this->tpl->atribui("valor",$this->valor);
		$this->tpl->atribui("carteira",$this->carteira);
		$this->tpl->atribui("agencia",$this->agencia);
		$this->tpl->atribui("conta",$this->conta);
		$this->tpl->atribui("convenio",$this->convenio);
		$this->tpl->atribui("vencimento",$this->vencimento);
		$this->tpl->atribui("id",$this->id);
		$this->tpl->atribui("sacado",$this->sacado);
		$this->tpl->atribui("scpf",$this->scpf);
		$this->tpl->atribui("cedente",$this->cedente);
		$this->tpl->atribui("ccpf",$this->ccpf);
		$this->tpl->atribui("tx_juros",$this->tx_juros);
		$this->tpl->atribui("multa",$this->multa);
		$this->tpl->atribui("sendereco",$this->sendereco);
		$this->tpl->atribui("observacoes",$this->observacoes);
			
		
		//$this->tpl->atribui("vencimento",$this->vencimento);
		//$this->tpl->atribui("",$this->);
		//$this->tpl->atribui("",$this->);
		
		$this->tpl->exibe($mapa[$banco]);
		
		
	}
	



}

//
// Teste
//
//$cod = @$_REQUEST["codigo"];
//if( $cod ) {
//   MBoleto::barCode($cod);
//} else {
//	$b = new MBoleto("001","18","6666","77777","888888","09/06/2005","53.26","22222");
//	$b->setTplPath("boletos/");
//	$b->setImgPath("boletos/");
//	
//	$b->exibe("001"); // Gera boleto para o banco "001";
//}





?>
