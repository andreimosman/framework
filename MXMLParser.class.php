<?

	require_once("XML/Parser.php");

	class MXMLParser extends XML_Parser {
	
		function __construct() {
			parent::__construct();
		}
		
		/**
		  * handle start element
		  *
		  * @access private
		  * @param  resource  xml parser resource
		  * @param  string    name of the element
		  * @param  array     attributes
		  */
		  function startHandler($xp, $name, $attribs) {
			//printf("handle start tag: %s\n", $name);
		  }

		 /**
		  * handle start element
		  *
		  * @access private
		  * @param  resource  xml parser resource
		  * @param  string    name of the element
		  */
		  function endHandler($xp, $name) {
			//printf("handle end tag: %s\n", $name);
		  }

		 /**
		  * handle character data
		  *
		  * @access private
		  * @param  resource  xml parser resource
		  * @param  string    character data
		  */
		  function cdataHandler($xp, $cdata) {
			// does nothing here, but might e.g. print $cdata
		  }
	
	}


?>
