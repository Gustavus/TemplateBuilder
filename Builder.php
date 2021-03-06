<?php
/**
 * @package TemplateBuilder
 * @author  Billy Visto
 */
namespace Gustavus\TemplateBuilder;

//Gatekeeper needs to be included after the request class
//because the request class sets a constant that gatekeeper needs.
require_once 'gatekeeper/gatekeeper.class.php';

use Gustavus\TwigFactory\TwigFactory,
  Gustavus\LocalNavigation\ItemFactory,
  Gustavus\Utility\File,
  Gustavus\Utility\Set,
  Gustavus\Extensibility\Filters,
  Gustavus\Concert\Controllers\MainController as ConcertController,
  Gustavus\Template\PageActions,
  Gustavus\Template\Request as TemplateRequest;

// start extreme maintenance and debugging if needed
TemplateRequest::init();

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
  private $head = '';

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
   * @var string
   */
  private $banners = '';

  /**
   * Preferences to set in the template
   *
   * @var array
   */
  private $templatePreferences = ['localNavigation' => true, 'auxBox' => false];

  /**
   * Flag to specify if we have initialized or not yet
   *
   * @var boolean
   */
  private static $initialized = false;

  /**
   * Flag to specify if we should store the current builder object instead of returning the template.
   *
   * @var boolean
   */
  private static $storeObjectInsteadOfRender = false;

  /**
   * Stored builder object. Will be populated if $storeObjectInsteadOfRender is true.
   *
   * @var Builder
   */
  private static $storedBuilder = null;

  /**
   * Constructs the object with the args specified
   *
   * Supported properties for $args:
   * <ul>
   *   <li>title = {string} Page title</li>
   *   <li>subtitle = {string} Page sub title</li>
   *   <li>content = {string} Page content</li>
   *   <li>focusBox = {string} Focus box content</li>
   *   <li>stylesheets = {string} Stylesheets to add to the head</li>
   *   <li>javascripts = {string} Javascripts to add to the head</li>
   *   <li>head = {string} Contents to add to the head</li>
   *   <li>localNavigation = {string|array} <a href="https://beta.gac.edu/docs/php/class-Gustavus.LocalNavigation.ItemFactory.html#_getItems">Configuration sent to LocalNavigation\Items::getItems</a> or html.</li>
   *   <li>breadCrumbs = {array} Array of arrays of crumb configuration with 'url' and 'text' as keys.</li>
   *   <li>breadCrumbAdditions = {array} Array of arrays of additional bread crumbs to add.</li>
   *   <li>messages = {string} Messsages to add to the bottom message of the page.</li>
   *   <li>banners = {string} HTML to add to the banners portion of the template</li>
   *   <li>templatePreferences = {array} Array of preferences to append to our default preferences. <a href="https://beta.gac.edu/docs/php/class-TemplateBase.html#$preferences">Template Properties</a></li>
   * </ul>
   *
   * @param array $args keyed by page part
   * @param  array $templatePreferences templatePreferences to add to the global template preferences.
   *   <strong>Note:</strong> This will override any templatePreferences specified in $args
   * @return  void
   */
  public function __construct(array $args = array(), $templatePreferences = array())
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
   * Sets whether or not we should store the object or return the template when render is called
   *
   * @param boolean $value
   * @return  void
   */
  public static function setShouldStoreObjectInsteadOfRender($value = true)
  {
    self::$storeObjectInsteadOfRender = $value;
  }

  /**
   * Gets the stored builder object that was stored instead of rendered
   *
   * @param boolean $resetToRender Whether to reset to rendering the template or not.
   * @return  Builder Stored builder object
   */
  public static function getStoredBuilder($resetToRender = true)
  {
    if ($resetToRender) {
      self::$storeObjectInsteadOfRender = false;
    }
    return self::$storedBuilder;
  }

  /**
   * Gets the title that is set for the page.
   *
   * @return string the page title.
   */
  public function getTitle()
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
  public function getSubtitle()
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
  public function getFocusBox()
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
  public function getStylesheets()
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
   * Gets the head HTML on the page.
   *
   * @return string the head HTML on the page
   */
  public function getHead()
  {
    return $this->head;
  }

  /**
   * Sets the head HTML on the page.
   *
   * @param string $head the new head HTML on the page
   * @return $this to enable method chaining
   */
  private function setHead($head)
  {
    $this->head = $head;
    return $this;
  }

  /**
   * Gets the javascript content on the page.
   *
   * @return string the javascript content on the page
   */
  public function getJavascripts()
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
  public function getLocalNavigation()
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
  public function getContent()
  {
    if (ob_get_level()) {
      // this either means that a warning, a notice, or any other output was thrown to the output buffer. Lets throw it above the content so we don't lose it.
      if (PHP_SAPI === 'cli') {
        // we don't want to wipe the buffer if from the command line.
        $this->content = ob_get_contents() . $this->content;
      } else {
        // we do here though, otherwise this output will be duplicated
        $this->content = ob_get_clean() . $this->content;
        // restart a buffer since one has been started somewhere.
        ob_start();
      }
      return $this->content;
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
  public function getMessages()
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
   * Gets the banners for the page.
   *
   * @return string the banners on the page
   */
  public function getBanners()
  {
    return $this->banners;
  }

  /**
   * Sets the banners
   *
   * @param string $banners
   * @return $this
   */
  private function setBanners($banners)
  {
    $this->banners = $banners;
    return $this;
  }

  /**
   * Gets the breadCrumbs for the page.
   *
   * @return array the breadCrumbs on the page
   */
  public function getBreadCrumbs()
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
    return (!empty($builtCrumbs)) ? implode('', $builtCrumbs): '';
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
    $this->templatePreferences = array_merge(array('breadcrumbTrailArray' => $translatedCrumbs), $this->templatePreferences);
    $this->appendAdditionalBreadCrumbs();
  }

  /**
   * Sets up anything that needs to happen right away such as logging in or impersonation requests.
   *
   * @param  boolean $runConcert Whether to attempt to use Concert for this request or not
   * @return void
   */
  public static function init($runConcert = true)
  {
    if (self::$initialized) {
      // we have already been initialized
      return;
    }
    // Handle users logging in/out and requesting impersonation.
    PageActions::handleActions();
    self::$initialized = true;

    // now check if the user is trying to do anything in Concert.
    if ($runConcert && class_exists('\Gustavus\Concert\Controllers\MainController')) {
      $requestedFile = $_SERVER['SCRIPT_FILENAME'];

      if (empty($requestedFile) && isset($_SERVER['REQUEST_URI'])) {
        // fallback to REQUEST_URI
        // Note: This should not happen. SCRIPT_FILENAME should always contain something, but just in case.
        $parsedUrl = parse_url($_SERVER['REQUEST_URI']);
        if (isset($parsedUrl['path'])) {
          $requestedFile = $parsedUrl['path'];
        }
      }
      // check to see if the user can access concert.
      $concertActions = (new ConcertController)->mosh($requestedFile);

      if (isset($concertActions['action'], $concertActions['value']) && $concertActions['action'] === 'return') {
        echo $concertActions['value'];
        exit;
      } // else: no action was needed.
    }
  }

  /**
   * Renders a page wrapped in the Gustavus template.
   *
   * @return string
   */
  public function render()
  {
    if (self::$storeObjectInsteadOfRender) {
      self::$storedBuilder = $this;
      return;
    }
    self::init();

    $this->setUpBreadCrumbs();
    // Set up template preferences
    global $templatePreferences;
    // we don't want to over-write any already set preferences before we render.
    if (!is_array($templatePreferences)) {
      $templatePreferences = [];
    }
    $templatePreferences = Set::recursivelyMergeArrays($templatePreferences, $this->templatePreferences);

    Filters::add('messages',
        function($content)
        {
          return $content . $this->getMessages();
        }
    );
    Filters::add('banner',
        function($content)
        {
          return $content . $this->getBanners();
        }
    );

    $sections = [
      'Title'           => trim($this->getTitle()),
      'Subtitle'        => trim($this->getSubtitle()),
      'Content'         => trim($this->getContent()),
      'LocalNavigation' => trim($this->renderLocalNavigation()),
      'FocusBox'        => trim($this->getFocusBox()),
      'Head'            => trim($this->getStylesheets() . $this->getHead()),
      'JavaScript'      => trim($this->getJavascripts()),
    ];

    if (Filters::exists('concertCMSCheckEditable')) {
      foreach ($sections as $section => &$value) {
        $value = Filters::apply('concertCMSCheckEditable', $value, $section);
      }
    }
    // we want any current template preferences to overwrite our sections
    $templatePreferences = Set::recursivelyMergeArrays($sections, $templatePreferences);

    return TemplateRequest::end(null, '');
  }
}