<?php

namespace Drupal\islandora_pdf\Form;

use Drupal\islandora\Form\ModuleHandlerAdminForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Module administration form.
 */
class Admin extends ModuleHandlerAdminForm {

  /**
   * Renderer instance.
   *
   * @var Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_pdf_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('islandora_pdf.settings');

    $config->set('islandora_pdf_thumbnail_width', $form_state->getValue('islandora_pdf_thumbnail_width'));
    $config->set('islandora_pdf_thumbnail_height', $form_state->getValue('islandora_pdf_thumbnail_height'));
    $config->set('islandora_pdf_path_to_pdftotext', $form_state->getValue('islandora_pdf_path_to_pdftotext'));
    $config->set('islandora_pdf_path_to_gs', $form_state->getValue('islandora_pdf_path_to_gs'));
    $config->set('islandora_pdf_preview_width', $form_state->getValue('islandora_pdf_preview_width'));
    $config->set('islandora_pdf_preview_height', $form_state->getValue('islandora_pdf_preview_height'));
    $config->set('islandora_pdf_create_fulltext', $form_state->getValue('islandora_pdf_create_fulltext'));
    $config->set('islandora_pdf_create_pdfa', $form_state->getValue('islandora_pdf_create_pdfa'));
    $config->set('islandora_pdf_use_duseciecolor', $form_state->getValue('islandora_pdf_use_duseciecolor'));
    $config->set('islandora_pdf_allow_text_upload', $form_state->getValue('islandora_pdf_allow_text_upload'));
    $config->save();

    islandora_set_viewer_info('islandora_pdf_viewers', $form_state->getValue('islandora_pdf_viewers'));

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['islandora_pdf.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form_state->loadInclude('islandora_pdf', 'inc', 'includes/admin.form');
    $form_state->loadInclude('islandora', 'inc', 'includes/solution_packs');
    if ($form_state->getValue('islandora_pdf_path_to_pdftotext')) {
      $islandora_pdf_path_to_pdftotext = $form_state->getValue('islandora_pdf_path_to_pdftotext');
    }
    else {
      $islandora_pdf_path_to_pdftotext = $this->config('islandora_pdf.settings')->get('islandora_pdf_path_to_pdftotext');
    }
    exec($islandora_pdf_path_to_pdftotext, $output, $return_value);
    $pdftotext_confirmation_image = [
      '#theme' => 'image',
      '#uri' => Url::fromUri('base:core/misc/icons/73b355/check.svg')->toString(),
    ];
    $pdftotext_confirmation_message = $this->renderer->render($pdftotext_confirmation_image)
      . $this->t('pdftotext executable found at <strong>@url</strong>', ['@url' => $islandora_pdf_path_to_pdftotext]);

    if ($return_value != 99) {
      $pdftotext_confirmation_image = [
        '#theme' => 'image',
        '#uri' => Url::fromUri('base:core/misc/icons/e32700/error.svg')->toString(),
      ];
      $pdftotext_confirmation_message = $this->renderer->render($pdftotext_confirmation_image)
        . $this->t('Unable to find pdftotext executable at <strong>@url</strong>', ['@url' => $islandora_pdf_path_to_pdftotext]);
    }

    if ($form_state->getValue('islandora_pdf_path_to_gs')) {
      $islandora_pdf_path_to_gs = $form_state->getValue('islandora_pdf_path_to_gs');
    }
    else {
      $islandora_pdf_path_to_gs = $this->config('islandora_pdf.settings')->get('islandora_pdf_path_to_gs');
    }
    $gs_test_command = $islandora_pdf_path_to_gs . ' --help';
    exec($gs_test_command, $output, $return_value);
    $gs_confirmation_image = [
      '#theme' => 'image',
      '#uri' => Url::fromUri('base:core/misc/icons/73b355/check.svg')->toString(),
    ];
    $gs_confirmation_message = $this->renderer->render($gs_confirmation_image)
      . $this->t('ghostscript executable found at <strong>@url</strong>', ['@url' => $islandora_pdf_path_to_gs]);

    if ($return_value != 0) {
      $gs_confirmation_image = [
        '#theme' => 'image',
        '#uri' => Url::fromUri('base:core/misc/icons/e32700/error.svg')->toString(),
      ];
      $gs_confirmation_message = $this->renderer->render($gs_confirmation_image)
        . $this->t('Unable to find ghotscript executable at <strong>@url</strong>', ['@url' => $islandora_pdf_path_to_gs]);
    }

    $form = [];

    // AJAX wrapper for url checking.
    $form['islandora_pdf_url_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('TEXT'),
    ];

    $form['islandora_pdf_url_fieldset']['islandora_pdf_allow_text_upload'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Allow users to upload .txt files with PDFs"),
      '#description' => $this->t("Uploaded text files are appended to PDFs as FULL_TEXT datastreams and are indexed into Solr."),
      '#default_value' => $this->config('islandora_pdf.settings')->get('islandora_pdf_allow_text_upload'),
    ];

    $form['islandora_pdf_url_fieldset']['islandora_pdf_create_fulltext'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Extract text streams from PDFs using pdftotext"),
      '#description' => $this->t("Extracted text streams are appended to PDFs as FULL_TEXT datastreams and are indexed into Solr. Uploading a text file takes priority over text stream extraction.
                             </br><strong>Note:</strong> PDFs that contain visible text do not necessarily contain text streams (e.g. images scanned and saved as PDFs). Consider converting text-filled images with no text streams to TIFFs and using the @book with @ocr enabled.", [
                               '@book' => Link::fromTextAndUrl($this->t('Book Solution Pack'), Url::fromUri('https://wiki.duraspace.org/display/ISLANDORA711/Book+Solution+Pack'))->toString(),
                               '@ocr' => Link::fromTextAndUrl($this->t('OCR'), Url::fromUri('https://wiki.duraspace.org/display/ISLANDORA711/Islandora+OCR'))->toString(),
                             ]),
      '#default_value' => $this->config('islandora_pdf.settings')->get('islandora_pdf_create_fulltext'),
    ];

    $form['islandora_pdf_url_fieldset']['islandora_pdf_create_pdfa'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Create PDF/A archival derivative from PDF"),
      '#description' => $this->t("Create a PDF/A version of any uploaded PDF. PDF/A is a restrictive standard that prohibits more easily broken components of the PDF spec, such as fillable forms and DRM. The PDF/A derivative will not be used for display. Requires ghostscript to be installed on the server."),
      '#default_value' => $this->config('islandora_pdf.settings')->get('islandora_pdf_create_pdfa'),
    ];

    $executable = escapeshellarg($this->config('islandora_pdf.settings')->get('islandora_pdf_path_to_gs'));
    $version_command = "$executable --version";
    exec($version_command, $version);
    $form['islandora_pdf_url_fieldset']['islandora_pdf_use_duseciecolor'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Use dUseCIEColor when generating PDFA datastream."),
      '#description' => $this->t('As of GhostScript @threshold, the <a href=":post_url">use of the @switch switch is not recommended</a>. </br>Version installed: @version.', [
        ':post_url' => 'https://ghostscript.com/pipermail/gs-devel/2014-July/009693.html',
        '@threshold' => '9.11',
        '@switch' => 'dUseCIEColor',
        '@version' => $version[0],
      ]),
      '#default_value' => $this->config('islandora_pdf.settings')->get('islandora_pdf_use_duseciecolor'),
    ];

    $form['islandora_pdf_url_fieldset']['wrapper'] = [
      '#prefix' => '<div id="islandora-url">',
      '#suffix' => '</div>',
      '#type' => 'markup',
    ];

    $form['islandora_pdf_url_fieldset']['wrapper']['islandora_pdf_path_to_pdftotext'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path to pdftotext executable'),
      '#default_value' => $islandora_pdf_path_to_pdftotext,
      '#description' => $pdftotext_confirmation_message,
      '#ajax' => [
        'callback' => 'islandora_update_pdftotext_url_div',
        'wrapper' => 'islandora-url',
        'effect' => 'fade',
        'event' => 'change',
        'progress' => ['type' => 'throbber'],
      ],
      '#states' => [
        'visible' => [
          ':input[name="islandora_pdf_create_fulltext"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['islandora_pdf_url_fieldset']['wrapper']['islandora_pdf_path_to_gs'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path to ghostscript executable'),
      '#default_value' => $islandora_pdf_path_to_gs,
      '#description' => $gs_confirmation_message,
      '#ajax' => [
        'callback' => 'islandora_update_gs_url_div',
        'wrapper' => 'islandora-url',
        'effect' => 'fade',
        'event' => 'change',
        'progress' => ['type' => 'throbber'],
      ],
      '#states' => [
        'visible' => [
          ':input[name="islandora_pdf_create_pdfa"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['islandora_pdf_thumbnail_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Thumbnail'),
      '#description' => $this->t('Settings for creating PDF thumbnail derivatives'),
    ];

    $form['islandora_pdf_thumbnail_fieldset']['islandora_pdf_thumbnail_width'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Width'),
      '#description' => $this->t('The width of the thumbnail in pixels.'),
      '#default_value' => $this->config('islandora_pdf.settings')->get('islandora_pdf_thumbnail_width'),
      '#size' => 5,
    ];

    $form['islandora_pdf_thumbnail_fieldset']['islandora_pdf_thumbnail_height'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Height'),
      '#description' => $this->t('The height of the thumbnail in pixels.'),
      '#default_value' => $this->config('islandora_pdf.settings')->get('islandora_pdf_thumbnail_height'),
      '#size' => 5,
    ];

    $form['islandora_pdf_preview_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Preview image'),
      '#description' => $this->t('Settings for creating PDF preview image derivatives'),
    ];

    $form['islandora_pdf_preview_fieldset']['islandora_pdf_preview_width'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Max width'),
      '#description' => $this->t('The maximum width of the preview in pixels.'),
      '#default_value' => $this->config('islandora_pdf.settings')->get('islandora_pdf_preview_width'),
      '#size' => 5,
    ];

    $form['islandora_pdf_preview_fieldset']['islandora_pdf_preview_height'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Max height'),
      '#description' => $this->t('The maximum height of the preview in pixels.'),
      '#default_value' => $this->config('islandora_pdf.settings')->get('islandora_pdf_preview_height'),
      '#size' => 5,
    ];

    $form += islandora_viewers_form('islandora_pdf_viewers', 'application/pdf');
    $form['submit'] = [
      '#type' => 'submit',
      '#name' => 'islandora-pdf-submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('islandora_pdf_create_fulltext')) {
      $islandora_pdf_path_to_pdftotext = $form_state->getValue('islandora_pdf_path_to_pdftotext');
      exec($islandora_pdf_path_to_pdftotext, $output, $return_value);
      if ($return_value != 99) {
        $form_state->setErrorByName('', $this->t('Cannot extract text from PDF without a valid path to pdftotext.'));
      }
    }
    if ($form_state->getValue('islandora_pdf_create_pdfa')) {
      $islandora_pdf_path_to_gs = $form_state->getValue('islandora_pdf_path_to_gs');
      $gs_test_command = $islandora_pdf_path_to_gs . ' --help';
      exec($gs_test_command, $output, $return_value);
      if ($return_value != 0) {
        $form_state->setErrorByName('', $this->t('Cannot create PDF/A without ghostscript.'));
      }
    }
  }

}
