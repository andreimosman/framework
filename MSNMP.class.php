<?

	class MSNMP {
	
		protected $host;
		protected $comm;
		
		protected $cachedReply;
		
		protected $parsedData;
		
		protected $networkInfo;
		
		function __construct($host,$comm) {
			$this->host = $host;
			$this->comm = $comm;
			
			$this->cachedReply = array();
			$this->parsedData = array();
			$this->networkInfo = array();
			
			$this->getReply();
		}
		
		protected function getReply() {
			if( !count($this->cachedReply) ) {
				$this->cachedReply = snmpwalkoid($this->host,$this->comm,NULL);
				
				// Informações adicionais da tabela de associação (sinal).
				//$extraReply = snmpwalkoid($this->host,$this->comm,".1.3.6.1.4.1.14988.1.1.1.2.1.3.0");
				
				// MIKROTIK SPECIFIC ASSOCIATION TABLE/SIGNAL LEVEL
				$extraReply = @snmpwalkoid($this->host,$this->comm,".1.3.6.1.4.1.14988.1.1.1.2.1.3");
				if( $extraReply && count($extraReply) ) {
					$this->cachedReply = array_merge($this->cachedReply,$extraReply);
				}
				
			}
			return($this->cachedReply);
		}
		
		public function parse() {
			
			// Se já tem retorna o parseado
			if( !count($this->parsedData) ) {
			
				$r = $this->getReply();
				
				$tmpIF = array();
				$tmpRotas = array();
				$tmpRecursos = array();
				
				$associacoes = array();
				
				$sysInfo = array();
				
				
				$tp = "ORINOCO";
				
				

				while(list($vr,$vl)=each($r)) {
				
					$vl = self::getSNMPValue($vl);
				
					// Parte das interfaces, processar.
					if( strstr($vr,"IF-MIB::") ) {
						// INFORMACOES DA INTERFACE
						$vr = str_replace("IF-MIB::","",$vr);
						list($op,$indice) = explode(".",$vr,2);
						//$vl = self::getSNMPValue($vl);
						//echo "OP: $op, IX: $indice = $vl\n";
						
						switch($op) {
							case 'ifNumber':
								$ni["Number"] = $vl;
								break;
							default:
								$oper = preg_replace('/^if/','',$op);
								$tmpIF[$indice][$oper] = $vl;
							
						}

					} elseif( strstr($vr,"SNMPv2-MIB::") ) {
						// INFORMACOES DO SISTEMA
						$vr = str_replace("SNMPv2-MIB::","",$vr);
						list($op,$indice) = explode(".",$vr,2);
						//$vl = self::getSNMPValue($vl);
						
						$oper = preg_replace('/^sys/','',$op);
						$sysInfo[$oper] = $vl;

					} elseif( strstr($vr,"RFC1213-MIB::") ) {
						// TABELA DE ROTEAMENTO
						$vr = str_replace("RFC1213-MIB::","",$vr);
						list($op,$indice) = explode(".",$vr,2);
						//$vl = self::getSNMPValue($vl);

						$oper = preg_replace('/^ip/','',$op);
						$tmpRotas[$indice][$oper] = $vl;

					} elseif( strstr($vr,"HOST-RESOURCES-MIB::") ) {
						// RECURSOS DO EQUIPAMENTO
						
						// echo $vr . " -- " . $vl . "\n";
						
						$vr = str_replace("HOST-RESOURCES-MIB::","",$vr);
						list($op,$indice) = explode(".",$vr,2);
						//$vl = self::getSNMPValue($vl);

						$oper = preg_replace('/^hr/','',$op);
						
						switch($oper) {
							case 'SystemUptime':
							case 'SystemDate':
								$tmpRecursos[$oper] = $vl;
								break;
							
							case 'StorageIndex':
							case 'StorageType':
							case 'StorageDescr':
							case 'StorageAllocationUnits':
							case 'StorageSize':
							case 'StorageUsed':
							case 'StorageAllocationFailures':
								$tmpRecursos["storage"][$indice][$oper] = $vl;
								break;
							
							case 'DeviceIndex':
							case 'DeviceType':
								$tmpRecursos["devices"][$indice][$oper] = $vl;
								break;
						
						}
					} elseif( strstr($vr,"NMPv2-SMI::enterprises.14988.") || strstr($vr,"SNMPv2-SMI::mib-2.17.4.3.1.1.")
					) {
						if(  strstr($vr,"NMPv2-SMI::enterprises.14988.") ) $tp = "MIKROTIK";
						// TABELA DE ASSOCIAÇÕES
						$oidarray = explode(".",$vr);

						// $oidarray=explode(".",$indexOID);
						$end_num=count($oidarray);
						$mac="";

						for ($counter=2;$counter<8;$counter++)
						{
							$temp=dechex($oidarray[$end_num-$counter]);
							if ($oidarray[$end_num-$counter]<16)
									$temp="0".$temp;

							if (($counter <5) and $mask_mac)
								$mac=":"."xx".$mac;
							else 
								if ($counter==7)
									$mac=$temp.$mac;
								else 
									$mac=":".$temp.$mac;
						}


						$mac_oiu = substr(str_replace(":","-",$mac),0,8);
						$mac=strtoupper($mac);
						
						$associacoes[$mac]["sinal"] = $vl;
						
						// echo "MAC: $mac<br>\n";
						
						
						
						//$tmpRecursos[$oper][$indice] = $vl;
						
						//$tmpRecursos[$indice][$oper] = $vl;

					} elseif( strstr($vr,"SNMPv2-SMI::mib-2.17.4.3.1.3.") ) {
						$vr = str_replace("SNMPv2-SMI::mib-2.17.4.3.1.3.","",$vr);
						$arrMac = explode(".",$vr);
						$aM = array();
						for($i=0;$i<count($arrMac);$i++) {
							$aM[] = strtoupper( str_pad( dechex($arrMac[$i]), 2, "0", STR_PAD_LEFT ) );
						}
						$mac = implode(":",$aM);
						
						//$associacoes[$mac]["iface2"] = $vl;
						
						//echo "MAC: $mac<br>\n";	
				
						
					} elseif( strstr($vr,"SNMPv2-SMI::mib-2.17.4.3.1.2.") || 
						strstr($vr,"SNMPv2-SMI::mib-2.17.4.3.1.3.")
					
					) {
						$vr = str_replace("SNMPv2-SMI::mib-2.17.4.3.1.2.","",$vr);
						$vr = str_replace("SNMPv2-SMI::mib-2.17.4.3.1.3.","",$vr);
						
						$arrMac = explode(".",$vr);
						$aM = array();
						for($i=0;$i<count($arrMac);$i++) {
							$aM[] = strtoupper( str_pad( dechex($arrMac[$i]), 2, "0", STR_PAD_LEFT ) );
						}
						$mac = implode(":",$aM);
						
						$associacoes[$mac]["iface"] = $vl;
						
						// echo "MAC: $mac<br>\n";	
						
					
										
					} else {
						// echo $vr . " -- " . $vl . "\n";
					}
					
				}
				
				// print_r($tmpIF);
				//$ifaces = array();
				//while(list($i,$ctt) = each($tmpIF)) {
				//	$ifaces[] = $ctt;
				//}
				$ifaces = $tmpIF;
				
				$rotas = array();
				
				$ix=0;
				while(list($i,$ctt) = each($tmpRotas)) {
				
					$rotas[$ix] = $ctt;
					$rotas[$ix]["RouteIfDescr"] = $tmpIF[ $ctt["RouteIfIndex"] ]["Descr"] ;
				
				
					$ix++;
				}
				
				while(list($i,$ctt) = each($ifaces)) {
					$ifaces[$i]["associacoes"] = array();
				}
				
				
				while( list($mac,$d) = each($associacoes) ) {
				
					if( $tp == "MIKROTIK" ) {
						if( $d["iface"] && $d["sinal"] ) {					
							$ifaces[ $d["iface"] ]["associacoes"][] = array("mac" => $mac, "sinal" => $d["sinal"]);
						}
					} else {
						if( $d["iface"] && $d["iface"] ) {					
							$ifaces[ $d["iface"] ]["associacoes"][] = array("mac" => $mac);
						}					
					}
					
				}
				
				//print_r($tmpRecursos);
				//print_r($sysInfo);
				//print_r($ifaces);
				// print_r($rotas);
				//print_r($associacoes);
				
				$retorno = array("sysinfo" => $sysInfo, "recursos" => $tmpRecursos, "interfaces" => $ifaces, "rotas" => $rotas);
				
				//print_r($retorno);
				
				return($retorno);
				
				
			
				
			}
			
			
			
			return($this->networkInfo);
			
		
		}
		
		
		
		public static function getSNMPValue($v) {
			list($tp,$vl) = explode(": ",$v,2);
			
			return($vl);
		
		}
		
		
	
	
	}
	
	
	//$snmp = new MSNMP('172.16.133.161', 'public');
	
	//$snmp->parse();
	
	// $netInfo = $snmp->getNetworkInfo();


  //$host = '172.16.133.161';
  //$comm = 'public';
  

  //$tmp = snmpwalkoid($host,$comm, NULL);

  //print_r($tmp);

?>
