<?php
/**
 * @package TemplateBuilder
 * @subpackage Test
 */

namespace Gustavus\TemplateBuilder;

use Gustavus\Test\Test,
  Gustavus\Test\TestObject,
  Gustavus\TemplateBuilder\Builder;

/**
 * @package TemplateBuilder
 * @subpackage Test
 */
class BuilderTest extends Test
{
  /**
   * Builder test object
   * @var Builder
   */
  private $builder;

  /**
   * Array of builder construction properties
   * @var array
   */
  private $builderProperties = array(
    'title'           => 'arst',
    'subTitle'        => 'subtitle',
    'focusBox'        => '<p>FocusBox</p>',
    'stylesheets'     => '<style>some css here</style>',
    'javascripts'     => '<script>Some js here</script>',
    'localNavigation' => [['text' => 'testUrl', 'url' => 'someUrl']],
    'content'         => '<p>some random content</p>',
  );

  /**
   * Sets up the object for every test
   */
  public function setUp()
  {
    $this->builder = new TestObject(new Builder($this->builderProperties));
  }

  /**
   * destroys the object after every test
   */
  public function tearDown()
  {
    unset($this->builder);
  }

  /**
   * @test
   */
  public function getTitle()
  {
    $this->assertSame($this->builderProperties['title'], $this->builder->getTitle());
  }

  /**
   * @test
   */
  public function getSubTitle()
  {
    $this->assertSame($this->builderProperties['subTitle'], $this->builder->getSubTitle());
  }

  /**
   * @test
   */
  public function getFocusBox()
  {
    $this->assertSame($this->builderProperties['focusBox'], $this->builder->getFocusBox());
  }

  /**
   * @test
   */
  public function getStylesheets()
  {
    $this->assertSame($this->builderProperties['stylesheets'], $this->builder->getStylesheets());
  }

  /**
   * @test
   */
  public function getJavascripts()
  {
    $this->assertSame($this->builderProperties['javascripts'], $this->builder->getJavascripts());
  }

  /**
   * @test
   */
  public function getContent()
  {
    $this->assertSame($this->builderProperties['content'], $this->builder->getContent());
  }

  /**
   * @test
   */
  public function renderLocalNavigationString()
  {
    $this->builder->setLocalNavigation('string');
    $this->assertSame('string', $this->builder->renderLocalNavigation());
  }

  /**
   * @test
   */
  public function renderLocalNavigationEmpty()
  {
    $this->builder->setLocalNavigation([]);
    $this->assertNotEmpty($this->builder->renderLocalNavigation());
  }

  /**
   * @test
   */
  public function renderLocalNavigation()
  {
    $expected = \Gustavus\LocalNavigation\ItemFactory::getItems($this->builderProperties['localNavigation'])->render();
    $this->assertSame($expected, $this->builder->renderLocalNavigation());
  }

  /**
   * @test
   */
  public function findFile()
  {
    $this->assertSame('/cis/www/site_nav.php', $this->builder->findFile('site_nav.php'));
  }

  /**
   * @test
   */
  public function findFileFound()
  {
    $this->assertSame('site_nav_test.php', $this->builder->findFile('site_nav_test.php'));
  }

  /**
   * @test
   */
  public function findLocalNavigationFile()
  {
    $this->assertSame('/cis/www/site_nav.php', $this->builder->findLocalNavigationFile('site_nav.php'));
  }

  /**
   * @test
   */
  public function autoLoadLocalNavigation()
  {
    $this->assertNotEmpty($this->builder->autoLoadLocalNavigation());
  }

  /**
   * @test
   */
  public function setAndGetBreadCrumbAdditions()
  {
    $crumbs = [['url' => 'some url', 'text' => 'some text']];
    $this->builder->setBreadCrumbAdditions($crumbs);

    $this->assertSame($crumbs, $this->builder->getBreadCrumbAdditions());
  }

  /**
   * @test
   */
  public function buildBreadCrumbAdditions()
  {
    $crumbs = [['url' => 'some url', 'text' => 'some text']];
    $this->builder->setBreadCrumbAdditions($crumbs);

    $this->assertSame('<a href="some url">some text</a> / ', $this->builder->buildBreadCrumbAdditions());
  }

  /**
   * @test
   */
  public function templatePreferences()
  {
    $this->assertSame(true, $this->builder->templatePreferences['localNavigation']);
    $this->builderProperties['templatePreferences'] = ['localNavigation' => false];
    $this->setUp();

    $expected = ['localNavigation' => false, 'auxBox' => false];
    $this->assertSame($expected, $this->builder->templatePreferences);
  }
}