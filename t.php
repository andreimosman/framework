<?

   $cmd = "/sbin/ifconfig";
   $fd = popen($cmd,"r");
   
   $mac_re = "/([0-9A-Fa-f]{2}\:){5}([0-9A-Fa-f]{2})/";
   $ip_re = "/(?:\d{1,3}\.){3}\d{1,3}/";
   
   $iflist = array();
   $iface = "";
   $ifmatch = false;
   
   while( ($linha = fgets($fd)) && !feof($fd) ) {
      $matches = array();
      $ifaceinfo = array();
      //$linha = preg_replace("/^[\s\t]/","",$linha);
      
      /**
       * Linux Match
       */
      preg_match("/^[A-Za-z0-9]+[\s\t]/",$linha,$ifaceinfo,PREG_OFFSET_CAPTURE);
      if(@$ifaceinfo[0][0]) {
         $iface = @$ifaceinfo[0][0];
         $ifmatch = true;
         //echo "IFACE: " . $iface . "\n";
      }
      
      /**
       * FreeBSD Match
       */
      if( !$ifmatch ) {
        preg_match("/^[A-Za-z0-9]+\:\s/",$linha,$ifaceinfo,PREG_OFFSET_CAPTURE);
        if(@$ifaceinfo[0][0]) {
          $iface = @$ifaceinfo[0][0];
          $ifmatch = true;
        }
      }

      if( $ifmatch ) {
         echo $linha;
         echo "IFMATCH\n";

         $iface = str_replace(":","",trim($iface));
         $iflist[$iface] = array();
         $iflist[$iface]["ips"] = array();
         $ifmatch = false;
      }

      $matches = array();

      preg_match($ip_re,$linha,$matches,PREG_OFFSET_CAPTURE);
      if(count($matches)) {
         //echo "IP MATCHED!!!: " . count($matches) . "\n" ;
         $iflist[$iface]["ips"][] = $matches[0][0];
         //echo "M: " . $matches[0][0] . "\n";
      }

      $matches = array();

      preg_match($mac_re,$linha,$matches,PREG_OFFSET_CAPTURE);
      $linha = strtoupper($linha);

      //echo $linha;
      //echo "C: " . count($matches) . "\n";

      
      $mac = @$matches[0][0];
      //echo  @$matches[0][0] . "\n";



      
      if($mac) {
         //echo "MAC: " . $mac . "\n";
         //$maclist[$mac] = 1;
         $iflist[$iface]["mac"] = strtoupper($mac);
         // Procura pela proxima interface
         $ifmatch = false;
         
      }      
   }
   fclose($fd);
   
   while(list($iface) = each($iflist) ) {
      echo "IFACE: $iface \n";
      echo "  - MAC: " . $iflist[$iface]["mac"] . "\n";
      if( count($iflist[$iface]["ips"]) ) {
         echo "  - IPS: ";
         for($i=0;$i<count($iflist[$iface]["ips"]);$i++) {
            echo $iflist[$iface]["ips"][$i];
         }
         echo "\n";
      }
   }
   
?>
