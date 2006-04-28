<?
/**
 * Classe para geração de arquivos PDF com base em arquivos HTML
 * Utiliza-se internamente da classe HTML_ToPDF.
 * (http://www.rustyparts.com/pdf.php)
 */

/********************************************************
 * TAGS ESPECIAIS                                       *
 ********************************************************
 *
 * <!--NewPage-->   Quebra de página
 *
 ********************************************************/


/********************************************************
 * ESTILOS PARA O PDF                                   *
 ********************************************************
 * <style>
 * // Change the paper size, orientation, and margins 
 * @page {
 *   size: 8.5in 14in;
 *   orientation: landscape;
 * }
 * // This is a bit redundant, but its works ;) 
 * // odd pages 
 * @page:right {
 *   margin-right: 1.0cm;
 *   margin-left: 1.0cm;
 *   margin-top: 1.0cm;
 *   margin-bottom: 1.0cm;
 * }
 * // even pages 
 * @page:left {
 *   margin-right: 1.0cm;
 *   margin-left: 1.0cm;
 *   margin-top: 1.0cm;
 *   margin-bottom: 1.0cm;
 * }
 * </style>
 ********************************************************/


if(!defined('_M_HTML2PDF')) {

	require_once("HTML_ToPDF.php");

	class MHTML2PDF {
		// Configuração do Aplicativo
		protected $tmpDir;
		protected $html2ps;
		protected $ps2pdf;
		
		// Personalizações
		protected $cabecalho;
		protected $rodape;
		
		// Debug
		protected $debug;
		
		
		/**
		 * Inicializa propriedades do objeto
		 */
		function initVars() {
			$this->debug=0;
			$this->tmpDir  = '/tmp';
			$this->html2ps = "/usr/local/bin/html2ps";
			$this->ps2pdf  = "/usr/bin/ps2pdf";
			
			$this->cabecalho = array();
			$this->rodape    = array();
			
			//$this->setHeader('left','&nbsp');
			//$this->setFooter('center','&nbsp');
			//HTML_ToPDF::$footers = array();
			
		}
		
		/**
		 * Adiciona propriedades no cabecalho e rodapé
		 * Valores especiais:
		 *   $D = Data/Hora; 
		 *   $N = Numero da pagina; 
		 *   $T = Título do documento;
		 *   $U = URL/Nome do Arquivo; 
		 *   $[meta-name] => Uma meta-tag, ex: $[autor]
		 */
		function setHeader($op,$valor) {
			$this->cabecalho[$op]=$valor;
		}
		
		function setFooter($op,$valor) {
			$this->rodape[$op]=$valor;
		}
		
		function setDebug($num) {
			$this->debug = $num;
		}
		
		/**
		 * Processa o header e footer
		 */
		protected function processaHeaderFooter(&$pdf) {
			// Zera os padrões:
			$pdf->footers = array();
			$pdf->headers = array();
			
			while( list($op,$valor) = each($this->cabecalho) ) {
				$pdf->setHeader($op,$valor);
			}

			while( list($op,$valor) = each($this->rodape) ) {
				$pdf->setFooter($op,$valor);
			}

		}
		 
		
		
		/**
		 * Constructor
		 */
		
		function __construct() {
			$this->initVars();
		}
		
		/**
		 * Funcao de conversao
		 *
		 * Recebe o host que contem as referências para imagem e o caminho
		 * padrão para a busca das imagens.
		 *
		 * Retorna o caminho para o arquivo temporario contendo o PDF gerado.
		 */
		function converte($arquivo,$host,$defaultPath='/') {
			$pdf =& new HTML_ToPDF($arquivo,$host);
			$pdf->setTmpDir($this->tmpDir);
			$pdf->setHtml2Ps($this->html2ps);
			$pdf->setPs2Pdf($this->ps2pdf);
			$pdf->setDefaultPath($defaultPath);
			
			//$pdf->setTitle('Teste');
			
			$this->processaHeaderFooter($pdf);
			
			//$pdf->setDebug(1);
			
			$result = $pdf->convert();

			// Check if the result was an error
			if (PEAR::isError($result)) {
				return "";	// Se tiver erro não retorna nada.
				//die($result->getMessage());
			}
			
			// Retorna o caminho para o arquivo temporário gerado.
			return( $this->tmpDir . "/" . basename($result));
			
		}
		
		/**
		 * Funcao de conversao
		 * Recebe a HTML na própria string
		 * Retorna o arquivo temporario contendo o PDF gerado.
		 */
		function converteHTML($html,$host, $defaultPath='/') {
			$tempFile = tempnam($this->tmpDir,"mpdf-");
			$fd=fopen($tempFile,"w");
			fputs($fd,$html);
			fclose($fd);
			
			$retorno = $this->converte($tempFile,$host,$defaultPath);
			
			unlink($tempFile);
			
			return($retorno);
		}
	
	
	}





	/**
	 * Teste
	 */
	
	/**
	$p = new MHTML2PDF();
	
	//$p->setHeader('color','blue');
	//$p->setHeader('left','Mosman Megafocker do Mau');
	//$p->setFooter('center','&nbsp;');
	
	$arqPDF = $p->converteHTML("<html><head><title>&nbsp;</title></head><body>Isso é um teste muito <b>punk</b><!--NewPage-->Pagina 2 megafocker do mau!</body></html>","dev.mosman.com.br");
	//echo "AP: $arqPDF<br>\n";
	
	if( !$arqPDF ) {
		echo "Alguma coisa aconteceu";
	} else {
		// Exibir o PDF na tela:
		
		//echo "

		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Type: application/pdf');
		header('Content-Disposition: attachment; filename="example.pdf"');
		readfile($arqPDF);
		
		//copy(
	}
	
	*/
	


}

?>
