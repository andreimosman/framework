<?

  /**
   * Classe de abstracao para modelo de dados
   */
  Abstract class MPersiste {
    protected static $_prefix = "";

    protected $_tabela;
    protected $_campos;
    protected $_chave;
    protected $_ordem;
    protected $_sequence;

    protected $bd;
    
    
    protected function __construct($bd=null) {
      $this->bd = ($bd?$bd:MDatabase::getInstance());
      
      $this->_campos 	= array();
      $this->_chave 	= "";
      $this->_tabela 	= "";
      $this->_sequence 	= "";
      $this->_ordem	= "";
    }
    
    public static function configuraPrefixo($prefixo) {
      self::$_prefix = $prefixo;
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
    
    protected function _where($condArray) {
      $cond = array();
      
      $operadores = array( "=" => "=", "%" => "ilike", "!" => "!=", "in" => "*especial*", "!in" => "*especial*", "array in" => "*especial*" );
      
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
          if(!strstr($vl,":") ) {
          	$vl = "=:" . $vl;
          }
          
          list($operador,$valor) = explode(":",$vl,2);
          
          $cnd = $campo . " ";
          
          //echo "OP: " . $operadores[$operador] . " <br>\n";
          
          if( $operadores[$operador] == '*especial*' ) {
          	//echo "OPERADOR ESPECIAL: $operador<br>\n";
            switch($operador) {
              case "array in":
              	//echo "array in<br>\n";
                 // Operador array in inclui ":tipo";
                 list($tipo,$valor) = explode(":",$valor,2);
                 
                 //$cnd .= " >= ARRAY['$valor'::$tipo]";
                 $cnd = "array_to_string($campo,' ') ilike '%$valor%'";
                 
                 //echo "CND: $cnd<br>\n";
                 break;
                 
              case "in":
              case "!in":
                $elementos = explode("::",$valor);
                
                for($x=0;$x<count($elementos);$x++) {
                  $elementos[$x] = $this->bd->escape($elementos[$x]);
                }
                
                $cnd .= ($operador == "in" ? "IN" : "NOT IN") . " ('" . implode("','",$elementos) . "') ";
                
                break;            
            }
          } else {
            $cnd = $campo . " " . $operadores[$operador] . "'" . $this->bd->escape($valor) . "'";
          }
        
          //$cond[] = $campo . " " . $operadores[$operador] . "'" . $this->bd->escape($valor) . "'";
          $cond[] = $cnd;
        }
      }
      
      $retorno = "";
      
      if( count($cond) ) {
        $retorno = "WHERE " . implode(" AND ",$cond);
      }
      
      //echo "RETORNO: " . $retorno . "<br>\n";
      
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
      $sql = "SELECT " . ($conta? "count(".$this->_chave.") as num_regs":implode(",",$this->_campos)) . " FROM " . $this->_tabela . " " . $this->_where($condicao);
      
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
      
      
      // echo "SQL: $sql<br><br>\n";
      
      return( $unico?$this->bd->obtemUnicoRegistro($sql):$this->bd->obtemRegistros($sql));
    }

    /**
     * Select Generico (um registro somente)
     */
    function obtemUnico($condicao=array(),$ordem="",$limite="") {
      return($this->obtem($condicao,$ordem,$limite,true));
    }
    
    protected function separaDados($dados) {
      $campos = array();
      $valores = array();
      $id = array();

      $chaves = explode(",",$this->_chave);
      for($i=0;$i<count($chaves);$i++) {
        $chaves[$i] = trim($chaves[$i]);
      }


      while( list($campo,$valor) = each($dados) ) {
        $valor = $this->bd->escape($valor);
        if( (!$this->_sequence || !in_array($campo,$chaves)) && in_array($campo,$this->_campos) ) {
          $campos[] = $campo;
          $valores[] = $valor;

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
      $sql = "INSERT INTO " . $this->_tabela . " ( " . implode(',',$campos) . " ) VALUES ( '" . implode("','",$valores) . "' )";
      
      //echo "$sql<br>\n";
      
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
      
      $sql = "UPDATE " . $this->_tabela . " SET ";
      
      
      for($i=0;$i<count($campos);$i++) {
        $sql .= " " . $campos[$i] . " = '" . $valores[$i] . "'";
        
        if( $i < count($campos) - 1 ) {
          $sql .= ",";
        }
      }
      
      $sql .= " " . $this->_where($condicao);
      
      //echo $sql ."<br>\n";
      
      return($this->bd->consulta($sql,false));
      
    }
    
    /**
     * Delete Generico
     */
    function exclui($condicao) {
      $sql = "DELETE FROM " . $this->_tabela . " " . $this->_where($condicao);
      return($this->bd->consulta($sql,false));
    }

  }


?>
