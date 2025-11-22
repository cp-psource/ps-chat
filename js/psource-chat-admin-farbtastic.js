// WordPress Color Picker for chat settings panels.
(function ($) {
	jQuery(document).ready(function () {
		
		jQuery('input.pickcolor_input').each(function() {
			// Initialize WordPress Color Picker
			var color_val = jQuery(this).val();
			if (color_val == '') {
				color_val = "#FFFFFF";
				jQuery(this).val(color_val);
			}
			
			// Initialize wp-color-picker
			jQuery(this).wpColorPicker({
				defaultColor: color_val,
				change: function(event, ui) {
					// Update the input field with the selected color
					jQuery(this).val(ui.color.toString());
				},
				clear: function() {
					// Set to white when cleared
					jQuery(this).val('#FFFFFF');
				}
			});
		});
	});
})(jQuery);
