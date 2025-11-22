// See themes-options.js for better method. Need to convert the code below to work the same. The code below does not 
// initialize the color wheel properly with the input field color value. 
(function ($) {
	$(document).ready(function () {
		
		// When the 'Reset' form button is clicked we remove all shortcode parameters. This will foce the shortcode to inherit all settings
		jQuery('input#reset').on( "click", function() {
			output  = '[chat ';
			if ((psource_chat_current_options.id != undefined) && (psource_chat_current_options.id != ''))
				output = output+'id="'+psource_chat_current_options.id+'" ]';
			else
				output = output+' ]';
				
			if (psource_chat_shortcode_str == '') {
				tinyMCEPopup.execCommand('mceReplaceContent', false, output);
			} else {
				tinyMCEPopup.execCommand('mceSetContent', false, tinyMCEPopup.editor.getContent().replace(psource_chat_shortcode_str, output));
			}

			// Return
			tinyMCEPopup.close();						
		});

		// When the 'Insert' form button button is clicked we go through the form fields and check the value against the
		// default options. If there is a difference we add that parameter set to the shortcode output
		jQuery('input#insert').on( "click", function() {
			output  ='[chat ';

			//console.log('psource_chat_wp_user_level_10_roles[%o]', psource_chat_wp_user_level_10_roles);
			
			if ((psource_chat_current_options.id != undefined) && (psource_chat_current_options.id != ''))
				output = output+'id="'+psource_chat_current_options.id+'" ';

			psource_chat_default_options['box_title'] = '';
			for (var chat_form_key in psource_chat_default_options) {
				//console.log("chat_form_key=["+chat_form_key+"]");
				
				//if (chat_form_key == "users_entered_existed_status") {
				//	continue;
				//}
				if ((chat_form_key == "id") || (chat_form_key == "blog_id") || (chat_form_key == "session_type") || (chat_form_key == "session_status") || (chat_form_key == "tinymce_roles") || (chat_form_key == "tinymce_post_types") || (chat_form_key == "update_transient")) {
					continue;
					
				} else if (chat_form_key == "login_options") {
					var chat_login_options_arr = [];
					
					jQuery('input.chat_login_options:checked').each(function() {
						chat_login_options_arr.push(jQuery(this).val());
					});					
					//console.log('chat_login_options_arr[%o]', chat_login_options_arr);
					
					// So we don't add the empty item
					if (chat_login_options_arr.length > 0) {

						psource_chat_default_options.login_options.sort();
						chat_login_options_arr.sort();
						
						if (psource_chat_default_options.login_options.join(',') !== chat_login_options_arr.join(',').trim()) {
							var user_role_str = '';
							for (var user_role_idx in chat_login_options_arr) {
								var user_role = chat_login_options_arr[user_role_idx].trim();
								if (jQuery.inArray(user_role, psource_chat_wp_user_level_10_roles) == -1) {
									if (user_role_str != '') user_role_str+=',';
									user_role_str+=user_role;
								}
							}
							//if (user_role_str != '')
								output += 'login_options="'+user_role_str+'" ';
						}
					}
					
				} else if (chat_form_key == "moderator_roles") {
					var chat_moderator_roles_arr = [];

					jQuery('input.chat_moderator_roles:checked').each(function() {
						chat_moderator_roles_arr.push(jQuery(this).val());
					});					

					// So we don't add the empty item
					if (chat_moderator_roles_arr.length > 0) {

						chat_moderator_roles_arr.sort();

						psource_chat_default_options.moderator_roles.sort();
						
						if (psource_chat_default_options.moderator_roles.join(',') !== chat_moderator_roles_arr.join(',').trim()) {
							var user_role_str = '';
							for (var user_role_idx in chat_moderator_roles_arr) {
								var user_role = chat_moderator_roles_arr[user_role_idx].trim();
								if (jQuery.inArray(user_role, psource_chat_wp_user_level_10_roles) == -1) {
									if (user_role_str != '') user_role_str+=',';
									user_role_str+=user_role;
								}
							}
							//if (user_role_str != '')
								output += 'moderator_roles="'+user_role_str+'" ';
						}
						
					}
				} else {
					var chat_form_value = jQuery('#chat_'+chat_form_key).val();
					if (chat_form_value !== undefined && chat_form_value !== null) {
						chat_form_value = chat_form_value.trim();
					} else {
						chat_form_value = '';
					}
					//console.log("chat_form_key=["+chat_form_key+"]=["+chat_form_value+"]");
					
					if ((chat_form_key == "blocked_words_active") && (chat_form_value == '')) {
						chat_form_value = 'disabled';
					}
					if ((chat_form_key == "blocked_ip_addresses_active") && (chat_form_value == '')) {
						chat_form_value = 'disabled';
					}

					if (chat_form_value != psource_chat_default_options[chat_form_key]) {
						output += chat_form_key+'="'+chat_form_value+'" ';
					}
				}
			}
			output += ']';

			if (psource_chat_shortcode_str == '') {
				tinyMCEPopup.execCommand('mceReplaceContent', false, output);
			} else {
				tinyMCEPopup.execCommand('mceSetContent', false, tinyMCEPopup.editor.getContent().replace(psource_chat_shortcode_str, output));
			}

			// Return
			tinyMCEPopup.close();						
		});		
	});
})(jQuery);
