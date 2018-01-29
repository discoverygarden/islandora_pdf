<?php

namespace Drupal\islandora_pdf\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Upload form when ingesting PDF objects.
 */
class PdfUploadForm extends FormBase {

  protected $fileEntityStorage;

  /**
   * Constructor.
   */
  public function __construct(EntityStorageInterface $file_entity_storage) {
    $this->fileEntityStorage = $file_entity_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('file')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_pdf_pdf_upload_form';
  }

  /**
   * Defines a file upload form for uploading the PDF file.
   *
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $upload_size = min((int) ini_get('post_max_size'), (int) ini_get('upload_max_filesize'));
    $extensions = ['pdf'];
    $upload_required = $this->config('islandora.settings')->get('islandora_require_obj_upload');
    $form = [];
    $form['file'] = [
      '#title' => $this->t('PDF File'),
      '#type' => 'managed_file',
      '#required' => $upload_required,
      '#description' => $this->t('Select file to upload.<br/>Files must be less than <strong>@size MB.</strong><br/>Allowed file types: <strong>@ext.</strong>', ['@size' => $upload_size, '@ext' => $extensions[0]]),
      '#default_value' => $form_state->getValue('file') ? $form_state->getValue('file') : NULL,
      '#upload_location' => 'temporary://',
      '#upload_validators' => [
        'file_validate_extensions' => $extensions,
        // Assume it's specified in MB.
        'file_validate_size' => [$upload_size * 1024 * 1024],
      ],
    ];

    if ($this->config('islandora_pdf.settings')->get('islandora_pdf_allow_text_upload')) {
      $form['islandora_pdf_text_upload'] = [
        '#type' => 'checkbox',
        '#title' => $this->t("Add text file to this upload?"),
        '#default_value' => FALSE,
      ];
      $form['text'] = [
        '#title' => $this->t('PDF text'),
        '#type' => 'managed_file',
        '#required' => FALSE,
        '#description' => $this->t('Select text file to upload.<br/>Files must be less than <strong>@size MB.</strong><br/>Allowed file types: <strong>@ext.</strong><br />This file is optional.', ['@size' => $upload_size, '@ext' => 'txt']),
        '#default_value' => $form_state->getValue('text') ? $form_state->getValue('text') : NULL,
        '#upload_location' => 'temporary://',
        '#upload_validators' => [
          'file_validate_extensions' => ['txt'],
          // Assume it's specified in MB.
          'file_validate_size' => [$upload_size * 1024 * 1024],
        ],
        '#states' => [
          'visible' => [
            ':input[name="islandora_pdf_text_upload"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }
    return $form;
  }

  /**
   * Adds the uploaded file into the ingestable objects 'OBJ' datastream.
   *
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $object = islandora_ingest_form_get_object($form_state);
    if ($form_state->getValue('file')) {
      if (empty($object['OBJ'])) {
        $ds = $object->constructDatastream('OBJ', 'M');
        $object->ingestDatastream($ds);
      }
      else {
        $ds = $object['OBJ'];
      }
      $pdf_file = $this->fileEntityStorage->load(reset($form_state->getValue('file')));
      $ds->setContentFromFile($pdf_file->getFileUri(), FALSE);
      $ds->label = $pdf_file->getFilename();
      $ds->mimetype = $pdf_file->getMimeType();
    }

    if ($form_state->getValue('text') && $form_state->getValue('text') > 0) {
      if (empty($object['FULL_TEXT'])) {
        $ds = $object->constructDatastream('FULL_TEXT', 'M');
        $object->ingestDatastream($ds);
      }
      else {
        $ds = $object['FULL_TEXT'];
      }
      $text_file = $this->fileEntityStorage->load(reset($form_state->getValue('file')));
      $ds->setContentFromFile($text_file->getFileUri(), FALSE);
      $ds->label = $text_file->getFilename();
      $ds->mimetype = $text_file->getMimeType();
    }
  }

}
