<?php

namespace Drupal\groups_demo\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for groups_demo routes.
 */
class GroupsDemoController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
