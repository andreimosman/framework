#
# ARQUIVO DE PUBLICAÇÃO
#

TARGET=/usr/mosman/php/framework/

install:
	install -d ${TARGET}
	install MBackup.class.php ${TARGET}
	install MConfig.class.php ${TARGET}
	install MDatabase.class.php ${TARGET}
	install MDatabaseResultado.class.php ${TARGET}
	install MTemplate.class.php ${TARGET}
	install MSmarty.class.php ${TARGET}
	install MWebApp.class.php ${TARGET}
	install MBoleto.class.php ${TARGET}
	install MBoleto001.class.php ${TARGET}
	install MBoleto104.class.php ${TARGET}
	install MBoleto237.class.php ${TARGET}
	install MRetorno.class.php ${TARGET}
	install MRetornoPAGCONTAS.class.php ${TARGET}
	install MRetornoBBCBR643.class.php ${TARGET}
	install MRemessa.class.php ${TARGET}
	install MUtils.class.php ${TARGET}
	install MArrecadacao.class.php ${TARGET}
	install MLicenca.class.php ${TARGET}
	install mimage_barcode_int25.class.php ${TARGET}
	install SistemaOperacional.class.php ${TARGET}
	install SOFreeBSD.class.php ${TARGET}
	install MBanco.class.php ${TARGET}
	install MXMLUtils.class.php ${TARGET}
	install MXMLParser.class.php ${TARGET}
	install MXMLBackupParser.class.php ${TARGET}
	install autoload.def.php ${TARGET}
	install MBackup.class.php ${TARGET}
	install MPersiste.class.php ${TARGET}
	install MException.class.php ${TARGET}
	install MRegex.class.php ${TARGET}
	install MInet.class.php ${TARGET}
	install MJson.class.php ${TARGET}
	install MData.class.php ${TARGET}
	install MCript.class.php ${TARGET}
	install MCarne.class.php ${TARGET}
	cp -r ${TARGET} /mosman/virtex
