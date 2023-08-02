<?php
/**
 * guide.php
 *
 * Provide a destination guide for V3 viewers.
 *
 * Requires OpenSimulator Search module
 *   [OpenSimSearch](https://github.com/kcozens/OpenSimSearch)
 * Events need to be fetched with a separate script, from an HYPEvents server
 *
 * @package    magicoli/opensim-helpers
 * @subpackage    magicoli/opensim-helpers/guide
 * @author     Gudule Lapointe <gudule@speculoos.world>
 * @link       https://github.com/magicoli/opensim-helpers
 * @license    AGPLv3
 */

// require_once __DIR__ . '/includes/config.php'; // DEBUG: disabled until we're ready with WP
require_once __DIR__ . '/includes/functions.php';

class OpenSim_Guide
{
  private $destinations = [];
  private $fullHTML = false; // Flag to determine whether to output HTML tags
  private $url_base;
  private $url_args = [];
  private $source;

  public function __construct()
  {
    $this->url_base = $this->getFullURL();

    if (defined('OPENSIM_GUIDE_SOURCE') && !empty(OPENSIM_GUIDE_SOURCE)) {
      $this->source = OPENSIM_GUIDE_SOURCE;
    } else {
      $this->source = isset($_GET['source']) ? $_GET['source'] : null;
      $this->url_args['source'] = $this->source;
    }

    if( ! defined('OPENSIM_GUIDE_SOURCE' ) ) {
      // TODO: Check if script was really called directly even while
      // OPENSIM_GUIDE_SOURCE if not set
      $this->fullHTML = true;
      $this->outputHTML();
    }
  }

  public function outputHTML() {
    echo $this->buildHTML();
  }

  public function buildHTML() {
    $this->loadDestinations($this->source);

    $content = $this->startHTML();

    if( empty($this->destinations) ) {
      $content .= $this->noResultsMessage();
    } else if (empty($_GET['category'])) {
      $content .= $this->displayMainCategories();
    } else {
      $content .= $this->displayDestinations($_GET['category']);
    }
    $content .= $this->stopHTML();

    return $content;
  }

  private function loadDestinations($source)
  {
    $fileContent = null;
    // Check if the source is a URL or a file path
    if (filter_var($source, FILTER_VALIDATE_URL)) {
      $fileContent = file_get_contents($source);
    } else if (file_exists($source)) {
      $fileContent = file_get_contents($source);
    }

    $lines = explode("\n", $fileContent);

    $categoryTitle = 'Destinations list';
    foreach ($lines as $line) {
      // Exclude lines starting with "#" or "//" or containing no actual characters
      if (substr(trim($line), 0, 1) === '#' || substr(trim($line), 0, 2) === '//' || !trim($line)) {
        continue;
      }

      list($name, $url) = explode('|', $line . '|');
      if (empty($name)) {
        continue;
      }

      if (empty($url)) {
        // New category found
        $categoryTitle = trim($name);
      } else {
        // Destination within a category
        $this->destinations[$categoryTitle][] = ['name' => $name, 'url' => $url];
      }
    }
  }

  public function displayMainCategories()
  {
    $content = '<div class=header>'
    . '<h1>Destinations List</h1>'
    . '</div>'
    . '<div class="list">';
    foreach ($this->destinations as $categoryTitle => $destinations) {
      if (!empty($destinations)) {
        $content .= '<a href="' . $this->buildURL($categoryTitle) . '">'
        . '<div class="item">'
        . '<img class="thumbnail" src="' . $this->getThumbnail() . '" alt="' . $categoryTitle . '">'
        . '<div class="name">' . $categoryTitle . '</div>'
        . '<div class="data">' . count($destinations) . ' destinations</div>'
        . '</div>'
        . '</a>';
      }
    }
    $content .= '</div>';
    return $content;
  }

  public function displayDestinations($categoryTitle)
  {
    $content = '<div class=header>'
    . '<h2>' . $categoryTitle . '</h2>'
    . '<a href="' . $this->buildURL() . '" class="back">Back to categories</a>'
    . '</div>'
    . '<div class="list">';
    foreach ($this->destinations[$categoryTitle] as $destination) {
      $traffic = $this->getTraffic();
      $people = $this->getNumberOfPeople();
      $content .= '<a href="' . opensim_format_tp($destination['url'], TPLINK_HG) . '">'
      . '<div class="item">'
      . '<img class="thumbnail" src="' . $this->getThumbnail() . '" alt="' . $destination['name'] . '">'
      . '<div class="name">' . $destination['name'] . '</div>'
      . '<div class="data">';
      if ($people > 0) {
        $content .= ' <span>' . $this->getNumberOfPeople() . ' people</span> ';
      }
      if ($traffic > 0) {
        $content .= ' <span>Traffic: ' . $this->getTraffic() . '</span> ';
      }
      $content .= '</div></div></a>';
    }
    $content .= '</div>';

    return $content;
  }

  private function noResultsMessage()
  {
    echo '<div class="error">';
    echo 'The realm of destinations you seek has eluded our grasp, spirited away by elusive knomes. Rally the grid managers, let them venture forth to curate a grand tapestry of remarkable places for your exploration!';
    echo '</div>';
  }

  // Rest of the class remains unchanged...

  private function getAnchor($text)
  {
    // Helper function to create an anchor-friendly version of the text
    return strtolower(str_replace(' ', '-', $text));
  }

  private function getThumbnail()
  {
    // Replace this with the actual URL for the thumbnail placeholder
    return 'no-img.jpg';
  }

  private function getTraffic()
  {
    // Replace this with the actual traffic placeholder value
    return null;
  }

  private function getNumberOfPeople()
  {
    // Replace this with the actual number of people placeholder value
    return null;
  }

  private function is_included() {
    $trace = debug_backtrace();
    return isset($trace[1]) && $trace[1]['function'] === 'include' || $trace[1]['function'] === 'require';
  }


  private function startHTML() {
    $content = '';
    if ($this->fullHTML) {
      $content = '<!DOCTYPE html>'
      . '<html lang="en">'
      . '<head>'
      . '<meta charset="UTF-8">'
      . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
      . '<title>Destination Guide</title>'
      . '</head>'
      . '<body class="destination-guide">';
    }

    $content .= '<link rel="stylesheet" type="text/css" href="css/guide.css?' . time() . '">'
    . '<div id="guide">';
    return $content;
  }

  private function stopHTML() {
    $content = "</div>";
    if ($this->fullHTML) {
      $content .= '</body></html>';
    }
    return $content;
  }

  private function getFullURL() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $request = $_SERVER['PHP_SELF'];
    return $protocol . $host . $request;
  }

  private function buildURL($category = null)
  {
    $args = array_filter( array_merge( $this->url_args, array(
      'category' => $category,
    )));
    if(empty($args)) {
      return $this->url_base;
    }
    return $this->url_base . '?' . http_build_query($args);
  }
}

$destinationGuide = new OpenSim_Guide();
