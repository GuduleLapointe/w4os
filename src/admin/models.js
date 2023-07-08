jQuery(document).ready(function($) {
  var previousMatch = $('input[name="match"]:checked').val();
  var previousName = $('#name').val();
  var previousUuids = $('#uuids').val();
  var reloadCount = 0;
  var initialUpdate = true;

  // Function to update the available models content
  function updateAvailableModelsContent() {
    // Function to check if the field values have changed
    function hasFieldsChanged() {
      var currentMatch = $('input[name="match"]:checked').val();
      var currentName = $('#name').val();
      var currentUuids = $('#uuids').val();

      // Compare the current values with the previous values
      if (currentMatch !== previousMatch || currentName !== previousName || currentUuids.join(',') !== previousUuids.join(',')) {
        previousMatch = currentMatch;
        previousName = currentName;
        previousUuids = currentUuids;

        return true; // Fields have changed
      }

      return false; // Fields have not changed
    }

    if (hasFieldsChanged()) {
      reloadCount++;
      var loadingMessage = '<div class="loading-message">Reloading (' + reloadCount + ')...</div>';
      $('#w4os-available-models-container .available-models-container').html(loadingMessage);

      var data = {
        action: 'update_available_models_content',
        nonce: $('#nonce_w4os-available-models-container').val() // Retrieve the nonce value from the existing field
      };

      // Perform the AJAX request
      $.post(ajaxurl, data, function(response) {
        $('#w4os-available-models-container .available-models-container').html(response);
      });
    }
  }

  // Display the "Loading available models" message on page load
  var loadingMessage = '<div class="loading-message">Loading available models...</div>';
  $('#w4os-available-models-container .available-models-container').html(loadingMessage);

  // Trigger the update function on initial page load
  updateAvailableModelsContent();

  // Trigger the update function when the 'match' field value changes
  $(document).on('change', 'input[name="match"]', function() {
    updateAvailableModelsContent();
  });

  // Trigger the update function when the 'name' field value changes
  $(document).on('input', '#name', function() {
    updateAvailableModelsContent();
  });

  // Trigger the update function when the 'uuids' field value changes
  $(document).on('change', '#uuids', function() {
    updateAvailableModelsContent();
  });
});
