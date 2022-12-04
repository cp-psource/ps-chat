<?php
function psource_chat_get_user_status( $user_id = 0 ) {
	global $psource_chat;

	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return "away";
	}

	//echo "user_id[". $user_id ."]<br />";
	$status = get_user_meta( $user_id, 'psource_chat_user_status', true );
	//echo "status[". $status ."]<br />";
	if ( empty( $status ) ) {
		$user_meta = get_user_meta( $user_id, 'psource-chat-user', true );
		//echo "user_meta[". $user_meta ."]<br />";
		if ( ( isset( $user_meta['chat_user_status'] ) ) && ( ! empty( $user_meta['chat_user_status'] ) ) ) {
			$status = $user_meta['chat_user_status'];
		} else {
			if ( isset( $psource_chat->_chat_options_defaults['user_meta']['chat_user_status'] ) ) {
				if ( ! empty( $psource_chat->_chat_options_defaults['user_meta']['chat_user_status'] ) ) {
					$status = $psource_chat->_chat_options_defaults['user_meta']['chat_user_status'];
				}
			}
		}
	}

	// Double check the value stored is a valid posible status.
	if ( isset( $psource_chat->_chat_options['user-statuses'][ $status ] ) ) {
		return $status;
	} else {
		return false;
	} //$psource_chat->_chat_options_defaults['user_meta']['chat_user_status'];
}

function psource_chat_update_user_status( $user_id = 0, $status = 'away' ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return;
	}

	update_user_meta( $user_id, 'psource_chat_user_status', $status );
}

function psource_chat_update_user_activity( $user_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return;
	}

	update_user_meta( $user_id, 'psource_chat_last_activity', time() );
}

function psource_chat_wpadminbar_render() {
	global $wp_admin_bar, $psource_chat;

	if ( ! is_user_logged_in() ) {
		return;
	}

	if ( ! isset( $psource_chat->user_meta['chat_wp_admin'] ) ) {
		return;
	}

	if ( ( is_admin() ) && ( $psource_chat->user_meta['chat_wp_admin'] != 'enabled' ) ) {
		return;
	}

	$user_id = get_current_user_id();
	if ( ! is_admin_bar_showing() ) {
		return;
	}

	//$psource_chat->load_configs();

	if ( ( ! isset( $psource_chat->user_meta['chat_wp_toolbar'] ) ) || ( $psource_chat->user_meta['chat_wp_toolbar'] != 'enabled' ) ) {
		return;
	}

	if ( is_admin() ) {
		if ( $psource_chat->_chat_plugin_settings['blocked_urls']['admin'] == true ) {
			return;
		}
	} else {
		if ( ( $psource_chat->_chat_plugin_settings['blocked_urls']['front'] == true )
		     && ( ! count( $psource_chat->chat_sessions ) )
		) {
			return;
		}
	}

	if ( ( isset( $psource_chat->user_meta['chat_wp_toolbar_show_status'] ) ) && ( $psource_chat->user_meta['chat_wp_toolbar_show_status'] == 'enabled' )
	     || ( isset( $psource_chat->user_meta['chat_wp_toolbar_show_friends'] ) ) && ( $psource_chat->user_meta['chat_wp_toolbar_show_friends'] == 'enabled' )
	) {

		$_parent_menu_id = 'psource-chat-container';

		if ( ( isset( $psource_chat->user_meta['chat_wp_toolbar_show_status'] ) ) && ( $psource_chat->user_meta['chat_wp_toolbar_show_status'] == 'enabled' ) ) {

			$chat_user_status = $psource_chat->user_meta['chat_user_status'];

			$wp_admin_bar->add_menu( array(
				'parent' => false,
				'id'     => $_parent_menu_id,
				'title'  => '<span class="psource-chat-user-status-current"><span class="psource-chat-ab-icon psource-chat-ab-icon-' . $chat_user_status
				            . '"></span><span class="psource-chat-ab-label">' . __( 'Chat', 'psource-chat' ) . '</span>'
				            . '</span>',
				'href'   => false,
			) );

			$wp_admin_bar->add_menu( array(
				'parent' => $_parent_menu_id,
				'id'     => 'psource-chat-user-statuses',
				'title'  => __( 'Chat Status', 'psource-chat' ) . ' - <span class="psource-chat-current-status-label psource-chat-ab-label">' .
				            $psource_chat->_chat_options['user-statuses'][ $chat_user_status ] . '</span>',
				'href'   => false
			) );

			foreach ( $psource_chat->_chat_options['user-statuses'] as $status_key => $status_label ) {
				if ( $status_key == 'away' ) {
					continue;
				}

				$sub_menu_meta_title  = __( 'Wechsle Chat Status auf', 'psource-chat' ) . ' ' . $status_label;
				$sub_menu_meta_status = '<span class="psource-chat-ab-icon psource-chat-ab-icon-' . $status_key . '"></span><span class="psource-chat-ab-label">' .
				                        $status_label . '</span>';
				$sub_menu_meta_rel    = $status_key;

				$wp_admin_bar->add_menu( array(
					'parent' => 'psource-chat-user-statuses',
					'id'     => 'psource-chat-user-status-change-' . $status_key,
					'title'  => '<a class="ab-item" title="' . $sub_menu_meta_title . '" href="#">' . $sub_menu_meta_status . '</a>',
					'href'   => false,
				) );
			}
		} else {
			$wp_admin_bar->add_menu( array(
				'parent' => false,
				'id'     => $_parent_menu_id,
				'title'  => '<span class="psource-chat-ab-label">' . __( 'Chat', 'psource-chat' ) . '</span>',
				'href'   => false,
			) );
		}

		if ( ( isset( $psource_chat->user_meta['chat_wp_toolbar_show_friends'] ) ) && ( $psource_chat->user_meta['chat_wp_toolbar_show_friends'] == 'enabled' ) ) {
			psource_chat_wpadminbar_menu_friends( $_parent_menu_id, $user_id );
		}

		// Future
		//psource_chat_wpadminbar_menu_invites($_parent_menu_id, $user_id);

	}
}

function psource_chat_wpadminbar_menu_friends( $_parent_menu_id = '', $user_id = 0 ) {
	global $wp_admin_bar, $psource_chat, $bp;

	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return;
	}

	if ( ( ! empty( $bp ) ) && ( function_exists( 'bp_get_friend_ids' ) ) ) {
		$friends_ids = bp_get_friend_ids( $bp->loggedin_user->id );
		if ( ! empty( $friends_ids ) ) {
			$friends_list_ids = explode( ',', $friends_ids );
		}
	} else {

		if ( ( ! is_admin() ) && ( ! function_exists( 'is_plugin_active' ) ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		if ( ! is_plugin_active( 'friends/friends.php' ) ) {
			if ( ( is_multisite() ) && ( ! is_plugin_active_for_network( 'friends/friends.php' ) ) ) {
				return;
			}
		}
		if ( ! function_exists( 'friends_get_list' ) ) {
			return;
		}

		$friends_list_ids = friends_get_list( $user_id );
	}

	if ( empty( $friends_list_ids ) ) {
		return;
	}

	$friends_status = psource_chat_get_friends_status( $user_id, $friends_list_ids );
	if ( ( $friends_status ) && ( is_array( $friends_status ) ) && ( count( $friends_status ) ) ) {
		$wp_admin_bar->add_menu( array(
			'parent' => $_parent_menu_id,
			'id'     => 'psource-chat-user-friends',
			'title'  => __( 'Freunde Online', 'psource-chat' ),
			'href'   => false
		) );
		$has_friends = false;
		foreach ( $friends_status as $friend ) {
			if ( ( isset( $friend->chat_status ) ) && ( $friend->chat_status == "available" ) ) {
				$friend_status_data = psource_chat_get_chat_status_data( $user_id, $friend );

				$menu_title     = '<a class="ab-item ' . $friend_status_data['href_class'] . '" title="' . $friend_status_data['href_title'] . '" href="#" rel="' .
				                  md5( $friend->ID ) . '">';
				$friend->avatar = get_avatar( $friend->ID, 25, get_option( 'avatar_default' ), $friend->display_name );
				if ( ! empty( $friend->avatar ) ) {
					$menu_title .= '<span class="psource-chat-friend-avatar">' . $friend->avatar . '</span>';
				}
				$menu_title .= '<span style="margin-left: 5px;" class="psource-chat-ab-label">' . $friend->display_name . '</span></a>';

				$wp_admin_bar->add_menu( array(
					'parent' => 'psource-chat-user-friends',
					'id'     => md5( $friend->ID ),
					'title'  => $menu_title
				) );
				$has_friends = true;
			}
		}

		// If we don't have any actual available friends then show a stub
		if ( $has_friends == false ) {
			$wp_admin_bar->add_menu( array(
				'parent' => 'psource-chat-user-friends',
				'title'  => __( 'Keine', 'psource-chat' ),
				'id'     => 'none'
			) );
		}

	} else {
		$wp_admin_bar->add_menu( array(
			'parent' => $_parent_menu_id,
			'id'     => 'psource-chat-user-friends',
			'title'  => __( 'Freunde Online', 'psource-chat' ),
			'href'   => false
		) );

		$wp_admin_bar->add_menu( array(
			'parent' => 'psource-chat-user-friends',
			'title'  => __( 'Keine', 'psource-chat' ),
			'id'     => 'none'
		) );
	}
}

function psource_chat_get_chat_status_label( $user_id, $friend_id, $label_on = '', $label_off = '' ) {
	global $psource_chat;

	$friends_status = psource_chat_get_friends_status( $user_id, $friend_id );
	if ( ! empty( $friends_status[0] ) ) {
		$friends_status = $friends_status[0];
	} else {
		$friends_status = '';
	}

	$friend_data           = psource_chat_get_chat_status_data( $user_id, $friends_status );
	$friend_status_display = $friend_data['icon'] . $friend_data['label'];
	if ( ( ! empty( $friend_data ) ) && ( isset( $friend_data['href'] ) ) && ( ! empty( $friend_data['href'] ) ) ) {
		return '<a class="' . $friend_data['href_class'] . '" title="' . $friend_data['href_title'] . '"
			href="#" rel="' . $friend_data['href'] . '">' . $friend_status_display . '</a>';
	} else {
		return $friend_status_display;
	}
}

function psource_chat_get_chat_status_data( $user_id, $friend ) {
	global $psource_chat;

	$chat_status_array = array();

	if ( ! is_object( $friend ) ) {
		$friend              = (object) $friend;
		$friend->chat_status = 'away';
	} else {
		if ( ( ! isset( $friend->chat_status ) ) || ( ! isset( $psource_chat->_chat_options['user-statuses'][ $friend->chat_status ] ) ) ) {
			$friend->chat_status = 'away';
		}
	}

	$chat_status_array['icon']  = '<span class="psource-chat-ab-icon psource-chat-ab-icon-' . $friend->chat_status . '"></span>';
	$chat_status_array['label'] = '<span class="psource-chat-ab-label">' . $psource_chat->_chat_options['user-statuses'][ $friend->chat_status ] . '</span>';

	if ( $friend->chat_status == "available" ) {
		$chat_status_array['href']       = md5( $friend->ID );
		$chat_status_array['href_title'] = $psource_chat->_chat_options['user-statuses'][ $friend->chat_status ]; //__('Chat now with') .' '. $friend->display_name;
		$chat_status_array['href_class'] = 'psource-chat-user-invite';
	} else {
		$chat_status_array['href']       = '';
		$chat_status_array['href_title'] = $psource_chat->_chat_options['user-statuses'][ $friend->chat_status ]; //__('Chat - Offline', 'psource-chat');
		$chat_status_array['href_class'] = '';
	}

	return $chat_status_array;
}

function psource_chat_get_friends_status( $user_id, $friends_list ) {
	global $wpdb, $psource_chat;

	if ( empty( $friends_list ) ) {
		return;
	}

	if ( ! is_array( $friends_list ) ) {
		$friends_list = array( $friends_list );
	}

	$time_threshold = time() - 300;    // 5 minites. Though the user_meta field is updated on each page load.

	if ( ( isset( $psource_chat->_chat_options_defaults['user_meta']['chat_user_status'] ) )
	     && ( ! empty( $psource_chat->_chat_options_defaults['user_meta']['chat_user_status'] ) )
	) {
		$default_status = $psource_chat->_chat_options_defaults['user_meta']['chat_user_status'];
	} else {
		$default_status = '';
	}

	$sql_str = "SELECT users.ID, users.display_name, usermeta.meta_value as last_activity, IFNULL(usermeta2.meta_value, '" . $default_status . "') as chat_status FROM " . $wpdb->base_prefix . "users as users LEFT JOIN " . $wpdb->base_prefix . "usermeta as usermeta ON users.ID=usermeta.user_id AND usermeta.meta_key='psource_chat_last_activity' LEFT JOIN " . $wpdb->base_prefix . "usermeta as usermeta2 ON users.ID=usermeta2.user_id AND usermeta2.meta_key='psource_chat_user_status' WHERE users.ID IN (" . implode( ",", $friends_list ) . ") AND usermeta.meta_value > " . $time_threshold . " ORDER BY users.display_name ASC LIMIT 50";

	$friends_status = $wpdb->get_results( $sql_str );

	return $friends_status;
}

function psource_chat_wpadminbar_menu_invites( $_parent_menu_id = '', $user_id = 0 ) {
	global $wp_admin_bar, $psource_chat;

	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return;
	}

	psource_chat_process_invites( $user_id );
	$invites = psource_chat_get_invites( $user_id );
	if ( $invites ) {
		$wp_admin_bar->add_menu( array(
			'parent' => $_parent_menu_id,
			'id'     => 'psource-chat-user-invites',
			'title'  => __( 'Einladungen', 'psource-chat' ),
			'href'   => '#'
		) );

		if ( isset( $invites['from'] ) ) {

			$wp_admin_bar->add_menu( array(
				'parent' => 'psource-chat-user-invites',
				'id'     => 'psource-chat-user-invites-from',
				'title'  => __( 'Von', 'psource-chat' ),
				'href'   => '#'
			) );
			foreach ( $invites['from'] as $invite_user_id => $invite ) {
				$wp_admin_bar->add_menu( array(
					'parent' => 'psource-chat-user-invites-from',
					'id'     => 'psource-chat-user-invites-from-user-' . $invite_user_id,
					'title'  => get_the_author_meta( 'display_name', $invite_user_id ) . " (" . human_time_diff( intval( $invite['timestamp'] ) ) . " ago)",
					'href'   => '#'
				) );
			}
		}

		if ( isset( $invites['to'] ) ) {
			$wp_admin_bar->add_menu( array(
				'parent' => 'psource-chat-user-invites',
				'id'     => 'psource-chat-user-invites-to',
				'title'  => __( 'To', 'psource-chat' ),
				'href'   => '#'
			) );
			foreach ( $invites['to'] as $invite_user_id => $invite ) {
				$wp_admin_bar->add_menu( array(
					'parent' => 'psource-chat-user-invites-to',
					'id'     => 'psource-chat-user-invites-to-user-' . $invite_user_id,
					'title'  => get_the_author_meta( 'display_name', $invite_user_id ) . " (" . human_time_diff( intval( $invite['timestamp'] ) ) . " ago)",
					'href'   => '#'
				) );
			}
		}
	}
}

function psource_chat_get_invites( $user_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return;
	}

	return get_user_meta( $user_id, 'psource_chat_invites', true );
}

function psource_chat_update_invites( $invites, $user_id = 0  ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return;
	}

	return update_user_meta( $user_id, 'psource_chat_invites', $invites );
}

function psource_chat_process_invites( $user_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return;
	}

	if ( isset( $_GET['psource-chat-invite-user'] ) ) {

		$friend_user_id = intval( $_GET['psource-chat-invite-user'] );
		if ( $friend_user_id > 0 ) {
			if ( ( isset( $_GET['psource-chat-invite-noonce-field'] ) )
			     && wp_verify_nonce( $_GET['psource-chat-invite-noonce-field'], 'psource-chat-invite-noonce-field' . $user_id . '-' . $friend_user_id )
			) {

				// For Chat Invites we set one record to the requestors 'to' stack...
				$user_invites = psource_chat_get_invites( $user_id );
				if ( ! isset( $user_invites['to'] ) ) {
					$user_invites['to'] = array();
				}
				$invite_item                           = array(
					'key'       => $_GET['psource-chat-invite-noonce-field'],
					'timestamp' => time()
				);
				$user_invites['to'][ $friend_user_id ] = $invite_item;
				psource_chat_update_invites( $user_id, $user_invites );

				// Then we set one record in the requestee's stack.
				$user_invites = psource_chat_get_invites( $friend_user_id );
				if ( ! isset( $user_invites['from'] ) ) {
					$user_invites['from'] = array();
				}
				$invite_item = array(
					'key'       => $_GET['psource-chat-invite-noonce-field'],
					'timestamp' => time()
				);

				$user_invites['from'][ $user_id ] = $invite_item;
				psource_chat_update_invites( $friend_user_id, $user_invites );
			}
		}
	}
}