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
 *
 * @author     Gudule Lapointe <gudule@speculoos.world>
 * @link       https://github.com/magicoli/opensim-helpers
 * @license    AGPLv3
 */

class OpenSim_Guide
{
    private $destinations = [];
    private $outputHTML = true; // Flag to determine whether to output HTML tags

    public function __construct($source = 'destinations', $outputHTML = true)
    {
        require_once __DIR__ . '/includes/functions.php';
        $this->loadDestinations($source);
        $this->outputHTML = $outputHTML;

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

        if (isset($_GET['category'])) {
            $this->displayDestinations($_GET['category']);
        } else {
            $this->displayMainCategories();
        }

        echo '<script src="js/guide.js?' . time() . '"></script>';

        if ($this->outputHTML) {
            echo '</body>';
            echo '</html>';
        }
    }

    private function loadDestinations($source)
    {
        if (file_exists($source)) {
            $fileContent = file_get_contents($source);
            $lines = explode("\n", $fileContent);

            $categoryTitle = 'Destinations list';
            foreach ($lines as $line) {
                // Exclude lines starting with "#" or "//" or containing no actual characters
                if (substr(trim($line), 0, 1) === '#' || substr(trim($line), 0, 2) === '//' || !trim($line)) {
                    continue;
                }

                list($name, $url) = explode('|', $line);
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
    }

    public function displayMainCategories()
    {
        echo '<link rel="stylesheet" type="text/css" href="css/guide.css?' . time() . '">';
        echo '<div id="guide">';
        echo '<div class=header>';
        echo '<h1>Categories</h1>';
        echo '</div>';
        echo '<div class="list">';
        foreach ($this->destinations as $categoryTitle => $destinations) {
            if (!empty($destinations)) {
                echo '<a href="?category=' . urlencode($categoryTitle) . '">';
                echo '<div class="item">';
                echo '<img class="thumbnail" src="' . $this->getThumbnail() . '" alt="' . $categoryTitle . '">';
                echo '<div class="name">' . $categoryTitle . '</div>';
                echo '<div class="data">' . count($destinations) . ' destinations</div>';
                echo '</div>';
                echo '</a>';
            }
        }
        echo '</div>';
        echo '</div>';
    }

    public function displayDestinations($categoryTitle)
    {
        echo '<link rel="stylesheet" type="text/css" href="css/guide.css">';

        echo '<div id="guide" class="section">';
        echo '<div class=header>';
        echo '<h2>' . $categoryTitle . '</h2>';
        echo '<a href="?" class="back">Back to categories</a>';
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
        echo '</div>';
    }

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
}

// Usage example:
$destinationGuide = new OpenSim_Guide();
