<?

	require_once("MBoleto.class.php");

	/**
	 * Implementações de Boleto da CEF.
	 */
	class MBoleto104 extends MBoleto {

		protected function init() {
			$this->banco = 104;
			$this->nome_banco = "Caixa Economica Federal";
		}
		
		protected function obtemNossoNumero() {
			if( !$this->nosso_numero ) {
				if( $this->carteira == "SR" || $this->carteira == "82" ) {
					// Carteira sem registro
					$this->nosso_numero = "82" . $this->padZero($this->id,8);
				} else {
					// Carteira Rapida
					$this->nosso_numero = "9" . $this->padZero($this->id,9);
				}
			}
				
			return($this->nosso_numero);		
		}
		
		protected function obtemCampoLivre() {
			$campoLivre = $this->padZero($this->obtemNossoNumero,10) . $this->padZero($this->cnpj_ag_cedente,4) . $this->padZero($this->operacao_cedente,3) . $this->padZero($this->codigo_cedente,8);
			return($this->padZero($campoLivre,25));
		}

	}


?>
