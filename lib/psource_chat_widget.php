<?php
if (!class_exists('PSOURCEChatWidget')) {

	class PSOURCEChatWidget extends WP_Widget {

		var $defaults = array();

		function __construct () {
			global $psource_chat;

			// Set defaults
			// ...
			$this->defaults = array(
				'box_title' 		=> 	'',
				'id'				=>	'',
				'box_height'		=>	'300px',
				'box_sound'			=>	'disabled',
				'row_name_avatar'	=>	'avatar',
				'box_emoticons'		=>	'disabled',
				'row_date'			=>	'disabled',
				'row_date_format'	=>	get_option('date_format'),
				'row_time'			=>	'disabled',
				'row_time_format'	=>	get_option('time_format'),
			);

			$widget_ops = array('classname' => __CLASS__, 'description' => __('PSC-Chat Widget, fügt der Seitenleiste einen Chat hinzu.', 'psource-chat'));
			parent::__construct(__CLASS__, __('PSC-Chat Widget', 'psource-chat'), $widget_ops);
		}

		function PSOURCEChatWidget () {
			$this->__construct();
		}

		function convert_settings_keys($instance) {

			if (isset($instance['title'])) {
				$instance['box_title'] = $instance['title'];
				unset($instance['title']);
			}

			if (isset($instance['height'])) {
				$instance['box_height'] = $instance['height'];
				unset($instance['height']);
			}

			if (isset($instance['sound'])) {
				$instance['box_sound'] = $instance['sound'];
				unset($instance['sound']);
			}

			if (isset($instance['avatar'])) {
				$instance['row_name_avatar'] = $instance['avatar'];
				unset($instance['avatar']);
			}

			return $instance;
		}

		function form($instance) {
			global $psource_chat;

			$instance = wp_parse_args( $this->convert_settings_keys($instance), $this->defaults );
			//echo "instance<pre>"; print_r($instance); echo "</pre>";

			//if (empty($instance['height'])) {
			//	$instance['height'] = "300px";
			//}

			?>
			<input type="hidden" name="<?php echo $this->get_field_name('id'); ?>" id="<?php echo $this->get_field_id('id'); ?>"
				class="widefat" value="<?php echo $instance['id'] ?> "/>
			<p>
				<label for="<?php echo $this->get_field_id('box_title') ?>"><?php _e('Title:', 'psource-chat'); ?></label>
				<input type="text" name="<?php echo $this->get_field_name('box_title'); ?>" id="<?php echo $this->get_field_id('box_title'); ?>"
					class="widefat" value="<?php echo $instance['box_title'] ?>" />
			</p>

			<p>
				<label for="<?php echo $this->get_field_id( 'box_height' ); ?>"><?php
					_e('Höhe des Widgets:', 'psource-chat'); ?></label>

				<input type="text" id="<?php echo $this->get_field_id( 'box_height' ); ?>" value="<?php echo $instance['box_height']; ?>"
					name="<?php echo $this->get_field_name( 'box_height'); ?>" class="widefat" style="width:100%;" />
					<span class="description"><?php _e('Die Breite beträgt 100% des Widget-Bereichs', 'psource-chat'); ?></span>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'box_sound' ); ?>"><?php
										_e('Aktiviere Sound', 'psource-chat'); ?></label><br />
				<select id="<?php echo $this->get_field_id( 'box_sound' ); ?>" name="<?php echo $this->get_field_name('box_sound'); ?>">
					<option value="enabled" <?php print ($instance['box_sound'] == 'enabled')?'selected="selected"':''; ?>><?php
						_e("Aktiviert", 'psource-chat'); ?></option>
					<option value="disabled" <?php print ($instance['box_sound'] == 'disabled')?'selected="selected"':''; ?>><?php
						_e("Deaktiviert", 'psource-chat'); ?></option>
				</select>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'row_name_avatar' ); ?>"><?php _e("Zeige Avatar/Name", 'psource-chat'); ?></label><br />
				<select id="<?php echo $this->get_field_id( 'row_name_avatar' ); ?>" name="<?php echo $this->get_field_name( 'row_name_avatar' ); ?>" >
					<option value="avatar" <?php print ($instance['row_name_avatar'] == 'avatar')?'selected="selected"':''; ?>><?php
					 	_e("Avatar", 'psource-chat'); ?></option>
					<option value="name" <?php print ($instance['row_name_avatar'] == 'name')?'selected="selected"':''; ?>><?php
						_e("Name", 'psource-chat'); ?></option>
					<option value="name-avatar" <?php print ($instance['row_name_avatar'] == 'name-avatar')?'selected="selected"':''; ?>><?php
					 	_e("Avatar und Name", 'psource-chat'); ?></option>
					<option value="disabled" <?php print ($instance['row_name_avatar'] == 'disabled')?'selected="selected"':''; ?>><?php
					 	_e("Nichts", 'psource-chat'); ?></option>
				</select>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'box_emoticons' ); ?>"><?php
										_e('Zeige Emoticons', 'psource-chat'); ?></label><br />
				<select id="<?php echo $this->get_field_id( 'box_emoticons' ); ?>" name="<?php echo $this->get_field_name( 'box_emoticons'); ?>">
					<option value="enabled" <?php print ($instance['box_emoticons'] == 'enabled')?'selected="selected"':''; ?>><?php
						_e("Aktiviert", 'psource-chat'); ?></option>
					<option value="disabled" <?php print ($instance['box_emoticons'] == 'disabled')?'selected="selected"':''; ?>><?php
						_e("Deaktiviert", 'psource-chat'); ?></option>
				</select>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'row_date' ); ?>"><?php
										_e('Zeige Datum', 'psource-chat'); ?></label><br />
				<select id="<?php echo $this->get_field_id( 'row_date' ); ?>" name="<?php echo $this->get_field_name( 'row_date'); ?>">
					<option value="enabled" <?php print ($instance['row_date'] == 'enabled')?'selected="selected"':''; ?>><?php
						_e("Aktiviert", 'psource-chat'); ?></option>
					<option value="disabled" <?php print ($instance['row_date'] == 'disabled')?'selected="selected"':''; ?>><?php
						_e("Deaktivert", 'psource-chat'); ?></option>
				</select> <input id="<?php echo $this->get_field_id( 'row_date_format' ); ?>" type="text" style="width:100px;" name="<?php echo $this->get_field_name( 'row_date_format'); ?>" value="<?php echo $instance['row_date_format']; ?>"/>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'row_time' ); ?>"><?php
					_e('Zeige Uhrzeit', 'psource-chat'); ?></label><br />
				<select id="<?php echo $this->get_field_id( 'row_time' ); ?>" name="<?php echo $this->get_field_name( 'row_time'); ?>">
					<option value="enabled" <?php print ($instance['row_time'] == 'enabled')?'selected="selected"':''; ?>><?php
						_e("Aktiviert", 'psource-chat'); ?></option>
					<option value="disabled" <?php print ($instance['row_time'] == 'disabled')?'selected="selected"':''; ?>><?php
						_e("Deaktiviert", 'psource-chat'); ?></option>
				</select> <input id="<?php echo $this->get_field_id( 'row_time_format' ); ?>" type="text" style="width:100px;" name="<?php echo $this->get_field_name( 'row_time_format'); ?>" value="<?php echo $instance['row_time_format']; ?>"/>
			</p>
			<p><?php _e('Mehr Kontrolle über Widgets-Optionen über', 'psource-chat')?> <a
				href="<?php echo admin_url( 'admin.php?page=chat_settings_panel_widget'); ?>"><?php _e('Widget Einstellungen', 'psource-chat'); ?></a></p>
			<?php
		}

		function update($new_instance, $old_instance) {
			global $psource_chat;

			$instance = $old_instance;
			$instance = $this->convert_settings_keys($instance);

			if (isset($new_instance['box_title'])) {
				$instance['box_title'] 			= strip_tags($new_instance['box_title']);
			}

			if (isset($new_instance['box_height'])) {
				$instance['box_height'] 		= esc_attr($new_instance['box_height']);
			}

			if (isset($new_instance['box_sound']))
				{$instance['box_sound'] 			= esc_attr($new_instance['box_sound']);}

			if (isset($new_instance['row_name_avatar']))
				{$instance['row_name_avatar'] 	= esc_attr($new_instance['row_name_avatar']);}

			if (isset($new_instance['box_emoticons']))
				{$instance['box_emoticons'] 		= esc_attr($new_instance['box_emoticons']);}

			if (isset($new_instance['row_date']))
				{$instance['row_date'] 			= esc_attr($new_instance['row_date']);}

			if (isset($new_instance['row_date_format']))
				{$instance['row_date_format'] 	= esc_attr($new_instance['row_date_format']);}

			if (isset($new_instance['row_time']))
				{$instance['row_time'] 			= esc_attr($new_instance['row_time']);}

			if (isset($new_instance['row_time_format']))
				{$instance['row_time_format'] 	= esc_attr($new_instance['row_time_format']);}

			return $instance;
		}

		function widget($args, $instance) {
			global $psource_chat, $post, $bp;

			if ($psource_chat->get_option('blocked_on_shortcode', 'widget') == "enabled") {
				if (strstr($post->post_content, '[chat ') !== false)
					{return;}
			}

			if ((isset($bp->groups->current_group->id)) && (intval($bp->groups->current_group->id))) {

				// Are we viewing the Group Admin screen?
				$bp_group_admin_url_path 	= parse_url(bp_get_group_admin_permalink($bp->groups->current_group), PHP_URL_PATH);
				$request_url_path 			= parse_url(get_option('siteurl') . $_SERVER['REQUEST_URI'], PHP_URL_PATH);

				if ( (!empty($request_url_path)) && (!empty($bp_group_admin_url_path))
			  	  && (substr($request_url_path, 0, strlen($bp_group_admin_url_path)) == $bp_group_admin_url_path) ) {
					if ($psource_chat->get_option('bp_group_admin_show_widget', 'global') != "enabled") {
						return;
					}
				} else {
					if ($psource_chat->get_option('bp_group_show_widget', 'global') != "enabled") {
						return;
					}
				}
			}

			if ($psource_chat->_chat_plugin_settings['blocked_urls']['widget'] != true) {

				$instance['id'] = $this->id;
				//echo "instance before<pre>"; print_r($instance); echo "</pre>";
				//die();
				$instance = wp_parse_args( $this->convert_settings_keys($instance), $this->defaults );
				//echo "instance<pre>"; print_r($instance); echo "</pre>";

				$instance['session_type'] = 'widget';
				$chat_output = $psource_chat->process_chat_shortcode($instance);
				if (!empty($chat_output)) {
					echo $args['before_widget'];

					$title = apply_filters('widget_title', $instance['box_title']);
					if ($title) {echo $args['before_title'] . $title . $args['after_title'];}

					echo $chat_output;

					echo $args['after_widget'];
				}
			}
		}
	}
}

if (!class_exists('PSOURCEChatFriendsWidget')) {

	class PSOURCEChatFriendsWidget extends WP_Widget {

		var $defaults = array();
		var $plugin_error_message;

		function __construct () {
			global $psource_chat;

			$this->defaults = array(
				'box_title' 		=> 	'',
				'height'			=>	'300px',
				'row_name_avatar'	=>	'avatar',
				'avatar_width'		=>	'25px'
			);

			$this->plugin_error_message = __('Für dieses Widget ist CP Community, BuddyPress Friends oder das PS Freunde Plugin erforderlich.', 'psource-chat');

			// Set defaults
			// ...
			$widget_ops = array('classname' => __CLASS__, 'description' => __('Zeigt Chat-Freunde und Status an. (CP Community, BuddyPress oder PS Freunde Plugin erforderlich)', 'psource-chat'));
			parent::__construct(__CLASS__, __('PSC-Chat Freunde', 'psource-chat'), $widget_ops);
		}

		function PSOURCEChatFriendsWidget () {
			$this->__construct();
		}

		function form($instance) {
			global $psource_chat, $bp;

			// Check if any supported friends system is available
			$has_cp_community = function_exists('cpc_get_friends');
			$has_buddypress = !empty($bp) && function_exists('bp_get_friend_ids');
			$has_friends_plugin = is_plugin_active('friends/friends.php') || 
								(is_multisite() && is_plugin_active_for_network('friends/friends.php'));

			if (!$has_cp_community && !$has_buddypress && !$has_friends_plugin) {
				?><p class="error"><?php echo $this->plugin_error_message; ?></p>
				<p class="description"><?php _e('Status: CP Community, BuddyPress und PS Freunde Plugin nicht erkannt.', 'psource-chat'); ?></p>
				<?php
			} else {
				// Show which system is active
				$active_systems = array();
				if ($has_cp_community) $active_systems[] = 'CP Community';
				if ($has_buddypress) $active_systems[] = 'BuddyPress';
				if ($has_friends_plugin) $active_systems[] = 'PS Freunde Plugin';
				
				?><p class="info"><?php 
					printf(__('Aktive Freunde-Systeme: %s', 'psource-chat'), implode(', ', $active_systems)); 
				?></p><?php
			}
			$instance = wp_parse_args( $instance, $this->defaults );

			?>
			<p class="info"><?php _e('Dieses Widget zeigt Informationen an, die für den von WordPress authentifizierten Benutzer spezifisch sind. Wenn der Benutzer nicht authentifiziert ist, gibt das Widget nichts aus.', 'psource-chat');?></p>
				<input type="hidden" name="<?php echo $this->get_field_name('id'); ?>" id="<?php echo $this->get_field_id('id'); ?>"
					class="widefat" value="<?php echo isset($instance['id']) ? esc_attr($instance['id']) : ''; ?>"/>
			<p>
				<label for="<?php echo $this->get_field_id('box_title') ?>"><?php _e('widgettitel:', 'psource-chat'); ?></label>
				<input type="text" name="<?php echo $this->get_field_name('box_title'); ?>" id="<?php echo $this->get_field_id('box_title'); ?>"
					class="widefat" value="<?php echo $instance['box_title'] ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'height' ); ?>"><?php
					_e('Höhe für Widget:', 'psource-chat'); ?></label>

				<input type="text" id="<?php echo $this->get_field_id( 'height' ); ?>" value="<?php echo $instance['height']; ?>"
					name="<?php echo $this->get_field_name( 'height'); ?>" class="widefat" style="width:100%;" />
					<span class="description"><?php _e('Das Widget scrollt bei Bedarf durch die Ausgabe.', 'psource-chat'); ?></span>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'row_name_avatar' ); ?>"><?php _e("Avatar/Name anzeigen", 'psource-chat'); ?></label><br />
				<select id="<?php echo $this->get_field_id( 'row_name_avatar' ); ?>" name="<?php echo $this->get_field_name( 'row_name_avatar' ); ?>" >
					<option value="avatar" <?php print ($instance['row_name_avatar'] == 'avatar')?'selected="selected"':''; ?>><?php
					 	_e("Avatar", 'psource-chat'); ?></option>
					<option value="name" <?php print ($instance['row_name_avatar'] == 'name')?'selected="selected"':''; ?>><?php
						_e("Name", 'psource-chat'); ?></option>
					<option value="name-avatar" <?php print ($instance['row_name_avatar'] == 'name-avatar')?'selected="selected"':''; ?>><?php
					 	_e("Avatar und Name", 'psource-chat'); ?></option>
				</select>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'avatar_width' ); ?>"><?php
					_e('Avatar Breite/Höhe:', 'psource-chat'); ?></label>

				<input type="text" id="<?php echo $this->get_field_id( 'avatar_width' ); ?>" value="<?php echo $instance['avatar_width']; ?>"
					name="<?php echo $this->get_field_name( 'avatar_width'); ?>" class="widefat" style="width:100%;" />
			</p>

			<?php
		}

		function update($new_instance, $old_instance) {
			global $psource_chat;

			$instance = $old_instance;

			if (isset($new_instance['box_title']))
				{$instance['box_title'] 			= strip_tags($new_instance['box_title']);}

			if (isset($new_instance['height']))
				{$instance['height'] 		= esc_attr($new_instance['height']);}

			if (isset($new_instance['row_name_avatar']))
				{$instance['row_name_avatar'] 	= esc_attr($new_instance['row_name_avatar']);}


			if (isset($new_instance['avatar_width']))
				{$instance['avatar_width'] 		= esc_attr($new_instance['avatar_width']);}

			return $instance;
		}

		function widget($args, $instance) {
			global $psource_chat, $bp, $current_user;

			if (!$current_user->ID) {return;}

			// IF we are blocking the Widgets from Chat
			if ($psource_chat->_chat_plugin_settings['blocked_urls']['widget'] == true) {
				return;
			}

			// Check for supported friends systems - prioritize CP Community
			$friends_list_ids = array();
			$friends_source = '';

			// 1. Try CP Community first (modern system)
			if (function_exists('cpc_get_friends')) {
				try {
					$cp_friends = cpc_get_friends($current_user->ID, false);
					if (is_array($cp_friends) && !empty($cp_friends)) {
						// CP Community returns array of arrays with 'ID' key
						$friends_list_ids = array_map(function($friend) {
							return $friend['ID'];
						}, $cp_friends);
						$friends_source = 'CP Community';
					}
				} catch (Exception $e) {
					error_log('PS Chat Widget: CP Community friends error - ' . $e->getMessage());
				}
			}

			// 2. Fallback to BuddyPress
			if (empty($friends_list_ids) && !empty($bp) && function_exists('bp_get_friend_ids')) {
				$friends_ids = bp_get_friend_ids($bp->loggedin_user->id);
				if (!empty($friends_ids)) {
					$friends_list_ids = explode(',', $friends_ids);
					$friends_source = 'BuddyPress';
				}
			}

			// 3. Final fallback to PS Friends Plugin
			if (empty($friends_list_ids)) {
				if ((!is_admin()) && (!function_exists('is_plugin_active'))) {
					include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				}

				$has_friends_plugin = is_plugin_active('friends/friends.php') || 
									(is_multisite() && is_plugin_active_for_network('friends/friends.php'));
				
				if ($has_friends_plugin && function_exists('friends_get_list')) {
					$friends_list_ids = friends_get_list($current_user->ID);
					$friends_source = 'PS Freunde Plugin';
				}
			}

			// If no friends system is available or no friends found, return
			if (empty($friends_list_ids) || !is_array($friends_list_ids)) {
				// Debug output for admins
				if (current_user_can('manage_options')) {
					echo '<!-- PS Chat Friends Widget: No friends found. Source checked: ';
					if (function_exists('cpc_get_friends')) echo 'CP Community ';
					if (!empty($bp)) echo 'BuddyPress ';
					if (function_exists('friends_get_list')) echo 'PS Friends ';
					echo '-->';
				}
				return;
			}

			if ($psource_chat->_chat_plugin_settings['blocked_urls']['widget'] == true)
				{return;}

			$instance['id'] = $this->id;
			$instance = wp_parse_args( $instance, $this->defaults );

			$chat_output = '';

			$friends_status = psource_chat_get_friends_status($current_user->ID, $friends_list_ids);
			if ( ($friends_status) && (is_array($friends_status)) && (count($friends_status)) ) {
				foreach($friends_status as $friend) {
					if ((isset($friend->chat_status)) && ($friend->chat_status == "available")) {
						$friend_status_data = psource_chat_get_chat_status_data($current_user->ID, $friend);

						$chat_output .= '<li><a class="'. $friend_status_data['href_class'] .'" title="'. $friend_status_data['href_title'] .' - '. __('Chat-Sitzung starten', 'psource-chat') .'" href="#" rel="'.md5($friend->ID) .'">';

						//$chat_output .= '<span class="psource-chat-ab-icon psource-chat-ab-icon-'. $friend->chat_status .'"></span>';

						if (($instance['row_name_avatar'] == "name-avatar") || ($instance['row_name_avatar'] == "avatar")) {
							// Use modern avatar system with CP Community support
							$friend->avatar	= PSource_Chat_Avatar::get_avatar($friend->ID, intval($instance['avatar_width']), true);
							if (!empty($friend->avatar)) {
								$chat_output .= '<span class="psource-chat-friend-avatar">'. $friend->avatar .'</span>';
							}
						}
						if ($instance['row_name_avatar'] == "name-avatar") {
							$chat_name_spacer_style = ' style="margin-left: 3px;" ';
						} else {
							$chat_name_spacer_style = '';
						}
						if (($instance['row_name_avatar'] == "name-avatar") || ($instance['row_name_avatar'] == "name")) {
							$chat_output .= '<span '. $chat_name_spacer_style .' class="psource-chat-ab-label">'. $friend->display_name .'</span>';
						}
						$chat_output .= '</a></li>';
					}
				}
				if (!empty($chat_output)) {
					if ((isset($instance['height'])) && (!empty($instance['height']))) {
						$height_style = ' style="max-height: '. $instance['height'] .'; overflow:auto;" ';
					} else {
						$height_style = '';
					}
					$chat_output = '<ul id="psource-chat-friends-widget-'. $this->number .'" '. $height_style .' class="psource-chat-friends-widget">'. $chat_output .'</ul>';
				}

			} else {
				$message = __("Keine Freunde online.", 'psource-chat');
				
				// Add debug info for admins
				if (current_user_can('manage_options') && !empty($friends_source)) {
					$message .= ' <!-- Freunde-Quelle: ' . $friends_source . ' -->';
				}
				
				$chat_output = '<p>'. $message .'</p>';
			}

			if (!empty($chat_output)) {
				echo $args['before_widget'];

				$title = apply_filters('widget_title', $instance['box_title']);
				if ($title) {echo $args['before_title'] . $title . $args['after_title'];}

				// Add debug info for admins about friends source
				if (current_user_can('manage_options') && !empty($friends_source)) {
					echo '<!-- PS Chat Friends Widget: Aktive Quelle: ' . $friends_source . ' -->';
				}

				echo $chat_output;

				echo $args['after_widget'];
			}
		}
	}
}


if (!class_exists('PSOURCEChatRoomsWidget')) {

	class PSOURCEChatRoomsWidget extends WP_Widget {

		var $defaults = array();
		var $plugin_error_message;

		function __construct () {
			global $psource_chat, $bp;

			$this->defaults = array(
				'box_title' 				=> 	__('Chat-Räume', 'psource-chat'),
				'height'					=>	'300px',
				'show_active_user_count'	=>	'enabled',
				'show_title'				=>	'chat',
				'session_types'				=>	array(
					'page'		=>	'on',
				),
				'session_types_labels'		=>	array(
					'page'		=>	__('Seite', 'psource-chat'),
				)

			);

			if ((!empty($bp)) && (is_object($bp))) {
				$this->defaults['session_types_labels']['bp-group'] =	__('BuddyPress Gruppe', 'psource-chat');
				$this->defaults['session_types']['bp-group'] 		=	'on';
			}

			$this->plugin_error_message = __('Für dieses Widget sind entweder BuddyPress Friends aktiviert oder Friends Plugins.', 'psource-chat');

			// Standardwerte festlegen
			// ...
			$widget_ops = array('classname' => __CLASS__, 'description' => __('Zeigt aktive Chatsitzungen der gesamten Seite an.', 'psource-chat'));
			parent::__construct(__CLASS__, __('PSC-Chat Räume', 'psource-chat'), $widget_ops);
		}

		function PSOURCEChatRoomsWidget () {
			$this->__construct();
		}

		function form($instance) {
			global $psource_chat, $bp;

			// Überprüft, ob der Schlüssel „session_types“ im Array „$instance“ vorhanden ist, bevor darauf zugegriffen wird
			$session_types = isset($instance['session_types']) ? $instance['session_types'] : array();

			$instance = wp_parse_args($instance, $this->defaults);

			// Weiset den Schlüssel „session_types“ aus der zuvor gespeicherten Variablen „$session_types“ neu zu
			$instance['session_types'] = $session_types;

			?>
			<p class="info"><?php _e('Dieses Widget zeigt alle aktiven Chat-Sitzungen auf der Webseite an.', 'psource-chat');?></p>
				<input type="hidden" name="<?php echo $this->get_field_name('id'); ?>" id="<?php echo $this->get_field_id('id'); ?>"
					class="widefat" value="<?php echo isset($instance['id']) ? esc_attr($instance['id']) : ''; ?>"/>
			<p>
				<label for="<?php echo $this->get_field_id('box_title') ?>"><?php _e('Widgettitel:', 'psource-chat'); ?></label>
				<input type="text" name="<?php echo $this->get_field_name('box_title'); ?>" id="<?php echo $this->get_field_id('box_title'); ?>"
					class="widefat" value="<?php echo $instance['box_title'] ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'height' ); ?>"><?php
					_e('Height for widget:', 'psource-chat'); ?></label>

				<input type="text" id="<?php echo $this->get_field_id( 'height' ); ?>" value="<?php echo $instance['height']; ?>"
					name="<?php echo $this->get_field_name( 'height'); ?>" class="widefat" style="width:100%;" />
					<span class="description"><?php _e('Das Widget scrollt bei Bedarf durch die Ausgabe.', 'psource-chat'); ?></span>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'show_active_user_count' ); ?>"><?php
										_e('Anzahl aktiver Benutzer anzeigen', 'psource-chat'); ?></label><br />
				<select id="<?php echo $this->get_field_id( 'show_active_user_count' ); ?>" name="<?php echo $this->get_field_name('show_active_user_count'); ?>">
					<option value="enabled" <?php print ($instance['show_active_user_count'] == 'enabled')?'selected="selected"':''; ?>><?php
						_e("Aktiviert", 'psource-chat'); ?></option>
					<option value="disabled" <?php print ($instance['show_active_user_count'] == 'disabled')?'selected="selected"':''; ?>><?php
						_e("Deaktiviert", 'psource-chat'); ?></option>
				</select>
			</p>

			<p>
				<label for="<?php echo $this->get_field_id('session_types'); ?>"><?php _e('Sitzungstypen einschließen - mindestens einen auswählen'); ?></label><br />
				<ul>
				<?php

				if ((empty($bp)) || (!is_object($bp))) {
					if (isset($instance['session_types']['bp-group'])) {
						unset($instance['session_types']['bp-group']);
					}
				}

				if (!empty($instance['session_types']) && is_array($instance['session_types'])) {
					if (count($instance['session_types']) == 0) {
						$instance['session_types']['page'] = 'on';
					}

					foreach ($this->defaults['session_types'] as $session_type_slug => $session_type_active) {
						?><li><input id="<?php echo $this->get_field_id('session_types'); ?>-<?php echo $session_type_slug ?>" name="<?php echo $this->get_field_name('session_types'); ?>[<?php echo $session_type_slug ?>]" type="checkbox" <?php
						checked(isset($instance['session_types'][$session_type_slug]) && $instance['session_types'][$session_type_slug] === 'on', true) ?> />&nbsp;<label for="<?php echo $this->get_field_id('session_types'); ?>-<?php echo $session_type_slug ?>"><?php
						if (isset($this->defaults['session_types_labels'][$session_type_slug])) {
							echo $this->defaults['session_types_labels'][$session_type_slug];
						} else {
							echo $session_type_slug;
						}
						?></label></li><?php
					}
				}
				?>
				</ul>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'show_title' ); ?>"><?php _e('Link-Titel aus der Chat-Sitzung oder der Seite/Gruppe anzeigen', 'psource-chat'); ?></label><br />
				<select id="<?php echo $this->get_field_id( 'show_title' ); ?>" name="<?php echo $this->get_field_name('show_title'); ?>">
					<option value="page" <?php selected($instance['show_title'], 'page') ?>><?php
						_e("Seite/Gruppe", 'psource-chat'); ?></option>
					<option value="chat" <?php selected($instance['show_title'], 'chat') ?>><?php
						_e("Chat-Sitzung", 'psource-chat'); ?></option>
				</select>
			</p>


			<?php
		}

		function update($new_instance, $old_instance) {
			global $psource_chat;

			//$instance = $old_instance;

			//echo "new_instance<pre>"; print_r($new_instance); echo "</pre>";

			if (isset($new_instance['box_title']))
				{$instance['box_title'] 			= strip_tags($new_instance['box_title']);}
			else

			if (isset($new_instance['height']))
				{$instance['height'] 			= esc_attr($new_instance['height']);}

			if (isset($new_instance['show_active_user_count']))
				{$instance['show_active_user_count'] 	= strip_tags($new_instance['show_active_user_count']);}

			if (isset($new_instance['session_types']))
				{$instance['session_types'] 		= $new_instance['session_types'];}

			if (isset($new_instance['show_title']))
				{$instance['show_title'] 		= esc_attr($new_instance['show_title']);}


			//echo "instance<pre>"; print_r($instance); echo "</pre>";
			//die();

			return $instance;
		}

		function widget($args, $instance) {
			global $psource_chat, $bp, $current_user;

			if (!$current_user->ID) {return;}

			// IF we are blocking the Widgets from Chat
			if ($psource_chat->_chat_plugin_settings['blocked_urls']['widget'] == true) {
				return;
			}

			// If BuddyPress or Friends plugins is not active
//			if ((empty($bp)) && (!is_plugin_active('friends/friends.php'))
//			 && ((is_multisite()) && (!is_plugin_active_for_network('friends/friends.php')))) {
//				 return;
//			}

			$instance['id'] = $this->id;

			$session_types = $instance['session_types'];
			$instance = wp_parse_args( $instance, $this->defaults );
			$instance['session_types'] = $session_types;

			if ((empty($bp)) || (!is_object($bp))) {
				if (isset($instance['session_types']['bp-group'])) {
					unset($instance['session_types']['bp-group']);
				}
			}

			$chat_output = '';

			$chat_sessions = psource_chat_get_active_sessions($instance['session_types']);
			//echo "chat_sessions<pre>"; print_r($chat_sessions); echo "</pre>";

			if ((isset($instance['show_active_user_count'])) && ($instance['show_active_user_count'] == 'enabled')) {
				$chat_sessions_users = psource_chat_get_active_sessions_users($chat_sessions);
				//echo "chat_sessions_users<pre>"; print_r($chat_sessions_users); echo "</pre>";
			}

			if (!empty($chat_sessions)) {
				echo $args['before_widget'];

				$title = apply_filters('widget_title', $instance['box_title']);
				if ($title) {echo $args['before_title'] . $title . $args['after_title'];}

				?><ul class="psource-chat-active-chats-list"><?php
				foreach($chat_sessions as $chat_session) {
					?><li><a href="<?php
						if (isset($chat_session['session_url'])) {
							echo $chat_session['session_url'];
						} else {
							echo "#";
						}
					?>"><?php
					//echo "session_title[". $chat_session['session_title'] ."]<br />";
					//echo "session_url[". $chat_session['session_url'] ."]<br />";

					$link_title = '';
					if ((empty($link_title)) && ($instance['show_title'] == 'chat')
					 && (isset($chat_session['box_title'])) && (!empty($chat_session['box_title']))) {
						$link_title = $chat_session['box_title'];
					}

					if ((empty($link_title)) && ($instance['show_title'] == 'page')
					 && (isset($chat_session['session_title'])) && (!empty($chat_session['session_title']))) {
						$link_title = $chat_session['session_title'];
					}

					if (empty($link_title)) {
						$link_title = $chat_session['id'];
					}
					echo $link_title;
					?></a><?php

					if ((isset($instance['show_active_user_count'])) && ($instance['show_active_user_count'] == 'enabled')) {
						?> (<?php
						if (isset($chat_sessions_users[$chat_session['id']])) {
							echo $chat_sessions_users[$chat_session['id']];
						} else {
							echo "0";
						}
						?>)<?php
					}

					?></li><?php
				}
				?></ul><?php


				echo $args['after_widget'];
			}
		}
	}
}


if (!class_exists('PSOURCEChatStatusWidget')) {

	class PSOURCEChatStatusWidget extends WP_Widget {

		var $defaults = array();
		var $plugin_error_message;

		function __construct () {
			global $psource_chat;

			$this->defaults = array(
				'box_title' 		=> 	'',
			);

			$widget_ops = array('classname' => __CLASS__, 'description' => __('Mit diesem Widget können Benutzer ihren Chat-Status über ein Seitenleisten-Widget festlegen.', 'psource-chat'));
			parent::__construct(__CLASS__, __('PSC-Chat Status', 'psource-chat'), $widget_ops);
		}

		function PSOURCEChatStatusWidget () {
			$this->__construct();
		}

		function form($instance) {
			global $psource_chat;
		
			// Legt Standardwerte für die Widget-Instanz fest
			$instance = wp_parse_args($instance, $this->defaults);
		
			?>
			<p class="info"><?php _e('Dieses Widget zeigt Informationen an, die für den von WordPress authentifizierten Benutzer spezifisch sind. Wenn der Benutzer nicht authentifiziert ist, gibt das Widget nichts aus.', 'psource-chat');?></p>
		
			<input type="hidden" name="<?php echo $this->get_field_name('id'); ?>" id="<?php echo $this->get_field_id('id'); ?>"
				   class="widefat" value="<?php echo isset($instance['id']) ? esc_attr($instance['id']) : ''; ?>"/>
			<p>
				<label for="<?php echo $this->get_field_id('box_title') ?>"><?php _e('Widgettitel:', 'psource-chat'); ?></label>
				<input type="text" name="<?php echo $this->get_field_name('box_title'); ?>" id="<?php echo $this->get_field_id('box_title'); ?>"
					   class="widefat" value="<?php echo isset($instance['box_title']) ? esc_attr($instance['box_title']) : ''; ?>" />
			</p>
			<?php
		}

		function update($new_instance, $old_instance) {
			//global $psource_chat;

			$instance = $old_instance;

			if (isset($new_instance['box_title']))
				{$instance['box_title'] 			= strip_tags($new_instance['box_title']);}

			if (isset($new_instance['box_height']))
				{$instance['box_height'] 		= esc_attr($new_instance['box_height']);}

			return $instance;
		}

		function widget($args, $instance) {
			global $psource_chat, $bp, $current_user;

			if (!$current_user->ID) {return;}

			// IF we are blocking the Widgets from Chat
			if ($psource_chat->_chat_plugin_settings['blocked_urls']['widget'] == true) {
				return;
			}

			$chat_output = '';

			//echo "user-statuses<pre>"; print_r($psource_chat->_chat_options['user-statuses']); echo "</pre>";
//			echo "current_user ID[". $current_user->ID ."]<br />";

			$chat_user_status = psource_chat_get_user_status($current_user->ID);
//			echo "chat_user_status[". $chat_user_status ."]<br />";

			foreach($psource_chat->_chat_options['user-statuses'] as $status_key => $status_label) {
				if ($status_key == $chat_user_status) {
					$selected = ' selected="selected" ';
				} else {
					$selected = '';
				}

				$class = '';
				if ($status_key == 'available')
					{$class .= ' available';}

				$chat_output .= '<option class="'. $class .'" value="'. $status_key .'" '. $selected .'>'. $status_label .'</option>';
			}

			if (!empty($chat_output)) {

				echo $args['before_widget'];

				$title = apply_filters('widget_title', $instance['box_title']);
				if ($title) {echo $args['before_title'] . $title . $args['after_title'];}

				echo '<select id="psource-chat-status-widget-'. $this->number .'" class="psource-chat-status-widget">'. $chat_output .'</select>';

				echo $args['after_widget'];
			}
		}
	}
}

function psource_chat_widget_init_proc() {
	register_widget('PSOURCEChatWidget');
	register_widget('PSOURCEChatFriendsWidget');
	register_widget('PSOURCEChatStatusWidget');
	register_widget('PSOURCEChatRoomsWidget');
}
add_action( 'widgets_init', 'psource_chat_widget_init_proc');

if (!class_exists('PSOURCEChatDashboardWidget')) {

	class PSOURCEChatDashboardWidget {
		var $instance = array();

		function __construct() {

			if (is_network_admin()) {
				if ( !function_exists( 'is_plugin_active_for_network' ) ) {
					require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
				}
				if (!is_plugin_active_for_network('psource-chat/psource-chat.php')) {return;}

				add_action( 'wp_network_dashboard_setup', array(&$this, 'psource_chat_add_dashboard_widgets') );
			} else {
				add_action( 'wp_dashboard_setup', array(&$this, 'psource_chat_add_dashboard_widgets') );
			}
		}

		function PSOURCEChatDashboardWidget() {
			$this->__construct();
		}

		function psource_chat_add_dashboard_widgets() {
			global $psource_chat, $blog_id, $current_user;

			$this->instance = $psource_chat->_chat_options['dashboard'];

			$user_meta = get_user_meta( $current_user->ID, 'psource-chat-user', true );
			$user_meta = wp_parse_args( $user_meta, $psource_chat->_chat_options_defaults['user_meta'] );

			if ($user_meta['chat_wp_admin'] == "enabled") {

				if (is_network_admin()) {

					if ((isset($this->instance['dashboard_widget'])) && ($this->instance['dashboard_widget'] == 'enabled')
					 && (isset($user_meta['chat_network_dashboard_widget'])) && ($user_meta['chat_network_dashboard_widget'] == 'enabled')) {

						$this->instance['id'] 					= 'dashboard-0';
						$this->instance['blog_id'] 				= 0;
						$this->instance['box_class']			= 'psource-chat-dashboard-widget';
						$this->instance['session_status']		= 'open';

						if ((!isset($this->instance['dashboard_widget_title'])) || (empty($this->instance['dashboard_widget_title']))) {
							$this->instance['dashboard_widget_title'] = __('Chat', 'psource-chat');
						}

						if ((isset($user_meta['chat_dashboard_widget_height'])) && (!empty($user_meta['chat_dashboard_widget_height']))) {
							$this->instance['box_height'] = $user_meta['chat_dashboard_widget_height'];

						} else if ((!isset($this->instance['dashboard_widget_height'])) || (empty($this->instance['dashboard_widget_height']))) {
							$this->instance['box_height'] = $this->instance['dashboard_widget_height'];
						}

						wp_add_dashboard_widget(
							'psource_chat_dashboard_widget',
							$this->instance['dashboard_widget_title'],
							array(&$this, 'psource_chat_dashboard_widget_proc')
						);
					}
				} else {

					if ((isset($this->instance['dashboard_widget'])) && ($this->instance['dashboard_widget'] == 'enabled')
	 				 && (isset($user_meta['chat_dashboard_widget'])) && ($user_meta['chat_dashboard_widget'] == 'enabled')) {

						if (((isset($current_user->allcaps['level_10'])) && ($current_user->allcaps['level_10'] == 1))
						|| (array_intersect($this->instance['login_options'], $current_user->roles))) {

							$this->instance['id'] 					= 'dashboard-'.$blog_id;
							$this->instance['blog_id'] 				= $blog_id;
							$this->instance['box_class']			= 'psource-chat-dashboard-widget';
							$this->instance['session_status']		= 'open';

							if ((!isset($this->instance['dashboard_widget_title'])) || (empty($this->instance['dashboard_widget_title']))) {
								$this->instance['dashboard_widget_title'] = __('Chat', 'psource-chat');
							}

							if ((isset($user_meta['chat_dashboard_widget_height'])) && (!empty($user_meta['chat_dashboard_widget_height']))) {
								$this->instance['box_height'] = $user_meta['chat_dashboard_widget_height'];

							} else if ((!isset($this->instance['dashboard_widget_height'])) || (empty($this->instance['dashboard_widget_height']))) {
								$this->instance['box_height'] = $this->instance['dashboard_widget_height'];
							}

							wp_add_dashboard_widget(
								'psource_chat_dashboard_widget',
								$this->instance['dashboard_widget_title'],
								array(&$this, 'psource_chat_dashboard_widget_proc')
								/* array(&$this, 'psource_chat_dashboard_widget_controls_proc') */
							);
						}
					}
				}
			}
		}

		function psource_chat_dashboard_widget_proc() {

			global $psource_chat;

			$chat_output = $psource_chat->process_chat_shortcode($this->instance);
			if (!empty($chat_output)) {
				echo $chat_output;
				?>
				<style>
					div#psource_chat_dashboard_widget .inside { padding:0px; margin-top: 0;}
					div#psource_chat_dashboard_widget .inside .psource-chat-box { border:0px;}
					/* div#psource_chat_dashboard_widget .inside .psource-chat-box .psource-chat-module-header { display:none;} */
					/* div#psource_chat_dashboard_widget .inside .psource-chat-box .psource-chat-module-message-area .psource-chat-send-meta { display:none;} */
				</style>
				<?php
			}
		}

		function psource_chat_dashboard_widget_controls_proc() {
			global $psource_chat;

			//echo "instance<pre>"; print_r($this->instance); echo "</pre>";
			?>
			<p><input id="psource-chat-dashboard-widget-<?php echo $this->instance['id'] ?>-action-archive" type="checkbox" value="1"
				name="psource-chat[dashboard-widget][<?php echo $this->instance['id'] ?>][action][archive]" /> <label
				for="psource-chat-dashboard-widget-<?php echo $this->instance['id'] ?>-action-archive"><?php
					_e('Aktiviert - Chat-Nachricht archivieren', 'psource-chat'); ?></label></p>
			<?php
		}
	}
	$psource_chat_dashboard_widget = new PSOURCEChatDashboardWidget();
}

if (!class_exists('PSOURCEChatStatusDashboardWidget')) {

	class PSOURCEChatStatusDashboardWidget {
		var $instance = array();

		function __construct() {

			if (is_network_admin()) {
				if ( !function_exists( 'is_plugin_active_for_network' ) ) {
					require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
				}
				if (!is_plugin_active_for_network('psource-chat/psource-chat.php')) {return;}

				add_action( 'wp_network_dashboard_setup', array(&$this, 'psource_chat_add_dashboard_widgets') );
			} else {
				add_action( 'wp_dashboard_setup', array(&$this, 'psource_chat_add_dashboard_widgets') );
			}
		}

		function PSOURCEChatStatusDashboardWidget() {
			$this->__construct();
		}

		function psource_chat_add_dashboard_widgets() {
			global $psource_chat, $blog_id, $current_user;

			$this->instance = $psource_chat->_chat_options['dashboard'];
			//echo "instance<pre>"; print_r($this->instance); echo "</pre>";

			$user_meta = get_user_meta( $current_user->ID, 'psource-chat-user', true );
			$user_meta = wp_parse_args( $user_meta, $psource_chat->_chat_options_defaults['user_meta'] );
			if ($user_meta['chat_wp_admin'] == "enabled") {

				if (is_network_admin()) {

					if ((isset($this->instance['dashboard_status_widget'])) && ($this->instance['dashboard_status_widget'] == 'enabled')
					 && (isset($user_meta['chat_network_dashboard_status_widget'])) && ($user_meta['chat_network_dashboard_status_widget'] == 'enabled')) {

						if ((!isset($this->instance['dashboard_status_widget_title'])) || (empty($this->instance['dashboard_status_widget_title']))) {
							$this->instance['dashboard_status_widget_title'] = __('WordPress Chat', 'psource-chat');
						}

						wp_add_dashboard_widget(
							'psource_chat_dashboard_status_widget',
							$this->instance['dashboard_status_widget_title'],
							array(&$this, 'psource_chat_status_dashboard_widget_proc')
						);
					}
				} else {

					if ((isset($this->instance['dashboard_status_widget'])) && ($this->instance['dashboard_status_widget'] == 'enabled')
					 && (isset($user_meta['chat_dashboard_status_widget'])) && ($user_meta['chat_dashboard_status_widget'] == 'enabled')) {

						//if (((isset($current_user->allcaps['level_10'])) && ($current_user->allcaps['level_10'] == 1))
						//	|| (array_intersect($this->instance['login_options'], $current_user->roles))) {

							if ((!isset($this->instance['dashboard_status_widget_title'])) || (empty($this->instance['dashboard_status_widget_title']))) {
								$this->instance['dashboard_status_widget_title'] = __('WordPress Chat', 'psource-chat');
							}

							wp_add_dashboard_widget(
								'psource_chat_status_dashboard_widget',
								$this->instance['dashboard_status_widget_title'],
								array(&$this, 'psource_chat_status_dashboard_widget_proc')
							);
							//}
					}
				}
			}
		}

		function psource_chat_status_dashboard_widget_proc() {
			global $psource_chat, $current_user;

			$chat_output = '';

			$chat_user_status = psource_chat_get_user_status($current_user->ID);

			foreach($psource_chat->_chat_options['user-statuses'] as $status_key => $status_label) {
				if ($status_key == $chat_user_status) {
					$selected = ' selected="selected" ';
				} else {
					$selected = '';
				}

				$class = '';
				if ($status_key == 'available')
					{$class .= ' available';}

				$chat_output .= '<option class="'. $class .'" value="'. $status_key .'" '. $selected .'>'. $status_label .'</option>';
			}

			if (!empty($chat_output)) {

				echo '<select id="psource-chat-status-widget-dashboard" class="psource-chat-status-widget">'. $chat_output .'</select>';
			}
		}
	}
	$psource_chat_status_dashboard_widget = new PSOURCEChatStatusDashboardWidget();
}

if (!class_exists('PSOURCEChatFriendsDashboardWidget')) {

	class PSOURCEChatFriendsDashboardWidget {
		var $instance = array();

		function __construct() {

			if (is_network_admin()) {
				if ( !function_exists( 'is_plugin_active_for_network' ) ) {
					require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
				}
				if (!is_plugin_active_for_network('psource-chat/psource-chat.php')) {return;}

				add_action( 'wp_network_dashboard_setup', array(&$this, 'psource_chat_add_dashboard_widgets') );
			} else {
				add_action( 'wp_dashboard_setup', array(&$this, 'psource_chat_add_dashboard_widgets') );
			}
		}

		function PSOURCEChatFriendsDashboardWidget() {
			$this->__construct();
		}

		function psource_chat_add_dashboard_widgets() {
			global $bp, $psource_chat, $blog_id, $current_user;

			// Check if any supported friendship system is available
			$has_friendship_system = false;
			
			// 1. Check for CP Community
			if ( function_exists( 'cpc_are_friends' ) && defined( 'CPC_CORE_PLUGINS' ) && strpos( CPC_CORE_PLUGINS, 'core-friendships' ) !== false ) {
				$has_friendship_system = true;
			}
			// 2. Check for BuddyPress
			elseif ( !empty( $bp ) && function_exists( 'bp_get_friend_ids' ) ) {
				$has_friendship_system = true;
			}
			// 3. Check for Friends plugin
			elseif ( is_plugin_active( 'friends/friends.php' ) || ( is_multisite() && is_plugin_active_for_network( 'friends/friends.php' ) ) ) {
				$has_friendship_system = true;
			}
			
			// If no friendship system is available, don't add the widget
			if ( !$has_friendship_system ) {
				return;
			}

			$this->instance = $psource_chat->_chat_options['dashboard'];

			$user_meta = get_user_meta( $current_user->ID, 'psource-chat-user', true );
			$user_meta = wp_parse_args( $user_meta, $psource_chat->_chat_options_defaults['user_meta'] );
			//echo "user_meta<pre>"; print_r($user_meta); echo "</pre>";

			if ($user_meta['chat_wp_admin'] == "enabled") {
				if (is_network_admin()) {

					if ((isset($this->instance['dashboard_friends_widget'])) && ($this->instance['dashboard_friends_widget'] == 'enabled')
					 && (isset($user_meta['chat_network_dashboard_friends_widget'])) && ($user_meta['chat_network_dashboard_friends_widget'] == 'enabled')) {

						if ((!isset($this->instance['dashboard_friends_widget_title'])) || (empty($this->instance['dashboard_friends_widget_title']))) {
							$this->instance['dashboard_friends_widget_title'] = __('Chat Freunde', 'psource-chat');
						}

						if ((isset($user_meta['chat_dashboard_friends_widget_height'])) && (!empty($user_meta['chat_dashboard_friends_widget_height']))) {
							$this->instance['box_height'] = $user_meta['chat_dashboard_friends_widget_height'];

						} else if ((!isset($this->instance['dashboard_friends_widget_height'])) || (empty($this->instance['dashboard_friends_widget_height']))) {
							$this->instance['box_height'] = $this->instance['dashboard_friends_widget_height'];
						}

						wp_add_dashboard_widget(
							'psource_chat_dashboard_friends_widget',
							$this->instance['dashboard_friends_widget_title'],
							array(&$this, 'psource_chat_friends_dashboard_widget_proc')
						);
					}
				} else {

					if ((isset($this->instance['dashboard_friends_widget'])) && ($this->instance['dashboard_friends_widget'] == 'enabled')
					 && (isset($user_meta['chat_dashboard_friends_widget'])) && ($user_meta['chat_dashboard_friends_widget'] == 'enabled')) {

						//if (((isset($current_user->allcaps['level_10'])) && ($current_user->allcaps['level_10'] == 1))
						//	|| (array_intersect($this->instance['login_options'], $current_user->roles))) {

							if ((!isset($this->instance['dashboard_friends_widget_title'])) || (empty($this->instance['dashboard_friends_widgettitle']))) {
								$this->instance['dashboard_friends_widget_title'] = __('Chat Freunde', 'psource-chat');
							}

							if ((isset($user_meta['chat_dashboard_friends_widget_height'])) && (!empty($user_meta['chat_dashboard_friends_widget_height']))) {
								$this->instance['box_height'] = $user_meta['chat_dashboard_friends_widget_height'];

							} else if ((!isset($this->instance['dashboard_friends_widget_height'])) || (empty($this->instance['dashboard_friends_widget_height']))) {
								$this->instance['box_height'] = $this->instance['dashboard_friends_widget_height'];
							}

							wp_add_dashboard_widget(
								'psource_chat_friends_dashboard_widget',
								$this->instance['dashboard_friends_widget_title'],
								array(&$this, 'psource_chat_friends_dashboard_widget_proc')
							);
							//}
					}
				}
			}
		}

		function psource_chat_friends_dashboard_widget_proc() {
			global $bp, $psource_chat, $current_user, $wpdb;

			$friends_list_ids = array();

			// 1. Try CP Community first (modern preferred method)
			if ( function_exists( 'cpc_are_friends' ) && defined( 'CPC_CORE_PLUGINS' ) && strpos( CPC_CORE_PLUGINS, 'core-friendships' ) !== false ) {
				
				// Get friends from CP Community
				if ( !get_option( 'cpc_friendships_all' ) ) {
					// Get published friendships for current user
					$sql = "SELECT p.ID, m1.meta_value as cpc_member1, m2.meta_value as cpc_member2
							FROM {$wpdb->prefix}posts p 
							LEFT JOIN {$wpdb->prefix}postmeta m1 ON p.ID = m1.post_id
							LEFT JOIN {$wpdb->prefix}postmeta m2 ON p.ID = m2.post_id
							WHERE p.post_type='cpc_friendship'
							  AND p.post_status='publish'
							  AND m1.meta_key = 'cpc_member1'
							  AND m2.meta_key = 'cpc_member2'
							  AND (m1.meta_value = %d OR m2.meta_value = %d)";
					
					$cp_friends = $wpdb->get_results( $wpdb->prepare( $sql, $current_user->ID, $current_user->ID ) );
					
					if ( $cp_friends ) {
						foreach ( $cp_friends as $friendship ) {
							// Get the other member of the friendship
							$other_member = ( $friendship->cpc_member1 == $current_user->ID ) 
								? $friendship->cpc_member2 
								: $friendship->cpc_member1;
							
							if ( $other_member != $current_user->ID ) {
								$friends_list_ids[] = intval( $other_member );
							}
						}
					}
				} else {
					// If "all friends" mode is enabled, get all site members
					$site_members = get_users( 'blog_id=' . get_current_blog_id() );
					foreach ( $site_members as $member ) {
						if ( $member->ID != $current_user->ID ) {
							$friends_list_ids[] = intval( $member->ID );
						}
					}
				}
				
			// 2. Try BuddyPress (legacy compatibility)
			} elseif ( !empty( $bp ) && function_exists( 'bp_get_friend_ids' ) ) {
				$friends_ids = bp_get_friend_ids( $bp->loggedin_user->id );
				if ( !empty( $friends_ids ) ) {
					$friends_list_ids = explode( ',', $friends_ids );
					$friends_list_ids = array_map( 'intval', $friends_list_ids );
				}
				
			// 3. Try old Friends plugin (legacy fallback)
			} else {
				if ( !is_admin() && !function_exists( 'is_plugin_active' ) ) {
					include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				}

				if ( !is_plugin_active( 'friends/friends.php' ) ) {
					if ( is_multisite() && !is_plugin_active_for_network( 'friends/friends.php' ) ) {
						?>
						<div class="psource-chat-dashboard-widget-error">
							<p class="error">
								<?php _e( 'Freunde-Widget benötigt CP Community, BuddyPress oder ein kompatibles Community-Plugin.', 'psource-chat' ); ?>
							</p>
							<p>
								<strong><?php _e( 'Empfehlung:', 'psource-chat' ); ?></strong> 
								<?php _e( 'Installiere das CP Community Plugin für moderne Freundschafts-Funktionen.', 'psource-chat' ); ?>
							</p>
						</div>
						<?php
						return;
					}
				}
				
				if ( function_exists( 'friends_get_list' ) ) {
					$friends_list_ids = friends_get_list( $current_user->ID );
				}
			}

			// Debug info for admins
			if ( current_user_can( 'manage_options' ) && empty( $friends_list_ids ) ) {
				?>
				<div class="psource-chat-debug-info">
					<p><em><?php _e( 'Debug-Info (nur für Admins sichtbar):', 'psource-chat' ); ?></em></p>
					<ul style="font-size: 11px; color: #666;">
						<li><?php _e( 'CP Community aktiv:', 'psource-chat' ); ?> <?php echo function_exists( 'cpc_are_friends' ) ? '✅' : '❌'; ?></li>
						<li><?php _e( 'BuddyPress aktiv:', 'psource-chat' ); ?> <?php echo function_exists( 'bp_get_friend_ids' ) ? '✅' : '❌'; ?></li>
						<li><?php _e( 'Friends Plugin aktiv:', 'psource-chat' ); ?> <?php echo function_exists( 'friends_get_list' ) ? '✅' : '❌'; ?></li>
						<li><?php _e( 'Gefundene Freunde:', 'psource-chat' ); ?> <?php echo count( $friends_list_ids ); ?></li>
					</ul>
				</div>
				<?php
			}

			if ( !is_array( $friends_list_ids ) || !count( $friends_list_ids ) ) {
				?>
				<div class="psource-chat-no-friends">
					<p><?php _e( 'Keine Freunde online.', 'psource-chat' ); ?></p>
					<?php if ( function_exists( 'cpc_are_friends' ) ) : ?>
						<p><small><?php _e( 'Verbinde Dich mit anderen Nutzern über das Community-Plugin.', 'psource-chat' ); ?></small></p>
					<?php endif; ?>
				</div>
				<?php
				return;
			}

			$chat_output = '';

			$friends_status = psource_chat_get_friends_status( $current_user->ID, $friends_list_ids );
			
			if ( $friends_status && is_array( $friends_status ) && count( $friends_status ) ) {
				foreach( $friends_status as $friend ) {
					if ( isset( $friend->chat_status ) && $friend->chat_status == "available" ) {
						$friend_status_data = psource_chat_get_chat_status_data( $current_user->ID, $friend );

						$chat_output .= '<li><a class="'. $friend_status_data['href_class'] .'" title="'. $friend_status_data['href_title'] .'" href="#" rel="'.md5($friend->ID) .'"><span class="psource-chat-ab-icon psource-chat-ab-icon-'. $friend->chat_status .'"></span><span class="psource-chat-ab-label">'. $friend->display_name .'</span>'.'</a></li>';
					}
				}

				if ( !empty( $chat_output ) ) {
					$height_style = ' style="max-height: '. $this->instance['box_height'] .'; overflow:auto;" ';
					echo '<ul id="psource-chat-friends-dashboard-widget" '. $height_style.' class="psource-chat-friends-widget">'. $chat_output .'</ul>';
				} else {
					?>
					<div class="psource-chat-no-online-friends">
						<p><?php _e( 'Keine Freunde sind derzeit online.', 'psource-chat' ); ?></p>
					</div>
					<?php
				}
			} else {
				?>
				<div class="psource-chat-no-friends-status">
					<p><?php _e( 'Freunde-Status konnte nicht abgerufen werden.', 'psource-chat' ); ?></p>
				</div>
				<?php
			}

		}
	}
	$psource_chat_friends_dashboard_widget = new PSOURCEChatFriendsDashboardWidget();
}