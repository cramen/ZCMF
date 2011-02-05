<?php

class Z_View_Helper_Z_Storagefile extends Zend_View_Helper_Abstract
{
	public function z_storagefile($id)
	{
		$stor = new Z_File_Storage();
		return $stor->getFile($id);
	}
}

?>