<?php

namespace Drupal\Tests\default_content\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;

/**
 * Tests import functionality.
 *
 * @coversDefaultClass \Drupal\default_content\Importer
 * @group default_content
 */
class ImporterIntegrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system'];

  /**
   * The tested default content importet.
   *
   * @var \Drupal\default_content\Importer
   */
  protected $importer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['router', 'sequences']);
  }

  protected function setupImport() {
    \Drupal::service('module_installer')->install(['node', 'taxonomy', 'field']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');

    NodeType::create([
      'type' => 'page',
    ])->save();

    FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'body',
      'bundle' => 'page',
    ])->save();

    \Drupal::service('module_installer')->install(['rest', 'serialization']);
    \Drupal::service('module_installer')->install([
      'default_content',
    ]);

    // Install the module but remove its content.
    \Drupal::service('module_installer')->install([
      'default_content_test',
    ]);

    // Cleanup previously installed content.
    \Drupal::service('entity.repository')->loadEntityByUuid('node', '65c412a3-b83f-4efb-8a05-5a6ecea10ad4')->delete();
    \Drupal::service('entity.repository')->loadEntityByUuid('taxonomy_term', '550f86ad-aa11-4047-953f-636d42889f85')->delete();
  }

  /**
   * Tests the import mechanism.
   */
  public function testSingleImport() {
    $this->setupImport();

    $this->importer = \Drupal::service('default_content.importer');

    $entities = $this->importer->importContent('default_content_test');
    $this->assertInstanceOf(TermInterface::class, $entities['550f86ad-aa11-4047-953f-636d42889f85']);
    $this->assertInstanceOf(NodeInterface::class, $entities['65c412a3-b83f-4efb-8a05-5a6ecea10ad4']);

    $this->assertCount(2, $entities);
    // Ensure the content can be actually loaded.
    $this->assertEquals('A tag', Term::load($entities['550f86ad-aa11-4047-953f-636d42889f85']->id())->label());
    $this->assertEquals('Imported node', Node::load($entities['65c412a3-b83f-4efb-8a05-5a6ecea10ad4']->id())->label());
  }

  /**
   * Tests the import mechanism of all modules.
   */
  public function testImportAllContent() {
    $this->setupImport();

    $this->importer = \Drupal::service('default_content.importer');

    $entities = $this->importer->importAllContent();
    $this->assertInstanceOf(TermInterface::class, $entities['550f86ad-aa11-4047-953f-636d42889f85']);
    $this->assertInstanceOf(NodeInterface::class, $entities['65c412a3-b83f-4efb-8a05-5a6ecea10ad4']);

    $this->assertCount(2, $entities);
    // Ensure the content can be actually loaded.
    $this->assertEquals('A tag', Term::load($entities['550f86ad-aa11-4047-953f-636d42889f85']->id())->label());
    $this->assertEquals('Imported node', Node::load($entities['65c412a3-b83f-4efb-8a05-5a6ecea10ad4']->id())->label());
  } 

}
