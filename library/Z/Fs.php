<?php

class Z_Fs
{
  private $rootdir;

  protected function __construct()
  {
  }

  public static function create_file($filename,$content="",$rewrite=false,$rights=0777)
  {
    global $m;
	
    $fileparts = explode(DIRECTORY_SEPARATOR,rtrim($filename,DIRECTORY_SEPARATOR));
	$pathparts = $fileparts;
	unset($pathparts[count($pathparts)-1]);
	$path = rtrim(implode(DIRECTORY_SEPARATOR,$pathparts),DIRECTORY_SEPARATOR);

	if (Z_Fs::create_folder($path))
	{
		if (file_exists($filename) && !$rewrite)
		{
		  return false;
		}
		else
		{
		  file_put_contents($filename,$content);
		  chmod($filename,$rights);
		}
	}
	else
	{
		return false;
	}
	
	/*
    $i=0;
    $fileparts_count = count($fileparts);
    foreach ($fileparts as $part)
    {
      $i++;
      if ($i<$fileparts_count)
      {
	if (file_exists($curdir.$part))
	{
	}
	else
	{
	  if (is_writable($curdir))
	  {
	    mkdir($curdir.$part);
	    chmod($curdir.$part,$rights);
	  }
	  else
	  {
	    return false;
	  }
	}
      }
      $curdir .= $part.DIRECTORY_SEPARATOR;
    }
    $curdir = rtrim($curdir,DIRECTORY_SEPARATOR);
    if (file_exists($curdir) && !$rewrite)
    {
      return false;
    }
    else
    {
      file_put_contents($curdir,$content);
      chmod($curdir,$rights);
    }
	*/
    return true;
  }

  public static function create_folder($filename,$rights=0777)
  {
    global $m;

    $fileparts = explode(DIRECTORY_SEPARATOR,rtrim($filename,DIRECTORY_SEPARATOR));
    $curdir = $fileparts[0].DIRECTORY_SEPARATOR;
	unset($fileparts[0]);
    foreach ($fileparts as $part)
    {
      if (file_exists($curdir.$part))
      {
      }
      else
      {
		if (is_writable($curdir))
		{
		  mkdir($curdir.$part);
		  chmod($curdir.$part,$rights);
		}
		else
		{
		  return false;
		}
      }
      $curdir .= $part.DIRECTORY_SEPARATOR;
    }
    return true;
  }

}