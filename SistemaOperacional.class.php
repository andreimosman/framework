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
		 * Executa um comando no sistema operacional
		 *
		 * retorna o resultado da execução deste comando.
		 */
		public static function executa($comando,$post=NULL) {
			$retorno = "";
			
			// echo $comando . "\n";
			// echo "EXECUTA: $comando\n";
			
			$fd = popen($comando, ($post ? 'w' : 'r'));
			if($post) {
				fputs($fd,$post);
			}
			$now = time();
			while(!feof($fd) && ($retorno .= fgets($fd)) ) {

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
		 * installDir
		 *
		 * Cria um diretório
		 */
		public static function installDir($target) {
		
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

	
	}




?>
