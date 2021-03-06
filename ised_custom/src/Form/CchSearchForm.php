<?php

namespace Drupal\ised_custom\Form;

use Drupal\bootstrap\Bootstrap;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\search\SearchPageRepositoryInterface;
use Drupal\wxt_library\LibraryService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CchSearchForm
 */
class CchSearchForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cch_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $op = \Drupal::request()->query->get('op');
    //$keywords = $op == t('Clear') ? '': \Drupal::request()->query->get('keys');
    $sector = $op == t('Show All') ? '':\Drupal::request()->query->get('search_sector');
    $province = $op == t('Show All') ? '':\Drupal::request()->query->get('search_province');

    if ($op != t('Show All')) {
      $sector_tid = \Drupal::request()->query->get('search_sector');
      $province_tid = \Drupal::request()->query->get('search_province');
    }
    
    if ($sector_tid) {
      $term = \Drupal\taxonomy\Entity\Term::load($sector_tid); 
      if ($langcode != 'en') {
        if ($term->hasTranslation($langcode)) {
          $term = $term->getTranslation($langcode);
        }
      }
      $sectorTermData[$term->id()] = $term->label();
    }
    else {
      // Load sectors
//      $sectorTerms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('sector');
//      $sectorTermData = ['' => (string)t('Select a Sector')];
//
//      foreach ($sectorTerms as $term) {
//        if ($langcode != 'en') {
//          $term = \Drupal\taxonomy\Entity\Term::load($term->tid); 
//          if ($term->hasTranslation($langcode)) {
//            $term = $term->getTranslation($langcode);
//          }
//          $sectorTermData[$term->id()] = $term->label();
//        }
//        else {
//          $sectorTermData[$term->tid] = $term->name;
//        }
//      }
    
    }

//    if ($province_tid) {
//      $term = \Drupal\taxonomy\Entity\Term::load($province_tid); 
//      if ($langcode != 'en') {
//        if ($term->hasTranslation($langcode)) {
//          $term = $term->getTranslation($langcode);
//        }
//      }
//      $provinceTermData[$term->id()] = $term->label();
//
//    }
//    else {
      // Load provinces
      $provinceTerms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('province');
      $provinceTermData = ['' => (string)t('Select a Province')];
      foreach ($provinceTerms as $term) {
        if ($langcode != 'en') {
          $term = \Drupal\taxonomy\Entity\Term::load($term->tid); 
          if ($term->hasTranslation($langcode)) {
            $term = $term->getTranslation($langcode);
          }
          $provinceTermData[$term->id()] = $term->label();
        }
        else {
          $provinceTermData[$term->tid] = $term->name;
        }
      }
//    }
  

    $form['keys'] = [
      '#type' => 'hidden',
      //'#attributes' => ['placeholder' => (string)t('Search terms')],
      '#value' => $keywords,
      '#required' => false,
    ];
// Disable sector (for now)
//    $form['search_sector'] = [
//      '#options' => $sectorTermData,
//      '#title' => $this->t('Filter options'),  
//      '#type' => 'select',
//      '#value' => $sector,
//    ];
// Re-enable later after some thought.

    $form['keywords'] = [
      '#type' => 'text',
      /*'#attributes' => ['placeholder' => (string)t('Search terms')],*/
      '#value' => $keywords,
      '#required' => false,
    ];
    $form['search_province'] = [
      '#options' => $provinceTermData,
      '#type' => 'select',
      '#value' => $province,
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
     '#type' => 'submit',
     '#value' => $this->t('Filter'),
    ];
    $form['actions']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Show All'),
      '#name' => 'op',
    ];
    $form['#attributes']['class'][] = 'form-inline';
    $form['#method'] = 'GET';
    $form['#cache'] = ['max-age' => 0];
//    $form['#cache'] = ['url.path' => 0];
//    $form['#cache']['contexts']['url.path'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Nothing.
  }

  /**
   * {@inheritdoc}
   */

  public function submitForm(array &$form, FormStateInterface $form_state) {
    //\Drupal::messenger()->addMessage('keys:' . print_r(dump(array_keys($form/*['tid']*/), TRUE)), TRUE);//Very good for debugging
  }
}
