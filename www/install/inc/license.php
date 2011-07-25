<?php
/**
 * User: cramen
 * Date: 29.03.11
 * Time: 14:46
 */

class install_license
{
    /**
     * @var Zend_View
     */
    protected $view;

    public function __construct($view)
    {
        $this->view = $view;
    }

    public function run()
    {
        $form = $this->getForm();
        
        $this->view->title = 'Лицензионное соглашение';
        $this->view->form = $form;
        $this->view->license = file_get_contents(SITE_PATH.'/LICENSE.txt');

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $form->isValid($_POST))
        {
            $data = $form->getValidValues($_POST);
            if ($data['send'])
            {
                try
                {
                    set_time_limit(0);
                    $client = new Zend_Http_Client('http://zcmf.ru/calculate/'.Z_Version::$value,array(
                                                                                              'maxredirects' => 0,
                                                                                              'timeout'      => 5));
                    $client->setHeaders('ua','ZCMF_UA');
                    $req = $client->request('GET');
                }
                catch (Exception $e)
                {
                    $this->view->license = $e->getMessage();
                }
            }
            return true;
        }

        $this->view->content = $this->view->render('license.phtml');

        return false;
    }

    /**
     * @return Zend_Form
     */
    protected function getForm()
    {
        $form = new Zend_Form();

        $validatorAgree = new Zend_Validate_Between(array('min'=>1,'max'=>1));
        $validatorAgree->setMessages(array( Zend_Validate_Between::NOT_BETWEEN  => 'agreeRules'));

        $form->addElement(new Zend_Form_Element_Checkbox('allow',array(
            'label'     =>  'Я согласен с условиями лицензионного соглашения',
            'required'  =>  TRUE,
            'value'     =>  false,
            'filters'     => array('Int'),
            'validators'  => array($validatorAgree),
        )));
        $form->addElement(new Zend_Form_Element_Checkbox('send',array(
            'label'     =>  'Отсылать статистику об установке ZCMF (никаких конфиденциальных данных не собирается)',
            'required'  =>  TRUE,
            'value'     =>  1,
        )));

        $form->addElement(new Zend_Form_Element_Submit('submit',array(
            'label'     =>  'Дальше',
        )));

        $translator = new Zend_Translate_Adapter_Array(Z_Admin_Form::$_translate_array,'ru_RU');
        $translator->addTranslation(array(
                                         Zend_Validate_Between::NOT_BETWEEN => 'Подтвердите Ваше согласие с условиями лицензионного соглашения.',
                                         Zend_Validate_Between::NOT_BETWEEN_STRICT => 'Подтвердите Ваше согласие с условиями лицензионного соглашения.',
                                    ),'ru_RU');
        $form->setTranslator($translator);


        return $form;
    }

}