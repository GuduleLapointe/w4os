jQuery(document).ready((function(n){var e=n('input[name="match"]:checked').val(),a=n("#name").val(),t=n("#uuids").val();function i(){var i=n('input[name="match"]:checked').val(),o=n("#name").val(),c=n("#uuids").val();if((i!==e||o!==a||c.join(",")!==t.join(","))&&(e=i,a=o,t=c,1)){var u=w4osSettings.loadingMessage;n("#w4os-available-models-container .available-models-container").text(u);var l={action:w4osSettings.updateAction,nonce:w4osSettings.nonce,preview_match:i,preview_name:o,preview_uuids:c};n.post(w4osSettings.ajaxUrl,l,(function(e){n("#w4os-available-models-container .available-models-container").html(e)}))}}n(document).on("change",'input[name="match"]',(function(){i()})),n(document).on("input","#name",(function(){i()})),n(document).on("change","#uuids",(function(){i()}))}));