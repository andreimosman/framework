<?


	class SistemaOperacional {
	
		public static $FW_SUB_BASERULE			= 2000;
		public static $FW_IP_BASERULE			= 10000;
		public static $FW_IP_BASEPIPE_IN		= 18000;
		public static $FW_IP_BASEPIPE_OUT		= 26000;

		public static $FW_PPPoE_BASERULE		= 34000;
		public static $FW_PPPoE_BASEPIPE_IN		= 42000;
		public static $FW_PPPoE_BASEPIPE_OUT	= 50000;

	
		public static $IFCONFIG	= "/sbin/ifconfig";
		public static $HOSTNAME	= "/bin/hostname";
		public static $ARP		= "/sbin/arp";
		public static $ROUTE	= "/sbin/route";
		
		public static $INSTALL	= "/usr/bin/install";
		public static $DEFAULT_NTP_SERVER = "146.164.48.5";
		
		/**
		 * Construtor
		 */
		public function __construct() {
		
		}
		
		/**
		 * pgDump 
		 */
		public static function pgDump($host,$usuario,$senha,$banco,$arquivoOutput,$opcoes="") {
			$possibilidades = array();
			$possibilidades[] = "/usr/local/bin/pg_dump";
			$possibilidades[] = "/usr/bin/pg_dump";
			
			$pg_dump = "";
			
			for($i=0;$i<count($possibilidades);$i++) {
				if( file_exists($possibilidades[$i]) && is_executable($possibilidades[$i])) {
					$pg_dump = $possibilidades[$i];
					break;
				}
			}
			
			$param = "";
			
			if( $host ) $param .= " -h $host";
			if( $usuario ) $param .= " -U $usuario";
			
			$param .= " " . $opcoes;			
			$param .= " " . $banco;
			
			$comando = $pg_dump . " " . trim($param) . " 2>&1";
			self::executa($comando,NULL,$arquivoOutput);
			
		}
		
		public static function obtemPHP() {
			$possibilidades = array();
			$possibilidades[] = "/usr/bin/php";
			$possibilidades[] = "/usr/local/bin/php";
			$possibilidades[] = "/bin/php";
			
			for($i=0;$i<count($possibilidades);$i++) {
				if( file_exists($possibilidades[$i]) && is_executable($possibilidades[$i])) {
					return($possibilidades[$i]);
				}
			}
			
			return "";
			
			
		}
		
		public static function tar($arquivoTar,$fileList) {
			$possibilidades = array();
			$possibilidades[] = "/bin/tar";
			$possibilidades[] = "/usr/bin/tar";
			$possibilidades[] = "/usr/local/bin/tar";
			
			$tar = "";
			
			for($i=0;$i<count($possibilidades);$i++) {
				if( file_exists($possibilidades[$i]) && is_executable($possibilidades[$i])) {
					$tar = $possibilidades[$i];
					break;
				}
			}
			
			if( is_array($fileList) ) {
				$fileList = implode(" ", $fileList);
			}
			
			$comando = $tar . " cf " . $arquivoTar . " " . $fileList;
			
			return(self::executa($comando));

		}
		
		public static function gzip($arquivo) {
			$possibilidades = array();
			$possibilidades[] = "/bin/gzip";
			$possibilidades[] = "/usr/bin/gzip";
			$possibilidades[] = "/usr/local/bin/gzip";
			
			$gzip = "";
			
			for($i=0;$i<count($possibilidades);$i++) {
				if( file_exists($possibilidades[$i]) && is_executable($possibilidades[$i])) {
					$gzip = $possibilidades[$i];
					break;
				}
			}
			
			$comando = $gzip . " " . $arquivo;
			
			return(self::executa($comando));
		}
		
		
		
		
		/**
		 * Executa um comando no sistema operacional
		 *
		 * retorna o resultado da execução deste comando.
		 */
		public static function executa($comando,$post=NULL,$outputFile="") {
			$retorno = "";
			
			// echo $comando . "\n";
			// echo "EXECUTA: $comando\n";
			
			$fd = popen($comando, ($post ? 'w' : 'r'));
			if($post) {
				fputs($fd,$post);
			}
			$now = time();
			
			if( $outputFile ) {
				$outFD = fopen($outputFile,"w");
			}
			
			
			while(!feof($fd) && ($linha = fgets($fd)) ) {
				if( $outputFile ) {
					fwrite($outFD,$linha,strlen($linha));
				} else {
					$retorno .= $linha;
				}
			}
			
			if( $outputFile ) {
				fclose($outFD);
			}
			
			
			pclose($fd);
			
			//$retorno = shell_exec($comando);
			
			return($retorno);

		}
		
		
		/**
		 * ifConfig / ifUnConfig
		 *
		 * Configura / Remove IP da interface
		 */
		
		public static function ifConfig($iface,$ip,$mascara) {
		
		}
		
		public static function ifUnConfig($iface,$ip) {
		
		}
		
		
		/**
		 * adcionaRegraBW / removeRegraBW
		 *
		 * Adiciona e remove regra com gerenciamento de banda no firewall.
		 */
		public static function adicionaRegraBW($id,$baserule,$basepipe_in,$basepipe_out,$int_iface,$ext_iface,$ip,$mac,$upload_kbps,$download_kbps,$username) {
		
		}
		
		public static function deletaRegraBW($id,$baserule, $basepipe_in,$basepipe_out) {
		
		}
	
		/**
		 * adicionaRegraSP / removeRegraSP
		 *
		 * Adiciona e remove regra de suporte e infra-estrutura
		 */
		
		public static function adicionaRegraSP($id,$baserule,$rede,$ext_iface) {
		
		}
		
		public static function deletaRegraSP($id,$baserule) {
		
		}
		
		/**
		 * Obtem as estatísticas de utilização
		 */
		public static function obtemEstatisticas() {
		
		}
		
		
		/**
		 * setNAT / unsetNAT
		 *
		 * Habilita e desabilita o NAT na interface
		 */
	
		public static function setNAT($iface) {
		
		}
		
		public static function unsetNAT($iface) {
		
		}
		
		/**
		 * routeAdd / routeDelete
		 *
		 * Cria e exclui rotas.
		 */
		public static function routeAdd($rede,$destino) {
		
		}
		
		public static function routeDelete($rede) {
		
		}

		/**
		 * removeARP / atribuiARP
		 * Cria e remove entradas na tabela arp.
		 */

		public static function removeARP($ip) {

		}
		
		public static function atribuiARP($ip,$mac) {
		
		}



		/**
		 * installDir
		 *
		 * Cria um diretório
		 */
		public static function installDir($target,$mode=755,$owner="") {

		}

		/**
		 * fping
		 */
		
		public static function fping($ip,$num_pacotes=2,$tamanho="",$timeout=1200) {
			return( array() );
		}
		
		/**
		 * ntpUpdate
		 *
		 * Atualiza a data via NTP
		 */
		public static function ntpDate($server="") {
		
		}
		
		/**
		 * mailDirMake
		 * Cria um diretório de maildir
		 */
		public static function mailDirMake($target,$uid,$gid) {
		
			self::installDir($target . "/cur","700",$uid,$gid);
			self::installDir($target . "/new","700",$uid,$gid);
			self::installDir($target . "/tmp","700",$uid,$gid);
		
		}

	
	}




?>
