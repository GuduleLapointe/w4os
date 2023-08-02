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
  private $outputHTML = true; // Flag to determine whether to output HTML tags
  private $url_base;
  private $url_args = [];

  public function __construct()
  {
    $this->url_base = $this->getFullURL();

    if (defined('OPENSIM_GUIDE_SOURCE') && !empty(OPENSIM_GUIDE_SOURCE)) {
      $source = OPENSIM_GUIDE_SOURCE;
    } else {
      $source = isset($_GET['source']) ? $_GET['source'] : null;
      $this->url_args['source'] = $source;
    }

    $this->loadDestinations($source);

    $this->startHTML();

    // if(!empty($source)) {
      if (isset($_GET['category'])) {
        $this->displayDestinations($_GET['category']);
      } else {
        $this->displayMainCategories();
      }
    // }

    // Display a custom message if there are no destinations
    if (empty($this->destinations)) {
      $this->noResultsMessage();
    }

    $this->stopHTML();
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
    echo '<div class=header>';
    echo '<h1>Destinations List</h1>';
    echo '</div>';
    echo '<div class="list">';
    foreach ($this->destinations as $categoryTitle => $destinations) {
      if (!empty($destinations)) {
        echo '<a href="' . $this->buildURL($categoryTitle) . '">';
        echo '<div class="item">';
        echo '<img class="thumbnail" src="' . $this->getThumbnail() . '" alt="' . $categoryTitle . '">';
        echo '<div class="name">' . $categoryTitle . '</div>';
        echo '<div class="data">' . count($destinations) . ' destinations</div>';
        echo '</div>';
        echo '</a>';
      }
    }
    echo '</div>';
  }

  public function displayDestinations($categoryTitle)
  {
    echo '<div class=header>';
    echo '<h2>' . $categoryTitle . '</h2>';
    echo '<a href="' . $this->buildURL() . '" class="back">Back to categories</a>';
    echo '</div>';

    echo '<div class="list">';
    foreach ($this->destinations[$categoryTitle] as $destination) {
      $traffic = $this->getTraffic();
      $people = $this->getNumberOfPeople();
      echo '<a href="' . opensim_format_tp($destination['url'], TPLINK_HG) . '">';
      echo '<div class="item">';
      echo '<img class="thumbnail" src="' . $this->getThumbnail() . '" alt="' . $destination['name'] . '">';
      echo '<div class="name">' . $destination['name'] . '</div>';
      echo '<div class="data">';
      if ($people > 0) {
        echo ' <span>' . $this->getNumberOfPeople() . ' people</span> ';
      }
      if ($traffic > 0) {
        echo ' <span>Traffic: ' . $this->getTraffic() . '</span> ';
      }
      echo '</div>';
      echo '</div>';
      echo '</a>';
    }
    echo '</div>';
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
    if ($this->outputHTML) {
      echo '<!DOCTYPE html>';
      echo '<html lang="en">';
      echo '<head>';
      echo '<meta charset="UTF-8">';
      echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
      echo '<title>Destination Guide</title>';
      echo '</head>';
      echo '<body class="destination-guide">';
    }

    echo '<link rel="stylesheet" type="text/css" href="css/guide.css?' . time() . '">';
    echo '<div id="guide">';
  }

  private function stopHTML() {
    echo "</div>";
    if ($this->outputHTML) {
      echo '</body>';
      echo '</html>';
    }
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
