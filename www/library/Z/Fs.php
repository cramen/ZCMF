<?php
/**
 * ZCMF
 * Copyright (c) 2010 ZCMF. <cramen@cramen.ru>, http://zcmf.ru
 *
 * Следующие ниже условия не относятся к сторонним библиотекам, используемым в ZCMF.
 * Все сторонние библиотеки распространяются согласно их лицензии.
 *
 * Лицензия
 *
 * Данная лицензия разрешает лицам, получившим копию данного программного обеспечения и сопутствующей документации
 * (в дальнейшем именуемыми "Программное Обеспечение"),безвозмездно использовать Программное Обеспечение без ограничений,
 * включая неограниченное право на использование, копирование, изменение, добавление, публикацию, распространение,
 * сублицензирование и/или продажу копий Программного Обеспечения, также как и лицам,
 * которым предоставляется данное Программное Обеспечение, соблюдении следующих условий:
 *
 * Вышеупомянутый копирайт и данные условия должны быть включены во все копии или значимые части данного Программного Обеспечения.
 *
 * При копировании, добавлении, изменении, распространении, продаже, публикации и сублицензировании программного обеспечения,
 * авторство может быть только дополнено, но не удалено или изменено на другое.
 *
 * ДАННОЕ ПРОГРАММНОЕ ОБЕСПЕЧЕНИЕ ПРЕДОСТАВЛЯЕТСЯ «КАК ЕСТЬ», БЕЗ КАКИХ-ЛИБО ГАРАНТИЙ, ЯВНО ВЫРАЖЕННЫХ ИЛИ ПОДРАЗУМЕВАЕМЫХ,
 * ВКЛЮЧАЯ, НО НЕ ОГРАНИЧИВАЯСЬ ГАРАНТИЯМИ ТОВАРНОЙ ПРИГОДНОСТИ, СООТВЕТСТВИЯ ПО ЕГО КОНКРЕТНОМУ НАЗНАЧЕНИЮ И
 * ОТСУТСТВИЯ НАРУШЕНИЙ ПРАВ. НИ В КАКОМ СЛУЧАЕ АВТОРЫ ИЛИ ПРАВООБЛАДАТЕЛИ НЕ НЕСУТ ОТВЕТСТВЕННОСТИ ПО ИСКАМ О ВОЗМЕЩЕНИИ УЩЕРБА,
 * УБЫТКОВ ИЛИ ДРУГИХ ТРЕБОВАНИЙ ПО ДЕЙСТВУЮЩИМ КОНТРАКТАМ, ДЕЛИКТАМ ИЛИ ИНОМУ, ВОЗНИКШИМ ИЗ, ИМЕЮЩИМ ПРИЧИНОЙ
 * ИЛИ СВЯЗАННЫМ С ПРОГРАММНЫМ ОБЕСПЕЧЕНИЕМ ИЛИ ИСПОЛЬЗОВАНИЕМ ПРОГРАММНОГО ОБЕСПЕЧЕНИЯ ИЛИ ИНЫМИ ДЕЙСТВИЯМИ С ПРОГРАММНЫМ ОБЕСПЕЧЕНИЕМ.
 *
 */

class Z_Fs
{
  private $rootdir;

  protected function __construct()
  {
  }

  public static function create_file($filename,$content="",$rewrite=false,$rights=0777)
  {

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