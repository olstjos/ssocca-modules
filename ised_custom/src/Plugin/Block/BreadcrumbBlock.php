<?php

namespace Drupal\ised_custom\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Breadcrumb' Block.
 *
 * @Block(
 *   id = "breadcrumb_block",
 *   admin_label = @Translation("Breadcrumb block"),
 *   category = @Translation("Breadcrumb block"),
 * )
 */
class BreadcrumbBlock extends BlockBase{

  /**
   * {@inheritdoc}
   */
  public function build() {

    $current_path = \Drupal::service('path.current')->getPath();
    $path_args = explode('/', $current_path);
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

    foreach($path_args as $args){
      if (is_numeric($args)) {
        $tid=$args;
      }
    }

    $current_route = \Drupal::routeMatch()->getRouteName();

    $ancestors = \Drupal::service('entity_type.manager')->getStorage("taxonomy_term")->loadAllParents($tid);
    $list = [];
    $list_tids=[];
    foreach ($ancestors as $term) {

      if ($term->hasTranslation($language)) {
        $termTranslation = $term->getTranslation($language);
        $list[] = $termTranslation->label();
      }
      else {
        $list[] = $term->label();
      }
      $list_tids[]=$term->id();
    }

    $extra_crumb = '';
    if ($current_route == 'view.sector_browse.page_2') {
      $extra_crumb = '<li class="breadcrumb-item active" aria-current="page">'.$list[0].'</li>';
    }
  $markup='';
    if(count($list) ==1){
      $markup='<nav property="breadcrumb" aria-label="breadcrumb" role="navigation">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">' . t('Start') . '</a></li>
        ' . $extra_crumb . '
      </ol>
    </nav>';

    }
    else if(count($list) ==2){
      $markup ='<nav property="breadcrumb" aria-label="breadcrumb" role="navigation">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">' . t('Start') . '</a></li>
        <li class="breadcrumb-item"><a href="/'.$language.'/sector/term/'.$list_tids[1].'">'.$list[1].'</a></li>
        ' . $extra_crumb . '
      </ol>
    </nav>';

    }
    else if(count($list) ==3){
      $markup ='<nav property="breadcrumb" aria-label="breadcrumb" role="navigation">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">' . t('Start') . '</a></li>
        <li class="breadcrumb-item"><a href="/'.$language.'/sector/term/'.$list_tids[2].'">'.$list[2].'</a></li>
        <li class="breadcrumb-item"><a href="/'.$language.'/sector/term/'.$list_tids[1].'">'.$list[1].'</a></li>
        ' . $extra_crumb . '
      </ol>
    </nav>';

    }
    else if(count($list) ==4){
      $markup ='<nav property="breadcrumb" aria-label="breadcrumb" role="navigation">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">' . t('Start') . '</a></li>
        <li class="breadcrumb-item"><a href="/'.$language.'/sector/term/'.$list_tids[3].'">'.$list[3].'</a></li>
        <li class="breadcrumb-item"><a href="/'.$language.'/sector/term/'.$list_tids[2].'">'.$list[2].'</a></li>
        <li class="breadcrumb-item"><a href="/'.$language.'/sector/term/'.$list_tids[1].'">'.$list[1].'</a></li>
        ' . $extra_crumb . '
      </ol>
    </nav>';
        //<li class="breadcrumb-item active" aria-current="page">'.$list[0].'</li>

    }
    else{
      $markup='';
    }

    return [
      '#markup' => $markup,
      '#cache' => ['contexts' => ['url.path']], // Improves performance.
      // '#cache'=>['max-age'=>0,],
    ];
  }

}
