<?php

namespace Drupal\yoast_seo;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\metatag\MetatagManagerInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Provides a preview renderer for entities.
 *
 * @package Drupal\yoast_seo
 */
class EntityAnalyser {

  /**
   * De Drupal entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Drupal content renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The metatag manager.
   *
   * @var \Drupal\metatag\MetatagManagerInterface
   */
  protected $metatagManager;

  /**
   * The Drupal router.
   *
   * @var \Symfony\Component\Routing\RouterInterface
   */
  protected $router;

  /**
   * Constructs a new EntityPreviewer.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   A Drupal Entity renderer.
   * @param \Drupal\metatag\MetatagManagerInterface $metatag_manager
   *   The service for retrieving metatag data.
   * @param \Symfony\Component\Routing\RouterInterface $router
   *   A non-access checking router.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    RendererInterface $renderer,
    MetatagManagerInterface $metatag_manager,
    RouterInterface $router
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->metatagManager = $metatag_manager;
    $this->router = $router;
  }

  /**
   * Takes an entity, renders it and adds the metatag values.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to retrieve preview data for.
   *
   * @return array
   *   An array containing the metatag values. Additionally the url is added if
   *   available under the `url` key and `text` contains a representation of the
   *   rendered HTML.
   */
  public function createEntityPreview(EntityInterface $entity) {
    $entity->in_preview = TRUE;

    $html = $this->renderEntity($entity);

    $metatags = $this->metatagManager->tagsFromEntityWithDefaults($entity);

    // Trigger hook_metatags_alter().
    // Allow modules to override tags or the entity used for token replacements.
    // Also used to override editable titles and descriptions.
    $context = [
      'entity' => $entity,
    ];
    \Drupal::service('module_handler')->alter('metatags', $metatags, $context);

    $this->replaceContextAwareTokens($metatags, $entity);

    // Resolve the metatags from tokens into actual values.
    $data = $this->metatagManager->generateRawElements($metatags, $entity);

    // Turn our tag render array into a key => value array.
    foreach ($data as $name => $tag) {
      if (isset($tag['#attributes']['content'])) {
        $data[$name] = $tag['#attributes']['content'];
      }
      elseif (isset($tag['#attributes']['href'])) {
        $data[$name] = $tag['#attributes']['href'];
      }
    }
    // Translate some fields that have different names between metatag module
    // and the Yoast library.
    foreach ($this->getFieldMappings() as $source => $target) {
      if (isset($data[$source])) {
        $data[$target] = $data[$source];
        unset($data[$source]);
      }
    }

    // Add fields that our widget displays.
    $data['title'] = $entity->label();
    // An entity must be saved before it has a URL.
    $data['url'] = !$entity->isNew() ? $entity->toUrl()->toString() : '';

    // Add our HTML as analyzable text (Yoast will sanitize).
    $data['text'] = $html->__toString();

    return $data;
  }

  /**
   * Takes an entity and renders it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to render.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The markup that represents the rendered entity.
   */
  public function renderEntity(EntityInterface $entity) {
    $type = $entity->getEntityTypeId();
    $view_builder = $this->entityTypeManager->getViewBuilder($type);
    // @todo Make the view mode configurable in Yoast SEO settings.
    $render_array = $view_builder->view($entity, 'full');
    return $this->renderer->renderRoot($render_array);
  }

  /**
   * Replace context aware tokens in a metatags array.
   *
   * Replaces context aware tokens in a metatags with an entity specific
   * version. This causes things like [current-page:title] to show the entity
   * page title instead of the entity create/edit form title.
   *
   * @param array $metatags
   *   The metatags array that contains the tokens.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to use as context.
   */
  protected function replaceContextAwareTokens(array &$metatags, EntityInterface $entity) {
    foreach ($metatags as $tag => $value) {
      $metatags[$tag] = str_replace('[current-page:title]', $entity->getTitle(), $value);
      // URL metatags cause issues for new nodes as they don't have a URL yet.
      if ($entity->isNew() && (substr($tag, -4) === '_url')) {
        $metatags[$tag] = '';
      }
    }
  }

  /**
   * Returns an array of mappings from metatag to Yoast.
   *
   * @return array
   *   The array containing keys that correspond to metatag names and values
   *   that map to the yoast expected names.
   */
  protected function getFieldMappings() {
    return [
      'title' => 'metaTitle',
      'description' => 'meta',
    ];
  }

}
