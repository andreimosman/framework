<?

	require_once("MBoleto.class.php");

	/**
	 * Implementa��es de Boleto do Banco do Brasil.
	 */
	class MBoleto001 extends MBoleto {
	
		protected function init() {
			$this->banco = 1;
			$this->nome_banco = "Banco do Brasil";
		}
		
		protected function obtemNossoNumero() {
			if( $this->nosso_numero ) return $this->nosso_numero;
		
			$nossoNumero = "";
			if( strlen((int)$this->convenio) == 7 ) {
				// Convenio com 7 d�gitos, nosso numero com 17
				$nossoNumero = $this->padZero($this->convenio,7) . $this->padZero($this->id,10);
			} else {
				// Convenio com 6 digitos, nosso numero com 11 
				$nossoNumero = $this->padZero($this->id,11);						
			}

			$this->nosso_numero = $nossoNumero;
			return($this->nosso_numero);
		}
		
		protected function obtemCampoLivre() {
			if( strlen((int)$this->convenio) == 7 ) {
				/**
				 * Sistema de cobran�a do banco do brasil (BBCobran�a) usa o campo livre
				 * com 7 digitos p/ convenio + 10 digitos p/ id (seu numero) + 
				 * 2 d�gitos p/ carteira e (provavelmente) faz um PAD para o tamanho de 25 do campo.
				 */

				// Convenio com 7 d�gitos, nosso numero com 17
				$campoLivre = $this->padZero($this->obtemNossoNumero(),17) . $this->carteira;
			} else {
				// Convenio com 6 d�gitos, nosso numero com 11 d�gitos
				// OBS:
				//	- cedente = conta
				$campoLivre = $this->padZero($this->obtemNossoNumero(),11) . $this->padZero($this->agencia,4) . $this->padZero($this->conta,8) . $this->padZero($this->carteira,2);

			}
			return($this->padZero($campoLivre,25));
		
		}
	
	}