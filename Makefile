TARGET=/usr/mosman/php/framework/

install:
	install -d ${TARGET}
	install MConfig.class.php ${TARGET}
	install MDatabase.class.php ${TARGET}
	install MTemplate.class.php ${TARGET}
	install MWebApp.class.php ${TARGET}
	install MBoleto.class.php $(TARGET)
