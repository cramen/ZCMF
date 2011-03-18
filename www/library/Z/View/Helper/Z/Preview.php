<?php

class Z_View_Helper_Z_Preview extends Zend_View_Helper_Abstract
{
	public function z_preview($id,$param=Array())
	{
		try
		{
			if (is_numeric($id))
			{
				$storage = new Z_File_Storage();
				$file = $storage->getFile($id);
				if (!$file) throw new Exception();
				$filename = $file->getFullName();
				unset($storage);
			}
			elseif(is_string($id))
			{
				$filename = $id;
			}
			else
			{
				throw new Exception('Идентификатор файла не является чистом или строкой');
			}
			
			if (isset($param['mark']) && is_numeric($param['mark']))
			{
				$storage = new Z_File_Storage();
				$file = $storage->getFile($param['mark']);
				if (!$file) throw new Exception();
				$param['mark'] = $file->getFullName();
				unset($storage);
			}
			
			$tn = new Z_File_Image_Thumbnail($filename);
			$fileSitename = str_replace(SITE_PATH,'',$filename);
			if ($tn->isPreviewActual($param))
			{
				$preResult = $tn->getPreviewName($param);
			}
			else
			{
				$param['file'] = $fileSitename;
				$preResult = '/sys/generatepreview.php?'.http_build_query($param,false);
			}
		}
		catch (Exception $e)
		{
			return '';
		}
		
		return str_replace(SITE_PATH,'',$preResult);
	}
}

?>