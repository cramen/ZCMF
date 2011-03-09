<?php

class Z_File_Image_Thumbnail
{
	private $filename;
	private $image;
	private $data;
	private $copy;
	
	function __construct($filename) {
		if(!is_file($filename))
			throw new Exception("Файл не найден");
			
		$this->filename = $filename;
	}

	public function createThumbnail($param=array())
	{
		$newFileName = $this->getPreviewName($param);
		
		if (!$this->isPreviewActual($param))
		{
			$w = array_key_exists('w',$param)?(int)$param['w']:NULL;
			$h = array_key_exists('h',$param)?(int)$param['h']:NULL;
			$w2 = array_key_exists('w2',$param)?(int)$param['w2']:NULL;
			$h2 = array_key_exists('h2',$param)?(int)$param['h2']:NULL;
			$grayscale = array_key_exists('grayscale',$param)?1:NULL;
			$contrast = array_key_exists('contrast',$param)?(int)$param['contrast']:NULL;
			$brightness = array_key_exists('brightness',$param)?(int)$param['brightness']:NULL;
			
			$pv = new Z_File_Image_Resizer($this->filename);
			if ($w && $h && $w2 && $h2)
			{
				$pv->resize($w,$h,$w2,$h2);
			}
			elseif ($w || $h)
			{
				$pv->resizeProportional($w,$h);
			}
			else
			{
				$pv->duplicate();
			}
			
			if (isset($param['grayscale']))
				$pv->grayscale();
			
			if (isset($param['contrast']))
				$pv->contrast($param['contrast']);
				
			if (isset($param['brightness']))
				$pv->brightness($param['brightness']);
			
			if (isset($param['mark']))
			{
				$pv->mark($param['mark'],isset($param['mark_pos'])?(int)$param['mark_pos']:1);
			}
			$pv->save($newFileName);
		}
		
		return $newFileName;
	}
	
	public function getPreviewName($param)
	{
		if (array_key_exists('w',$param)) $param['w'] = (int)$param['w'];
		if (array_key_exists('h',$param)) $param['h'] = (int)$param['h'];
		if (array_key_exists('w2',$param)) $param['w2'] = (int)$param['w2'];
		if (array_key_exists('h2',$param)) $param['h2'] = (int)$param['h2'];
		if (array_key_exists('mark_pos',$param)) $param['mark_pos'] = (int)$param['mark_pos'];
		if (array_key_exists('grayscale',$param)) $param['grayscale'] = 1;
		if (array_key_exists('contrast',$param)) $param['contrast'] = (int)$param['contrast'];
		if (array_key_exists('brightness',$param)) $param['brightness'] = (int)$param['brightness'];
		
		ksort($param);
		$filePathParts = explode('.',$this->filename);
		$fileExt = $filePathParts[count($filePathParts)-1];
		unset($filePathParts[count($filePathParts)-1]);
		$fileNameWithoutExt = implode('.',$filePathParts);
		if (function_exists('json_encode'))
			$paramstr = json_encode($param);
		else
			$paramstr = Zend_Json_Encoder::encode($param);
		$postfix = base64_encode($paramstr);
//		$postfix = md5($paramstr);
		
		return $fileNameWithoutExt.'_'.$postfix.'_preview.'.$fileExt;
	}
	
	public function isPreviewActual($param)
	{
		$newFileName = $this->getPreviewName($param);
		$timeOriginal = filemtime($this->filename);
		
		if (isset($param['mark']) && is_file($param['mark']))
			$timeMark = filemtime($param['mark']);
		else 
			$timeMark = 0;
		
		if (is_file($newFileName))
			$timeThumbnail = filemtime($newFileName);
		else
			$timeThumbnail=0;

		if ($timeThumbnail<$timeOriginal || $timeThumbnail<$timeOriginal)
			return false;
		return true;
	}
	
}
