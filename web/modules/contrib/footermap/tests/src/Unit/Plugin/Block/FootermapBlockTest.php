<?php

namespace Drupal\Tests\footermap\Unit\Plugin\Block;

use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\Form\FormState;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\footermap\Menu\AnonymousMenuLinkTreeManipulator;
use Drupal\footermap\Plugin\Block\FootermapBlock;
use Drupal\system\Entity\Menu;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Prophecy\Argument;

/**
 * Test footermap block methods.
 *
 * @group footermap
 */
class FootermapBlockTest extends UnitTestCase {

  /**
   * Service Container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create some menu entities.
    $menu1 = new Menu(
      ['id' => 'menu1', 'description' => 'menu1', 'label' => 'menu1'],
      'menu'
    );
    $menu2 = new Menu(
      ['id' => 'menu2', 'description' => 'menu2', 'label' => 'menu2'],
      'menu'
    );

    $link1 = ['title' => 'Link1'];
    $link1_definition = [
      'id' => 'menu_link1',
      'title' => 'Link1',
      'description' => 'A link',
      'enabled' => TRUE,
      'options' => [],
      'weight' => 0,
    ];

    $link1_child = ['title' => 'Child Link 1'];
    $link1_child_definition = [
      'id' => 'menu_link1_child',
      'title' => 'Child Link 1',
      'description' => 'A child link',
      'enabled' => TRUE,
      'options' => [],
      'weight' => 5,
    ];

    $link1_child2 = ['title' => 'Child Link 2'];
    $link1_child2_definition = [
      'id' => 'menu_link2',
      'title' => 'Child Link 2',
      'description' => 'A second link',
      'enabled' => TRUE,
      'options' => [],
      'weight' => -5,
    ];

    $configEntityType = new ConfigEntityType([
      'id' => 'menu',
      'label' => 'Menu',
      'handlers' => ['access' => 'Drupal\system\MenuAccessControlHandler'],
      'entity_keys' => ['id' => 'id', 'label' => 'label'],
      'admin_permission' => 'administer menu',
    ]);

    // Mock Static Menu link overrides.
    $staticMenuLinkProphet = $this->prophesize('\Drupal\Core\Menu\StaticMenuLinkOverridesInterface');
    $staticMenuLinkOverrides = $staticMenuLinkProphet->reveal();

    // Create menu link plugin instances.
    $menu1_link1 = new MenuLinkDefault($link1, 'menu_link1', $link1_definition, $staticMenuLinkOverrides);
    $menu1_link1_child = new MenuLinkDefault($link1_child, 'menu_link1_child', $link1_child_definition, $staticMenuLinkOverrides);
    $menu1_link2_child = new MenuLinkDefault($link1_child2, 'menu_link1_child2', $link1_child2_definition, $staticMenuLinkOverrides);
    // Create menu link tree for menu1 with menu routes.
    $menu1_subtree = new MenuLinkTreeElement($menu1_link1_child, FALSE, 2, FALSE, []);
    $menu1_subtree2 = new MenuLinkTreeElement($menu1_link2_child, FALSE, 2, FALSE, []);
    $menu1_tree = new MenuLinkTreeElement($menu1_link1, TRUE, 1, FALSE, [$menu1_subtree, $menu1_subtree2]);

    // Mock config entity storage.
    $configStorageProphet = $this->prophesize('\Drupal\Core\Config\Entity\ConfigEntityStorageInterface');
    $configStorageProphet
      ->loadMultiple(Argument::any())
      ->willReturn(['menu1' => $menu1, 'menu2' => $menu2]);

    // Mock the entity_type manager.
    $entityTypeProphet = $this->prophesize('\Drupal\Core\Entity\EntityTypeManagerInterface');
    $entityTypeProphet
      ->getStorage('menu')
      ->willReturn($configStorageProphet->reveal());
    $entityTypeProphet
      ->getDefinition('menu')
      ->willReturn($configEntityType);

    // Mock the entity repository.
    $entityRepositoryProphet = $this->prophesize('\Drupal\Core\Entity\EntityRepositoryInterface');
    $entityRepositoryProphet
      ->loadEntityByUuid('menu_link_content', Argument::any());

    // Mock the menu link tree manager.
    $menuLinkTreeProphet = $this->prophesize('\Drupal\Core\Menu\MenuLinkTreeInterface');
    $menuLinkTreeProphet
      ->load('menu1', $this->getMenuParameters())
      ->willReturn([$menu1_tree]);
    $menuLinkTreeProphet
      ->transform(Argument::any(), Argument::any())
      ->willReturn([$menu1_tree]);

    // Mock the plugin manager for menu link.
    $menuLinkPluginProphet = $this->prophesize('\Drupal\Core\Menu\MenuLinkManagerInterface');

    // Mock the Logger Channel factory.
    $loggerChannelProphet = $this->prophesize('\Drupal\Core\Logger\LoggerChannelFactoryInterface');
    $loggerChannelProphet->get('footermap')->willReturn(new LoggerChannel('footermap'));

    // Mock the Access Manager.
    $accessManager = $this->prophesize('\Drupal\Core\Access\AccessManagerInterface')->reveal();

    // Mock an anonymous user session.
    $account = $this->prophesize('\Drupal\Core\Session\AnonymousUserSession')->reveal();

    // Mock the entity_type.manager service.
    $entityTypeManager = $entityTypeProphet->reveal();

    $anonymousTreeManipulator = new AnonymousMenuLinkTreeManipulator($accessManager, $account, $entityTypeManager);

    $this->container = new ContainerBuilder();

    $this->container->set('entity_type.manager', $entityTypeManager);
    $this->container->set('entity.repository', $entityRepositoryProphet->reveal());
    $this->container->set('menu.link_tree', $menuLinkTreeProphet->reveal());
    $this->container->set('plugin.manager.menu.link', $menuLinkPluginProphet->reveal());
    $this->container->set('string_translation', $this->getStringTranslationStub());
    $this->container->set('footermap.anonymous_tree_manipulator', $anonymousTreeManipulator);
    $this->container->set('footermap.anonymous_user', $account);
    $this->container->set('logger.factory', $loggerChannelProphet->reveal());

    \Drupal::setContainer($this->container);
  }

  /**
   * Asserts that the footermap block instance is created.
   */
  public function testStaticCreate() {
    $block = $this->getPlugin();
    $this->assertInstanceOf('\Drupal\footermap\Plugin\Block\FootermapBlock', $block);
  }

  /**
   * Asserts that the initialize method works.
   */
  public function testInitialize() {
    $configuration = [
      'label' => 'Footermap',
      'display_label' => TRUE,
    ];
    $plugin_id = 'footermap';
    $plugin_definition = [
      'plugin_id' => $plugin_id,
      'provider' => 'footermap',
    ];

    $block = new FootermapBlock(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $this->container->get('entity_type.manager'),
      $this->container->get('entity.repository'),
      $this->container->get('menu.link_tree'),
      $this->container->get('plugin.manager.menu.link'),
      $this->container->get('logger.factory')
    );
    $this->assertInstanceOf('\Drupal\footermap\Plugin\Block\FootermapBlock', $block);
  }

  /**
   * Asserts that default configuration matches expected values.
   */
  public function testDefaultConfiguration() {
    $expected = [
      'footermap_recurse_limit' => 0,
      'footermap_display_heading' => 1,
      'footermap_avail_menus' => [],
      'footermap_top_menu' => '',
    ];
    $block = $this->getPlugin();
    $this->assertEquals($expected, $block->defaultConfiguration());
  }

  /**
   * Asserts that site map is built.
   */
  public function testBuild() {
    $block = $this->getPlugin();
    $block->setConfigurationValue('footermap_avail_menus', ['menu1' => 'menu1']);

    // Assert that site map is built with children.
    $map = $block->build();
    $this->assertArrayHasKey('#footermap', $map);
    $this->assertArrayHasKey('#attached', $map);
    $this->assertEquals('Footermap', $map['#title']);
    $this->assertCount(1, $map['#footermap']['menu1']['#items']);
    $this->assertEquals('Link1', $map['#footermap']['menu1']['#items']['menu-0']['#title']);
    $children = &$map['#footermap']['menu1']['#items']['menu-0']['#children'];
    $this->assertEquals('Child Link 1', $children['menu-0']['#title']);

    // Assert that the site map has the menu links with #weight property equal
    // to the menu link weight.
    $this->assertEquals(-5, $children['menu-1']['#weight']);
    $this->assertEquals(5, $children['menu-0']['#weight']);
  }

  /**
   * Asserts the block configuration form.
   */
  public function testBlockForm() {
    $form_state = new FormState();
    $block = $this->getPlugin();
    $block->setConfigurationValue('footermap_avail_menus', ['menu1' => 'menu1']);

    $form = $block->blockForm([], $form_state);

    $this->assertEquals($form['footermap_avail_menus']['#options'], ['menu1' => 'menu1', 'menu2' => 'menu2']);
    $this->assertArrayHasKey('footermap_recurse_limit', $form);
    $this->assertArrayHasKey('footermap_top_menu', $form);
    $this->assertArrayHasKey('footermap_display_heading', $form);
  }

  /**
   * Asserts the access callback for block access based on anonymous user.
   */
  public function testAccess() {
    $block = $this->getPlugin();
    $account = $this->container->get('footermap.anonymous_user');

    $this->assertInstanceOf('\Drupal\Core\Access\AccessResultAllowed', $block->access($account, TRUE));
    $this->assertTrue($block->access($account));
  }

  /**
   * Asserts the cache contexts are returned.
   */
  public function testGetCacheContexts() {
    $block = $this->getPlugin();
    $this->assertEquals(['languages'], $block->getCacheContexts());
  }

  /**
   * Create an instance of the footermap block.plugin.
   *
   * @returns \Drupal\footermap\Plugin\Block\FootermapBlock
   *   A block plugin instance.
   */
  protected function getPlugin() {
    $configuration = [
      'label' => 'Footermap',
      'display_label' => TRUE,
    ];
    $plugin_id = 'footermap';
    $plugin_definition = [
      'plugin_id' => $plugin_id,
      'provider' => 'footermap',
    ];

    return FootermapBlock::create($this->container, $configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Get the menu parameters to pass into menu tree parameters.
   *
   * @param int|bool $limit
   *   (Optional) The recurse limit.
   * @param string|bool $menu
   *   (Optional) The menu plugin id.
   *
   * @return \Drupal\Core\Menu\MenuTreeParameters
   *   Menu tree parameter class.
   */
  protected function getMenuParameters($limit = FALSE, $menu = FALSE) {
    $parameters = new MenuTreeParameters();
    $parameters->onlyEnabledLinks();
    $parameters->excludeRoot();

    if ($limit) {
      $parameters->setMaxDepth($limit);
    }

    if ($menu) {
      $parameters->setRoot($menu);
    }

    return $parameters;
  }

}
