<?php

/**
 * @file
 * This file contains admin helper functions.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Function updates PDF to text URL div.
 */
function islandora_update_pdftotext_url_div(array $form, FormStateInterface $form_state) {
  return $form['islandora_pdf_url_fieldset']['wrapper'];
}

/**
 * Function updates ghostscript URL div.
 */
function islandora_update_gs_url_div(array $form, FormStateInterface $form_state) {
  return $form['islandora_pdf_url_fieldset']['wrapper'];
}
