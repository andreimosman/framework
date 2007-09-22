<?

	require_once("MBoleto.class.php");

	/**
	 * Implementações de Boleto do Bradesco.
	 */
	class MBoleto237 extends MBoleto {

		protected function init() {
			$this->banco = 237;
			$this->nome_banco = "Bradesco";
		}

		protected function obtemNossoNumero() {
			if( !$this->nosso_numero ) 
				$this->nosso_numero = $this->padZero($this->id,11);
				
			return($this->nosso_numero);
		}
		
		protected function obtemCampoLivre() {
			$campoLivre = $this->padZero($this->agencia,4) . $this->padZero($this->carteira,2) . $this->padZero($this->obtemNossoNumero(),11) . $this->padZero($this->conta,7) . "0";
			return($this->padZero($campoLivre,25));
		}
	
	}


?>
