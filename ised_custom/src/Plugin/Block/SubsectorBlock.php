<?php

namespace Drupal\ised_custom\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Subsector' Block.
 *
 * @Block(
 *   id = "subsector_block",
 *   admin_label = @Translation("Subsector Block"),
 *   category = @Translation("Subsector Block"),
 * )
 */
class SubsectorBlock extends BlockBase{

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

    $current_path = \Drupal::request()->getRequestUri();
    $route_name = \Drupal::routeMatch()->getRouteName();
    
    if($tid && $route_name=='view.sector_browse.page_1'){
      $vid = 'sector';
      $child_terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, $tid, 1, FALSE);
      foreach ($child_terms as $key => $child_term) {
          $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
          $term_detail[$key]['label'] = $child_term->name;
          $term_detail[$key]['tid'] = $child_term->tid;
          $term_obj = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($child_term->tid);
          if ($langcode != 'en') {
            if ($term_obj->hasTranslation($langcode)) {
              $term_obj = $term_obj->getTranslation($langcode);
            }
            $term_detail[$key]['label'] = $term_obj->label();
          }
          $term_detail[$key]['provincial'] = $term_obj->get('field_provincial')->value;
          if(isset($term_obj->get('field_image_sector')->entity)){
            $term_detail[$key]['image'] = file_create_url($term_obj->get('field_image_sector')->entity->getFileUri());
          }
          $children = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, $child_term->tid, 1, FALSE);
          if ($children) {
            $term_detail[$key]['children'] = 1;
          } else {
            $term_detail[$key]['children'] = 0;
          }
      }

    }
    else if($current_path='/en/sector/start' || $current_path='/en/sector/start') {
      $vid = 'sector';
      $child_terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, 0, 1, FALSE);
      foreach ($child_terms as $key => $child_term) {
          $term_detail[$key]['label'] = $child_term->name;
          $term_detail[$key]['tid'] = $child_term->tid;
          $term_obj = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($child_term->tid);
          $term_detail[$key]['provincial'] = $term_obj->get('field_provincial')->value;
          if(isset($term_obj->get('field_image_sector')->entity)){
            $term_detail[$key]['image'] = file_create_url($term_obj->get('field_image_sector')->entity->getFileUri());
          }
          $children = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, $child_term->tid, 1, FALSE);
          if ($children) {
            $term_detail[$key]['children'] = 1;
          } else {
            $term_detail[$key]['children'] = 0;
          }
      }

    }
    
    
    // Clear block cache
    \Drupal::service('page_cache_kill_switch')->trigger();

    $renderable = [
      '#theme' => 'sub_sector',
      '#termdata' => $term_detail,
      '#language' => $language,
      '#cache' => [
        'max-age' => 0,
        'tags' => ['subsector-block'.$tid],
      ]
    ];

    return $renderable;
  }

}
