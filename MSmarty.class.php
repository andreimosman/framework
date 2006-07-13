<?
require_once("Smarty.class.php");


class MSmarty extends Smarty {

    function __construct() {
        parent::__construct();
    }

    public final function _read_file($filename)
    {

        $res = false;

        if (file_exists($filename)) {
            if (function_exists('ioncube_read_file')) {
                $res = ioncube_read_file($filename);
                if (is_int($res)) $res = false;
            }
            else if ( ($fd = @fopen($filename, 'rb')) ) {
                $res = ($size = filesize($filename)) ? fread($fd, $size) : '';
                fclose($fd);
            }
        }

        return $res;
    }

}

?>
