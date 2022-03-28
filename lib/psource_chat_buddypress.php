<?php
function psource_chat_buddypress_init() {
	if ( class_exists( 'BP_Group_Extension' ) ) {
		if ( version_compare( bp_get_version(), "1.8" ) >= 0 ) {
			include_once( 'buddypress/wpmudec_chat_buddypress_group_1.8.php' );
		} else {
			include_once( 'buddypress/wpmudec_chat_buddypress_group_1.7.2.php' );
		}

		if ( class_exists( 'PSOURCE_Chat_BuddyPress' ) ) {
			bp_register_group_extension( 'PSOURCE_Chat_BuddyPress' );
		}
	}
}

add_action( 'bp_include', 'psource_chat_buddypress_init' );


function psource_chat_buddypress_friends_list() {
	global $bp, $members_template, $psource_chat;

	// Set thsi so when we get to wp_footer it knows we need to load the JS/CSS for the Friends display.
	$psource_chat->_chat_plugin_settings['blocked_urls']['front'] = false;

	if ( ( ( isset( $members_template->member->is_friend ) ) && ( $members_template->member->is_friend == true ) ) || ( $bp->loggedin_user->is_site_admin == true ) ) {

		$content = '';

		$content .= '<div id="psource-chat-now-button-' . $members_template->member->id . '" class="psource-chat-now-button">';
		$friends_status = psource_chat_get_friends_status( $bp->loggedin_user->id, $members_template->member->id );

		if ( ! empty( $friends_status[0] ) ) {
			$friends_status = $friends_status[0];
		} else {
			$friends_status = '';
		}

		$friend_data = psource_chat_get_chat_status_data( $members_template->member->id, $friends_status );

		$friend_status_display = $friend_data['icon'] . $friend_data['label'];
		if ( ! empty( $friend_data['href'] ) ) {
			$content .= '<a class="button psource-chat-button ' . $friend_data['href_class'] . '" title="' . $friend_data['href_title'] . '" href="#" rel="' . $friend_data['href'] . '">' . $friend_status_display . '</a>';
		} else {
			$content .= '<a onclick="return false;" disabled="disabled" class="button psource-chat-button ' . $friend_data['href_class'] . '" title="' . $friend_data['href_title'] . '" href="#">' . $friend_status_display . '</a>';	 	 		 	  			   			
		}
		$content .= '</div>';

		echo $content;
	}
}

add_action( 'bp_member_header_actions', 'psource_chat_buddypress_member_header_actions', 30 );
add_action( 'bp_directory_members_actions', 'psource_chat_buddypress_friends_list' );
add_action( 'bp_group_members_list_item_action', 'psource_chat_buddypress_friends_list' );

function psource_chat_buddypress_member_header_actions() {
	global $bp, $members_template, $psource_chat, $current_user;

	if ( $bp->loggedin_user->id === bp_displayed_user_id() ) {
		return;
	}

	//if (!is_object('BP_Friends_Friendship')) return;
	if ( ! bp_is_active( 'friends' ) ) {
		return;
	}

	if ( ! class_exists( 'BP_Friends_Friendship' ) ) {
		return

			$is_friend_ret = BP_Friends_Friendship::check_is_friend( bp_loggedin_user_id(), bp_displayed_user_id() );
	}
	//echo "is_friend_ret[". $is_friend_ret ."]<br />";

	// Set this so when we get to wp_footer it knows we need to load the JS/CSS for the Friends display.
	$psource_chat->_chat_plugin_settings['blocked_urls']['front'] = false;
	if ( ( BP_Friends_Friendship::check_is_friend( bp_loggedin_user_id(), bp_displayed_user_id() ) == 'is_friend' )
	     || ( $bp->loggedin_user->is_site_admin == true )
	) {

		$content = '';

		$content .= '<div id="psource-chat-now-button-' . bp_displayed_user_id() . '" class="generic-button psource-chat-now-button">';
		$friends_status = psource_chat_get_friends_status( $bp->loggedin_user->id, bp_displayed_user_id() );

		if ( ! empty( $friends_status[0] ) ) {
			$friends_status = $friends_status[0];
		} else {
			$friends_status = '';
		}

		$friend_data = psource_chat_get_chat_status_data( bp_displayed_user_id(), $friends_status );

		$friend_status_display = $friend_data['icon'] . $friend_data['label'];
		if ( ! empty( $friend_data['href'] ) ) {
			$content .= '<a class="button psource-chat-button ' . $friend_data['href_class'] . '" title="' . $friend_data['href_title'] . '" href="#" rel="' . $friend_data['href'] . '">' . $friend_status_display . '</a>';
		} else {
			$content .= '<a onclick="return false;" disabled="disabled" class="psource-chat-button ' . $friend_data['href_class'] . '" title="' . $friend_data['href_title'] . '" href="#">' . $friend_status_display . '</a>';
		}
		$content .= '</div>';

		echo $content;
	}
}

function psource_chat_buddypress_settings_setup_nav() {
	global $psource_chat;

	$slug_parent = 'settings';
	$slug_page   = 'chat';

	// Set thsi so when we get to wp_footer it knows we need to load the JS/CSS for the Friends display.
	$psource_chat->_chat_plugin_settings['blocked_urls']['front'] = false;

	// Determine user to use
	if ( bp_displayed_user_domain() ) {
		$user_domain = bp_displayed_user_domain();
	} elseif ( bp_loggedin_user_domain() ) {
		$user_domain = bp_loggedin_user_domain();
	} else {
		return;
	}
	//echo "user_domain=[". $user_domain ."]<br />";
	$settings_link = trailingslashit( $user_domain . $slug_parent );
	//echo "settings_link=[". $settings_link ."]<br />";
	$sub_nav = array(
		'name'            => __( 'Chat', 'psource-chat' ),
		'slug'            => $slug_page,
		'parent_url'      => $settings_link,
		'parent_slug'     => $slug_parent,
		'screen_function' => 'psource_chat_settings_screen_chat_proc',
		'position'        => 30,
		'user_has_access' => bp_core_can_edit_settings()
	);
	bp_core_new_subnav_item( $sub_nav );
}

add_action( 'bp_settings_setup_nav', 'psource_chat_buddypress_settings_setup_nav' );

function psource_chat_settings_screen_chat_proc() {
	global $psource_chat, $current_user, $bp;

	if ( isset( $_POST['psource_chat_user_settings'] ) ) {
		//echo "_POST<pre>"; print_r($_POST); echo "</pre>";
		//die();

		if ( isset( $_POST['psource_chat_user_settings']['chat_user_status'] ) ) {
			$chat_user_status = esc_attr( $_POST['psource_chat_user_settings']['chat_user_status'] );
			if ( isset( $psource_chat->_chat_options['user-statuses'][ $chat_user_status ] ) ) {
				psource_chat_update_user_status( $bp->displayed_user->id, $chat_user_status );
			}
			unset( $_POST['psource_chat_user_settings']['chat_user_status'] );
		}

		$user_meta = get_user_meta( $bp->displayed_user->id, 'psource-chat-user', true );
		if ( ! $user_meta ) {
			$user_meta = array();
		}

		$user_meta = wp_parse_args( $_POST['psource_chat_user_settings'], $user_meta );

		update_user_meta( $bp->displayed_user->id, 'psource-chat-user', $user_meta );
		$psource_chat->user_meta = $user_meta;

		// Show the standard BP green success message
		bp_core_add_message( __( 'Einstellungen gespeichert.', 'psource-chat' ) );
	}

	add_action( 'bp_template_title', 'psource_chat_settings_show_screen_title' );
	add_action( 'bp_template_content', 'psource_chat_settings_show_screen_content' );
	bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );

	return;
}

function psource_chat_settings_show_screen_title() {
	global $psource_chat;

	_e( 'Chat Einstellungen', 'psource-chat' );

	return;
}

function psource_chat_settings_show_screen_content() {
	global $psource_chat, $current_user, $bp;

	$user_meta = get_user_meta( $bp->displayed_user->id, 'psource-chat-user', true );
	$user_meta = wp_parse_args( $user_meta, $psource_chat->_chat_options_defaults['user_meta'] );

	$chat_user_status = psource_chat_get_user_status( $bp->displayed_user->id );
	if ( isset( $psource_chat->_chat_options['user-statuses'][ $chat_user_status ] ) ) {
		$user_meta['chat_user_status'] = $chat_user_status;
	} else {
		$user_meta['chat_user_status'] = $psource_chat->_chat_options_defaults['user_meta']['chat_user_status'];
	}

	?>
	<form id="profile-edit-form" class="standard-form base" method="post" action="#">
		<div class="editfield psource_chat_status">
			<label for="psource_chat_status"><?php _e( 'Chat-Status festlegen', 'psource-chat' ); ?></label>

			<p class="description"><?php _e( 'Diese Benutzeroption steuert Deinen öffentlichen Chat-Status für andere Benutzer in Deinem Netzwerk. Auf diese Weise kannst Du steuern, wann andere Personen private Chat-Sitzungen mit Dir initiieren können. Beachte dass dies private Chats während bestehender Chat-Sitzungen, an denen Du teilnimmst, nicht verhindert.', 'psource-chat' ); ?></p>
			<select name="psource_chat_user_settings[chat_user_status]" id="psource_chat_status">
				<?php
				foreach ( $psource_chat->_chat_options['user-statuses'] as $status_key => $status_label ) {
					if ( $status_key == 'away' ) {
						continue;
					}
					if ( $status_key == $user_meta['chat_user_status'] ) {
						$selected = ' selected="selected" ';
					} else {
						$selected = '';
					}

					?>
					<option value="<?php echo $status_key; ?>" <?php echo $selected; ?>><?php echo $status_label; ?></option><?php
				}
				?>
			</select>
		</div>

		<div class="editfield psource_chat_name_display">
			<label for="psource_chat_name_display"><?php _e( 'In Chat-Sitzungen wird der Name als angezeigt', 'psource-chat' ); ?></label>

			<p class="description"><?php _e( 'Während Chat-Sitzungen, an denen Du teilnimmst, wird festgelegt, wie Du anderen Benutzern angezeigt wirst. Standard ist der Anzeigename.', 'psource-chat' ); ?></p>
			<select name="psource_chat_user_settings[chat_name_display]" id="psource_chat_name_display">
				<option value="display_name" <?php if ( $user_meta['chat_name_display'] == 'display_name' ) {
					echo ' selected="selected" ';
				} ?>><?php echo __( 'Anzeigename', 'psource-chat' ) . ": " . $current_user->display_name; ?></option>
				<option value="user_login" <?php if ( $user_meta['chat_name_display'] == 'user_login' ) {
					echo ' selected="selected" ';
				} ?>><?php echo __( 'Benutzername', 'psource-chat' ) . ": " . $current_user->user_login; ?></option>
			</select>
		</div>

		<?php if ( is_admin_bar_showing() ) { ?>
			<div class="editfield psource_chat_wp_toolbar">
				<label for="psource_chat_wp_toolbar"><?php _e( 'Chat WordPress Symbolleistenmenü anzeigen?', 'psource-chat' ); ?></label>
				<select name="psource_chat_user_settings[chat_wp_toolbar]" id="psource_chat_wp_toolbar">
					<option value="enabled"<?php if ( $user_meta['chat_wp_toolbar'] == 'enabled' ) {
						echo ' selected="selected" ';
					} ?>><?php
						_e( 'Aktiviert', 'psource-chat' ); ?></option>
					<option value="disabled"<?php if ( $user_meta['chat_wp_toolbar'] == 'disabled' ) {
						echo ' selected="selected" ';
					} ?>><?php
						_e( 'Deaktiviert', 'psource-chat' ); ?></option>
				</select>
			</div>
		<?php } ?>
		<div class="submit">
			<input id="psource_chat-submit" type="submit" value="<?php _e( 'Einstellungen speichern', 'psource-chat' ); ?>" name="psource_chat">
		</div>
	</form>
	<?php
	return;

}