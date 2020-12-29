<?php

namespace Drupal\contentimport;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\media\Entity\Media;

/**
 * Description of Utils
 *
 * @author j
 */
class Utils {
    //put your code here
    
  static public function addMessage($message, $type = MessengerInterface::TYPE_WARNING) {
    \Drupal::messenger()->addMessage($message, $type, TRUE);
  }


  static public function addToLog($message, $DEBUG = TRUE/*Set this to true for DEBUG.*/) {
    if ($DEBUG) {
      \Drupal::logger('ised-isde')->notice($message);
    }
  }


  public static function importContentIntoMediaLib($url, &$importedFileNameArray) {
    self::addToLog("url: " . $url);
    self::addToLog("fullpath: " . DRUPAL_ROOT  . $url);
    $fullpath = DRUPAL_ROOT  . $url;
    //$fileArray = global $importedFileNameArray;

    if (!file_exists($fullpath)){
      echo "----file not exist: " . $fullpath . "\n";
      return;
    }

    if (in_array($fullpath, $importedFileNameArray)){
      echo "----skip existing: " . $fullpath . "\n";
      return;
    }

    $file_data = file_get_contents($fullpath);
    self::addToLog("getcwd: " . getcwd());
    self::addToLog("replace: " . DRUPAL_ROOT . "/sites/default/files/");
    $directory = str_replace(DRUPAL_ROOT . "/sites/default/files/","",$fullpath); // fullpath

    self::addToLog("directory: " . $directory);
    $file = file_save_data($file_data, 'public://' . $directory, FILE_EXISTS_REPLACE);
    // print_r($file);
    echo $file->id() . "\n";

    $path_parts = pathinfo($fullpath);
    $filename = $path_parts['basename'];
    $fileExtension = $path_parts['extension'];
    $bundleType = '';
    switch (strtolower($fileExtension)) {
        case "csv":
            $bundleType = 'document';
            $media = Media::create([
              'bundle'           => $bundleType,
              'uid'              => 1,
              'title'       => $filename,
              'import'      => TRUE,
              'imported'    => FALSE,
              'field_document' => [
                'target_id' => $file->id()
              ],
            ]);
            $media->setName($filename)->setPublished(TRUE)->save();
            break;
        case "doc":
        case "docx":
        case "json":
        case "pdf":
        case "ppt":
        case "xls":
        case "xlsm":
        case "xlsx":
            $bundleType = 'document';
            $media = Media::create([
              'bundle'           => $bundleType,
              'uid'              => 1,
              'title'       => $filename,
              'field_document' => [
                'target_id' => $file->id()
              ],
            ]);
            $media->setName($filename)->setPublished(TRUE)->save();
            break;
        case "gif":
        case "jfif":
        case "jpg":
        case "png":
        case "tif":
            $bundleType = 'image';
            $media = Media::create([
              'bundle'           => $bundleType,
              'uid'              => 1,
              /*'alt'       => $filename,*/ // WCAG says no , filename should not be same as title attribute. agrcms/d8#179 gitlab.com
              'image' => [
                'target_id' => $file->id()
              ],
            ]);
            $media->setName($filename)->setPublished(TRUE)->save();
            break;
        case "mp4":
        case "wmv":
            $bundleType = 'video_file';
            $media = Media::create([
              'bundle'           => $bundleType,
              'uid'              => 1,
              /*'title'       => $filename,*/ // WCAG says no , filename should not be same as title attribute. agrcms/d8#179 gitlab.com.
              'field_media_video_file' => [
                'target_id' => $file->id()
              ],
            ]);
            $media->setName($filename)->setPublished(TRUE)->save();
            break;

    }

  //$url = $file->url();

  $importedFileNameArray[] = $fullpath;

  return $media;
}



}
