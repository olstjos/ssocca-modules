<?php

namespace Drupal\ised_custom\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides a block with a simple text.
 *
 * @Block(
 *   id = "sector_search_block",
 *   admin_label = @Translation("Sector Search Block"),
 * )
 */
class SectorSearchBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs an SearchBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ModuleHandlerInterface $module_handler,
    FormBuilderInterface $form_builder
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function build() {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $form = \Drupal::formBuilder()->getForm('Drupal\wxt_library\Form\SearchBlockForm');

    global $base_url;
    $theme = \Drupal::theme()->getActiveTheme();
    $themePath = $base_url.'/'. $theme->getPath();

    $sectorHtml = '<h3 class="wb-inv">
    Explore categories
  </h3>
  <ul class="list-inline menu small" role="menubar">
    <li class="text-center">
      <a href="010-ssocca-02-01-en.html"
        class="item"
        tabindex="0"
        aria-posinset="1"
        aria-setsize="3"
        role="menuitem">
        <img src="'.$themePath.'/images/001-trans-01b1.png"
          class="img-thumbnail mrgn-bttm-sm"
          alt="" /><br />
        Transportation
      </a>
    </li>
    <li class="text-center">
      <a href="/'.$language.'/sector/term/1"
        class="item"
        tabindex="-1"
        aria-posinset="2"
        aria-setsize="3"
        role="menuitem">
        <img src="'.$themePath.'/images/001-financial-01t1.png"
          class="img-thumbnail mrgn-bttm-sm"
          alt="" /><br />
        Financial services
      </a>
    </li>
    <li class="text-center">
      <a href="010-ssocca-04-01-en.html"
        class="item"
        tabindex="-1"
        aria-posinset="3"
        aria-setsize="3"
        role="menuitem">
        <img src="'.$themePath.'/images/001-telecommunications-01t1.png"
          class="img-thumbnail mrgn-bttm-sm"
          alt="" /><br />
        Telecommunications
      </a>
    </li>
  </ul>';

    $build = [];
    $build['block-container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['container-fluid wb-menu wb-init wb-data-ajax-replace-inited wb-menu-inited wb-navcurr-inited'], 'id' => ['wb-sm']],
    ];
    $build['block-container']['inner'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['container']],
    ];
    $sectorContainer = [
      '#type' => 'container',
      '#attributes' => ['class' => ['col-md-8 sector-holder']],
    ];
    $sectorContainer['header'] = [
      '#type' => 'markup',
      '#markup' => $sectorHtml,
    ];
    $formContainer = [
      '#type' => 'container',
      '#attributes' => ['class' => ['col-md-4 form-holder']],
    ];
    $formContainer['form'] = $form;
    $build['block-container']['inner']['sector-container'] = $sectorContainer;
    $build['block-container']['inner']['form-container'] = $formContainer;
    /*

    $build['heading'] = [
      '#type' => 'markup',
      '#markup' => '<div class="container col-md-6">Search here</div>',
    ];
    $build['form-container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['container','col-md-6']],
    ];
    $build['form-container']['form'] = $form;
    */
    
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['sector_search_block_settings'] = $form_state->getValue('sector_search_block_settings');
  }
}