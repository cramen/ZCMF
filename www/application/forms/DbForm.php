<?php

class Site_Form_DbForm extends Zend_Form
{
    protected $_dbsid;


    public function __construct($sid, $options=array())
    {
        parent::__construct($options);
        $this->_dbsid = $sid;
        $this->buildFromDb();

        $translator = new Zend_Translate_Adapter_Array(Z_Admin_Form::$_translate_array,'ru_RU');
       	$this->setTranslator($translator);

    }

    protected function buildFromDb()
    {
        $modelForms = new Site_Model_Client_Forms();
        $modelElements = new Site_Model_Client_Forms_Elements();

        $formRecord = $modelForms->fetchRow(array('sid=?'=>$this->_dbsid));
        if (null === $formRecord)
        {
            throw new Zend_Exception(sprintf('Форма с идентификатором "%s" не найдена',$this->_dbsid));
        }

        $elements = $modelElements->fetchAll(array('form_id=?'=>$formRecord->id),'orderid asc');

        foreach ($elements as $element)
        {
            $method = 'add_Db_Element_'.$element->type;
            if (method_exists($this,$method))
            {
                $this->$method($element);
            }
            else
            {
                throw new Zend_Exception(sprintf('Метод "%s" в классе "%s" не существует',$method,get_class($this)));
            }
        }

    }

    protected function getDbElementOptions($element)
    {
        $options = array();
        $options['label'] = $element->label;
        $options['required'] = (boolean)$element->required;
        $options['value'] = $element->default_value;
        $options['description'] = $element->description;

        if ($element->options)
        {
            $addopts = Zend_Json::decode($element->options);

            if (isset($addopts['add_array']) && trim($addopts['add_array']))
            {
                $addarray = eval($addopts['add_array']);
                $options = array_merge($addarray,$options);
            }

            if (isset($addopts['validators']))
            {
                $options['validators'] = empty($options['validators'])?array():$options['validators'];
                $options['validators'] = array_merge($addopts['validators'],$options['validators']);
            }

        }

        return $options;
    }

    protected function add_Db_Element_Text($element)
    {
        $options = $this->getDbElementOptions($element);
        $this->addElement(new Zend_Form_Element_Text($element->name,$options));
    }

    protected function add_Db_Element_Textarea($element)
    {
        $options = $this->getDbElementOptions($element);
        $this->addElement(new Zend_Form_Element_Textarea($element->name,$options));
    }

    protected function add_Db_Element_Select($element)
    {
        $options = $this->getDbElementOptions($element);
        $this->addElement(new Zend_Form_Element_Select($element->name,$options));
    }

    protected function add_Db_Element_Captcha($element)
    {
        $options = $this->getDbElementOptions($element);

        $captcha = array(
        'captcha' => 'Image',
        'font' => APPLICATION_PATH . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'captcha' . DIRECTORY_SEPARATOR . 'font.ttf',
        'imgDir' => SITE_PATH . DIRECTORY_SEPARATOR . 'captcha',
        'imgUrl' => '/captcha/',
        'wordLen' => 4,
        'fontSize' => 27,
        'width' => 120,
        'height' => 65,
        'gcFreq' => 1,
        'dotNoiseLevel' => 0,
        'lineNoiseLevel' => 0,
        'expiration' => 300,
        'timeout' => 300,
        );
        $options['captcha'] = $captcha;


        $this->addElement(new Zend_Form_Element_Captcha($element->name,$options));
    }

    protected function add_Db_Element_Submit($element)
    {
        $options = $this->getDbElementOptions($element);
        $this->addElement(new Zend_Form_Element_Submit($element->name,$options));
    }



}
?>