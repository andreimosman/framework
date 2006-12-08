<?

	/**
	 * Classe de resultado para fazer fetch
	 */
	class MDatabaseResultado {
		protected $result;
		
		public function __construct($result) {
			$this->result = $result;
		}
		
		public function fetch() {
			return($this->result->fetchRow());
		}
	
	}

?>
