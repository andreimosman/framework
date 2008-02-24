<?

  require_once("MDatabase.class.php");	// Conexão com o banco de dados.
  require_once("MInet.class.php");		// Utilizado na validação de campos inet.

  /**
   * Classe de abstracao para modelo de dados
   */
  Abstract class MPersiste {
    protected static $_prefix = "";
    
    protected static $_DEBUG = true;

    protected $_tabela;
    protected $_campos;
    protected $_chave;
    protected $_ordem;
    protected $_sequence;
    protected $_filtros;
    protected $_language;
    
    protected $_forupdate;

    protected static $bd;
    
    
    protected function __construct($bd=null) {
      $this->bd = ($bd?$bd:MDatabase::getInstance());
      
      $this->_campos 	= array();
      $this->_chave 	= "";
      $this->_tabela 	= "";
      $this->_sequence 	= "";
      $this->_ordem	= "";
      $this->_filtro	= array();
      $this->_language	= "pt_BR";
      $this->_forupdate = false;
      
    }
    
    public static function setDebug($st) {
    	self::$_DEBUG = $st;
    }
    
    public static function configuraPrefixo($prefixo) {
      self::$_prefix = $prefixo;
    }
    
    public function begin() {
      if( $this->bd ) {
        $this->bd->begin();
      }
    }
    
    public function commit() {
      if( $this->bd ) {
        $this->bd->commit();
      }
    }
    
    public function rollback() {
      if( $this->bd ) {
        $this->bd->rollback();
      }
    }
    abstract static function init();
    
    public static function &factory($tabela,$bd=null) {
      if( !self::$_prefix ) {
        throw new MException("Classe nao inicializada\n");
      }

      $class_name = self::$_prefix . "_" . strtoupper($tabela);
      
      $object = new $class_name;

      if( !($object instanceof MPersiste) ) {
        throw MException("Classe '$class_name' nao eh uma classe de persistencia");
      }
      
      return($object);
      
    }
    
    protected function _where($condArray,$recursive=false,$forceop="AND") {
      $cond = array();
      
      $operadores = array( "=" => "=", "%" => "ilike", "!=" => "!=", "!" => "!=", "in" => "*especial*", "!in" => "*especial*", "array in" => "*especial*", "null" => "*especial*", "~" => '~', "~*" => '~*' );
      
      $keys = array_keys($condArray);
      
      $tipo_K = "array";
      
      for($i=0;$i<count($keys);$i++) {
        if( !is_numeric($keys[$i]) ) {
          $tipo_K = "hash";
          break;
        }
      }
      
      if( $tipo_K == "hash" ) {
        $condArray = array( $condArray );
      }

      for( $i=0;$i<count($condArray);$i++ ) {
        while(list($campo,$vl) = each($condArray[$i])) {
          //$valor 		= substr($vl,1);
          //$operador 	= $vl[0];
          
          $byPass = false;
          
          if( substr($campo,0,4) == "*OR*" ) {
            $cnd = "(" . $this->_where($vl,true,"OR") . ")";
            $byPass = true;
          }
          
          if( !$byPass ) {
            if(!strstr($vl,":") ) {
            	$vl = "=:" . $vl;
            }
          
            list($operador,$valor) = explode(":",$vl,2);
          
            $cnd = $campo . " ";
          
            if( $operadores[$operador] == '*especial*' ) {
              switch($operador) {
                case "array in":
                   // Operador array in inclui ":tipo";
                   list($tipo,$valor) = explode(":",$valor,2);
                   
                   //$cnd .= " >= ARRAY['$valor'::$tipo]";
                   $cnd = "array_to_string($campo,' ') ilike '%$valor%'";
                 
                   break;
                 
                case "in":
                case "!in":
                  $elementos = explode("::",$valor);
                
                  for($x=0;$x<count($elementos);$x++) {
                    $elementos[$x] = $this->bd->escape($elementos[$x]);
                  }
                
                  $cnd .= ($operador == "in" ? "IN" : "NOT IN") . " ('" . implode("','",$elementos) . "') ";
                
                  break;
                case "null":
                  $cnd .= "is null";
                  break;
              }
            } else {
              if( is_null($valor) ) {
                $cnd = $campo . " is null";
              } else {
                $cnd = $campo . " " . $operadores[$operador] . " '" . $this->bd->escape($valor) . "'";
              }
            }
          }
        
          //$cond[] = $campo . " " . $operadores[$operador] . "'" . $this->bd->escape($valor) . "'";
          $cond[] = $cnd;
        }
      }
      
      $retorno = "";
      
      if( count($cond) ) {
        if( !$recursive ) {
          $retorno = "WHERE ";
        }
        $retorno .= implode(" " . $forceop . " ",$cond);
      }
      
      return($retorno);
      
    }



    /**
     * Select com Paginação
     * @param $pagina			Numero da página
     * @param $regsPorPagina	Numero de registros por página
     * @param $condicao			Condição utilizada para filtrar os registros
     * @param $ordem			Ordenação
     * @param $maxLinks			Numero maximo de links que será exibido.
     */
    function obtemComPaginacao($pagina=1,$regsPorPagina=20,$condicao=array(),$ordem="",$maxLinks=10) {
    	$numeroRegistros = $this->obtemNumeroRegistros($condicao,$ordem);
    	
    	$maxPagina = (int) ($numeroRegistros / $regsPorPagina);
    	
//    	echo "MaxPagina: $maxPagina<br>\n";
    	
    	
    	if( $numeroRegistros % $regsPorPagina != 0 ) {
    		$maxPagina++;
    	}

//    	if( $numeroRegistros < $regsPorPagina ) $maxPagina = 1;
    	
    	
    	$offset = ($pagina -1) * $regsPorPagina;
    	
    	$limite = $regsPorPagina . "," . $offset;
    	
    	if( $pagina > $maxPagina ) $pagina = $maxPagina;
    	
    	$registros = $this->obtem($condicao,$ordem,$limite);
    	
    	// Gera um array com os numeros de página que serão utilizados pelo smarty
    	$links = array();
    	
    	//$linkMeio = (int) (($pagina + $maxPagina) / 2);
    	$linkIni = (int) $pagina - ((int)$maxLinks/2);
    	if( $maxPagina < $maxLinks ) $linkIni = 1;
    	if($linkIni < 1) $linkIni = 1;
    	$linkFim = $linkIni + $maxLinks;
    	if($linkFim > $maxPagina) $linkFim = $maxPagina;
    	
    	for($i=$linkIni;$i<=$linkFim;$i++) {
    		$links[] = $i;
    	}
    	
    	if(count($links) < $maxLinks && $linkIni > 1) {
    		$nr = $links[0] - ($maxLinks - count($links));
    		if( $nr < 1 ) $nr = 1;
    		for($i=$links[0];$i > $nr; --$i) {
    			$links[] = $i;
    		}
    		asort($links);
    		$nLinks = array();
    		while( list($vr,$vl) = each($links) ) {
    			//echo "$vr = $vl <br>\n";
    			$nLinks[] = $vl;
    		}
    		$links = $nLinks;
    		unset($nLinks);
    		
    	}
    	
    	while( count($links) > $maxLinks ) {
			array_pop($links);    	
    	}
    	
    	return( array(
    					"max_pagina" => $maxPagina,
    					"numero_registros" => $numeroRegistros,
    					"pagina" => $pagina,
    					"registros_por_pagina" => $regsPorPagina,
    					"min_link" => @$links[0],
    					"max_link" => @$links[ count($links) - 1 ],
    					"numero_maximo_links" => $maxLinks,
    					"links" => $links,
    					"registros" => $registros ) );
    	
    	//return($this->obtem($condicao,$ordem,$limite);
    }

    
    /**
     * Count(*) genérico
     */    
    function obtemNumeroRegistros($condicao=array(),$ordem="",$limite="",$unico = false) {
    	return($this->obtem($condicao,$ordem,$limite,$unico,true));
    }
    
    /**
     * Select Generico
     */
    function obtem($condicao=array(),$ordem="",$limite="",$unico = false,$conta = false) {
    
      $sql = "SELECT " . ($conta? "count(".$this->_chave.") as num_regs":implode(",",$this->_campos)) . " FROM " . $this->_tabela . " " . (is_array($condicao)?$this->_where($condicao):$condicao);
      
      if(!$ordem) $ordem = $this->_ordem;
      
      if( $ordem == "**RANDOMICO**" ) $ordem = "random()";
      
      if( $ordem && !$conta) {
      	$sql .= " ORDER BY " . $ordem;
      }
      
      if( $unico ) {
        $limite = "1";
      }
      
      if( $limite ) {
      	@list($limit,$offset) = explode(",",$limite);
      	$offset = (int)$offset;
        $sql .= " LIMIT $limit OFFSET $offset";
      }
      
      if( $conta ) {
      	$info = $this->bd->obtemUnicoRegistro($sql);
      	return($info["num_regs"]);
      }
      
      if( $this->_forupdate ) {
      	$sql .= " FOR UPDATE";
      }
      
      
      $this->debug("MPersiste","obtem",$sql);
      
      //echo "SQL: $sql<br>\n";
      
      
      return( $unico?$this->bd->obtemUnicoRegistro($sql):$this->bd->obtemRegistros($sql));
    }
    
    function obtemUltimos($limite=10) {
    	$ordem = "";
    
    	if( $this->_chave ) {
    		$ordem = $this->_chave . " DESC";
    	}
    	
    	return($this->obtem(array(),$ordem,$limite));
    	
    }

    /**
     * Select Generico (um registro somente)
     */
    function obtemUnico($condicao=array(),$ordem="",$limite="") {
      return($this->obtem($condicao,$ordem,$limite,true));
    }
    
    protected function separaDados($dados,$quote=true) {
      $campos = array();
      $valores = array();
      $id = array();

      $chaves = explode(",",$this->_chave);
      for($i=0;$i<count($chaves);$i++) {
        $chaves[$i] = trim($chaves[$i]);
      }


      while( list($campo,$valor) = each($dados) ) {
        //$valor = $this->bd->escape($valor);
        if( (!$this->_sequence || !in_array($campo,$chaves)) && in_array($campo,$this->_campos) ) {
          $campos[] = $campo;
          
          $vl = $this->filtra($campo,@$this->_filtros[$campo],$valor);
          
          if( is_null($vl) ) {
            $vl = 'null';
          } else {
          	if( $vl === "=now" ) {
          		$vl = 'now()';
          	} else {
          		$vl = $this->bd->escape($vl);
          		if($quote) $vl = "'".$vl."'";
          	}
          		
          }
          
          $valores[] = $vl;
          
          // Campo compoe a chave
          if( in_array($campo,$chaves) ) {
            $id[$campo] = $valor;
          }
        }

      }
      
      return( array("id" => $id, "campos" => $campos, "valores" => $valores ) );
    
    }

    /**
     * Insert Generico
     */
    function insere($dados) {
      if( !count($dados) ) { 
        return 0;
      }
      
      $info = $this->separaDados($dados);
      
      $id = $info["id"];
      $campos = $info["campos"];
      $valores = $info["valores"];
      
      unset($info);
      
      if( !count($campos) ) {
        return 0;
      }
      
      if( $this->_sequence ) {
        $id = $this->bd->proximoID($this->_sequence);
        $campos[]  = $this->_chave;
        $valores[] = $id;

      }
      
      // Monta a query 
      $sql = "INSERT INTO " . $this->_tabela . " ( " . implode(',',$campos) . " ) VALUES ( " . implode(",",$valores) . " )";
      echo "<pre>$sql</pre><br><br>\n";
      
      $this->debug("MPersiste","insere",$sql);
      
      $this->bd->consulta($sql,false);
      
      return($id);
      
    }
    
    /**
     * Update Generico
     */
    function altera($dados,$condicao) {
      $info = $this->separaDados($dados);
      
      $id = $info["id"];
      $campos = $info["campos"];
      $valores = $info["valores"];
      
      unset($info);
      
      if( !count($campos) ) {
        return 0;
      }
      
      $sql = "UPDATE \n\t" . $this->_tabela . " \nSET \n";
      
      
      for($i=0;$i<count($campos);$i++) {
        $sql .= " \t" . $campos[$i] . " = " . $valores[$i];
        
        if( $i < count($campos) - 1 ) {
          $sql .= ",";
        }
        $sql .= "\n";
      }
      
      $sql .= " " . $this->_where($condicao);
      
      $sql .= "\n";
      
      $this->debug("MPersiste","update",$sql);
      // echo "\n<hr>MPersiste::altera:: " . $sql . "<hr>\n";
      
      return($this->bd->consulta($sql,false));
      
    }
    
    /**
     * Delete Generico
     */
    function exclui($condicao) {
      $sql = "DELETE FROM " . $this->_tabela . " " . $this->_where($condicao);
      return($this->bd->consulta($sql,false));
    }
    
    public function obtemOrdem() {
      return($this->_ordem);    
    
    }
    
    // Para usar em Join
    public function obtemCamposComTabela() {
      $campos = array();
      for($i=0;$i<count($this->_campos);$i++) {
        $campos[] = $this->_tabela . "." . $this->_campos[$i];
      }
      
      return($campos);
    }
    
    // Define a filtragem padrao utilizada em cada campo
    public function filtra($campo,$tipo,$valor) {
    	//echo "FILTRA: [$campo] - [$tipo] - [$valor]<br>\n";
    
      switch($tipo) {
        case 'number':
        case 'numeric':
        case 'int':
          if( !$valor ) {
            $retorno = 0;
          } else {
            if( $this->_language == "pt_BR" ) {
              $retorno = str_replace(",",".",$valor);
            }
          }

          $retorno = (double)$retorno;

          break;
        case 'bool':
        case 'boolean':
          $valor = strtolower($valor);
          if( !$valor ) {
            $retorno = '0';
          } else {
            switch($valor) {
              case false:
              case 'false':
                case 'f':
              case '':
              case '0':
              case 'n':
                $retorno = '0';
                break;
              default:
                $retorno = '1';
                break;
            }
          }
          break;
        case 'cidr':	// Endereços de rede
        case 'inet':	// Endereços IP
          // Utilizado pra processar endereços ip e de rede.
          try {
          	@list($ip,$bits) = explode("/",$valor);
          	if(!$bits) {
          		$bits = 32;
          	}
          	$mip = new MInet($ip."/".$bits);
          	
          	if( $tipo == 'inet' ) {
          		// Somente IP
          		$retorno = $ip;
          	} else {
          		$retorno = $mip->obtemRede() . "/" . $mip->obtemBitmask();
          	}
          	
          } catch(MException $e) {
          	// Invalido, registrar NULL (p/ evitar erros no banco);
          	// O erro só vai acontecer caso o formulário de validação falhe.
          	// TODO: Disparar exception.
          	//echo "IP: $ip<br>\n";
          	//echo "BITS: $bits<br>\n";
          	//echo "EXCEPTION: " . $e->getMessage() . "<br>\n";
          	//echo "----------------------------<br>\n";
          	$retorno = null;
          }
          
          
          break;
        case 'date':
         // echo "DATE!!!!";
          if( !$valor ) {
            $retorno = null;
          } else {
			if( $valor == '=now' ) {
				$retorno = $valor;
			} else {
				if( substr_count($valor, "-") > 0 ) {
				  $retorno = $valor;
				} else {
				  list($dia, $mes, $ano) = explode("/", $valor);

				  $retorno = $ano."-".$mes."-".$dia;
				}
			}
          }

          //echo $retorno;
          
          break;
        case 'custom':
          // Chama funcao filtroCampo($campo,$valor);
          $retorno = $this->filtroCampo($campo,$valor);
          break;

        default:
          $retorno = $valor;
          
      }
		//echo "RETORNO: [$campo] - [$tipo] - [$retorno]<br>\n";

      return($retorno);
      
    }
    
    public function filtroCampo($campo,$valor) {
      return($valor);
    }
    
    // Altera a flag _forupdate e retorna o valor antigo da flag.
    public function setForUpdate($bool) {
    	$retorno = $this->_forupdate;
    	$this->_forupdate = $bool;
    	return($retorno);
    }
    
    protected static function debug($classe,$metodo,$info) {
    	$arqDebug = "/tmp/va-debug.txt";
    	//if( self::$_DEBUG ) {
    	//	$fd = fopen($arqDebug,"a");
    	//	fputs($fd,"$classe::$metodo()::".$info."\n\n-------------------------------------------------------\n");
    	//	fclose($fd);
    	//}
    }
    
  }


?>