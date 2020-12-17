<?php

namespace Drupal\contentimport\Form;

use Drupal\contentimport\Controller\ContentImportController;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\file\Entity\File;

/**
 * Configure Content Import settings for this site.
 */
class ContentImport extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contentimport';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'contentimport.settings',
    ];
  }

  /**
   * Content Import Form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
  /*  $contentTypes = ContentImportController::getAllContentTypes();
    $form['contentimport_contenttype'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Content Type'),
      '#options' => $contentTypes,
      '#default_value' => $this->t('Select'),
      '#required' => TRUE,
      /*
      '#ajax' => [
        'event' => 'change',
        'callback' => '::contentImportcallback',
        'wrapper' => 'content_import_fields_change_wrapper',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],*/
  //  ];
  

    $form['file_upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Import CSV File'),
      '#size' => 40,
      '#description' => $this->t('Select the CSV file to be imported.'),
      '#required' => TRUE,
      '#autoupload' => TRUE,
      '#upload_location' => 'public://',
      '#upload_validators' => array(
        'file_validate_extensions' => array('csv'),
      ),
    ];

    $form['loglink'] = [
      '#type' => 'link',
      '#title' => $this->t('Check Log..'),
      '#url' => Url::fromUri('base:sites/default/files/contentimportlog.txt'),
      '#attributes' => ['target' => '_blank'],
    ];

    $form['import_ct_markup'] = [
      '#suffix' => '<div id="content_import_fields_change_wrapper"></div>',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
      '#button_type' => 'primary',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  
  public function validateForm(array &$form, FormStateInterface $form_state) {
 /*   $this->managed_file = file_save_upload('file_upload', $form['file_upload']['#upload_validators'], FALSE, 0);

    if (!$this->managed_file) {
      $form_state->setErrorByName('file_upload', $this->t('Provided file is not a CSV file or is corrupted.'));*/

 /* if($form_state->getValue('contentimport_contenttype')=='none'){
    $form_state->setErrorByName('contentimport_contenttype', $this->t('Select a Content Type'));

  }*/
  }

  /**
   * Content Import Sample CSV Creation.
   */
  public function contentImportcallback(array &$form, FormStateInterface $form_state) {
    global $base_url;
    $ajax_response = new AjaxResponse();
    $contentType = $form_state->getValue('contentimport_contenttype');
    $fields = get_fields($contentType);
    $fieldArray = $fields['name'];
    $contentTypeFields = 'title,';
    $contentTypeFields .= 'langcode,';
    foreach ($fieldArray as $val) {
      $contentTypeFields .= $val . ',';
    }
    $contentTypeFields = substr($contentTypeFields, 0, -1);
    $sampleFile = $contentType . '.csv';
    $handle = fopen("sites/default/files/" . $sampleFile, "w+") or die("There is no permission to create log file. Please give permission for sites/default/file!");
    fwrite($handle, $contentTypeFields);
    $result = '<a class="button button--primary" href="' . $base_url . '/sites/default/files/' . $sampleFile . '">Click here to download Sample CSV</a>';
    $ajax_response->addCommand(new HtmlCommand('#content_import_fields_change_wrapper', $result));
    return $ajax_response;
  }

  /**
   * Content Import Form Submission.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
 
    $fid = $form_state->getValue(['file_upload', 0]);
    $file = File::load($fid);
    $new_filename = "import.csv";
    $stream_wrapper = \Drupal::service('file_system')->uriScheme($file->getFileUri());
    $new_filename_uri = "{$stream_wrapper}://{$new_filename}";
    file_move($file, $new_filename_uri);
    drupal_set_message(t('file has been uploaded'));
  }

}
