<?php

namespace Drupal\contentimport\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for contentimport routes.
 */
class ContentImportController extends ControllerBase {

  /**
   * Get All Content types.
   */
  public static function getAllContentTypes() {
    $contentTypes = \Drupal::service('entity_type.manager')->getStorage('node_type')->loadMultiple();
    $contentTypesList = [];
    $contentTypesList['none'] = 'Select';
    $allowedList = ['info_link', 'complaint_link'];
    foreach ($contentTypes as $contentType) {
      if ( in_array($contentType->id(), $allowedList) ) {
        $contentTypesList[$contentType->id()] = $contentType->label();
      }
    }
    return $contentTypesList;
  }


  public function getLog() {
    $from = (int) ($_GET['from'] ?? 0);
    $messages = file('/tmp/oca-log');
    $length = count($messages);
    $next = $length;
    if ($length > 0 && substr($messages[$length-1], 0, 3) == '---') {
      $next = false;
    }
    if ($from > 0) {
      $messages = array_slice($messages, $from);
    }
    if ($next === false) {
      array_pop($messages);
    }
    return new JsonResponse(['status' => 'ok', 'from' => $from, 'next' => $next, 'messages' => $messages]);
  }

}
