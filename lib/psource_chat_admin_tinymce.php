<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<?php
	wp_print_styles( array( 'wp-color-picker', 'psource-chat-admin-css' ) );
	wp_print_scripts( array(
		'jquery',
		'tiny_mce_popup.js',
		'mctabs.js',
		'validate.js',
		'form_utils.js',
		'editable_selects.js',
		'wp-color-picker',
		'jquery-cookie',
		'psource-chat-admin-js',
		'psource-chat-admin-tinymce-js',
		'psource-chat-admin-farbtastic-js'
	) );
	?>
	<script type="text/javascript">
		<?php
		// We basically want to built to options lists. The 'psource_chat_default_options' will match our Settings panel.
		// The 'psource_chat_current_options' will match the parsed shortcode combined with the default_options values.
		?>
		var psource_chat_wp_user_level_10_roles = [];
		<?php
			global $wp_roles;
				if (is_object($wp_roles) && is_array($wp_roles->roles)) {
						$psource_chat_wp_user_level_10_roles = [];
						foreach ($wp_roles->roles as $role_slug => $role) {
							if (isset($role['capabilities']['level_10'])) {
								$psource_chat_wp_user_level_10_roles[] = $role_slug;
							}
						}
					} 
				else {
					$psource_chat_wp_user_level_10_roles = [];
				}
			?>
			//console.log('psource_chat_wp_user_level_10_roles[%o]', psource_chat_wp_user_level_10_roles);

		var psource_chat_default_options = {
			<?php
				$this->_chat_options['page'] = $this->convert_config('page', $this->_chat_options['page']);
				foreach($this->_chat_options['page'] as $key => $val) {
					if (($key == "blog_id") || ($key == "id") || ($key == "session_type") || ($key == "session_status")) {continue;}

					if ($key == 'blocked_words_active') {
						if ((!$this->_chat_options['banned']['blocked_words_active'] == "enabled") || ($val == '')) {
							$val = 'disabled';
						}
					} else if ($key == 'login_options') {
						global $wp_roles;
						$current_user_idx = in_array('current_user', $val);
						if ($current_user_idx !== false) {
							unset($val[$current_user_idx]);
							foreach ($wp_roles->roles as $role_slug => $role) {
								$val[] = $role_slug;
							}
						} else {
							foreach ($wp_roles->roles as $role_slug => $role) {
								if ((isset($role['capabilities']['level_10'])) && (in_array($role_slug, $val) === false)) {
									$val[] = $role_slug;
								}
							}
						}
					} else if ($key == 'moderator_roles') {
						foreach ($wp_roles->roles as $role_slug => $role) {
							if ((isset($role['capabilities']['level_10'])) && (in_array($role_slug, $val) === false)) {
								$val[] = $role_slug;
							}
						}
					}

					?>'<?php echo $key; ?>': "<?php
						if (is_array($val)) {
							echo join(',', $val);
						} else {
							echo $val;
						} ?>", <?php
					}
				?>
		};

		if ((psource_chat_default_options.login_options != undefined) && (psource_chat_default_options.login_options.length > 0)) {

			// Convert to an array...
			psource_chat_default_options.login_options = psource_chat_default_options.login_options.split(',');

			// ...finally sort the array
			psource_chat_default_options.login_options.sort();
		}

		if ((psource_chat_default_options.moderator_roles != undefined) && (psource_chat_default_options.moderator_roles.length > 0)) {

			// Convert to an array...
			psource_chat_default_options.moderator_roles = psource_chat_default_options.moderator_roles.split(',');

			// ...finally sort the array
			psource_chat_default_options.moderator_roles.sort();
		}
		//console.log('psource_chat_default_options[%o]', psource_chat_default_options);


		var psource_chat_current_options = {};
		for (attr in psource_chat_default_options) {
			psource_chat_current_options[attr] = psource_chat_default_options[attr];
		}

		var psource_chat_shortcode_str = '';
		var _tmp_chat_shortcode = tinyMCEPopup.editor.getContent().split('[chat ');
		if (_tmp_chat_shortcode.length > 1) {
			_tmp_chat_shortcode = _tmp_chat_shortcode[1].split(']');
			psource_chat_shortcode_str = '[chat ' + _tmp_chat_shortcode[0] + ']';

			// Parse the WP shortcode. Taken from shortcode.js
			var psource_chat_shortcode_pairs = {},
				numeric = [],
				pattern, match;

			pattern = /(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/g;

			// Map zero-width spaces to actual spaces.
			psource_chat_shortcode_str = psource_chat_shortcode_str.replace(/[\u00a0\u200b]/g, ' ');

			// Match and normalize attributes.
			while ((match = pattern.exec(psource_chat_shortcode_str))) {
				if (match[1]) {
					psource_chat_shortcode_pairs[match[1].toLowerCase()] = match[2];
				} else if (match[3]) {
					psource_chat_shortcode_pairs[match[3].toLowerCase()] = match[4];
				} else if (match[5]) {
					psource_chat_shortcode_pairs[match[5].toLowerCase()] = match[6];
				}
			}
			// Now that we have the shortcode parsed into object pairs we apply the values to our psource_chat_current_options object which is then
			// loaded to the form fields later.
			for (attr in psource_chat_shortcode_pairs) {
				//var attr_val = psource_chat_shortcode_pairs[attr];

				// For the login_options and moderator_roles we convert to array (easier to work with)...
				if ((attr == "login_options") || (attr == "moderator_roles")) {
					var attr_array = psource_chat_current_options[attr] = psource_chat_shortcode_pairs[attr].split(',');
					if (attr_array.length > 0) {

						// If we have a non-empty array we loop through and trim the elements. No whitespace allowed!
						for (attr_idx in attr_array) {
							attr_array[attr_idx] = attr_array[attr_idx].trim();
						}
					}

					// Check for the existance of the 'key' items. These values are ALWAYS set.
					if (attr == "login_options") {
						for (role_idx in psource_chat_wp_user_level_10_roles) {
							var role_slug = psource_chat_wp_user_level_10_roles[role_idx];
							role_slug.trim();
							if (jQuery.inArray(role_slug, attr_array) == -1) {
								attr_array.push(role_slug);
							}
						}


					} else if (attr == "moderator_roles") {
						for (role_idx in psource_chat_wp_user_level_10_roles) {
							var role_slug = psource_chat_wp_user_level_10_roles[role_idx];
							role_slug.trim();
							if (jQuery.inArray(role_slug, attr_array) == -1) {
								attr_array.push(role_slug);
							}
						}
					}
					// Reassign the value back to our current options array.
					psource_chat_current_options[attr] = attr_array;
				} else {
					if (attr == "log_display") {
						console.log('log_display[' + psource_chat_shortcode_pairs[attr] + ']')
					}
					psource_chat_current_options[attr] = psource_chat_shortcode_pairs[attr];
				}
			}
		}

	</script>
	<title><?php _e( 'WordPress Chat', 'psource-chat' ); ?></title>
</head>
<body style="display: none">
<div id="psource-chat-wrap" class="wrap psource-chat-wrap-popup">
	<form action="#">
		<div class="tabs">
			<ul>
				<li id="psource-chat-box-appearance-tab" class="current"><span><a
							href="javascript:mcTabs.displayTab('psource-chat-box-appearance-tab', 'psource-chat-box-appearance-panel');"
							onmousedown="return false;"><?php _e( 'Box Darstellung', 'psource-chat' ); ?></a></span>
				</li>

				<li id="psource-chat-messages-appearance-tab"><span><a
							href="javascript:mcTabs.displayTab('psource-chat-messages-appearance-tab', 'psource-chat-messages-appearance-panel');"
							onmousedown="return false;"><?php _e( 'Nachricht Darstellung', 'psource-chat' ); ?></a></span>
				</li>

				<li id="psource-chat-messages-input-tab"><span><a
							href="javascript:mcTabs.displayTab('psource-chat-messages-input-tab', 'psource-chat-messages-input-panel');"
							onmousedown="return false;"><?php _e( 'Nachrichteneingabe', 'psource-chat' ); ?></a></span>
				</li>

				<li id="psource-chat-users-list-tab"><span><a
							href="javascript:mcTabs.displayTab('psource-chat-users-list-tab', 'psource-chat-users-list-panel');"
							onmousedown="return false;"><?php _e( 'Benutzerliste', 'psource-chat' ); ?></a></span>
				</li>

				<li id="psource-chat-authentication-tab"><span><a
							href="javascript:mcTabs.displayTab('psource-chat-authentication-tab', 'psource-chat-authentication-panel');"
							onmousedown="return false;"><?php _e( 'Authentication', 'psource-chat' ); ?></a></span>
				</li>

				<li id="psource-chat-advanced-tab"><span><a
							href="javascript:mcTabs.displayTab('psource-chat-advanced-tab', 'psource-chat-advanced-panel');"
							onmousedown="return false;"><?php _e( 'Fortgeschritten', 'psource-chat' ); ?></a></span>
				</li>
			</ul>
		</div>
		<?php $form_section = "page"; ?>
		<div class="panel_wrapper">
			<div id="psource-chat-box-appearance-panel" class="panel current">
				<?php psource_chat_form_section_information( $form_section ); ?>
				<?php psource_chat_form_section_container( $form_section ); ?>
			</div>
			<div id="psource-chat-messages-appearance-panel" class="panel">
				<?php psource_chat_form_section_messages_wrapper( $form_section ); ?>
				<?php psource_chat_form_section_messages_rows( $form_section ); ?>
			</div>
			<div id="psource-chat-messages-input-panel" class="panel">
				<?php psource_chat_form_section_messages_input( $form_section ); ?>
				<?php psource_chat_form_section_messages_send_button( $form_section ); ?>
			</div>
			<div id="psource-chat-users-list-panel" class="panel">
				<?php psource_chat_users_list( $form_section ); ?>
				<?php psource_chat_form_section_user_enter_exit_messages( $form_section ); ?>
			</div>
			<div id="psource-chat-authentication-panel" class="panel">
				<?php psource_chat_form_section_login_options( $form_section ); ?>
				<?php psource_chat_form_section_login_view_options( $form_section ); ?>
				<?php psource_chat_form_section_moderator_roles( $form_section ); ?>
			</div>
			<div id="psource-chat-advanced-panel" class="panel">
				<?php psource_chat_form_section_logs( $form_section ); ?>
				<?php psource_chat_form_section_logs_limit( $form_section ); ?>
				<?php psource_chat_form_section_session_messages( $form_section ); ?>

				<?php if ( $this->get_option( 'blocked_ip_addresses_active', 'global' ) == "enabled" ) {
					psource_chat_form_section_blocked_ip_addresses( $form_section );
				}
				psource_chat_form_section_blocked_words( $form_section );
				?>

			</div>
		</div>

		<div class="mceActionPanel">
			<div style="float: left; width: 40%;">
				<input type="button" id="cancel" name="cancel"
					value="<?php _e( 'Abbrechen', 'psource-chat' ); ?>"
					title="<?php _e( 'Änderung abbrechen und Popup schließen', 'psource-chat' ); ?>"
					onclick="tinyMCEPopup.close();"/>
			</div>

			<div style="float: right; width: 60%;">
				<input type="submit" id="reset" class="mceButton" name="reset" style="float: right;"
					value="<?php _e( 'Standards', 'psource-chat' ); ?>"
					title="<?php _e( 'Setze den Shortcode auf die Standardwerte zurück', 'psource-chat' ); ?>"/>
				<input type="submit" id="insert" name="insert" style="float: right;"
					value="<?php _e( 'Einfügen', 'psource-chat' ); ?>"
					title="<?php _e( 'Speichere die Einstellungen und füge den Shortcode am Cursor ein', 'psource-chat' ); ?>"/>
			</div>
			<br />
		</div>
	</form>
</div>
<script type="text/javascript">
	jQuery(window).on("load", function () {

		// This code takes the JS psource_chat_current_options array and applies the value to the form elements.
		for (attr in psource_chat_current_options) {
			if (attr == "id") continue;

			if (typeof psource_chat_shortcode_pairs != 'undefined' && psource_chat_shortcode_pairs[attr] != undefined)
				var attr_val = psource_chat_shortcode_pairs[attr];
			else
				var attr_val = '';

			//console.log('psource_chat_current_options['+attr+']=['+attr_val+']');

			// For checkboxes we need to build the unique ID and check the box.
			if ((attr == "login_options") || (attr == "moderator_roles")) {
				// But first we need to unset all checkboxes in the set.
				jQuery('input.chat_' + attr).each(function () {
					jQuery(this).attr('checked', false);
				});

				for (attr_value in psource_chat_current_options[attr]) {
					jQuery("#chat_" + attr + "_" + psource_chat_current_options[attr][attr_value]).attr('checked', 'checked');
				}

			} else {
				if (attr == "row_name_avatar") {
					if (psource_chat_current_options[attr] == "avatar") {
						jQuery('tr#chat_row_avatar_width_tr').show();
						jQuery('tr#chat_row_name_color_tr').hide();
						jQuery('tr#chat_row_moderator_name_color_tr').hide();

					} else if (psource_chat_current_options[attr] == "name") {
						jQuery('tr#chat_row_avatar_width_tr').hide();
						jQuery('tr#chat_row_name_color_tr').show();
						jQuery('tr#chat_row_moderator_name_color_tr').show();
					}
				} else if (attr == "users_list_show") {
					if (psource_chat_current_options[attr] == "avatar") {
						jQuery('tr#chat_users_list_avatar_width_tr').show();
						jQuery('tr#chat_users_list_name_color_tr').hide();
						jQuery('tr#chat_users_list_font_family_tr').hide();
						jQuery('tr#chat_users_list_font_size_tr').hide();

					} else if (psource_chat_current_options[attr] == "name") {
						jQuery('tr#chat_users_list_avatar_width_tr').hide();
						jQuery('tr#chat_users_list_name_color_tr').show();
						jQuery('tr#chat_users_list_font_family_tr').show();
						jQuery('tr#chat_users_list_font_size_tr').show();
					}
				} else if (attr == "log_display") {
					jQuery("#chat_" + attr).val(psource_chat_current_options[attr]);
				}
				jQuery("#chat_" + attr).val(psource_chat_current_options[attr]);
				if (jQuery("input#chat_" + attr).hasClass('pickcolor_input')) {
					jQuery("#chat_" + attr).attr('value', psource_chat_current_options[attr]);
					jQuery("#chat_" + attr).attr('data-default-color', psource_chat_current_options[attr]);
					jQuery("#chat_" + attr).css('background-color', psource_chat_current_options[attr]);

				}
			}
		}
	});
</script>
<?php
// Force print of tooltip JS/CSS
$this->tips->initialize();
?>
</body>
</html>
<?php
exit( 0 );