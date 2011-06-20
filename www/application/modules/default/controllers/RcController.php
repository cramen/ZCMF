<?php

class RcController extends Zend_Controller_Action
{

    public function init()
    {
        Zend_Layout::getMvcInstance()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
    }

    public function indexAction()
    {
        $params = array(
            'MrchLogin'         =>  'cramen',
            'OutSum'            =>  '100',
            'InvId'             =>  '123123',
            'Desc'              =>  'оплата книги',
            'SignatureValue'    =>  md5('cramen:100:123123:password1'),
//            'IncCurrLabel'        =>  'PCR',
            'Culture'        =>  'ru',
        );
        $this->_redirect('http://test.robokassa.ru/Index.aspx?'.http_build_query($params));

    }

	public function resAction()
	{

        if ( strtoupper($_POST['SignatureValue']) == strtoupper(md5($_POST['OutSum'].':'.$_POST['InvId'].':password2')) )
        {
            echo 'OK'.$_POST['InvId'];
        }
        else
        {
            echo 'fail';
        }
//        print_r($_POST);
//        echo md5($_POST['OutSum'].':'.$_POST['InvId'].':password2');

	}

    public function sucAction()
    {
        print_r($_POST);
    }

    public function failAction()
    {
        print_r($_POST);
    }

}

