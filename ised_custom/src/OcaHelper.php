<?php

namespace Drupal\ised_custom;

class OcaHelper {

  static public function getTidFromPathParam() {
    $tid = FALSE;
    $path = \Drupal::request()->getpathInfo();
    $arg  = explode('/',$path);
    if (isset($arg[5]) && is_numeric($arg[5])) {
      $tid = $arg[5];
    }
    else {
      // This throws a warning for some reason, but is a good fallback.
      $tid = \Drupal::request()->get('arg_0');
    }
    return $tid;
  }

  static public function getLabelFromTid($tid, $lang) {
    if (is_numeric($tid)) {
      $term = \Drupal\taxonomy\Entity\Term::load($tid);
      if ($lang != 'en' && $term->hasTranslation($lang)) {
        $term = $term->getTranslation($lang);
      } 
      return $term->label();
    }
    return FALSE;
  }

}
