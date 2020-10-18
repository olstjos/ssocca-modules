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

}
