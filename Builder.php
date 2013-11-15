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
  Gustavus\Utility\File,
  Gustavus\Extensibility\Filters;

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
   * @var array
   */
  private $breadCrumbs = [];

  /**
   * @var array
   */
  private $breadCrumbAdditions = [];

  /**
   * @var string
   */
  private $content = '';

  /**
   * @var string
   */
  private $messages = '';

  /**
   * Preferences to set in the template
   * @var array
   */
  private $templatePreferences = ['localNavigation' => true, 'auxBox' => false];

  /**
   * Constructs the object with the args specified
   *
   * @param array $args keyed by page part
   * @param  array $templatePreferences templatePreferences to add to the global template preferences.
   *   <strong>Note:</strong> This will override any templatePreferences specified in $args
   * @return  void
   */
  public function __construct($args = array(), $templatePreferences = array())
  {
    if (isset($args['templatePreferences'])) {
      $templatePreferences = array_merge($args['templatePreferences'], $templatePreferences);
      unset($args['templatePreferences']);
    }
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
   * Searches for a local navigation file to load.
   *
   * @return string Local navigation HTML
   */
  private function autoLoadLocalNavigation()
  {
    return (new File('site_nav.php'))->find(null, '/cis/www/site_nav.php', 5)->loadAndEvaluate();
  }

  /**
   * Gets the content on the page.
   *
   * @return string the content on the page
   */
  private function getContent()
  {
    if (ob_get_level()) {
      // this either means that a warning, a notice, or any other output was thrown to the output buffer. Lets throw it above the content so we don't lose it.
      return ob_get_contents() . $this->content;
    }
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
   * Gets the messages for the page.
   *
   * @return string the messages on the page
   */
  private function getMessages()
  {
    return $this->messages;
  }

  /**
   * Sets the messages
   *
   * @param string $messages
   * @return $this
   */
  private function setMessages($messages)
  {
    $this->messages = $messages;
    return $this;
  }

  /**
   * Gets the breadCrumbs for the page.
   *
   * @return array the breadCrumbs on the page
   */
  private function getBreadCrumbs()
  {
    return $this->breadCrumbs;
  }

  /**
   * Sets the breadCrumbs
   *
   * @param array $breadCrumbs
   *        array of arrays of ['url' => url, 'text' => text]
   * @return $this
   */
  private function setBreadCrumbs(array $breadCrumbs)
  {
    $this->breadCrumbs = $breadCrumbs;
    return $this;
  }

  /**
   * Sets the breadCrumbAdditions to be added to the breadCrumbs
   *
   * @param array $breadCrumbAdditions
   *        array of arrays of ['url' => url, 'text' => text]
   * @return $this
   */
  private function setBreadCrumbAdditions(array $breadCrumbAdditions)
  {
    $this->breadCrumbAdditions = $breadCrumbAdditions;
    return $this;
  }

  /**
   * Gets the breadCrumbAdditions for the page.
   *
   * @return array the breadCrumbAdditionss on the page
   */
  private function getBreadCrumbAdditions()
  {
    return $this->breadCrumbAdditions;
  }

  /**
   * Builds a string to append onto the bread crumbs
   *
   * @return string
   */
  private function buildBreadCrumbAdditions()
  {
    $builtCrumbs = [];
    foreach ($this->breadCrumbAdditions as $crumb) {
      $builtCrumbs[] = sprintf('<a href="%s">%s</a>',
          $crumb['url'],
          $crumb['text']
      );
    }
    return (!empty($builtCrumbs)) ? implode(' / ', $builtCrumbs) . ' / ' : '';
  }

  /**
   * Appends the additional bread crumbs onto the already existing bread crumbs
   *
   * @return void
   */
  private function appendAdditionalBreadCrumbs()
  {
    Filters::add('breadcrumbTrail',
        function($content)
        {
          return $content . $this->buildBreadCrumbAdditions();
        }
    );
  }

  /**
   * Translates bread crumbs into a format the template understands.
   *
   * @return  void
   */
  private function setUpBreadCrumbs()
  {
    $translatedCrumbs = [];
    foreach ($this->breadCrumbs as $crumb) {
      // translate each crumb into something the template understands
      $translatedCrumbs[] = [
        'attributes' => sprintf('href="%s"', $crumb['url']),
        'value'      => $crumb['text'],
      ];
    }
    $this->templatePreferences = array_merge($this->templatePreferences, array('breadcrumbTrailArray' => $translatedCrumbs));
    $this->appendAdditionalBreadCrumbs();
  }

  /**
   * Renders a page wrapped in the Gustavus template.
   *
   * @return string
   */
  public function render()
  {
    $this->setUpBreadCrumbs();
    // Set up template preferences
    global $templatePreferences;
    $templatePreferences = $this->templatePreferences;
    TemplatePageRequest::initExtremeMaintenance();

    Filters::add('messages',
        function($content)
        {
          return $content . $this->getMessages();
        }
    );

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