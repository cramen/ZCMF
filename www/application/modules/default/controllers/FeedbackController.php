<?php

class FeedbackController extends Zend_Controller_Action
{
	
	public function indexAction()
	{
        $form = new Site_Form_DbForm('feedback');
		$form->setAction($this->view->url());

		if ($this->getRequest()->isPost())
		{
			$data = $this->getRequest()->getPost();
			if ($form->isValid($data))
			{
				$data = Z_Text::htmlSpecialChars($form->getValues());
				$mailer = new Z_Mail_Mailer_Database('feedback_admin',$data);
				$mailer->set('from',$data['email']);
				$mailer->set('to',new Z_Config('email'));
				$mailer->send();
				
			}
			else
			{
				$this->view->form = $form;
			}
		}
		else 
		{
			$this->view->form = $form;
		}
		
		
	}
	
}
