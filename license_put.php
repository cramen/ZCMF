<?php
/**
 * User: cramen
 * Date: 25.07.11
 * Time: 15:10
 */


$license_text = file_get_contents('www/LICENSE.txt');

function iterate($dir) {
    global $license_text;

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir),
                                              RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $path) {

      if ($path->isFile())
      {
          $file = $path->__toString();
          $info = pathinfo($file);
          if ($info['extension'] === 'php')
          {
              $content = file_get_contents($file);
              if (strpos($content,$license_text) !== false) continue;
              if (preg_match('~^<\?(php){0,1}\s*/\*\*\s+\* ZCMF(.|\n)+?\*/~i',$content,$matches))
              {
                  $content = preg_replace('~^<\?(php){0,1}\s*/\*\*\s+\* ZCMF(.|\n)+?\*/~i',"<?php\n".$license_text,$content);
                  file_put_contents($file,$content);
                  echo "Change: ".$file."\n";
              }
              else
              {
                  $content = preg_replace('~^<\?(php){0,1}~i',"<?php\n".$license_text,$content);
                  file_put_contents($file,$content);
                  echo "Add lc: ".$file."\n";
              }


          }
      }
    }
//    rmdir($dir);
}

iterate(__DIR__.'/www/library/Z/');
