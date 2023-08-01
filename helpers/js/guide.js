/**
 * js/guide.js
 *
 * @package    magicoli/opensim-helpers
 * @subpackage    magicoli/opensim-helpers/guide
 *
 * @author     Gudule Lapointe <gudule@speculoos.world>
 * @link       https://github.com/magicoli/opensim-helpers
 * @license    AGPLv3
 */

// JavaScript code to force horizontal scrolling with mouse wheel
document.querySelector('#guide .list').addEventListener('wheel', function (event) {
  // Check if Shift key is pressed to allow vertical scrolling
  if (event.shiftKey) {
    return;
  }

  // Prevent the default vertical scrolling behavior
  event.preventDefault();

  // Calculate the scroll amount
  const scrollAmount = event.deltaY * 1.5;

  // Set the horizontal scroll position
  this.scrollBy({
    left: scrollAmount,
    behavior: 'smooth', // Add a smooth scrolling effect
  });
});
