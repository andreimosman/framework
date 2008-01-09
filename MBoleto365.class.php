<?

	require_once("MBoleto.class.php");

	/**
	 * Implementações de Boleto do Banco Real.
	 *
	 * Carteira Suportada: 20
	 *
	 * TODO: Testes e homologacao.
	 */
	class MBoleto365 extends MBoleto {
	
		protected $comRegistro;

		protected function init() {
			$this->banco = 365;
			$this->nome_banco = "ABN AMRO Real";
			$this->comRegistro = false;
			
		}
		
		
		
		protected function obtemConvenio() {
			if( !$this->convenio ) $this->convenio = $this->padZero($this->agencia,4).$this->padZero($this->conta,7);			
			return($this->convenio);
		}
		
		protected function obtemDigitao() {
			$ret = self::modulo10(self::soma($this->obtemNossoNumero().$this->obtemConvenio()));
			return($ret);
		}
		
		/**
		 * Nosso número - cobrança sem registro.
		 */
		protected function obtemNossoNumero() {
			$tamanho = $this->comRegistro ? 7 : 13;			
			if( $this->nosso_numero ) return $this->nosso_numero;			
			$this->nosso_numero = $this->padZero($this->id,$tamanho);			
			return($this->nosso_numero);
		}
		
		/**
		 * Retorna o campo livre de 25 posições
		 */
		protected function obtemCampoLivre() {
			//$campoLivre = ;
			//$campoLivre .= self::modulo10($campoLivre);
			
			echo "OBTEMCONVENIO: ". $this->obtemConvenio() . "<br>\n";
			echo "OBTEMDIGITAO: ". $this->obtemDigitao() . "<br>\n";
			echo "OBTEMNOSSONUMERO: ". $this->obtemNossoNumero() . "<br>\n";
			
			$campoLivre = $this->obtemConvenio() . $this->obtemDigitao() . $this->obtemNossoNumero();
			echo "CL: $campoLivre<br>\n";
			
			return($campoLivre);
			
			
			
			
			
			
		}


	}
?>