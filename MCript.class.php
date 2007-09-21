<?

	class MCript {
		public static function criptSenha($senha) {
			$sal = '$1$';
			for($i=0;$i<8;$i++) {
				$j = mt_rand(0,53);
				if($j<26)
					$sal .= chr(rand(65,90));
				else if($j<52)
					$sal .= chr(rand(97,122));
				else if($j<53)
					$sal .= '.';
				else
					$sal .= '/';
			}
			$sal .= '$';
			return( crypt($senha,$sal) );
		}

	}

?>
