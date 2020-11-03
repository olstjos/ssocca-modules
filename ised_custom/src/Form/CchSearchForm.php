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
    $op = \Drupal::request()->query->get('op');
    $keywords = $op == t('Clear') ? '':\Drupal::request()->query->get('keys');
    $sector = $op == t('Clear') ? '':\Drupal::request()->query->get('search_sector');
    $province = $op == t('Clear') ? '':\Drupal::request()->query->get('search_province');

    // Load sectors
    $sectorTerms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('sector');
    $sectorTermData = ['' => (string)t('Select a Sector')];
    foreach ($sectorTerms as $term) {
      $spaces = (int)$term->depth * 2;
      $space = '';
      for ($i=0; $i<$spaces; $i++) {
        $space .= '-';
      }
      $space = $space == '' ? '':$space.' ';
      $name = str_pad($term->name, strlen($term->name)+$spaces, ' ', STR_PAD_LEFT);
      $sectorTermData[$term->tid] = $space . $term->name;
    }

    // Load provinces
    $provinceTerms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('province');
    $provinceTermData = ['' => (string)t('Select a Province')];
    foreach ($provinceTerms as $term) {
      $provinceTermData[$term->tid] = $term->name;
    }

    $form['keys'] = [
      '#type' => 'hidden',
      //'#attributes' => ['placeholder' => (string)t('Search terms')],
      '#value' => $keywords,
      '#required' => false,
    ];
    $form['search_sector'] = [
      '#options' => $sectorTermData,
      '#type' => 'select',
      '#value' => $sector,
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
      '#value' => $this->t('Clear'),
      '#name' => 'op',
    ];
    $form['#attributes']['class'][] = 'form-inline';
    $form['#method'] = 'GET';

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
  }
}