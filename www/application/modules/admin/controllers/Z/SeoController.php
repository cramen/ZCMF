<?php

class Admin_Z_SeoController extends Z_Admin_Controller_Datacontrol_Abstract
{
	public function addOverridePreValidate($param)
	{
		$uri = $this->getRequest()->getParam('uri');
		if ($uri) $uri = base64_decode($uri);
		$this->z_form->getElement('uri')->setValue($uri);
		return $param;
	}
}

