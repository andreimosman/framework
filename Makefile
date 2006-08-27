#
# ARQUIVO DE PUBLICAÇÃO
#

TARGET=/usr/mosman/php/framework/

install:
	install -d ${TARGET}
	install MConfig.class.php ${TARGET}
	install MDatabase.class.php ${TARGET}
	install MTemplate.class.php ${TARGET}
	install MSmarty.class.php ${TARGET}
	install MWebApp.class.php ${TARGET}
	install MBoleto.class.php $(TARGET)
	install MRetornoBanco.class.php ${TARGET}
	install MRetornoPagContas.class.php ${TARGET}
	install MUtils.class.php ${TARGET}
	install MHTML2PDF.class.php ${TARGET}
	install MArrecadacao.class.php ${TARGET}
	install MLicenca.class.php ${TARGET}
	install mimage_barcode_int25.class.php ${TARGET}
	install SistemaOperacional.class.php ${TARGET}
	install SOFreeBSD.class.php ${TARGET}
	install MBanco.class.php $(TARGET)
