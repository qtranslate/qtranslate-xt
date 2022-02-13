/*############### Error messages ######################*/
jQuery(function () {

    var error_msg = jQuery("#message p[class='setting-error-message']");
    // look for admin messages with the "setting-error-message" error class
    if (error_msg.length != 0) {
        // get the title
        var error_setting = error_msg.attr('title');

        // look for the label with the "for" attribute=setting title and give it an "error" class (style this in the css file!)
        jQuery("label[for='" + error_setting + "']").addClass('error');

        // look for the input with id=setting title and add a red border to it.
        jQuery("input[id='" + error_setting + "']").attr('style', 'border-color: red');
    }


});