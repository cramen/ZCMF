<?php

define("JPG", 0);
define("GIF", 1);
define("PNG", 2);
define("BMP", 3);

define("JPG_QUALITY", 95);
define("PNG_QUALITY", 0);

class Z_File_Image_Resizer
{
	private $filename;
	private $image;
	private $data;
	private $copy;
	private $copy_width;
	private $copy_height;
	private $format;
	private $mark;
	
	function __construct($filename) {
		if(!is_file($filename))
			throw new Exception("File does not exist");
			
		$this->filename = $filename;
		$this->data = getimagesize($this->filename);
		
		switch($this->data['mime']) {
			case 'image/pjpeg'		: $this->format = JPG;
			case 'image/jpeg'		: $this->format = JPG; $this->image = imagecreatefromjpeg($this->filename); break;
			case 'image/gif'		: $this->format = GIF; $this->image = imagecreatefromgif($this->filename); break;
			case 'image/png'		: $this->format = PNG; $this->image = imagecreatefrompng($this->filename); break;
			case 'image/x-ms-bmp'	: $this->format = BMP; $this->image = imagecreatefromwbmp($this->filename); break;
			default					: throw new Exception("File format is not supported"); break;
		}
	}
	
	// Makes a plain copy of the original
	public function duplicate() {
		if(!isset($this->image))
			throw new Exception("No image loaded");
		$this->copy = $this->image;
		$this->copy_width = $this->data[0];
		$this->copy_height = $this->data[1];
	}
	
	public function resizeProportional($w=NULL,$h=NULL)
	{
		if ($w===NULL && $h===NULL)
			throw new Exception('Не указаны размеры превью файла');
			
		$ow = $this->data[0];
		$oh = $this->data[1];
		$relation = $ow/$oh;
		
		if ($h===NULL)
		{
			$h = (int) ($w/$relation);
		}
		
		if ($w===NULL)
		{
			$w = (int) ($h*$relation);
		}
			
		$this->resize($w,$h);		
		
	}
	
	// Makes a resized copy of the original
	public function resize($wx, $hx, $wm = 0, $hm = 0) {
		
		if(!isset($this->image))
			throw new Exception("No image loaded");

		if($wx != $wm && $hx != $hm && $wm != 0 && $hm != 0)
			throw new Exception("Bad dimensions specified");
				
		$r = $this->data[0] / $this->data[1];
		$rx = $wx / $hx;
		
		if($wm == 0 || $hm == 0)
			$rm = $rx;
		else
			$rm = $wm / $hm;

		$dx=0; $dy=0; $sx=0; $sy=0; $dw=0; $dh=0; $sw=0; $sh=0; $w=0; $h=0;
		
//		$w = $wx;
//		$h = $hx;
		
//		$sw = $this->data[0];
//		$sh = $this->data[1];
		

		if($r > $rx && $r > $rm) {
			$w = $wx;
			$h = $hx;
			$sw = $this->data[1] * $rx;
			$sh = $this->data[1];
			$sx = ($this->data[0] - $sw) / 2;
			$dw = $wx;
			$dh = $hx;
		} elseif($r < $rm && $r < $rx) {
			$w = $wx;
			$h = $hx;
			$sh = $this->data[0] / $rx;
			$sy = ($this->data[1] - $sh) / 2;
			$sw = $this->data[0];
			$dw = $wx;
			$dh = $hx;
		} elseif($r >= $rx && $r <= $rm) {
			$w = $wx;
			$h = $wx / $r;
			$dw = $wx;
			$dh = $wx / $r;
			$sw = $this->data[0];
			$sh = $this->data[1];
		} elseif($r <= $rx && $r >= $rm) {
			$w = $hx * $r;
			$h = $hx;
			$dw = $hx * $r;
			$dh = $hx;
			$sw = $this->data[0];
			$sh = $this->data[1];
		} else {
			throw new Exception("Can't resize the image");
		}
		

/*		if($r < $rx) {
			$dh = $h;
			$hh = $dh;
			$ww = (int)(($this->data[0]*$hh)/$this->data[1]);


			$dx = (int)(($w/2)-($ww/2));
			$dy = 0;
			
			$dw = $ww;
			$dh = $hh;
		}
		else
		{
		    $ww = $w;
		    $hh = (int)(($this->data[1]*$ww)/$this->data[0]);
		    
		    $dx = 0;
		    $dw = $w;
		    
		    $dy = (int)(($h/2)-($hh/2));
		    $dh = $hh;
		    
		
		}*/


		$this->copy = imagecreatetruecolor($w, $h);
		imagealphablending( $this->copy, false );
		imagesavealpha( $this->copy, true);
		$transparentColor = imagecolorallocatealpha($this->copy, 255, 255, 255, 0);
		imagefilledrectangle($this->copy, 0, 0, $w-1, $h-1, $transparentColor);
		
		$this->copy_width = $w;
		$this->copy_height = $h;
		imagecopyresampled($this->copy, $this->image, $dx, $dy, $sx, $sy, $dw, $dh, $sw, $sh);
		
		return true;
	}
	
	// Save copy to file. If no file name omitted it will overwrite the original
	public function save($filename = false, $type = NULL) {
		if ($type===NULL) $type = $this->format;
		if(!isset($this->copy))
			throw new Exception("No copy to save");
			
		if(!$filename)
			$filename = $this->filename;
			
		switch($type) {
			case GIF	: imagegif($this->copy, $filename); return true; break;
			case PNG	: imagepng($this->copy, $filename, PNG_QUALITY); return true; break;
			case BMP	: imagewbmp($this->copy, $filename); return true; break;
			case JPG	:
			default		: imagejpeg($this->copy, $filename, JPG_QUALITY); return true; break;
		}
		throw new Exception("Save failed");
	}
	
	// Save copy to string and return it
	public function getString($type = JPG) {
		if(!isset($this->copy))
			throw new Exception("No copy to return");
		
		$contents = ob_get_contents();
		if ($contents !== false) ob_clean();
		else ob_start();
		
		$this->show($type);
		
		$data = ob_get_contents();
		if ($contents !== false) {
			ob_clean();
			echo $contents;
		}
		else ob_end_clean();
		return $data;
	}
	
	// Output copy to browser
	public function show($type = JPG) {
		if(!isset($this->copy))
			throw new Exception("No copy to show");
		
		switch($type) {
			case GIF	: imagegif($this->copy, null); return true; break;
			case PNG	: imagepng($this->copy, null, PNG_QUALITY); return true; break;
			case BMP	: imagewbmp($this->copy, null); return true; break;
			case JPG	:
			default		: imagejpeg($this->copy, null, JPG_QUALITY); return true; break;
		}
		throw new Exception("Show failed");
	}
	
	public function __destruct()
	{
		if ($this->copy)
			@imagedestroy($this->copy);
		if ($this->image)
			@imagedestroy($this->image);
		$this->filename = null;
		$this->data = null;
	}
	
	public function mark($image,$position=1)
	{
		if (!$this->copy) return;
		if (!file_exists($image)) return;

		$data = getimagesize($image);
		
		switch($data['mime']) {
			case 'image/pjpeg'		: $markImage = imagecreatefromjpeg($image); break;
			case 'image/jpeg'		: $markImage = imagecreatefromjpeg($image); break;
			case 'image/gif'		: $markImage = imagecreatefromgif($image); break;
			case 'image/png'		: $markImage = imagecreatefrompng($image); break;
			case 'image/x-ms-bmp'	: $markImage = imagecreatefromwbmp($image); break;
			default					: throw new Exception("File format is not supported"); break;
		}
				
		$width = $data[0];
		$height = $data[1];
		
		$xPosition = 'left';
		$yPosition = 'top';
		switch($position)
		{
			case 2 : $xPosition = 'center'; $yPosition = 'top' ; break;
			case 3 : $xPosition = 'right'; $yPosition = 'top' ; break;
			case 4 : $xPosition = 'left'; $yPosition = 'middle' ; break;
			case 5 : $xPosition = 'center'; $yPosition = 'middle' ; break;
			case 6 : $xPosition = 'right'; $yPosition = 'middle' ; break;
			case 7 : $xPosition = 'left'; $yPosition = 'bottom' ; break;
			case 8 : $xPosition = 'center'; $yPosition = 'bottom' ; break;
			case 9 : $xPosition = 'right'; $yPosition = 'bottom' ; break;
		}

		$x=0;
		$y=0;
		if ($xPosition=='right')
		{
			$x = $this->copy_width - $width +1;
		}
		elseif ($xPosition=='center')
		{
			$x = ($this->copy_width/2) - ($width/2);
		}
		if ($yPosition=='bottom')
		{
			$y = $this->copy_height - $height +1;
		}
		elseif ($yPosition=='middle')
		{
			$y = ($this->copy_height/2) - ($height/2);
		}
		
		imagecopy($this->copy, $markImage, $x, $y, 0, 0, $width, $height);
		@imagedestroy($markImage);
		
	}
	
	public function grayscale()
	{
		imagefilter($this->copy, IMG_FILTER_GRAYSCALE);
	}
	
	public function brightness($val)
	{
		imagefilter($this->copy, IMG_FILTER_BRIGHTNESS, $val);
	}
	
	public function contrast($val)
	{
		imagefilter($this->copy, IMG_FILTER_CONTRAST, $val);
	}
}
