<?php

class PSOURCE_Chat_BuddyPress extends CPC_Group_Extension {
	/**
	 * Here you can see more customization of the config options
	 */
	//var static $settings_slug;
	const settings_slug = 'psource_chat_cpc_group';

	function __construct() {
		global $cpc, $psource_chat;

		$psource_chat->load_configs();

		$this->create_step_position = 21;
		$this->nav_item_position    = 31;
		$this->slug                 = $psource_chat->get_option( 'cpc_menu_slug', 'global' );
		$this->name                 = $psource_chat->get_option( 'cpc_menu_label', 'global' );
		$this->enable_nav_item      = false;

		if ( isset( $cpc->groups->current_group->id ) ) {
			if ( groups_is_user_member( $cpc->loggedin_user->id, $cpc->groups->current_group->id ) ) {

				// First check if the old value
				$enabled = groups_get_groupmeta( $cpc->groups->current_group->id, 'psourcechatbpgroupenable' );
				if ( ! empty( $enabled ) ) {
					echo "here!<br />";
					groups_delete_groupmeta( $cpc->groups->current_group->id, 'psourcechatbpgroupenable' );
					groups_update_groupmeta( $cpc->groups->new_group_id, self::settings_slug . '_enable', $enabled );
				}

				$enabled = groups_get_groupmeta( $cpc->groups->current_group->id, self::settings_slug . '_enable', true );
				if ( $enabled == "yes" ) {
					$this->enable_nav_item = true;
				}
			}
		}

		$args = array(
			'slug'              => $this->slug,
			'name'              => $this->name,
			'enable_nav_item'   => $this->enable_nav_item,
			'nav_item_position' => $this->nav_item_position,
			'screens'           => array(
				'edit'   => array(
					'name'        => $this->name,
					// Changes the text of the Submit button
					// on the Edit page
					'submit_text' => __( 'Speichern', 'psource-chat' ),
				),
				'create' => array(
					'position' => $this->create_step_position,
				),
			),
		);
		parent::init( $args );
	}

	function PSOURCE_Chat_CPCommunitie() {
		$this->__construct();
	}

	public static function show_enable_chat_button() {
		global $cpc, $psource_chat;

		$checked = '';

		$cpc_group_id = 0;

		if ( isset( $cpc->groups->current_group->id ) ) {
			$cpc_group_id = $cpc->groups->current_group->id;
		} else if ( ( is_admin() ) && ( isset( $_GET['page'] ) ) && ( $_GET['page'] == "cpc-groups" ) ) {
			if ( isset( $_GET['gid'] ) ) {
				$cpc_group_id = $_GET['gid'];
			}
		}

		if ( $cpc_group_id ) {
			$enabled = groups_get_groupmeta( $cpc_group_id, self::settings_slug . '_enable' );
			if ( $enabled == "yes" ) {
				$checked = ' checked="checked" ';
			}
		}
		?><p>
		<label for="<?php echo self::settings_slug; ?>_enable">
			<input type="checkbox" name="<?php echo self::settings_slug; ?>_enable" <?php echo $checked; ?>
			       id="<?php echo self::settings_slug; ?>_enable"/> <?php _e( "Gruppenchat aktivieren", 'psource-chat' ); ?>
		</label></p><?php
	}

	function display( $group_id = null ) {
		global $cpc, $psource_chat;

		if ( groups_is_user_member( $cpc->loggedin_user->id, $cpc->groups->current_group->id ) ) {

			$chat_id = 'cpc-group-' . $cpc->groups->current_group->id;
			//echo "chat_id=[". $chat_id ."]<br />";

			$atts = groups_get_groupmeta( $cpc->groups->current_group->id, self::settings_slug );
			if ( empty( $atts ) ) {

				$atts = array(
					'id'                      => $chat_id,
					'session_type'            => 'cpc-group',
					'box_input_position'      => 'top',
					'box-width'               => '100%',
					'users_list_show'         => 'avatar',
					'users_list_position'     => 'right',
					'users_list_width'        => '10%',
					'users_list_avatar_width' => '50',

				);
			}

			// We changed the key because it was too long for the wp_options optin_name field
			if ( ( ! isset( $atts['id'] ) ) || ( $atts['id'] != $chat_id ) ) {
				$atts['id'] = $chat_id;
			}
			echo $psource_chat->process_chat_shortcode( $atts );
		} else {
			?>
			<p><?php _e( 'Du musst Mitglied dieser Gruppe sein, um den Chat nutzen zu können', 'psource-chat' ); ?></p><?php
		}
	}

	function settings_screen( $group_id = null ) {
		global $psource_chat, $cpc;

		// Set thsi so when we get to wp_footer it knows we need to load the JS/CSS for the Friends display.
		$psource_chat->_chat_plugin_settings['blocked_urls']['front'] = false;

		$cpc_group_id = 0;
		if ( isset( $cpc->groups->current_group->id ) ) {
			$cpc_group_id = $cpc->groups->current_group->id;
		} else if ( ( is_admin() ) && ( isset( $_GET['page'] ) ) && ( $_GET['page'] == "cpc-groups" ) ) {
			if ( isset( $_GET['gid'] ) ) {
				$cpc_group_id = $_GET['gid'];
			}
		}

		if ( $cpc_group_id ) {

			if ( ( groups_is_user_mod( $cpc->loggedin_user->id, $cpc_group_id ) )
			     || ( groups_is_user_admin( $cpc->loggedin_user->id, $cpc_group_id ) )
			     || ( is_super_admin() )
			) {

				self::show_enable_chat_button();

				$atts = groups_get_groupmeta( $cpc_group_id, self::settings_slug );
				if ( ! empty( $atts ) ) {
					$psource_chat->_chat_options['cpc-group'] = $atts;
				}

				// Add our tool tips.
				if ( ! class_exists( 'PSOURCE_HelpTooltips' ) ) {
					require_once( $psource_chat->_chat_plugin_settings['plugin_path'] . '/lib/class_wd_help_tooltips.php' );
				}
				$psource_chat->tips = new PSOURCE_HelpTooltips();
				$psource_chat->tips->set_icon_url( $psource_chat->_chat_plugin_settings['plugin_url'] . '/images/information.png' );


				include_once( $psource_chat->_chat_plugin_settings['plugin_path'] . '/lib/psource_chat_admin_panels.php' );
				$admin_panels = new psource_chat_admin_panels();

				$admin_panels->chat_settings_panel_buddypress();

				// not sure why Farbtastic will not work with wp_register/enqueue_script
				?>
				<link rel='stylesheet' id='farbtastic-css'
				      href='<?php echo admin_url(); ?>/css/farbtastic.css?ver=1.3u1'
				      type='text/css' media='all'/>
				<script type='text/javascript' src='<?php echo admin_url(); ?>/js/farbtastic.js'></script>
				<?php
				$psource_chat->tips->initialize();
			}
		}
	}

	function settings_screen_save( $group_id = null ) {
		global $cpc, $wpdb;

		if ( ( ! isset( $_POST['psource_chat_settings_save_wpnonce'] ) )
		     || ( ! wp_verify_nonce( $_POST['psource_chat_settings_save_wpnonce'], 'psource_chat_settings_save' ) )
		) {
			echo "HERE failed #1<br />";

			return false;
		}

		// Controls our menu visibility. See the __construct logic.
		if ( ( isset( $_POST[ self::settings_slug . '_enable' ] ) ) && ( $_POST[ self::settings_slug . '_enable' ] == "on" ) ) {
			$enabled = "yes";
		} else {
			$enabled = "no";
		}

		$cpc_group_id = 0;
		if ( isset( $cpc->groups->current_group->id ) ) {
			$cpc_group_id = $cpc->groups->current_group->id;
		} else if ( ( is_admin() ) && ( isset( $_GET['page'] ) ) && ( $_GET['page'] == "cpc-groups" ) ) {
			if ( isset( $_GET['gid'] ) ) {
				$cpc_group_id = $_GET['gid'];
			}
		}
		//echo "bp_group_id=[". $bp_group_id ."]<br />";
		//die();
		if ( $cpc_group_id ) {

			groups_update_groupmeta( $cpc_group_id, self::settings_slug . '_enable', $enabled );

			if ( ! isset( $_POST['chat'] ) ) {
				return false;
			}

			if ( ( groups_is_user_mod( $cpc->loggedin_user->id, $cpc_group_id ) )
			     || ( groups_is_user_admin( $cpc->loggedin_user->id, $cpc_group_id ) )
			     || ( is_super_admin() )
			) {

				$success = $chat_section = false;

				$chat_settings = $_POST['chat'];

				if ( isset( $chat_settings['section'] ) ) {
					$chat_section = $chat_settings['section'];
					unset( $chat_settings['section'] );
				}
				$chat_settings['session_type'] = 'cpc-group';
				$chat_settings['id']           = 'psource-chat-cpc-group-' . $cpc_group_id;
				$chat_settings['blog_id']      = $wpdb->blogid;
				groups_update_groupmeta( $cpc_group_id, self::settings_slug, $chat_settings );

				if ( ! is_admin() ) {
					/* Insert your edit screen save code here */
					$success = true;

					/* To post an error/success message to the screen, use the following */
					if ( ! $success ) {
						cpc_core_add_message( __( 'Fehler beim speichern. Bitte versuche es erneut', 'buddypress' ), 'error' );
					} else {
						cpc_core_add_message( __( 'Einstellungen erfolgreich gespeichert', 'buddypress' ) );
					}
				}
			} else {
				//echo "NOT GROUP ADMIN!<br />";
				//die();
			}
		}
	}

	/**
	 * create_screen() is an optional method that, when present, will
	 * be used instead of settings_screen() in the context of group
	 * creation.
	 *
	 * Similar overrides exist via the following methods:
	 *   * create_screen_save()
	 *   * edit_screen()
	 *   * edit_screen_save()
	 *   * admin_screen()
	 *   * admin_screen_save()
	 */
	function create_screen( $group_id = null ) {
		$setting = groups_get_groupmeta( $group_id, 'group_extension_example_2_setting' );
		self::show_enable_chat_button();
	}

	function create_screen_save( $group_id = null ) {
		global $cpc;

		if ( ( isset( $_POST[ self::settings_slug . '_enable' ] ) ) && ( $_POST[ self::settings_slug . '_enable' ] == "on" ) ) {
			groups_update_groupmeta( $bp->groups->new_group_id, self::settings_slug . '_enable', 'yes' );
		} else {
			groups_update_groupmeta( $bp->groups->new_group_id, self::settings_slug . '_enable', 'no' );
		}
	}
}