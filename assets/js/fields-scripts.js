jQuery( document ).ready( function($) {
  // Adding a photo to a developer.
  if ( typeof wp !== 'undefined' && wp.media && wp.media.editor ) {
    $('.developers').on('click', '.add_photo', function(e) {
      e.preventDefault();
      var devId = $(this).attr('data-dev');
      var inputField = $('#dev' + devId + ' .dev_photo');
      var showPhoto = $('#dev' + devId + ' .show_photo');
      wp.media.editor.send.attachment = function(props, attachment) {
        inputField.val(attachment.url);
        showPhoto.html('<img src="' + attachment.url + '">');
      };
      wp.media.editor.open($(this));
      return false;
    });
  }

  // Delete a developer.
  $('.developers').on('click', '.delete_dev', function(e) {
    e.preventDefault();

    var devId = $(this).attr('data-dev');

    $('#dev' + devId ).remove();
  });

  // Add a new Developer.
  $('#add_dev').click( function(e) {
    e.preventDefault();

    var devId = $('.dev').length + 1;

    var dev = zf_vars.fields_template.replace(/\[id\]/g, devId);

    $('.developers').append(dev);
  });

  // Validations.
  /**
   * Check if a string is an URL
   *
   * @param {string} str The string to checked.
   * @return {boolean} true if the string is an URL, otherwise false.
   */
  function validURL(str) {
    var regexp = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
    return regexp.test(str);
  }

  $('#publish').click( function(e) {
    $('.field_error').remove();
    $('.developers .dev input').each(function() {
      var value = $(this).val();
      if ( !value ) {
        $(this).after('<p class="field_error">This field is required</p>');
      } else if ( $(this).hasClass('url') && !validURL(value) ) {
        $(this).after('<p class="field_error">Invalid URL</p>');
      }
    });

    if ( $('.developers .field_error').length > 0 ) {
      alert( 'Some fields have invalid values in the developer fields' );
      e.preventDefault();
    }
  });
});
