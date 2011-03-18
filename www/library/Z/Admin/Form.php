<?php

class Z_Admin_Form extends Zend_Form {
	
	public static $_errorDecoratorClass = 'z-form-errors ui-state-error ui-corner-all';
	
	public static $_translate_array = array(  
	Zend_Validate_Alnum::NOT_ALNUM => 'Введенное значение "%value%" неправильное. Разрешены только латинские символы и цифры',  
	Zend_Validate_Alnum::STRING_EMPTY => 'Поле не может быть пустым. Заполните его, пожалуйста',  
	    
	Zend_Validate_Alpha::NOT_ALPHA => 'Введите в это поле только латинские символы',  
	Zend_Validate_Alpha::STRING_EMPTY => 'Поле не может быть пустым. Заполните его, пожалуйста',  
	    
	Zend_Validate_Between::NOT_BETWEEN => '"%value%" не находится между "%min%" и "%max%", включительно',  
	Zend_Validate_Between::NOT_BETWEEN_STRICT => '"%value%" не находится строго между "%min%" и "%max%"',  
	    
	Zend_Validate_Ccnum::LENGTH => '"%value%" должно быть численным значением от 13 до 19 цифр длинной',  
	Zend_Validate_Ccnum::CHECKSUM => 'Подсчёт контрольной суммы неудался. Значение "%value%" неверно',  
	    
	Zend_Validate_Digits::NOT_DIGITS => 'Значение "%value%" неправильное. Введите только цифры',  
	Zend_Validate_Digits::STRING_EMPTY => 'Поле не может быть пустым. Заполните его, пожалуйста',  
	    
	Zend_Validate_EmailAddress::INVALID => '"%value%" неправильный адрес електронной почты. Введите его в формате имя@домен',  
	Zend_Validate_EmailAddress::INVALID_HOSTNAME => '"%hostname%" неверный домен для адреса "%value%"',  
	Zend_Validate_EmailAddress::INVALID_MX_RECORD => 'Домен "%hostname%" не имеет MX-записи об адресе "%value%"',  
	Zend_Validate_EmailAddress::DOT_ATOM => '"%localPart%" не соответствует формату dot-atom',  
	Zend_Validate_EmailAddress::QUOTED_STRING => '"%localPart%" не соответствует формату указанной строки',  
	Zend_Validate_EmailAddress::INVALID_LOCAL_PART => '"%localPart%" не правильное имя для адреса "%value%", вводите адрес вида имя@домен',  
	Zend_Validate_EmailAddress::INVALID_FORMAT => "Вы ввели неверный e-mail адрес. Введите e-mail в формате example@domain.com",  
	    
	Zend_Validate_Float::NOT_FLOAT => '"%value%" не является дробным числом',  
	  
	Zend_Validate_GreaterThan::NOT_GREATER => '"%value%" не превышает "%min%"',  
	    
	Zend_Validate_Hex::NOT_HEX => '"%value%" содержит в себе не только шестнадцатеричные символы',  
	    
	Zend_Validate_Hostname::IP_ADDRESS_NOT_ALLOWED => '"%value%" - это IP-адрес, но IP-адреса не разрешены ',  
	Zend_Validate_Hostname::UNKNOWN_TLD => '"%value%" - это DNS имя хоста, но оно не дожно быть из TLD-списка',  
	Zend_Validate_Hostname::INVALID_DASH => '"%value%" - это DNS имя хоста, но знак "-" находится в неправильном месте',  
	Zend_Validate_Hostname::INVALID_HOSTNAME_SCHEMA => '"%value%" - это DNS имя хоста, но оно не соответствует TLD для TLD "%tld%"',  
	Zend_Validate_Hostname::UNDECIPHERABLE_TLD => '"%value%" - это DNS имя хоста. Не удаётся извлечь TLD часть',  
	Zend_Validate_Hostname::INVALID_HOSTNAME => '"%value%" - не соответствует ожидаемой структуре для DNS имени хоста',  
	Zend_Validate_Hostname::INVALID_LOCAL_NAME => '"%value%" - адрес является недопустимым локальным сетевым адресом',  
	Zend_Validate_Hostname::LOCAL_NAME_NOT_ALLOWED => '"%value%" - адрес является сетевым расположением, но локальные сетевые адреса не разрешены',  
	    
	Zend_Validate_Identical::NOT_SAME => 'Значения не совпадают',  
	Zend_Validate_Identical::MISSING_TOKEN => 'Не было введено значения для проверки на идентичность',  
	    
	Zend_Validate_InArray::NOT_IN_ARRAY => '"%value%" не найдено в перечисленных допустимых значениях',  
	  
	Zend_Validate_Int::NOT_INT => '"%value%" не является целочисленным значением',  
	    
	Zend_Validate_Ip::NOT_IP_ADDRESS => '"%value%" не является правильным IP-адресом',  
	    
	Zend_Validate_LessThan::NOT_LESS => '"%value%" не меньше, чем "%max%"',  
	    
	Zend_Validate_NotEmpty::IS_EMPTY => 'Введённое значение пустое, заполните поле, пожалуйста',  
	    
	Zend_Validate_Regex::NOT_MATCH => 'Значение "%value%" не подходит под шаблон регулярного выражения "%pattern%"',  
	    
	Zend_Validate_StringLength::TOO_SHORT => 'Длина введённого значения "%value%", меньше чем %min% симв.',  
	Zend_Validate_StringLength::TOO_LONG => 'Длина введённого значения "%value%", больше чем %max% симв.',
	
	Zend_Validate_Db_Abstract::ERROR_RECORD_FOUND => 'Такая запись уже существует',
	Zend_Validate_Db_Abstract::ERROR_NO_RECORD_FOUND => 'Такая запись не существует',
	
	Zend_Validate_Date::INVALID_DATE   => "'%value%' не является верной датой",
	Zend_Validate_Date::FALSEFORMAT    => "'%value%' не соответствует формату '%format%'",
	
	Zend_Validate_File_Size::TOO_BIG   => "Размер файла '%value%' слишком большой. Файл должен быть не более '%max%'",
	Zend_Validate_File_Size::TOO_SMALL => "Размер файла '%value%' слишком маленький.",
	Zend_Validate_File_Size::NOT_FOUND => "Файл '%value%' не найден",
	
	Zend_Validate_File_IsImage::FALSE_TYPE   => "Файл '%value%' Не является картинкой",
	Zend_Validate_File_IsImage::NOT_DETECTED => "Тип файла '%value%' не идентифицирован",
	Zend_Validate_File_IsImage::NOT_READABLE => "Файл '%value%' не может быть прочитан",
	
	Z_Admin_Validate_File_Upload::INI_SIZE       => "Размер файла превышает максимально допустимый для загрузки",
	Z_Admin_Validate_File_Upload::FORM_SIZE      => "Размер файла превышает максимально допустимый для загрузки",
	Z_Admin_Validate_File_Upload::PARTIAL        => "File '%value%' was only partially uploaded",
	Z_Admin_Validate_File_Upload::NO_FILE        => "Файл обязателен для загрузки",
	Z_Admin_Validate_File_Upload::NO_TMP_DIR     => "Не найдена временная дириктория для файла",
	Z_Admin_Validate_File_Upload::CANT_WRITE     => "File '%value%' can't be written",
	Z_Admin_Validate_File_Upload::EXTENSION      => "A PHP extension returned an error while uploading the file '%value%'",
	Z_Admin_Validate_File_Upload::ATTACK         => "File '%value%' was illegally uploaded. This could be a possible attack",
	Z_Admin_Validate_File_Upload::FILE_NOT_FOUND => "Файл '%value%' обязателен для загрузки",
	Z_Admin_Validate_File_Upload::UNKNOWN        => "Неизвестная ошибка",

	Zend_Captcha_Word::MISSING_VALUE => 'Не верно введен код',
	Zend_Captcha_Word::MISSING_ID    => 'Captcha ID field is missing',
	Zend_Captcha_Word::BAD_CAPTCHA   => 'Не верно введен код',

	Zend_Validate_File_ImageSize::WIDTH_TOO_BIG    => "Ширина картинки '%value%' не должна превышать '%maxwidth%'",
	Zend_Validate_File_ImageSize::WIDTH_TOO_SMALL  => "Ширина картинки '%value%' не должна быть меньше '%minwidth%'",
	Zend_Validate_File_ImageSize::HEIGHT_TOO_BIG   => "Высота картинки '%value%' не должна превышать '%maxheight%'",
	Zend_Validate_File_ImageSize::HEIGHT_TOO_SMALL => "Высота картинки '%value%' не должна быть меньше '%minheight%'",
	Zend_Validate_File_ImageSize::NOT_DETECTED     => "Размер изображения '%value%' не может быть определен",
	Zend_Validate_File_ImageSize::NOT_READABLE     => "Файл '%value%' не может быть прочитан",
		
	); 

	public function init()
	{
    	$this->setMethod('post');
    	$this->setAttrib('class', 'z-form');
    	$this->setAttrib('enctype', 'multipart/form-data');

    	$translator = new Zend_Translate_Adapter_Array(self::$_translate_array,'ru_RU');
    	$this->setTranslator($translator);
	}
	
	public function render(Zend_View_Interface $view=null)
	{
		jQuery::evalScript('
			$(".z-form fieldset legend").click(function(){
				$(this).parent().find(">dl").toggle("blind",200);
			})
		');
		
    	if (!$this->getAttrib('id')) $this->setAttrib('id', 'z-admin-form-'.rand(1000000,10000000));
		return parent::render($view);
	}
	
}




















