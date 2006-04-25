TARGET=/usr/mosman/php/framework/

install:
	install -d ${TARGET}
	install MConfig.class.php ${TARGET}
	install MDatabase.class.php ${TARGET}
	install MTemplate.class.php ${TARGET}
	install MWebApp.class.php ${TARGET}
	install MBoleto.class.php $(TARGET)
	install MRetornoBanco.class.php ${TARGET}
	install MRetornoPagContas.class.php ${TARGET}
	install MUtils.class.php ${TARGET}
	install MHTML2PDF.class.php ${TARGET}
