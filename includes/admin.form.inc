<?php

/**
 * @file
 * This file contains admin helper functions.
 */

/**
 * Function updates PDF to text URL div.
 */
function islandora_update_pdftotext_url_div($form, $form_state) {
  unset($form_state['submit_handlers']);
  $form_state['rebuild'] = TRUE;

  return $form['islandora_pdf_url_fieldset']['wrapper'];
}

/**
 * Function updates ghostscript URL div.
 */
function islandora_update_gs_url_div($form, $form_state) {
  unset($form_state['submit_handlers']);
  $form_state['rebuild'] = TRUE;

  return $form['islandora_pdf_url_fieldset']['wrapper'];
}
