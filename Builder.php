<?php
/**
 * @package TemplateBuilder
 * @author  Billy Visto
 */
namespace Gustavus\TemplateBuilder;

require_once 'template/request.class.php';
//Gatekeeper needs to be included after the request class
//because the request class sets a constant that gatekeeper needs.
require_once 'gatekeeper/gatekeeper.class.php';

use TemplatePageRequest,
  Gustavus\TwigFactory\TwigFactory,
  Gustavus\LocalNavigation\ItemFactory,
  Gustavus\Utility\File;

/**
 * Class to build the template
 *
 * @package TemplateBuilder
 * @author  Billy Visto
 */
class Builder
{
  /**
   * @var string
   */
  private $title = '';

  /**
   * @var string
   */
  private $subtitle = '';

  /**
   * @var string
   */
  private $focusBox = '';

  /**
   * @var string
   */
  private $stylesheets = '';

  /**
   * @var string
   */
  private $javascripts = '';

  /**
   * @var array
   */
  private $localNavigation = [];

  /**
   * @var string
   */
  private $content = '';

  /**
   * Preferences to set in the template
   * @var array
   */
  private $templatePreferences = ['localNavigation' => true, 'auxBox' => false];

  /**
   * Constructs the object with the args specified
   *
   * @param array $args keyed by page part
   * @param  array $templatePreferences templatePreferences to add to the global template preferences
   * @return  void
   */
  public function __construct($args = array(), $templatePreferences = array())
  {
    $this->templatePreferences = array_merge($this->templatePreferences, $templatePreferences);
    foreach ($args as $key => $value) {
      $function = 'set' . ucfirst($key);
      if (is_callable(array($this, $function))) {
        $this->$function($value);
      }
    }
  }

  /**
   * Gets the title that is set for the page.
   *
   * @return string the page title.
   */
  private function getTitle()
  {
    return $this->title;
  }

  /**
   * Sets the page title.
   *
   * @param string $title the new page title
   * @return $this to enable method chaining
   */
  private function setTitle($title)
  {
    $this->title = $title;
    return $this;
  }

  /**
   * Gets the subtitle of the page.
   *
   * @return string the page subtitle
   */
  private function getSubtitle()
  {
    return $this->subtitle;
  }

  /**
   * Sets the page subtitle.
   *
   * @param string $subtitle the new page subtitle
   * @return $this to enable method chaining
   */
  private function setSubtitle($subtitle)
  {
    $this->subtitle = $subtitle;
    return $this;
  }

  /**
   * Gets the focus box HTML on the page.
   *
   * @return string the HTML in the page focusbox
   */
  private function getFocusBox()
  {
    return $this->focusBox;
  }

  /**
   * Sets the focus box HTML on the page.
   *
   * @param string $focusBox the new page focusbox content.
   * @return $this to enable method chaining
   */
  private function setFocusBox($focusBox)
  {
    $this->focusBox = $focusBox;
    return $this;
  }

  /**
   * Gets the stylesheets HTML on the page.
   *
   * @return string the stylesheets HTML on the page
   */
  private function getStylesheets()
  {
    return $this->stylesheets;
  }

  /**
   * Sets the stylesheets HTML on the page.
   *
   * @param string $stylesheets the new stylesheets HTML on the page
   * @return $this to enable method chaining
   */
  private function setStylesheets($stylesheets)
  {
    $this->stylesheets = $stylesheets;
    return $this;
  }

  /**
   * Gets the javascript content on the page.
   *
   * @return string the javascript content on the page
   */
  private function getJavascripts()
  {
    return $this->javascripts;
  }

  /**
   * Sets the javascript to be added on the page.
   *
   * @param string $javascripts the new javascripts HTML on the page
   * @return $this to enable method chaining
   */
  private function setJavascripts($javascripts)
  {
    $this->javascripts = $javascripts;
    return $this;
  }

  /**
   * Gets the localNavigation for the page.
   *
   * @return array localNavigation
   */
  private function getLocalNavigation()
  {
    return $this->localNavigation;
  }

  /**
   * Sets the localNavigation
   *
   * @param array|string $localNavigation
   * @return $this
   */
  private function setLocalNavigation($localNavigation)
  {
    $this->localNavigation = $localNavigation;
    return $this;
  }

  /**
   * Renders local navigation from an array.
   *
   * @return string
   */
  protected function renderLocalNavigation()
  {
    if (is_array($this->getLocalNavigation()) && !empty($this->localNavigation)) {
      return ItemFactory::getItems($this->localNavigation)->render();
    } else {
      if (empty($this->localNavigation)) {
        return $this->autoLoadLocalNavigation();
      } else {
        return $this->getLocalNavigation();
      }
    }
  }

  /**
   * @return string Local navigation HTML
   */
  private function autoLoadLocalNavigation()
  {
    return (new File($this->findLocalNavigationFile()))->loadAndEvaluate();
  }

  /**
   * @return string Path of local navigation file
   */
  private function findLocalNavigationFile()
  {
    return $this->findFile('site_nav.php');
  }

  /**
   * @param string $filename
   * @param integer $levels Maximum number of levels higher to check
   * @return string Path of file
   */
  private function findFile($filename, $levels = 5)
  {
    assert('is_int($levels)');

    for ($i = 0; $i < $levels; ++$i) {
      $check  = str_repeat('../', $i) . $filename;
      if (file_exists($check)) {
        return $check;
      }
    }
    // default to the homepage's siteNav if nothing is found
    return '/cis/www/site_nav.php';
  }

  /**
   * Gets the content on the page.
   *
   * @return string the content on the page
   */
  private function getContent()
  {
    return $this->content;
  }

  /**
   * Sets the content
   *
   * @param string $content
   * @return $this
   */
  private function setContent($content)
  {
    $this->content = $content;
    return $this;
  }

  /**
   * Renders a page wrapped in the Gustavus template.
   *
   * @return string
   */
  public function render()
  {
    // Set up template preferences
    global $templatePreferences;
    $templatePreferences = $this->templatePreferences;

    $bodyContent = TwigFactory::renderTwigFilesystemTemplate(__DIR__ . '/views/templateBody.html.twig', array(
        'title'           => $this->getTitle(),
        'subtitle'        => $this->getSubtitle(),
        'pageContent'     => $this->getContent(),
        'localNavigation' => $this->renderLocalNavigation(),
        'focusBox'        => $this->getFocusBox(),
        'stylesheets'     => $this->getStylesheets(),
        'javascripts'     => $this->getJavascripts(),
      )
    );

    return TemplatePageRequest::end(null, $bodyContent);
  }
}