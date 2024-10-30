(function (jQuery) {
	jQuery('#default_pickup').autoComplete({
		minChars: 2
	});

	jQuery('#default_destination').autoComplete({
		minChars: 2
	});

	jQuery('.radio-container [type="radio"]').on('change', function() {
		var parent = jQuery(this).parent();
		var sibling = parent.siblings();
		parent.removeClass('radio-container-disabled');
		parent.addClass('radio-container-active');

		sibling.removeClass('radio-container-active');
		sibling.addClass('radio-container-disabled');
	});

    jQuery(".toggle-password").click(function() {
        var icon = jQuery(this);

        var input = jQuery(icon.attr("toggle"));
        input.attr("type", "text");
        icon.hide();
    });

}(jQuery));