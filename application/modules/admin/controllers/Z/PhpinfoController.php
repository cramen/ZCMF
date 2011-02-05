<?php

class Admin_Z_PhpinfoController extends Z_Admin_Controller_Action
{
	public function indexAction()
	{
	  $this->disableRenderView();
	  echo $this->view->admin_Head('phpinfo');
	  echo $this->view->admin_Bodybegin();
	  echo '<iframe src="/sys/phpinfo.php" width="100%" height="800px" style="border: none;">';
	  echo '</iframe>';
	  echo $this->view->admin_Bodyend();
	}

}

