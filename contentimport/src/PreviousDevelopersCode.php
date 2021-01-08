<?php


namespace Drupal\contentimport;

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\user\Entity\User;


/**
 * Description of PreviousDevelopersCode
 *
 * @author j
 */
class PreviousDevelopersCode {

    public static function md5_validation($chaine) {
      $md5 = $chaine;
      if (strpos($md5,':') > 0) {
        // Still serialized, make it an md5.
        $md5 = md5($md5);
      }
      $result = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
        ->condition('field_md5', $md5, 'IN')
        ->execute();
      if (count($result) > 0) {
        return FALSE;
      }
      return TRUE;
    }

    public static function sha_validation($chaine) {
      $md5 = $chaine;
      if (strpos($md5,':') > 0) {
        // Still serialized, make it an md5.
        $md5 = md5($md5);
      }
      $result = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
      ->condition('field_sha', $md5)
      ->execute();
      if (count($result) > 0) {
        return FALSE;
      }
      return TRUE;
    }
    /**
     * To import data as Content type nodes.
     */
    public static function create_node($location, $contentType)
    {
      $countErreur = 0;
      drupal_flush_all_caches();
      global $base_url;

      $files = \Drupal::entityTypeManager()
        ->getStorage('file')
        ->loadByProperties(['uri' => $location]);
      $file = reset($files);
      $fid = $file->fid->value;


      //\Drupal::logger('TEST')->notice('<pre>' . print_r($location, TRUE) . '</pre>');
      //\Drupal::logger('TEST')->notice('<pre>' . print_r($contentType, TRUE) . '</pre>');
      $request_time = \Drupal::time()->getCurrentTime();
      $logFileName = "contentimportlog.txt";
      $logFile = fopen("sites/default/files/" . $logFileName, "w") or die("There is no permission to create log file. Please give permission for sites/default/file!");
      $fields = self::get_fields($contentType);
      $fieldNames = $fields['name'];
      $fieldTypes = $fields['type'];
      $fieldSettings = $fields['setting'];
      $bag_of_md5s = [];
      $processed_tids = [];
      // Code for import csv file.
      $mimetype = 1;
      if ($mimetype) {
        if (($handle = fopen($location, "r")) !== FALSE) {
          $keyIndex = [];
          $index = 0;
          $importHasErrors = false;
          $logVariationFields = "***************************** Content Import Begins ************************************\r\n";
          $headerFields = [];
          while (($data = fgetcsv($handle)) !== FALSE) {
            //echo '<pre>'; print_r ($data);die;
            $index++;
            $errors = [];
            if ($index < 2) {
              $data = self::format_header($data, $contentType);
              array_push($fieldNames, 'title');
              array_push($fieldTypes, 'text');
              array_push($fieldNames, 'langcode');
              array_push($fieldTypes, 'lang');
              array_push($fieldNames, 'author');
              array_push($fieldTypes, 'authored_by');
              if (array_search('langcode', $data) === FALSE) {
                $logVariationFields .= "Langcode missing --- Assuming EN as default langcode.. Import continues  \n \n";
                $data[count($data)] = 'langcode';
              }
              foreach ($fieldNames as $fieldValues) {
                $i = 0;
                foreach ($data as $dataValues) {
                  //$dataValues = Unicode::convertToUtf8($dataValues, 'ascii');
                  //$fieldValues = Unicode::convertToUtf8($fieldValues, 'ascii');
                  if ($fieldValues == $dataValues) {
                    //echo '*'.$fieldValues . '*|*' . $dataValues . '*<br>';
                    //$logVariationFields .= "Data Type : " . $fieldValues . "  Matches \n";
                    $keyIndex[$fieldValues] = $i;
                  } else if ($fieldValues == 'title') {
                    //printf('*%s: %s*|%s: %s<br>', $fieldValues, mb_detect_encoding($fieldValues), $dataValues, mb_detect_encoding($dataValues));
                  }
                  $i++;
                }
              }
              
              // Header
              $headerFields = $data;
              continue;
            } else {
              // Log record
              $logVariationFields .= sprintf("RECORD %s\r\n", $index - 1);

              // Check content type
              $indexContentType = array_search('content_type', $headerFields);
              if ($data[$indexContentType] != $contentType) {
                $logVariationFields .= sprintf("- Content type is not %s\r\n", $contentType);
                $logVariationFields .= "----------------------------------------\r\n";
                continue;
              }
            }
            $array_partiel = []; // Réinitialiser.
            // Ajouter validation pour sha ici:
            $title_check = $data[0/*title*/];
            $source_check = $data[8/*field_source*/];
            $link_check = $data[4/*field_link_info OR field_link_complaint??*/];
            
            if (empty($title_check) || empty($source_check) || empty($link_check)) {
                \Drupal::logger('ERROR')->notice('<pre>title_check OR link_check OR source_check was empty</pre>');
                continue;
            }

            $array_partiel['title'] = $title_check;
            $array_partiel['field_source'] = $source_check;
            $array_partiel['field_link'] = $link_check;
            //\Drupal::logger('ARRAY_PARTIEL')->notice('<pre>' . $index  . '|'. print_r($array_partiel, TRUE) . '</pre>');
            $chaine_ensemble = serialize($data);
            $md5_partiel = serialize($array_partiel);
            if (!self::sha_validation($chaine_ensemble)) {
                $countErreur++;
                /*\Drupal::messenger()
                  ->addError('Appel les pompiers!!!' . $countErreur);*/
              //continue;
            }
            if (!self::md5_validation(md5($md5_partiel))) {
                $countErreur++;
/*\Drupal::messenger()
                ->addError('Appel les secours!!!' . $countErreur);*/
              //continue;
            }
            if (
              !isset($keyIndex['title']) ||
              ($contentType == 'info_link' && !isset($keyIndex['field_link_info'])) ||
              ($contentType == 'complaint_link' && !isset($keyIndex['field_link_complaint']))
            ) {
              \Drupal::messenger()
                ->addError(t('Title and/or link is missing in CSV file. Please add these fields and import again'));
              $url = $base_url . "/admin/config/system/cron";
              header('Location:' . $url);
              exit;
            }
            // Default Language.
            $nodeArrayEn = [];
            for ($f = 0; $f < count($fieldNames); $f++) {
              switch ($fieldTypes[$f]) {
                case 'image':
                  //$logVariationFields .= "Importing Image (" . trim($data[$keyIndex[$fieldNames[$f]]]) . ") :: ";
                  if (!empty($data[$keyIndex[$fieldNames[$f]]])) {
                    $imgIndex = trim($data[$keyIndex[$fieldNames[$f]]]);
                    $files = glob('sites/default/files/' . $contentType . '/images/' . $imgIndex);
                    $fileExists = file_exists('sites/default/files/' . $imgIndex);
                    if (!$fileExists) {
                      $images = [];
                      foreach ($files as $file_name) {
                        $image = File::create(['uri' => 'public://' . $contentType . '/images/' . basename($file_name)]);
                        $image->save();
                        $images[basename($file_name)] = $image;
                        $imageId = $images[basename($file_name)]->id();
                      }
                      $nodeArrayEn[$fieldNames[$f]] = [
                        [
                          'target_id' => $imageId,
                          'alt' => $images['title'],
                          'title' => $images['title'],
                        ],
                      ];
                      $logVariationFields .= "Image uploaded successfully \n ";
                    }
                  }
                  $logVariationFields .= " Success \n";
                  break;

                case 'entity_reference':
                  //$logVariationFields .= "Importing Reference Type (" . $fieldSettings[$f]['target_type'] . ") :: ";
                  $terms = [];
                  if ($fieldSettings[$f]['target_type'] == 'taxonomy_term') {
                    $target_bundles = $fieldSettings[$f]['handler_settings']['target_bundles'];
                    // If vocabulary field settings target is single, assume it.
                    if (count($target_bundles) == 1 && !empty($data[$keyIndex[$fieldNames[$f]]])) {
                      $terms = self::get_term_reference($target_bundles[key($target_bundles)], $data[$keyIndex[$fieldNames[$f]]]);
                    }
                    // If not, assume vocabulary is added with ":" delimiter.
                    else {
                      $reference = explode(":", $data[$keyIndex[$fieldNames[$f]]]);
                      if (is_array($reference) && $reference[0] != '') {
                        $terms = self::get_term_reference($reference[0], $reference[1]);
                      }
                    }
                    //$logVariationFields .= sprintf('%s %s', $fieldNames[$f], print_r($terms, true));
                    //echo '|'.$fieldNames[$f].':'.$data[$keyIndex[$fieldNames[$f]]].'|'.'<br>';
                    if (!empty($terms)) {
                      $nodeArrayEn[$fieldNames[$f]] = $terms;
                    }
                  } elseif ($fieldSettings[$f]['target_type'] == 'user') {
                    $userArray = explode(', ', $data[$keyIndex[$fieldNames[$f]]]);
                    $users = self::get_user_info($userArray);
                    $nodeArrayEn[$fieldNames[$f]] = $users;
                  } elseif ($fieldSettings[$f]['target_type'] == 'node') {
                    $nodeArrayEns = explode(':', $data[$keyIndex[$fieldNames[$f]]]);
                    $nodeReference1 = self::get_node_id($nodeArrayEns);
                    $nodeArrayEn[$fieldNames[$f]] = $nodeReference1;
                  }
                  //$logVariationFields .= " Success \n";
                  break;

                case 'text_long':
                case 'text':
                  //$logVariationFields .= "Importing Content (" . $fieldNames[$f] . ") :: ";
                  $nodeArrayEn[$fieldNames[$f]] = [
                    'value' => $data[$keyIndex[$fieldNames[$f]]],
                    'format' => 'full_html',
                  ];
                  //$logVariationFields .= " Success \n";
                  break;

                case 'entity_reference_revisions':
                case 'text_with_summary':
                  //$logVariationFields .= "Importing Content (" . $fieldNames[$f] . ") :: ";
                  $nodeArrayEn[$fieldNames[$f]] = [
                    'summary' => substr(strip_tags($data[$keyIndex[$fieldNames[$f]]]), 0, 100),
                    'value' => $data[$keyIndex[$fieldNames[$f]]],
                    'format' => 'full_html',
                  ];
                  //$logVariationFields .= " Success \n";

                  break;

                case 'datetime':
                  //$logVariationFields .= "Importing Datetime (" . $fieldNames[$f] . ") :: ";
                  $dateArray = explode(':', $data[$keyIndex[$fieldNames[$f]]]);
                  if (count($dateArray) > 1) {
                    $dateTimeStamp = strtotime($data[$keyIndex[$fieldNames[$f]]]);
                    $newDateString = date('Y-m-d\TH:i:s', $dateTimeStamp);
                  } else {
                    $dateTimeStamp = strtotime($data[$keyIndex[$fieldNames[$f]]]);
                    $newDateString = date('Y-m-d', $dateTimeStamp);
                  }
                  $nodeArrayEn[$fieldNames[$f]] = ["value" => $newDateString];
                  //$logVariationFields .= " Success \n";
                  break;

                case 'timestamp':
                  //$logVariationFields .= "Importing Content (" . $fieldNames[$f] . ") :: ";
                  $nodeArrayEn[$fieldNames[$f]] = ["value" => $data[$keyIndex[$fieldNames[$f]]]];
                  //$logVariationFields .= " Success \n";
                  break;

                case 'boolean':
                  //$logVariationFields .= "Importing Boolean (" . $fieldNames[$f] . ") :: ";
                  $affirmative = ['On', 'on', 'Yes', 'yes', 1, 'Oui', 'oui'];
                  $nodeArrayEn[$fieldNames[$f]] = in_array($data[$keyIndex[$fieldNames[$f]]], $affirmative);/*  (
                    $data[$keyIndex[$fieldNames[$f]]] == 'On' ||
                    $data[$keyIndex[$fieldNames[$f]]] == 'Yes' ||
                    $data[$keyIndex[$fieldNames[$f]]] == 'on' ||
                    $data[$keyIndex[$fieldNames[$f]]] == 1 ||
                    $data[$keyIndex[$fieldNames[$f]]] == 'yes') ? 1 : 0;*/
                  //$logVariationFields .= " Success \n";
                  break;

                case 'langcode':
                  //$logVariationFields .= "Importing Langcode (" . $fieldNames[$f] . ") :: ";
                  $nodeArrayEn[$fieldNames[$f]] = ($data[$keyIndex[$fieldNames[$f]]] != '') ? $data[$keyIndex[$fieldNames[$f]]] : 'en';
                  //$logVariationFields .= " Success \n";
                  break;

                case 'geolocation':
                  //$logVariationFields .= "Importing Geolocation Field (" . $fieldNames[$f] . ") :: ";
                  $geoArray = explode(";", $data[$keyIndex[$fieldNames[$f]]]);
                  if (count($geoArray) > 0) {
                    $geoMultiArray = [];
                    for ($g = 0; $g < count($geoArray); $g++) {
                      $latlng = explode(",", $geoArray[$g]);
                      for ($l = 0; $l < count($latlng); $l++) {
                        $latlng[$l] = floatval(preg_replace("/\[^0-9,.]/", "", $latlng[$l]));
                      }
                      array_push(
                        $geoMultiArray,
                        [
                          'lat' => $latlng[0],
                          'lng' => $latlng[1],
                        ]
                      );
                    }
                    $nodeArrayEn[$fieldNames[$f]] = $geoMultiArray;
                  } else {
                    $latlng = explode(",", $data[$keyIndex[$fieldNames[$f]]]);
                    for ($l = 0; $l < count($latlng); $l++) {
                      $latlng[$l] = floatval(preg_replace("/\[^0-9,.]/", "", $latlng[$l]));
                    }
                    $nodeArrayEn[$fieldNames[$f]] = ['lat' => $latlng[0], 'lng' => $latlng[1]];
                  }
                  //$logVariationFields .= " Success \n";
                  break;
                case 'moderation_state':
                case 'field_sha':
                case 'field_md5':
                  break;
                case 'entity_reference_revisions':
                  /* In Progress */
                  break;

                case 'list_string':
                  //$logVariationFields .= "Importing Content (" . $fieldNames[$f] . ") :: ";
                  $listArray = explode(",", $data[$keyIndex[$fieldNames[$f]]]);
                  array_walk($listArray, 'trim');
                  $nodeArrayEn[$fieldNames[$f]] = $listArray;
                  //$logVariationFields .= " Success \n";
                  break;

                case 'geofield':
                  //$logVariationFields .= "Importing Geofield Field (" . $fieldNames[$f] . ") :: ";
                  if (!empty(trim($data[$keyIndex[$fieldNames[$f]]]))) {
                    $geoFieldArray = explode(";", trim($data[$keyIndex[$fieldNames[$f]]]));
                    if (count($geoFieldArray) > 0) {
                      $geoFieldMultiArray = [];
                      for ($g = 0; $g < count($geoFieldArray); $g++) {
                        $latlng = explode(",", $geoFieldArray[$g]);
                        for ($l = 0; $l < count($latlng); $l++) {
                          $latlng[$l] = floatval($latlng[$l]);
                        }
                        array_push(
                          $geoFieldMultiArray,
                          \Drupal::service('geofield.wkt_generator')->WktBuildPoint([trim($latlng[1]), trim($latlng[0])])
                        );
                      }
                      $nodeArrayEn[$fieldNames[$f]] = $geoFieldMultiArray;
                    } else {
                      $latlng = explode(",", trim($data[$keyIndex[$fieldNames[$f]]]));
                      for ($l = 0; $l < count($latlng); $l++) {
                        $latlng[$l] = floatval($latlng[$l]);
                      }
                      $lonlat = \Drupal::service('geofield.wkt_generator')->WktBuildPoint([trim($latlng[1]), trim($latlng[0])]);
                      $nodeArrayEn[$fieldNames[$f]] = $lonlat;
                    }
                    //$logVariationFields .= " Success \n";
                  }
                  break;

                case 'authored_by':
                  //$logVariationFields .= "Importing Content (" . $fieldNames[$f] . ") :: ";
                  if (isset($data[$keyIndex[$fieldNames[$f]]])) {
                    $user_id = self::get_user_id($data[$keyIndex[$fieldNames[$f]]]);                      
                  }
                  else {
                    $user_id = \Drupal::currentUser()->id();
                  }
                  $nodeArrayEn['uid'] = ($user_id > 0) ? $user_id : \Drupal::currentUser()->id();
                  //$logVariationFields .= " Success \n";
                  break;

                default:
                  $nodeArrayEn[$fieldNames[$f]] = $data[$keyIndex[$fieldNames[$f]]];
                  break;
              }
            }
            $nodeArrayEn['type'] = strtolower($contentType);
            $nodeArrayEn['promote'] = 0;
            $nodeArrayEn['sticky'] = 0;
            $nodeArrayEn['langcode'] = 'en';
            $nodeArrayEn['moderation_state'] = 'published';
            $errors = self::get_record_errors($nodeArrayEn);

            if (count($errors) > 0) {
              $importHasErrors = true;
              $logVariationFields .= sprintf("Errors: %s\r\n- Record not imported\r\n", implode(', ', $errors));
              $logVariationFields .= "----------------------------------------\r\n";
              continue;
            }

            // Build French translation data
            $nodeArrayFr = $nodeArrayEn;
            $nodeArrayFr['langcode'] = 'fr';
            foreach ($fieldNames as $fieldName) {
              $headerIndex = array_search($fieldName . '_fr', $headerFields);
              if ($headerIndex !== false && $fieldName == 'title') {
                $nodeArrayFr[$fieldName]['value'] = $data[$headerIndex];
              } else if ($headerIndex !== false) {
                $nodeArrayFr[$fieldName] = $data[$headerIndex];
              }
            }

            //$matchingEn = self::get_matching_nodes($nodeArrayEn);
            //$matchingFr = self::get_matching_nodes($nodeArrayFr);
            
            $matchingEn = self::get_matching_nodes_by_hashes($nodeArrayEn, $chaine_ensemble, $md5_partiel);
            //$matchingFr = self::get_matching_nodes_by_hashes($nodeArrayFr, $chaine_ensemble, $md5_partiel);
            /*
            print "<pre>";
            print_r($nodeArrayEn);
            print_r($nodeArrayFr);
            die();
            */
            //\Drupal::logger('TESTen')->notice('<pre>' . print_r($matchingEn, TRUE) . '</pre>');
            if (count($matchingEn) > 1) {
              // Log error
              $logVariationFields .= "- Multiple existing records found\r\n";
              \Drupal::messenger()->addError('Multiple existing records found?? one example nid=' . $matchingEn[0]);
              continue;
            } elseif (count($matchingEn) == 1) {
              // We're doing an update
              $action = 'update';
              //\Drupal::logger('TESTupdate')->notice('<pre>update ' . print_r($matchingEn, TRUE) . '</pre>');
              $node = Node::load($matchingEn[0]);
              $logVariationFields .= sprintf("- count($matchingEn) == 1 NID %s\r\n", $node->id());
              foreach ($nodeArrayEn as $field => $values) {
                //\Drupal::logger('DEBUG'.$field)->notice('<pre>update ' . print_r($values, TRUE) . '</pre>');
                if (is_array($values) && count($values) > 0) {
                    foreach($values as $val) {
                      if (isset($val['target_id'])) {
                        if ($field == 'field_sector' || $field == 'field_province') {
                          if ($field == 'field_sector') {
                              $vocab = 'sector';
                          }
                          else {
                              $vocab = 'province';
                          }
                          if (!isset($processed_tids[$vocab][$node->id()])) {
                              $processed_tids[$vocab][$node->id()] = [];
                          }
                          if (!in_array($val['target_id'], $processed_tids[$vocab][$node->id()])) {
                            // Only process the tid if it's NOT found.
                            $node->{$field}[] = $val['target_id'];
                            $processed_tids[$vocab][$node->id()][] = $val['target_id'];
                          }
                        }
                        else {
                          if (!in_array($val['target_id'], $processed_tids[$field][$node->id()])) {
                              $node->{$field}[] = $val['target_id'];
                              $processed_tids[$field][$node->id()][] = $val['target_id'];
                          }
                        }
                      }
                    }                    
                }
                switch ($field) {
                    case 'entity_reference':
                      $vocab = 'province';
                      if ($field == 'field_sector') {
                        $vocab = 'sector';
                      }
                      else if ($field == 'field_province') {
                        $vocab = 'province';
                      }
                      self::term_help_setter($node, $values, $vocab, $field, $processed_tids);
                      break;
                    default:
                      break;
                }
                //$node->set($field, $values); // DISABLED!
              }
               //$node->set('field_sha', md5($chaine_ensemble));
              if (self::md5_validation($md5_partiel)) {
                $node->field_md5[] = md5($md5_partiel);
              }
  
//              $link_field_name = 'field_link_info';
//              if (!$node->field_link_other_lang) {
//                if ($node->hasField('field_link_info') && $node->field_link_info->hasTranslation()) {
//                    $en_link = $node->field_link_info;
//                    $fr_link = $node->getTranslation('fr')->field_link_info;
//                    if ($en_link != $fr_link) {
//                      if (!empty($en_link) && !empty($fr_link)) {
//                        $node->set('field_link_other_lang', TRUE);
//                      }                        
//                    }
//                }
//                else if ($node->hasField('field_link_complaint')) {
//                    $en_link = $node->field_link_complaint;
//                    $fr_link = $node->getTranslation('fr')->field_link_complaint;
//                    if ($en_link != $fr_link) {
//                      if (!empty($en_link) && !empty($fr_link)) {
//                        $node->set('field_link_other_lang', TRUE);
//                      }
//                    }
//                }
//              }
// 

              $node->save();
              // Log
              $logVariationFields .= sprintf("- English Updated successfully NID %s\r\n", $node->id());

              if ($node->hasTranslation('fr')) {
                $nodeFr = $node->getTranslation('fr');
                foreach ($nodeArrayFr as $field => $values) {
                  //$nodeFr->set($field, $values);
                  switch ($field) {
                    case 'field_link':
                    case 'field_source':
                      $nodeFr->set($field, $values);
                      break;
                    default:
                      break;
                  }
                }
                $nodeFr->save();

                // Log
                $logVariationFields .= sprintf("- French Updated successfully NID %s\r\n", $nodeFr->id());
              } else if (
                $nodeArrayFr['title']['value'] != ''
                && (($contentType == 'complaint_link' && $nodeArrayFr['field_link_complaint'] != '') || ($contentType == 'info_link' && $nodeArrayFr['field_link_info'] != ''))
              ) {
                $nodeFr = $node->addTranslation('fr', $nodeArrayFr);
                $nodeFr->title = $nodeArrayFr['title']['value'];// . ' - ' . $nodeArrayFr['field_source_fr']['value']
                $nodeFr->save();

                // Log
                $logVariationFields .= sprintf("- French Imported successfully NID %s\r\n", $nodeFr->id());
              } else {
                // Log
                $logVariationFields .= "- French data not present\r\n";
              }

              //fwrite($logFile, $logVariationFields);
            } else if (!in_array(md5($md5_partiel), $bag_of_md5s) && self::md5_validation(md5($md5_partiel))) {
              $action = 'insert';
              $nodeArrayEn['moderation_state'] = $nodeArrayFr['moderation_state'] = 'published';
              if (
                $nodeArrayEn['title']['value'] != ''
                && (($contentType == 'complaint_link' && $nodeArrayEn['field_link_complaint'] != '') || ($contentType == 'info_link' && $nodeArrayEn['field_link_info'] != ''))
              ) {
                $node = Node::create($nodeArrayEn);
                $node->uid = 1;
                $node->set('field_sha', md5($chaine_ensemble));
                if (strpos($md5_partiel, ':') > 0) {
                  $md5_partiel = md5($md5_partiel);
                }
                $node->set('field_md5', $md5_partiel);
                $bag_of_md5s[] = $md5_partiel;
                $node->save();
                // Log
                $logVariationFields .= sprintf("- English Imported successfully NID %s\r\n", $node->id());

                // If French data exists, add the translation
                if (
                  $nodeArrayFr['title']['value'] != ''
                  && (($contentType == 'complaint_link' && $nodeArrayFr['field_link_complaint'] != '') || ($contentType == 'info_link' && $nodeArrayFr['field_link_info'] != ''))
                ) {
                  $nodeFr = $node->addTranslation('fr', $nodeArrayFr);
                  $nodeFr->title = $nodeArrayFr['title']['value'];
                  $nodeFr->save();

                  // Log
                  $logVariationFields .= sprintf("- French Imported successfully NID %s\r\n", $nodeFr->id());
                } else {
                  // Log
                  $logVariationFields .= "- French data not present\r\n";
                }
                //fwrite($logFile, $logVariationFields);
              }
            }
            else {
              $logVariationFields .= "----------md5 in bag no add---------------\r\n";                
            }
            $logVariationFields .= "----------------------------------------\r\n";
          }
          fwrite($logFile, $logVariationFields);
          fclose($handle);

          //die('tta');
          if ($importHasErrors) {
            $message = t('Import completed with errors.  Please see error log.');
            \Drupal::messenger()->addWarning($message);
          } else {
            $message = t('Import completed successfully');
            \Drupal::messenger()->addMessage($message);
            if ($contentType == 'info_link') {
              $file = File::load($fid);
              $new_filename = $request_time . ".csv";
              $stream_wrapper = \Drupal::service('file_system')->uriScheme($file->getFileUri());
              $new_filename_uri = "{$stream_wrapper}://{$new_filename}";
              file_move($file, $new_filename_uri);
              \Drupal::messenger()->addMessage('file has been renamed', MessengerInterface::TYPE_WARNING, TRUE);
            }
          }

          //  $url = $base_url . "/admin/config/system/cron";
          //header('Location:' . $url);
          //exit;
        }
      }
    }
    
    
  /**
    * @TODO
    * 
    * @param object $node (Drupal node, objects are passed by ref automatically).
    * @param string $termvalues ,multiple term labels separated by : or single value.
    * @param string $vocab name of taxonomy vocabulary.
    * @param string $field name of node/entity field to update.
    */
//    public static function tidRefExists($node, $termvalues, $vocab, $field) {
//    {
//              $entityStorage = \Drupal::entityTypeManager()->getStorage('node');
//              $result = $entityStorage->getQuery()
//              ->condition($field, $tid, 'IN')
//              ->condition('title', $node->title)
//              ->condition('field_source', $node->field_source)
//              ->execute();
//            if (count($result) == 0) {
//            }
//    }
    
    
   /**
    * Set term ids on field using label and vocab name.
    * 
    * @param object $node (Drupal node, objects are passed by ref automatically).
    * @param string $termvalues ,multiple term labels separated by : or single value.
    * @param string $vocab name of taxonomy vocabulary.
    * @param string $field name of node/entity field to update.
    */
    public static function term_help_setter($node, $termvalues, $vocab, $field, &$processed_tids) {
        $tids = [];
        if (is_array($termvalues) && isset($termvalues[0]['target_id'])) {
            $entityStorage = \Drupal::entityTypeManager()->getStorage('node');
            foreach($termvalues as $termvalue) {
              $tid = $termvalue['target_id'];
              $tids[] = $termvalue['target_id'];
            }
            $tids = array_unique($tids);
            foreach ($tids as $tid) {
              $result = $entityStorage->getQuery()
                ->condition($field, $tid, 'IN')
                ->condition('title', $node->title)
                ->condition('field_source', $node->field_source->getValue())
                ->execute();
              if (!isset($processed_tids[$vocab])) {
                  $processed_tids[$vocab][$node->id()] = [];
              }
              if (is_numeric($tid) && count($result) == 0 && !in_array($tid, $processed_tids[$vocab][$node->id()])) {
                $node->{$field}[] = $tid;
                $processed_tids[$vocab][$node->id()][] = $tid;
              }
            }
            return $processed_tids;
            $term_csv = implode(',', $tids);
            return;
        }
        if (strpos($termvalues, ':') > 0) {
          $ref_array = explode(':', $values);

          foreach ($ref_array as $ref) {
            $tid = self::get_term_id($ref, $vocab);
            if (is_numeric($tid)) {
                $node->{$field}[] = $tid;
            }    
          }
        }
        else {
          $tid = self::get_term_id($termvalues, $vocab);
          if (is_numeric($tid)) {
            $node->{$field}[] = $tid;
          }
        }
    }

    //put your code here
    /**
    * To Create Terms if it is not available.
    */
   public static function create_voc($vid, $voc)
   {
     $vocabulary = Vocabulary::create(
       [
         'vid' => $vid,
         'machine_name' => $vid,
         'name' => $voc,
       ]
     );
     $vocabulary->save();
   }

   /**
    * To Create Terms if it is not available.
    */
   public static function create_term($voc, $term, $vid)
   {
     Term::create(
       [
         'parent' => [$voc],
         'name' => $term,
         'vid' => $vid,
       ]
     )->save();
     $termId = self::get_term_id($term, $vid);
     return $termId;
   }
   
   

    public static function format_header($header, $contentType)
    {
      foreach ($header as $key => $columnName) {
        if ($contentType == 'complaint_link' && in_array($columnName, ['field_link', 'field_link_fr'])) {
          $header[$key] = str_replace('field_link', 'field_link_complaint', $columnName);
        } elseif ($contentType == 'info_link' && in_array($columnName, ['field_link', 'field_link_fr'])) {
          $header[$key] = str_replace('field_link', 'field_link_info', $columnName);
        }
      }

      return $header;
    }


    /**
     * To get all Content Type Fields.
     */
    public static function get_fields($contentType)
    {
      $fields = [];
      foreach (\Drupal::service('entity_field.manager')
        ->getFieldDefinitions('node', $contentType) as $field_definition) {
        if (!empty($field_definition->getTargetBundle())) {
          $fields['name'][] = $field_definition->getName();
          $fields['type'][] = $field_definition->getType();
          $fields['setting'][] = $field_definition->getSettings();
        }
      }
      return $fields;
    }

   /**
    * To get Termid available.
    */
   public static function get_term_id($termname, $vid)
   {
        $query = \Drupal::entityQuery('taxonomy_term'); 
        $query->condition('vid', $vid); //select the collection
        $query->condition('name', $termname, 'CONTAINS'); //searching the title for a search term
        $term_ids= $query->execute();
        $terms = \Drupal\taxonomy\Entity\Term::loadMultiple($term_ids);  //process the nodes however is desired

        $result = [];
        $term_id = NULL;
        foreach($terms as $term){
          //$result[$term->id()] = $term->getName();
          // Grab the first one.
          $term_id = $term->id();
          return $term->id();
        }
        return $term_id;
//
//            return $result;
//     $query = \Drupal::database()->select('taxonomy_term_field_data', 't');
//     $query->fields('t', ['tid']);
//     $query->condition('t.vid', $vid);
//     $query->condition('t.name', $termname);
//     $termRes = $query->execute()->fetchAll();
//     foreach ($termRes as $val) {
//       $term_id = $val->tid;
//     }
//     return $term_id;
   }


    /**
     * To get node available.
     */
    public static function get_node_id($title)
    {
      $nodeReference = [];
      $db = \Drupal::database();
      foreach ($title as $key => $value) {
        $query = $db->select('node_field_data', 'n');
        $query->fields('n', ['nid']);
        $nodeId = $query
          ->condition('n.title', trim($value))
          ->execute()
          ->fetchField();
        $nodeReference[$key]['target_id'] = $nodeId;
      }
      return $nodeReference;
    }

   /**
    * To get Reference field ids.
    */
   public static function get_term_reference($voc, $terms)
   {
     //print "|$terms|<br >";
     $vocName = strtolower($voc);
     $vid = preg_replace('@[^a-z0-9_]+@', '_', $vocName);
     $vocabularies = Vocabulary::loadMultiple();

     if (!isset($vocabularies[$vid])) {
       \Drupal\contentimport\Utils::create_voc($vid, $voc);
       \Drupal::messenger()
         ->addError(t('Vocabulary not exists please add the vocabulary and import again'));
     }
     $termArray = array_map('trim', explode(',', $terms));
     $termIds = [];
     foreach ($termArray as $term) {
       $term_id = PreviousDevelopersCode::get_term_id($term, $vid);
       if (empty($term_id)) {
         $term_id = PreviousDevelopersCode::create_term($voc, $term, $vid);
         \Drupal::messenger()
           ->addError(t('Term does not exist') . ": $term");
       }
       $termIds[]['target_id'] = $term_id;
     }
     return $termIds;
   }


    /**
     * To get user id.
     */
    public static function get_user_id($name)
    {
      $user_id = \Drupal::database()
        ->select('users_field_data', 'u')
        ->fields('u', ['uid'])
        ->condition('u.name', trim($name))
        ->execute()
        ->fetchField();
      return $user_id;
    }


   /**
    * To get user information based on emailIds.
    */
   public static function get_user_info($userArray)
   {
     $uids = [];
     foreach ($userArray as $usermail) {
       if (filter_var($usermail, FILTER_VALIDATE_EMAIL)) {
         $users = \Drupal::entityTypeManager()->getStorage('user')
           ->loadByProperties(
             [
               'mail' => $usermail,
             ]
           );
       } else {
         $users = \Drupal::entityTypeManager()->getStorage('user')
           ->loadByProperties(
             [
               'name' => $usermail,
             ]
           );
       }
       $user = reset($users);
       if ($user) {
         $uids[] = $user->id();
       } else {
         $user = User::create();
         $user->uid = '';
         $user->setUsername($usermail);
         $user->setEmail($usermail);
         $user->set("init", $usermail);
         $user->enforceIsNew();
         $user->activate();
         $user->save();
         $users = \Drupal::entityTypeManager()->getStorage('user')
           ->loadByProperties(['mail' => $usermail]);
         $uids[] = $user->id();
       }
     }
     return $uids;
   }

   /**
    * Get matching nids for given node data by hashkey
    * 
    * @param array $data Node data
    * @param string $md5 hashkey value for title, field_link (or field_link_fr), and field_source
    * @return array Matching node IDs
    */
   public static function get_matching_nodes_by_hashes($data, $sha, $md5)
   {
     if (strpos($md5,':') > 0) {
       $md5 = md5($md5);
     }
     if (strpos($sha,':') > 0) {
       $sha = md5($sha);
     }
     $nids = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
        ->condition('field_md5', $md5, 'IN')
        ->execute();
     //\Drupal::logger('QUERYts')->notice('<pre>' . print_r($testQuery->__toString(), TRUE) . '</pre>');
     // \Drupal::logger('QUERYargs')->notice('<pre>' . print_r($testQuery->arguments(), TRUE) . '</pre>');
     $values = array_values($nids);
     return $values;
     
     /*$query = \Drupal::entityQuery('node')
       ->condition('title', $data['title']['value'])
       ->condition('field_source', $data[]
       ->condition('type', $data['type']);*/
//     $query = \Drupal::entityQuery('node')
//       ->condition('field_md5', $md5, 'IN');
//
//     $nids = $query->execute();
//     
//     if (empty($nids)) {
//         // Create an object of type Select.
//        $database = \Drupal::database();
//        $query = $database->select('node__field_md5', 'm');
//
//        // Add extra detail to this query object: a condition, fields and a range.
//        $query->condition('m.field_md5_value', $md5);
//        $query->fields('m', ['entity_id']);
//        $nids = [];
//        $result = $query->execute();
//        foreach ($result as $record) {
//          $nids[] = $record;
//        }
//
//       // print_r($query->__toString());
//       // print_r($query->arguments());
//        return array_values($nids);
//
//     }
//     return array_values($nids);
     /*$nids = array_values($nids);
     foreach ($nids as $nid) {
       $node = Node::load($nids[0]);
       $md5_array = $node->get('field_md5')->getValue();
       if (in_array($md5_array, [$md5])) {
         return $nids;
       }
         
     }
     return [];*/
   }
   
   /**
    * Get matching nids for given node data
    * 
    * @param array $data Node data
    * @return array Matching node IDs
    */
   public static function get_matching_nodes($data)
   {
     /*
     print "<pre>";
     print_r($sectors);
     print_r($provinces);
     print "</pre>";
     */

     $query = \Drupal::entityQuery('node')
       ->condition('title', $data['title']['value'])
       ->condition('langcode', $data['langcode'])
       ->condition('type', $data['type']);

     // Get sectors and provinces into arrays
     $sectors = $provinces = [];
     if (isset($data['field_sector'])) {
       foreach ($data['field_sector'] as $sector) {
         $sectors[] = $sector['target_id'];
       }
       $query->condition($query->andConditionGroup()->condition('field_sector', $sectors));
     }
     if (isset($data['field_province'])) {
       foreach ($data['field_province'] as $province) {
         $provinces[] = $province['target_id'];
       }
       $query->condition($query->andConditionGroup()->condition('field_province', $provinces));
     }

     // Add type-specific conditions
     if ($data['type'] == 'complaint_link') {
       $query->condition('field_link_complaint', $data['field_link_complaint']);
     } else if ($data['type'] == 'info_link') {
       $query->condition('field_link_info', $data['field_link_info']);
     }
     $nids = $query->execute();

     return array_values($nids);
   }


   /**
    * Get errors for record
    * 
    * @param array $data Node data
    * @return array Errors
    */
   public static function get_record_errors($data)
   {
     $errors = [];
     if ($data['type'] == 'complaint_link') {
       if (!isset($data['field_sector']) || empty($data['field_sector'])) {
         $errors[] = t('Missing Sector');
       }
       if ($data['field_link_complaint'] == '') {
         $errors[] = t('Missing Complaint link');
       }
     } else if ($data['type'] == 'info_link') {
       if (!isset($data['field_sector']) || empty($data['field_sector'])) {
         $errors[] = t('Missing Sector');
       }
       if ($data['field_link_info'] == '') {
         $errors[] = t('Missing Info link');
       }
     }

     return $errors;
   }

}
