<?php
// Verhindern von direktem Zugriff
defined('ABSPATH') or die();

if ( ! class_exists( 'PSOURCE_Chat' ) ) {
	class PSOURCE_Chat {
		public $tips;
		private $_admin_panels;
		private $_pagehooks;
		var $chat_current_version = '2.5.2';
		//var $translation_domain = 'psource-chat';
		
		/**
		 * @var PSource_Chat_Emoji $emoji_system Modern emoji picker system
		 */
		var $emoji_system = null;

		/**
		 * @var        array $_chat_options Consolidated options
		 */
		var $_chat_plugin_settings = array(); // Container for setting within the running code.

		var $_chat_options = array(); // This is the local instance of the combined options from the wp_options table
		var $_chat_options_defaults = array();

		var $_admin_notice_messages = array();
		var $_admin_notice_messages_key = ''; // Used to store local admin notices message during setting save submits

		var $chat_localized = array(); // Contains localized strings passed to the JS file for display to user.

		var $chat_performance = array(); // Contains information related to execution time, memory usage and other performance metrics

		var $chat_sessions = array(); // Contains all active user chat_sessions known.
		var $chat_sessions_meta = array(); // Contains all session meta information last_row, sessions_status, deleted rows, active users.
		var $chat_user = array(); // Container for all user settings by chat_session. Things like if the chat window is minimized, sound on/off etc.
		var $chat_auth = array(); // Container global information for how user is authenticated, avatar src, name, ID, user_hash.

		var $user_meta = array(); // Contains usermeta information related to Chat functionality within wp-admin

		var $using_popup_out_template = false; // Flag set when loadin the pop-out template

		var $_show_own_admin = false; // Flag set in on_load_panels() and user in wp-footer and wp_enqueue_scripts to know what script/styles to load.

		var $_registered_scripts = array(); // array of scripts registered or enqueued via the various startup methods. User in wp_footer via print_scripts
		var $_registered_styles = array(); // array of styles registered or enqueued via the various startup methods. User in wp_footer via print_styles

		var $site_content = ''; // Holder for site (bottom corner chats) build during many actions.
		var $font_list; // Not used.

		var $chat_log_list_table = false;

		/**
		 * Initializing object
		 *
		 * Plugin register actions, filters and hooks.
		 */

		function __construct() {

			$this->chat_sessions      = array();
			$this->chat_sessions_meta = array();
			$this->chat_auth          = array();
			$this->chat_user          = array();
			$this->_chat_options      = array();
			
			// Emoji-System initialisieren
			$this->emoji_system = null;

			$this->_chat_plugin_settings['plugin_path']    = dirname( dirname( __FILE__ ) );
			$this->_chat_plugin_settings['plugin_url']     = plugins_url( '', dirname( __FILE__ ) );
			$this->_chat_plugin_settings['blocked_urls']   = array();
			$this->_chat_plugin_settings['network_active'] = false;
			// Legacy settings - no longer needed with modern AJAX system

			// Check our version against the options table
			if ( is_multisite() ) {
				$this->_chat_plugin_settings['options_version'] = get_site_option( 'psource-chat-version', false );
			} else {
				$this->_chat_plugin_settings['options_version'] = get_option( 'psource-chat-version', false );
			}

			// Short circut out. We don't need these during our SHORTINIT processing.
			if ( ( defined( 'PSOURCE_CHAT_SHORTINIT' ) ) && ( PSOURCE_CHAT_SHORTINIT == true ) ) {
				return;
			}


			// Activation deactivation hooks
			register_activation_hook( __FILE__, array( &$this, 'install' ) );
			register_deactivation_hook( __FILE__, array( &$this, 'uninstall' ) );

			add_action( 'init', array( &$this, 'init' ) );
			add_action( 'wp_head', array( &$this, 'wp_head' ) );
			add_action( 'wp_enqueue_scripts', array( &$this, 'wp_enqueue_scripts' ) );

			add_action( 'plugins_loaded', array( &$this, 'plugins_loaded' ) );

			add_action( 'admin_init', array( &$this, 'admin_init' ) );
			add_action( 'admin_head', array( &$this, 'wp_head' ) );
			add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) );
			add_action( 'admin_notices', array( &$this, 'admin_notices' ) );
			add_action( 'network_admin_notices', array( &$this, 'admin_notices' ) );

			add_action( 'wp', array( &$this, 'load_template' ), 1 );
//			add_action( 'wp_login', array( &$this, 'unset_cookies' ), 99, 2 );

			add_action( 'wp_footer', array( &$this, 'wp_footer' ), 1 );
			add_action( 'admin_footer', array( &$this, 'admin_footer' ), 1 );

			// Actions for editing/saving user profile.
			add_action( 'show_user_profile', array( &$this, 'chat_edit_user_profile' ) );
			add_action( 'personal_options_update', array( &$this, 'chat_save_user_profile' ) );
			add_action( 'edit_user_profile', array( &$this, 'chat_edit_user_profile' ) );
			add_action( 'edit_user_profile_update', array( &$this, 'chat_save_user_profile' ) );
			add_action( 'delete_user', array( &$this, 'chat_delete_user' ), 10, 2 );

			add_filter( 'manage_users_columns', array( &$this, 'chat_manage_users_columns' ) );
			add_filter( 'manage_users_custom_column', array( &$this, 'chat_manage_users_custom_column' ), 10, 3 );

			add_action( 'wp_ajax_chatProcess', array( &$this, 'process_chat_actions' ) );
			add_action( 'wp_ajax_nopriv_chatProcess', array( &$this, 'process_chat_actions' ) );

			// TinyMCE options
			add_action( 'wp_ajax_chatTinymceOptions', array( &$this, 'tinymce_options' ) );

			add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
			add_action( 'network_admin_menu', array( &$this, 'network_admin_menu' ) );

			// Uncomment when ready to show chat on the WP admin toolbar. Not ready 2013-01-31
			add_action( 'wp_before_admin_bar_render', 'psource_chat_wpadminbar_render' );

			add_shortcode( 'chat', array( &$this, 'process_chat_shortcode' ) );
		}

		/**
		 * Get the table name with prefixes
		 *
		 * @global    object $wpdb
		 *
		 * @param    string $table Table name
		 *
		 * @return    string            Table name complete with prefixes
		 */
		static function tablename( $table ) {
			global $wpdb;

			// We use a single table for all chats accross the network
			return $wpdb->base_prefix . 'psource_chat_' . $table;
		}

		/**
		 * Determine if we need to run the DB update options to bring the system up to date.
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */
		function check_upgrade() {

			if ( ( empty( $this->_chat_plugin_settings['options_version'] ) )
			     || ( version_compare( $this->chat_current_version, $this->_chat_plugin_settings['options_version'] ) > 0 )
			) {

				// Setup the database tables
				$this->install();

				if ( version_compare( $this->_chat_plugin_settings['options_version'], '2.6' ) < 0 ) {
					global $wpdb, $blog_id;

					$sql_str = $wpdb->prepare( "UPDATE `" . PSOURCE_Chat::tablename( 'log' ) . "` SET blog_id = %d WHERE blog_id = %d AND session_type != %s", 1, 0, 'network-site' );
					//echo "sql_str=[". $sql_str ."]<br />";
					$wpdb->query( $sql_str );

					$sql_str = $wpdb->prepare( "UPDATE `" . PSOURCE_Chat::tablename( 'message' ) . "` SET blog_id = %d WHERE blog_id = %d AND session_type != %s", 1, 0, 'network-site' );
					//echo "sql_str=[". $sql_str ."]<br />";
					$wpdb->query( $sql_str );

					$sql_str = $wpdb->prepare( "UPDATE `" . PSOURCE_Chat::tablename( 'users' ) . "` SET blog_id = %d WHERE blog_id = %d ", 1, 0 );
					//echo "sql_str=[". $sql_str ."]<br />";
					$wpdb->query( $sql_str );

					// Need to drop the 'created column from the log table as it it not used anymore
					//$sql_str = "ALTER TABLE `". PSOURCE_Chat::tablename('log') ."` DROP COLUMN `created`";
					//@$wpdb->query( $sql_str );

					// Now to do some data manipulation.
					$logs = $this->get_archives( array() );

					if ( ( $logs ) && ( count( $logs ) ) ) {

						foreach ( $logs as $log ) {

							$chat_session_log                 = array();
							$chat_session_log['blog_id']      = $log->blog_id;
							$chat_session_log['id']           = $log->chat_id;
							$chat_session_log['session_type'] = $log->session_type;

							$chat_session_log['since']       = strtotime( $log->start );
							$chat_session_log['end']         = strtotime( $log->end );
							$chat_session_log['log_limit']   = 0;
							$chat_session_log['orderby']     = 'ASC';
							$chat_session_log['archived']    = array( 'yes' );
							$chat_session_log['last_row_id'] = 0;

							$chat_log_rows = $this->chat_session_get_messages( $chat_session_log );
							if ( ( $chat_log_rows ) && ( count( $chat_log_rows ) ) ) {
								$row_ids = array();

								foreach ( $chat_log_rows as $row ) {

									$row_ids[] = $row->id;
								}

								if ( count( $row_ids ) ) {

									// Update the message rows to reference the log id
									$sql_str = $wpdb->prepare( "UPDATE `" . PSOURCE_Chat::tablename( 'message' ) . "` SET log_id = %d WHERE id IN (" . implode( ',', $row_ids ) . ");",
										$log->id );
									//echo "sql_str=[". $sql_str ."]<br />";
									$wpdb->query( $sql_str );
								}
							}
						}
					}

					// Now update the options
					if ( is_multisite() ) {
						update_site_option( 'psource-chat-version', $this->chat_current_version );
					} else {
						update_option( 'psource-chat-version', $this->chat_current_version );
					}

				}
			}
		}

		/**
		 * Activation hook
		 *
		 * Create tables if they don't exist and add plugin options
		 *
		 * @see        http://codex.wordpress.org/Function_Reference/register_activation_hook
		 *
		 * @global    object $wpdb
		 */
		function install() {
			global $wpdb;

			/**
			 * WordPress database upgrade/creation functions
			 */
			if ( ! function_exists( 'dbDelta' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			}

			// Get the correct character collate
			if ( ! empty( $wpdb->charset ) ) {
				$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
			}
			if ( ! empty( $wpdb->collate ) ) {
				$charset_collate .= " COLLATE $wpdb->collate";
			}

			$sql_table_message_1_3_x = "CREATE TABLE " . PSOURCE_Chat::tablename( 'message' ) . " (
			id BIGINT NOT NULL AUTO_INCREMENT,
			blog_id INT NOT NULL ,
			chat_id INT NOT NULL ,
			timestamp TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ,
			name VARCHAR( 255 ) CHARACTER SET utf8 NOT NULL ,
			avatar VARCHAR( 1024 ) CHARACTER SET utf8 NOT NULL ,
			message TEXT CHARACTER SET utf8 NOT NULL ,
			moderator ENUM( 'yes', 'no' ) NOT NULL DEFAULT 'no' ,
			archived ENUM( 'yes', 'no', 'yes-deleted', 'no-deleted' ) DEFAULT 'no' ,
			PRIMARY KEY  (id),
			KEY blog_id (blog_id),
			KEY chat_id (chat_id),
			KEY timestamp (timestamp),
			KEY archived (archived)
			) {$charset_collate};";

			$sql_table_log_1_3_x = "CREATE TABLE " . PSOURCE_Chat::tablename( 'log' ) . " (
			id BIGINT NOT NULL AUTO_INCREMENT,
			blog_id INT NOT NULL ,
			chat_id INT NOT NULL ,
			start TIMESTAMP DEFAULT '0000-00-00 00:00:00' ,
			end TIMESTAMP DEFAULT '0000-00-00 00:00:00' ,
			created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY blog_id (blog_id),
			KEY chat_id (chat_id)
			) {$charset_collate};";


			$sql_table_message_current = "CREATE TABLE " . PSOURCE_Chat::tablename( 'message' ) . " (
			id bigint(20) NOT NULL AUTO_INCREMENT ,
			blog_id bigint(20) unsigned NOT NULL ,
			chat_id varchar(40) NOT NULL ,
			session_type varchar(40) NOT NULL ,
			timestamp TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ,
			name varchar(255) NOT NULL ,
			avatar varchar(1024) NOT NULL ,
			auth_hash varchar(50) NOT NULL ,
			user_type varchar(50) NOT NULL ,
			ip_address varchar(50) NOT NULL ,
			message text NOT NULL ,
			moderator enum('no','yes') NOT NULL DEFAULT 'no' ,
			deleted enum('no','yes') NOT NULL DEFAULT 'no' ,
			archived enum('no','yes') NOT NULL DEFAULT 'no' ,
			log_id INT(11) NOT NULL ,
			PRIMARY KEY  (id) ,
			KEY blog_id (blog_id) ,
			KEY chat_id (chat_id) ,
			KEY auth_hash (auth_hash) ,
			KEY session_type (session_type) ,
			KEY timestamp (timestamp) ,
			KEY archived (archived) ,
			KEY log_id (log_id)
			) {$charset_collate};";

			$sql_table_log_current = "CREATE TABLE " . PSOURCE_Chat::tablename( 'log' ) . " (
			id BIGINT NOT NULL AUTO_INCREMENT,
			blog_id bigint(20) unsigned NOT NULL ,
			chat_id VARCHAR( 40 ) NOT NULL ,
			session_type VARCHAR( 40 ) NOT NULL,
			box_title VARCHAR( 50 ) NOT NULL,
			start TIMESTAMP DEFAULT '0000-00-00 00:00:00',
			end TIMESTAMP DEFAULT '0000-00-00 00:00:00',
			deleted enum('no','yes') NOT NULL DEFAULT 'no',
			archived enum('no','yes') NOT NULL DEFAULT 'yes',
			PRIMARY KEY  (id),
			KEY blog_id (blog_id),
			KEY chat_id (chat_id),
			KEY session_type (session_type),
			KEY deleted (deleted)
			) {$charset_collate};";

			$sql_table_users_current = "CREATE TABLE " . PSOURCE_Chat::tablename( 'users' ) . " (
		  	blog_id bigint(20) unsigned NOT NULL,
		  	chat_id varchar(40) NOT NULL,
		  	auth_hash varchar(50) NOT NULL,
		  	name varchar(255) NOT NULL,
		  	avatar varchar(255) NOT NULL,
		  	moderator enum('no','yes') NOT NULL DEFAULT 'no',
		  	last_polled timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  	entered timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
			user_type varchar(50) NOT NULL ,
		  	ip_address varchar(39) NOT NULL DEFAULT '',
		  	PRIMARY KEY  (blog_id,chat_id,auth_hash),
		  	KEY blog_id (blog_id),
		  	KEY chat_id (chat_id),
		  	KEY auth_hash (auth_hash),
		  	KEY last_polled (last_polled)
			) {$charset_collate};";

			if ( $wpdb->get_var( "SHOW TABLES LIKE '" . PSOURCE_Chat::tablename( 'message' ) . "'" ) != PSOURCE_Chat::tablename( 'message' ) ) {
				// First check if we have the old Chat 1.3.x table still around.
				if ( $wpdb->get_var( "SHOW TABLES LIKE '" . $wpdb->base_prefix . "chat_message'" ) == $wpdb->base_prefix . "chat_message" ) {

					// Create a new table with the same structure.
					dbDelta( $sql_table_message_1_3_x );

					// Now copy the rows from the old table into the new table.
					$sql_str = "INSERT INTO `" . PSOURCE_Chat::tablename( 'message' ) . "` SELECT * FROM " . $wpdb->base_prefix . "chat_message;";
					$wpdb->query( $sql_str );

					// Finally we alter the table structure to work with our new plugin
					dbDelta( $sql_table_message_current );

					$wpdb->query( $sql_str );

				} else {
					dbDelta( $sql_table_message_current );
				}

			} else {
				dbDelta( $sql_table_message_current );
			}

			if ( $wpdb->get_var( "SHOW TABLES LIKE '" . PSOURCE_Chat::tablename( 'log' ) . "'" ) != PSOURCE_Chat::tablename( 'log' ) ) {
				// First check if we have the old Chat 1.3.x table still around.
				if ( $wpdb->get_var( "SHOW TABLES LIKE '" . $wpdb->base_prefix . "chat_log'" ) == $wpdb->base_prefix . "chat_log" ) {
					dbDelta( $sql_table_log_1_3_x );

					// Now copy the rows from the old table into the new table.
					$sql_str = "INSERT INTO `" . PSOURCE_Chat::tablename( 'log' ) . "` SELECT * FROM " . $wpdb->base_prefix . "chat_log;";
					$wpdb->query( $sql_str );

					// Setup the chat log table
					dbDelta( $sql_table_log_current );

				} else {
					dbDelta( $sql_table_log_current );
				}
			} else {
				dbDelta( $sql_table_log_current );
			}

			if ( $wpdb->get_var( "SHOW TABLES LIKE '" . PSOURCE_Chat::tablename( 'users' ) . "'" ) != PSOURCE_Chat::tablename( 'users' ) ) {
				dbDelta( $sql_table_users_current );
			} else {
				dbDelta( $sql_table_users_current );
			}

			$this->load_configs();

			add_option( 'psource-chat-page', $this->_chat_options['page'] );
			add_option( 'psource-chat-site', $this->_chat_options['site'] );
			add_option( 'psource-chat-widget', $this->_chat_options['widget'] );
			add_option( 'psource-chat-global', $this->_chat_options['global'] );
			add_option( 'psource-chat-banned', $this->_chat_options['banned'] );

			if ( ( is_multisite() ) && ( is_network_admin() ) ) {
				add_site_option( 'psource-chat-site', $this->_chat_options['site'] );
				add_site_option( 'psource-chat-widget', $this->_chat_options['widget'] );
			}
		}

		/**
		 * Deactivation hook
		 *
		 * @see        http://codex.wordpress.org/Function_Reference/register_deactivation_hook
		 *
		 * @global    object $wpdb
		 */
		function uninstall() {
			global $wpdb;
			// Nothing to do
		}

		/**
		 * Loads the various config options from get_options calls. Parse the config array with the defaults in case new options were added/removed.
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */

		function load_configs() {
			global $blog_id;

			$this->set_option_defaults();

			$this->_chat_options['user-statuses'] = get_option( 'psource-chat-user-statuses', array() );
			$this->_chat_options['user-statuses'] = wp_parse_args( $this->_chat_options['user-statuses'], $this->_chat_options_defaults['user-statuses'] );

			if ( isset( $_POST['psource-chat-sessions'] ) ) {
				if ( ( is_array( $_POST['psource-chat-sessions'] ) ) && ( count( $_POST['psource-chat-sessions'] ) ) ) {

					foreach ( $_POST['psource-chat-sessions'] as $chat_session ) {
						$chat_id       = $chat_session['id'];
						$transient_key = "chat-session-" . $chat_session['id'] . '-' . $chat_session['session_type'];

						if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
							$chat_session_transient = get_option( $transient_key );
						} else {
							$chat_session_transient = get_transient( $transient_key );
						}

						if ( ( ! empty( $chat_session_transient ) ) && ( is_array( $chat_session_transient ) ) ) {
							$chat_session_merge = wp_parse_args( $chat_session, $chat_session_transient );
							if ( ( ! empty( $chat_session_merge ) ) && ( is_array( $chat_session_merge ) ) ) {

								$chat_session_merge = $this->chat_session_show_via_logs( $chat_session_merge );
							}

							$this->chat_sessions[ $chat_id ] = $chat_session_merge;
						}

						$this->chat_sessions_meta[ $chat_id ] = $this->chat_session_get_meta( $chat_session );
					}
				}
			}

			foreach ( $this->chat_sessions as $chat_id => $chat_session ) {
				if ( ( isset( $chat_session['template'] ) ) && ( $chat_session['template'] == "psource-chat-pop-out" ) ) {
					$this->using_popup_out_template = true;
				}
			}

			if ( ! empty( $_COOKIE['psource-chat-auth'] ) ) {
				$this->chat_auth = json_decode( stripslashes( $_COOKIE['psource-chat-auth'] ), true );
			} else {
				$this->chat_auth = array();
			}


			if ( ( ! isset( $this->chat_auth['type'] ) ) || ( empty( $this->chat_auth['type'] ) ) ) {
				if ( is_user_logged_in() ) {
					$this->chat_auth['type'] = 'wordpress';
				}
			}

			if ( ( isset( $this->chat_auth['type'] ) ) && ( $this->chat_auth['type'] == 'wordpress' ) ) {

				if ( is_user_logged_in() ) {
					// This is needed to update the user's activity we use to check if a user is online and available for chats
					$current_user = wp_get_current_user();

					psource_chat_update_user_activity( $current_user->ID );

					$this->chat_auth['type']         = 'wordpress';
					$this->chat_auth['name']         = $current_user->display_name;
					$this->chat_auth['email']        = $current_user->user_email;
					$this->chat_auth['auth_hash']    = md5( $current_user->ID );
					$this->chat_auth['profile_link'] = '';
					$this->chat_auth['ip_address']   = ( isset( $_SERVER['HTTP_X_FORWARD_FOR'] ) ) ? $_SERVER['HTTP_X_FORWARD_FOR'] : $_SERVER['REMOTE_ADDR'];

					$this->user_meta = get_user_meta( $current_user->ID, 'psource-chat-user', true );

					// Merge the user's meta info with the defaults.
					$this->user_meta = wp_parse_args( $this->user_meta, $this->_chat_options_defaults['user_meta'] );

					// Get the user's Chat status carried as part of the user_meta information
					$chat_user_status = psource_chat_get_user_status( $current_user->ID );
					if ( isset( $this->_chat_options['user-statuses'][ $chat_user_status ] ) ) {
						$this->user_meta['chat_user_status'] = $chat_user_status;
					} else {
						$this->user_meta['chat_user_status'] = $this->_chat_options_defaults['user_meta']['chat_user_status'];
					}

					if ( $this->user_meta['chat_name_display'] == "user_login" ) {
						$this->chat_auth['name'] = $current_user->user_login;
					} else {
						$this->chat_auth['name'] = $current_user->display_name;
					}

					// Use the modern avatar system with CP Community support and fallbacks
					if ( ( ! defined( 'PSOURCE_CHAT_SHORTINIT' ) ) || ( PSOURCE_CHAT_SHORTINIT != true ) ) {
						// Use our new robust avatar system
						$this->chat_auth['avatar'] = PSource_Chat_Avatar::get_chat_avatar( 
							$current_user->ID, 
							$current_user->data->user_email, 
							$this->chat_auth['name'] 
						);
					}
					$this->chat_auth['chat_status'] = $this->user_meta['chat_user_status'];
				} else {
					//log_chat_message(__FUNCTION__ .": is_user_logged_in: no");
					$this->chat_auth = array();
					//$this->chat_auth['type'] 	= '';
				}
			}

			foreach ( $this->chat_sessions as $session_id => $chat_session ) {

				if ( ( isset( $this->chat_auth['type'] ) ) && ( $this->chat_auth['type'] == 'wordpress' ) ) {
					if ( psource_chat_is_moderator( $chat_session ) ) {
						$this->chat_sessions[ $session_id ]['moderator'] = "yes";
					} else {
						$this->chat_sessions[ $session_id ]['moderator'] = "no";
					}

				} else {
					$this->chat_sessions[ $session_id ]['moderator'] = "no";
				}
			}

			// Grab user chat_user cookie data. The chat_user cookie contains user settings like sounds on/off or chat box maximixed/minimized.
			if ( ! empty( $_COOKIE['psource-chat-user'] ) ) {
				$this->chat_user = json_decode( stripslashes( $_COOKIE['psource-chat-user'] ), true );
			}

			if ( ! isset( $this->chat_user['__global__'] ) ) {
				$this->chat_user['__global__'] = array();
			}

			// The chat_user '__global__' array is the default settings used and compared to the individual settings from the chat_user cookie
			if ( ! isset( $this->chat_user['__global__']['status_max_min'] ) ) {
				$this->chat_user['__global__']['status_max_min'] = "max";
			}

			if ( ! isset( $this->chat_user['__global__']['sound_on_off'] ) ) {
				$this->chat_user['__global__']['sound_on_off'] = "on";
			}
			//Global chat Settings
			if ( ( is_multisite() ) && ( is_network_admin() ) ) {
				$this->_chat_options['global'] = get_site_option( 'psource-chat-global', array() );
			} else {
				$this->_chat_options['global'] = get_option( 'psource-chat-global', array() );
			}
			if ( empty( $this->_chat_options['global'] ) ) { // If empty see if we have an older version
				$_chat_options_global = get_option( 'chat_default', array() );
				if ( ! empty( $_chat_options_global ) ) {
					$this->_chat_options['global'] = $this->convert_config( 'global', $_chat_options_global );
				}
			}
			$this->_chat_options['global'] = wp_parse_args( $this->_chat_options['global'], $this->_chat_options_defaults['global'] );

			$this->_chat_options['page'] = get_option( 'psource-chat-page', array() );

			// Note: For tinymce options we want to allow to conditions. First if these option is NOT set
			// then we pull the default. But we need to allow the admin to clear the selection. So if the
			// item is found but empty we don't merge the default options.

			if ( empty( $this->_chat_options['page'] ) ) { // If empty see if we have an older version
				$_chat_options_default = get_option( 'chat_default', array() );
				if ( ! empty( $_chat_options_default ) ) {
					$this->_chat_options['page'] = $this->convert_config( 'page', $_chat_options_default );
				}
				$this->_chat_options['page']['tinymce_roles']      = array( 'administrator' );
				$this->_chat_options['page']['tinymce_post_types'] = array( 'page' );
			}
			$this->_chat_options['page'] = wp_parse_args( $this->_chat_options['page'], $this->_chat_options_defaults['page'] );

			$this->_chat_options['site'] = get_option( 'psource-chat-site', array() );
			if ( empty( $this->_chat_options['site'] ) ) { // If empty see if we have an older version
				$_chat_options_site = get_option( 'chat_site', array() );
				if ( ! empty( $_chat_options_site ) ) {
					$this->_chat_options['site'] = $this->convert_config( 'site', $_chat_options_site );
				}
			}
			$this->_chat_options['site'] = wp_parse_args( $this->_chat_options['site'], $this->_chat_options_defaults['site'] );

			$this->_chat_options['widget'] = get_option( 'psource-chat-widget', array() );
			$this->_chat_options['widget'] = wp_parse_args( $this->_chat_options['widget'], $this->_chat_options_defaults['widget'] );

			if ( is_network_admin() ) {
				$this->_chat_options['dashboard'] = get_site_option( 'psource-chat-dashboard', array() );
			} else {
				$this->_chat_options['dashboard'] = get_option( 'psource-chat-dashboard', array() );
			}
			$this->_chat_options['dashboard'] = wp_parse_args( $this->_chat_options['dashboard'], $this->_chat_options_defaults['dashboard'] );

			$this->_chat_options['bp-group'] = get_option( 'psource-chat-bp-group', array() );

			$this->_chat_options['bp-group'] = wp_parse_args( $this->_chat_options['bp-group'], $this->_chat_options_defaults['bp-group'] );
			$this->_chat_options['bp-group'] = wp_parse_args( $this->_chat_options['bp-group'], $this->_chat_options['global'] );

			$this->_chat_options['banned'] = get_option( 'psource-chat-banned', array() );
			$this->_chat_options['banned'] = wp_parse_args( $this->_chat_options['banned'], $this->_chat_options_defaults['banned'] );


			$this->_chat_options_defaults['fonts_list'] = array(
				"Arial"               => "Arial, Helvetica, sans-serif",
				"Arial Black"         => "'Arial Black', Gadget, sans-serif",
				"Bookman Old Style"   => "'Bookman Old Style', serif",
				"Comic Sans MS"       => "'Comic Sans MS', cursive",
				"Courier"             => "Courier, monospace",
				"Courier New"         => "'Courier New', Courier, monospace",
				"Garamond"            => "Garamond, serif",
				"Georgia"             => "Georgia, serif",
				"Impact"              => "Impact, Charcoal, sans-serif",
				"Lucida Console"      => "'Lucida Console', Monaco, monospace",
				"Lucida Sans Unicode" => "'Lucida Sans Unicode', 'Lucida Grande', sans-serif",
				"MS Sans Serif"       => "'MS Sans Serif', Geneva, sans-serif",
				"MS Serif"            => "'MS Serif', 'New York', sans-serif",
				"Palatino Linotype"   => "'Palatino Linotype', 'Book Antiqua', Palatino, serif",
				"Symbol"              => "Symbol, sans-serif",
				"Tahoma"              => "Tahoma, Geneva, sans-serif",
				"Times New Roman"     => "'Times New Roman', Times, serif",
				"Trebuchet MS"        => "'Trebuchet MS', Helvetica, sans-serif",
				"Verdana"             => "Verdana, Geneva, sans-serif",
				"Webdings"            => "Webdings, sans-serif",
				"Wingdings"           => "Wingdings, 'Zapf Dingbats', sans-serif"
			);

			if ( ( ! defined( 'PSOURCE_CHAT_SHORTINIT' ) ) || ( PSOURCE_CHAT_SHORTINIT != true ) ) {

				$this->_chat_plugin_settings['blocked_urls']['admin'] = psource_chat_check_is_blocked_urls( $this->get_option( 'blocked_admin_urls', 'global' ),
					$this->get_option( 'blocked_admin_urls_action', 'global' ), false );

				$this->_chat_plugin_settings['blocked_urls']['site'] = psource_chat_check_is_blocked_urls( $this->get_option( 'blocked_urls', 'site' ),
					$this->get_option( 'blocked_urls_action', 'site' ), false );

				$this->_chat_plugin_settings['blocked_urls']['widget'] = psource_chat_check_is_blocked_urls( $this->get_option( 'blocked_urls', 'widget' ),
					$this->get_option( 'blocked_urls_action', 'widget' ), true );

				if ( $this->get_option( 'load_jscss_all', 'global' ) == "enabled" ) {
					$this->_chat_plugin_settings['blocked_urls']['front'] = psource_chat_check_is_blocked_urls( $this->get_option( 'blocked_front_urls', 'global' ),
						$this->get_option( 'blocked_front_urls_action', 'global' ), false );
				} else {
					$this->_chat_plugin_settings['blocked_urls']['front'] = true;
				}
			}

			// Related to the Network settings.
			if ( $this->_chat_plugin_settings['network_active'] == true ) {
				$this->_chat_options['network-site'] = get_site_option( 'psource-chat-network-site', array() );
				$this->_chat_options['network-site'] = wp_parse_args( $this->_chat_options['network-site'], $this->_chat_options_defaults['network-site'] );
			}
		}

		/**
		 * Converts the config arrays from the old format (version 1.3.x) into the 2.x format.
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */
		function convert_config( $section, $old_config ) {

			$new_config = array();

			$old_to_new = array(
				'id'                           => 'id',
				'sound'                        => 'box_sound',
				'avatar'                       => 'row_name_avatar',
				'emoticons'                    => 'box_emoticons',
				'date_show'                    => 'row_date',
				'time_show'                    => 'row_time',
				'width'                        => 'box_width',
				'height'                       => 'box_height',
				'background_color'             => 'box_background_color',
				'background_row_area_color'    => 'row_area_background_color',
				'background_row_color'         => 'row_background_color',
				'row_border_color'             => 'row_border_color',
				'row_border_width'             => 'row_border_width',
				'row_spacing'                  => 'row_spacing',
				'background_highlighted_color' => 'box_new_message_color',
				'date_color'                   => 'row_date_color',
				'name_color'                   => 'row_name_color',
				'moderator_name_color'         => 'row_moderator_name_color',
				'special_color'                => 'special_color',
				'text_color'                   => 'row_text_color',
				'code_color'                   => 'row_code_color',
				'font'                         => 'box_font_family',
				'font_size'                    => 'box_font_size',
				'font'                         => 'row_font_family',
				'font_size'                    => 'row_font_size',
				'font'                         => 'row_message_input_font_family',
				'font_size'                    => 'row_message_input_font_size',
				'log_creation'                 => 'log_creation',
				'log_display'                  => 'log_display',
				'log_limit'                    => 'log_limit',
				'login_options'                => 'login_options',
				'moderator_roles'              => 'moderator_roles',
				'tinymce_roles'                => 'tinymce_roles',
				'tinymce_post_types'           => 'tinymce_post_types',
				'site'                         => 'bottom_corner',
			);

			foreach ( $old_config as $old_config_key => $old_config_val ) {
				// Remove emoty values to force new defaults.
				if ( ( empty( $old_config_val ) ) && ( $old_config_key != 'id' ) ) {
					continue;
				}

				// Partial kludge. The previous 'avatar' values were enabled/disabled. So we need to convert these to our
				// new values of 'avatar' or 'name'. Eventually we will have 'avatar_name' to show both. Tristate
				if ( $old_config_key == "avatar" ) {
					if ( $old_config_val == "enabled" ) {
						$new_config[ $old_config_key ] = "avatar";
					} else {
						$new_config[ $old_config_key ] = "name";
					}
				} else {
					if ( isset( $old_to_new[ $old_config_key ] ) ) {
						$new_config[ $old_to_new[ $old_config_key ] ] = $old_config_val;
					} else {
						$new_config[ $old_config_key ] = $old_config_val;
					}
				}
			}

			switch ( $section ) {
				case 'page':

					foreach ( $new_config as $_key => $_val ) {
						if ( ! isset( $this->_chat_options_defaults['page'][ $_key ] ) ) //echo "key removed[". $_key ."]<br />";
						{
							unset( $new_config[ $_key ] );
						}
					}

					break;

				case 'site':

					foreach ( $new_config as $_key => $_val ) {
						if ( ! isset( $this->_chat_options_defaults['site'][ $_key ] ) ) {
							unset( $new_config[ $_key ] );
						}
					}

					break;

				case 'global':
					foreach ( $new_config as $_key => $_val ) {
						if ( ! isset( $this->_chat_options_defaults['global'][ $_key ] ) ) {
							unset( $new_config[ $_key ] );
						}
					}
					break;

				default:
					break;
			}

			return $new_config;
		}

		/**
		 * Initializes our default options. All options processing is marge with the default arrays. This is how we add/remove options over time.
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */
		function set_option_defaults() {
			global $blog_id;
			$this->_chat_options_defaults['page'] = array(
				'id'                                       => '',
				'blog_id'                                  => $blog_id,
				'session_type'                             => 'page',
				'session_status'                           => defined( 'PSOURCE_CHAT_PAGE_SESSION_STATUS' ) ? PSOURCE_CHAT_PAGE_SESSION_STATUS : '',
				'blocked_ip_addresses_active'              => defined( 'PSOURCE_CHAT_PAGE_BLOCKED_IP_ADDRESSES_ACTIVE' ) ? PSOURCE_CHAT_PAGE_BLOCKED_IP_ADDRESSES_ACTIVE : 'enabled',
				'blocked_words_active'                     => defined( 'PSOURCE_CHAT_PAGE_BLOCKED_WORDS_ACTIVE' ) ? PSOURCE_CHAT_PAGE_BLOCKED_WORDS_ACTIVE : 'disabled',
				'session_status_message'                   => __( 'Der Moderator hat diese Chat-Sitzung geschlossen', 'psource-chat' ),
				'session_cleared_message'                  => __( 'Der Moderator hat die Chat-Nachrichten gelöscht', 'psource-chat' ),
				//session_status_auto_close' testweise
				//'session_status_auto_close'			       => 'yes',
				'box_title'                                => '',
				'box_width'                                => defined( 'PSOURCE_CHAT_PAGE_BOX_WIDTH' ) ? PSOURCE_CHAT_PAGE_BOX_WIDTH : '100%',
				'box_width_mobile_adjust'                  => defined( 'PSOURCE_CHAT_PAGE_BOX_WIDTH_MOBILE_ADJUST' ) ? PSOURCE_CHAT_PAGE_BOX_WIDTH_MOBILE_ADJUST : 'window',
				'box_height'                               => defined( 'PSOURCE_CHAT_PAGE_BOX_HEIGHT' ) ? PSOURCE_CHAT_PAGE_BOX_HEIGHT : '500px',
				'box_height_mobile_adjust'                 => defined( 'PSOURCE_CHAT_PAGE_BOX_HEIGHT_MOBILE_ADJUST' ) ? PSOURCE_CHAT_PAGE_BOX_HEIGHT_MOBILE_ADJUST : 'full',
				'box_class'                                => '',
				'box_sound'                                => defined( 'PSOURCE_CHAT_PAGE_BOX_SOUND' ) ? PSOURCE_CHAT_PAGE_BOX_SOUND : 'enabled',
				'box_popout'                               => defined( 'PSOURCE_CHAT_PAGE_BOX_POPOUT' ) ? PSOURCE_CHAT_PAGE_BOX_POPOUT : 'enabled',
				'box_moderator_footer'                     => defined( 'PSOURCE_CHAT_PAGE_BOX_MODERATOR_FOOTER' ) ? PSOURCE_CHAT_PAGE_BOX_MODERATOR_FOOTER : 'enabled',
				'box_input_position'                       => defined( 'PSOURCE_CHAT_PAGE_BOX_INPUT_POSITION' ) ? PSOURCE_CHAT_PAGE_BOX_INPUT_POSITION : 'bottom',
				'box_send_button_enable'                   => defined( 'PSOURCE_CHAT_PAGE_BOX_SEND_BUTTON_ENABLE' ) ? PSOURCE_CHAT_PAGE_BOX_SEND_BUTTON_ENABLE : 'disabled',
				'box_send_button_position'                 => defined( 'PSOURCE_CHAT_PAGE_BOX_SEND_BUTTON_POSITION' ) ? PSOURCE_CHAT_PAGE_BOX_SEND_BUTTON_POSITION : 'none',
				'box_send_button_label'                    => defined( 'PSOURCE_CHAT_PAGE_BOX_SEND_BUTTON_LABEL' ) ? PSOURCE_CHAT_PAGE_BOX_SEND_BUTTON_LABEL : 'Senden',
				'box_font_family'                          => defined( 'PSOURCE_CHAT_PAGE_BOX_FONT_FAMILY' ) ? PSOURCE_CHAT_PAGE_BOX_FONT_FAMILY : '',
				'box_font_size'                            => defined( 'PSOURCE_CHAT_PAGE_BOX_FONT_SIZE' ) ? PSOURCE_CHAT_PAGE_BOX_FONT_SIZE : '',
				'box_text_color'                           => defined( 'PSOURCE_CHAT_PAGE_BOX_TEXT_COLOR' ) ? PSOURCE_CHAT_PAGE_BOX_TEXT_COLOR : '#000000',
				'box_background_color'                     => defined( 'PSOURCE_CHAT_PAGE_BOX_BACKGROUND_COLOR' ) ? PSOURCE_CHAT_PAGE_BOX_BACKGROUND_COLOR : '#CCCCCC',
				'box_border_color'                         => defined( 'PSOURCE_CHAT_PAGE_BOX_BORDER_COLOR' ) ? PSOURCE_CHAT_PAGE_BOX_BORDER_COLOR : '#CCCCCC',
				'box_border_width'                         => defined( 'PSOURCE_CHAT_PAGE_BOX_BORDER_WIDTH' ) ? PSOURCE_CHAT_PAGE_BOX_BORDER_WIDTH : '1px',
				//'box_padding'							   => '3px',
				'box_emoticons'                            => defined( 'PSOURCE_CHAT_PAGE_BOX_EMOTICONS' ) ? PSOURCE_CHAT_PAGE_BOX_EMOTICONS : 'disabled',
				'file_uploads_enabled' 					   => defined( 'PSOURCE_CHAT_PAGE_FILE_UPLOADS_ENABLED' ) ? PSOURCE_CHAT_PAGE_FILE_UPLOADS_ENABLED : 'disabled',
				//Buttonbar testweise
				//'buttonbar'							       => 'disabled',
				'row_name_avatar'                          => defined( 'PSOURCE_CHAT_PAGE_ROW_NAME_AVATAR' ) ? PSOURCE_CHAT_PAGE_ROW_NAME_AVATAR : 'avatar',
				'row_avatar_width'                         => defined( 'PSOURCE_CHAT_PAGE_ROW_AVATAR_WIDTH' ) ? PSOURCE_CHAT_PAGE_ROW_AVATAR_WIDTH : '40px',
				'row_date'                                 => defined( 'PSOURCE_CHAT_PAGE_ROW_DATE' ) ? PSOURCE_CHAT_PAGE_ROW_DATE : 'disabled',
				'row_date_format'                          => defined( 'PSOURCE_CHAT_PAGE_ROW_DATE_FORMAT' ) ? PSOURCE_CHAT_PAGE_ROW_DATE_FORMAT : get_option( 'date_format' ),
				'row_time'                                 => defined( 'PSOURCE_CHAT_PAGE_ROW_TIME' ) ? PSOURCE_CHAT_PAGE_ROW_TIME : 'disabled',
				'row_time_format'                          => defined( 'PSOURCE_CHAT_PAGE_ROW_TIME_FORMAT' ) ? PSOURCE_CHAT_PAGE_ROW_TIME_FORMAT : get_option( 'time_format' ),
				'row_area_background_color'                => defined( 'PSOURCE_CHAT_PAGE_ROW_AREA_BACKGROUND_COLOR' ) ? PSOURCE_CHAT_PAGE_ROW_AREA_BACKGROUND_COLOR : '#F9F9F9',
				'row_background_color'                     => defined( 'PSOURCE_CHAT_PAGE_ROW_BACKGROUND_COLOR' ) ? PSOURCE_CHAT_PAGE_ROW_BACKGROUND_COLOR : '#FFFFFF',
				'row_border_color'                         => defined( 'PSOURCE_CHAT_PAGE_ROW_BORDER_COLOR' ) ? PSOURCE_CHAT_PAGE_ROW_BORDER_COLOR : '#CCCCCC',
				'row_border_width'                         => defined( 'PSOURCE_CHAT_PAGE_ROW_BORDER_WIDTH' ) ? PSOURCE_CHAT_PAGE_ROW_BORDER_WIDTH : '1px',
				'row_spacing'                              => defined( 'PSOURCE_CHAT_PAGE_ROW_SPACING' ) ? PSOURCE_CHAT_PAGE_ROW_SPACING : '3px',
				'row_message_input_font_size'              => defined( 'PSOURCE_CHAT_PAGE_ROW_MESSAGE_INPUT_FONT_SIZE' ) ? PSOURCE_CHAT_PAGE_ROW_MESSAGE_INPUT_FONT_SIZE : '',
				'row_message_input_font_family'            => defined( 'PSOURCE_CHAT_PAGE_ROW_MESSAGE_INPUT_FONT_FAMILY' ) ? PSOURCE_CHAT_PAGE_ROW_MESSAGE_INPUT_FONT_FAMILY : '',
				'row_message_input_height'                 => defined( 'PSOURCE_CHAT_PAGE_ROW_MESSAGE_INPUT_HEIGHT' ) ? PSOURCE_CHAT_PAGE_ROW_MESSAGE_INPUT_HEIGHT : '45px',
				'row_message_input_lock'                   => defined( 'PSOURCE_CHAT_PAGE_ROW_MESSAGE_INPUT_LOCK' ) ? PSOURCE_CHAT_PAGE_ROW_MESSAGE_INPUT_LOCK : 'vertical',
				'row_message_input_length'                 => defined( 'PSOURCE_CHAT_PAGE_ROW_MESSAGE_INPUT_LENGTH' ) ? PSOURCE_CHAT_PAGE_ROW_MESSAGE_INPUT_LENGTH : '450',
				'row_message_input_background_color'       => defined( 'PSOURCE_CHAT_PAGE_ROW_MESSAGE_INPUT_BACKGROUND_COLOR' ) ? PSOURCE_CHAT_PAGE_ROW_MESSAGE_INPUT_BACKGROUND_COLOR : '#FFFFFF',
				'row_message_input_text_color'             => defined( 'PSOURCE_CHAT_PAGE_ROW_MESSAGE_INPUT_TEXT_COLOR' ) ? PSOURCE_CHAT_PAGE_ROW_MESSAGE_INPUT_TEXT_COLOR : '#000000',
				'row_date_text_color'                      => defined( 'PSOURCE_CHAT_PAGE_ROW_DATE_TEXT_COLOR' ) ? PSOURCE_CHAT_PAGE_ROW_DATE_TEXT_COLOR : '#6699CC',
				'row_date_color'                           => defined( 'PSOURCE_CHAT_PAGE_ROW_DATE_COLOR' ) ? PSOURCE_CHAT_PAGE_ROW_DATE_COLOR : '#FFFFFF',
				'row_name_color'                           => defined( 'PSOURCE_CHAT_PAGE_ROW_NAME_COLOR' ) ? PSOURCE_CHAT_PAGE_ROW_NAME_COLOR : '#666666',
				'row_moderator_name_color'                 => defined( 'PSOURCE_CHAT_PAGE_ROW_MODERATOR_NAME_COLOR' ) ? PSOURCE_CHAT_PAGE_ROW_MODERATOR_NAME_COLOR : '#6699CC',
				'row_text_color'                           => defined( 'PSOURCE_CHAT_PAGE_ROW_TEXT_COLOR' ) ? PSOURCE_CHAT_PAGE_ROW_TEXT_COLOR : '#000000',
				'row_code_color'                           => defined( 'PSOURCE_CHAT_PAGE_ROW_CODE_COLOR' ) ? PSOURCE_CHAT_PAGE_ROW_CODE_COLOR : '#FFFFCC',
				'row_font_family'                          => defined( 'PSOURCE_CHAT_PAGE_ROW_FONT_FAMILY' ) ? PSOURCE_CHAT_PAGE_ROW_FONT_FAMILY : '',
				'row_font_size'                            => defined( 'PSOURCE_CHAT_PAGE_ROW_FONT_SIZE' ) ? PSOURCE_CHAT_PAGE_ROW_FONT_SIZE : '',
				'log_creation'                             => defined( 'PSOURCE_CHAT_PAGE_LOG_CREATION' ) ? PSOURCE_CHAT_PAGE_LOG_CREATION : 'disabled',
				'log_display'                              => defined( 'PSOURCE_CHAT_PAGE_LOG_DISPLAY' ) ? PSOURCE_CHAT_PAGE_LOG_DISPLAY : 'disabled',
				'log_display_label'                        => defined( 'PSOURCE_CHAT_PAGE_LOG_DISPLAY_LABEL' ) ? PSOURCE_CHAT_PAGE_LOG_DISPLAY_LABEL : __( 'Chat Verlauf', 'psource-chat' ),
				'log_display_limit'                        => defined( 'PSOURCE_CHAT_PAGE_LOG_DISPLAY_LIMIT' ) ? PSOURCE_CHAT_PAGE_LOG_DISPLAY_LIMIT : 10,
				'log_display_hide_session'                 => defined( 'PSOURCE_CHAT_PAGE_LOG_DISPLAY_HIDE_SESSION' ) ? PSOURCE_CHAT_PAGE_LOG_DISPLAY_HIDE_SESSION : 'show',
				'log_display_role_level'                   => defined( 'PSOURCE_CHAT_PAGE_LOG_DISPLAY_ROLE_LEVEL' ) ? PSOURCE_CHAT_PAGE_LOG_DISPLAY_ROLE_LEVEL : 'public',
				'log_limit'                                => defined( 'PSOURCE_CHAT_PAGE_LOG_LIMIT' ) ? PSOURCE_CHAT_PAGE_LOG_LIMIT : '100',
				//'log_purge'							=>	'',
				'login_options'                            => array( 'public_user' ),
				'moderator_roles'                          => array(),
				'users_list_show'                          => defined( 'PSOURCE_CHAT_PAGE_USERS_LIST_SHOW' ) ? PSOURCE_CHAT_PAGE_USERS_LIST_SHOW : 'avatar',
				'users_list_position'                      => defined( 'PSOURCE_CHAT_PAGE_USERS_LIST_POSITION' ) ? PSOURCE_CHAT_PAGE_USERS_LIST_POSITION : 'none',
				'users_list_style'                         => defined( 'PSOURCE_CHAT_PAGE_USERS_LIST_STYLE' ) ? PSOURCE_CHAT_PAGE_USERS_LIST_STYLE : 'split',
				'users_list_width'                         => defined( 'PSOURCE_CHAT_PAGE_USERS_LIST_WIDTH' ) ? PSOURCE_CHAT_PAGE_USERS_LIST_WIDTH : '25%',
				'users_list_avatar_width'                  => defined( 'PSOURCE_CHAT_PAGE_USERS_LIST_AVATAR_WIDTH' ) ? PSOURCE_CHAT_PAGE_USERS_LIST_AVATAR_WIDTH : '30px',
				'users_list_moderator_avatar_border_color' => defined( 'PSOURCE_CHAT_PAGE_USERS_LIST_MODERATOR_AVATAR_BORDER_COLOR' ) ? PSOURCE_CHAT_PAGE_USERS_LIST_MODERATOR_AVATAR_BORDER_COLOR : '#FFFFFF',
				'users_list_user_avatar_border_color'      => defined( 'PSOURCE_CHAT_PAGE_USERS_LIST_USER_AVATAR_BORDER_COLOR' ) ? PSOURCE_CHAT_PAGE_USERS_LIST_USER_AVATAR_BORDER_COLOR : '#FFFFFF',
				'users_list_avatar_border_width'           => defined( 'PSOURCE_CHAT_PAGE_USERS_LIST_AVATAR_BORDER_WIDTH' ) ? PSOURCE_CHAT_PAGE_USERS_LIST_AVATAR_BORDER_WIDTH : '1px',
				'users_list_threshold_delete'              => defined( 'PSOURCE_CHAT_PAGE_USERS_LIST_THRESHOLD_DELETE' ) ? PSOURCE_CHAT_PAGE_USERS_LIST_THRESHOLD_DELETE : '20',
				'users_list_background_color'              => defined( 'PSOURCE_CHAT_PAGE_USERS_LIST_BACKGROUND_COLOR' ) ? PSOURCE_CHAT_PAGE_USERS_LIST_BACKGROUND_COLOR : '#FFFFFF',
				'users_list_name_color'                    => defined( 'PSOURCE_CHAT_PAGE_USERS_LIST_NAME_COLOR' ) ? PSOURCE_CHAT_PAGE_USERS_LIST_NAME_COLOR : '#000000',
				'users_list_moderator_color'               => defined( 'PSOURCE_CHAT_PAGE_USERS_LIST_MODERATOR_COLOR' ) ? PSOURCE_CHAT_PAGE_USERS_LIST_MODERATOR_COLOR : '#000000',
				'users_list_header'                        => __( 'Aktive Teilnehmer', 'psource-chat' ),
				'users_list_font_size'                     => defined( 'PSOURCE_CHAT_PAGE_USERS_LIST_FONT_SIZE' ) ? PSOURCE_CHAT_PAGE_USERS_LIST_FONT_SIZE : '',
				'users_list_header_color'                  => defined( 'PSOURCE_CHAT_PAGE_USERS_LIST_HEADER_COLOR' ) ? PSOURCE_CHAT_PAGE_USERS_LIST_HEADER_COLOR : '#000000',
				'users_list_header_font_family'            => defined( 'PSOURCE_CHAT_PAGE_USERS_LIST_HEADER_FONT_FAMILY' ) ? PSOURCE_CHAT_PAGE_USERS_LIST_HEADER_FONT_FAMILY : '',
				'users_list_header_font_size'              => defined( 'PSOURCE_CHAT_PAGE_USERS_LIST_HEADER_FONT_SIZE' ) ? PSOURCE_CHAT_PAGE_USERS_LIST_HEADER_FONT_SIZE : '',
				'users_enter_exit_status'                  => 'disabled',
				'users_enter_exit_delay'                   => '2',
				'tinymce_roles'                            => array(),
				'tinymce_post_types'                       => array(),
				'noauth_view'                              => defined( 'PSOURCE_CHAT_PAGE_NOAUTH_VIEW' ) ? PSOURCE_CHAT_PAGE_NOAUTH_VIEW : 'default',
				'noauth_login_message'                     => __( 'Gib einfach Deine E-Mail-Adresse und den gewünschten Benutzernamen ein:', 'psource-chat' ),
				'noauth_login_prompt'                      => __( 'Du musst Dich anmelden, um am Chat teilnehmen zu können', 'psource-chat' ),
				'box_input_moderator_hide'				=>	'disabled',
				'box_input_moderator_hide_label'		=>	__('Warte auf Moderator', 'psource-chat'),
				'update_transient'                         => 'enabled'
			);

			$this->_chat_options_defaults['site'] = wp_parse_args( array(
				'session_type'               => 'site',
				'bottom_corner'              => defined( 'PSOURCE_CHAT_SITE_BOTTOM_CORNER' ) ? PSOURCE_CHAT_SITE_BOTTOM_CORNER : 'disabled',
				'status_max_min'             => defined( 'PSOURCE_CHAT_SITE_STATUS_MAX_MIN' ) ? PSOURCE_CHAT_SITE_STATUS_MAX_MIN : 'min',
				'poll_max_min'               => defined( 'PSOURCE_CHAT_SITE_POLL_MAX_MIN' ) ? PSOURCE_CHAT_SITE_POLL_MAX_MIN : 'disabled',
				'bottom_corner_wpadmin'      => defined( 'PSOURCE_CHAT_SITE_BOTTOM_CORNER_WPADMIN' ) ? PSOURCE_CHAT_SITE_BOTTOM_CORNER_WPADMIN : 'disabled',
				'box_width'                  => defined( 'PSOURCE_CHAT_SITE_BOX_WIDTH' ) ? PSOURCE_CHAT_SITE_BOX_WIDTH : '200px',
				'box_height'                 => defined( 'PSOURCE_CHAT_SITE_BOX_HEIGHT' ) ? PSOURCE_CHAT_SITE_BOX_HEIGHT : '300px',
				'box_position_h'             => defined( 'PSOURCE_CHAT_SITE_BOX_POSITION_H' ) ? PSOURCE_CHAT_SITE_BOX_POSITION_H : 'right',
				'box_position_v'             => defined( 'PSOURCE_CHAT_SITE_BOX_POSITION_V' ) ? PSOURCE_CHAT_SITE_BOX_POSITION_V : 'bottom',
				'box_position_adjust_mobile' => defined( 'PSOURCE_CHAT_SITE_BOX_POSITION_ADJUST_MOBILE' ) ? PSOURCE_CHAT_SITE_BOX_POSITION_ADJUST_MOBILE : 'enabled',
				'box_width_mobile_adjust'    => defined( 'PSOURCE_CHAT_SITE_BOX_WIDTH_MOBILE_ADJUST' ) ? PSOURCE_CHAT_SITE_BOX_WIDTH_MOBILE_ADJUST : 'window',
				'box_height_mobile_adjust'   => defined( 'PSOURCE_CHAT_SITE_BOX_HEIGHT_MOBILE_ADJUST' ) ? PSOURCE_CHAT_SITE_BOX_HEIGHT_MOBILE_ADJUST : 'window',
				'box_offset_h'               => defined( 'PSOURCE_CHAT_SITE_BOX_OFFSET_H' ) ? PSOURCE_CHAT_SITE_BOX_OFFSET_H : '0px',
				'box_offset_v'               => defined( 'PSOURCE_CHAT_SITE_BOX_OFFSET_V' ) ? PSOURCE_CHAT_SITE_BOX_OFFSET_V : '0px',
				'box_spacing_h'              => defined( 'PSOURCE_CHAT_SITE_BOX_SPACING_H' ) ? PSOURCE_CHAT_SITE_BOX_SPACING_H : '10px',
				'box_shadow_show'            => defined( 'PSOURCE_CHAT_SITE_BOX_SHADOW_SHOW' ) ? PSOURCE_CHAT_SITE_BOX_SHADOW_SHOW : 'enabled',
				'box_shadow_v'               => defined( 'PSOURCE_CHAT_SITE_BOX_SHADOW_V' ) ? PSOURCE_CHAT_SITE_BOX_SHADOW_V : '10px',
				'box_shadow_h'               => defined( 'PSOURCE_CHAT_SITE_BOX_SHADOW_H' ) ? PSOURCE_CHAT_SITE_BOX_SHADOW_H : '10px',
				'box_shadow_blur'            => defined( 'PSOURCE_CHAT_SITE_BOX_SHADOW_BLUR' ) ? PSOURCE_CHAT_SITE_BOX_SHADOW_BLUR : '5px',
				'box_shadow_spread'          => defined( 'PSOURCE_CHAT_SITE_BOX_SHADOW_SPREAD' ) ? PSOURCE_CHAT_SITE_BOX_SHADOW_SPREAD : '0px',
				'box_shadow_color'           => defined( 'PSOURCE_CHAT_SITE_BOX_SHADOW_COLOR' ) ? PSOURCE_CHAT_SITE_BOX_SHADOW_COLOR : '#888888',
				'box_resizable'              => 'disabled',
				'users_list_show'            => defined( 'PSOURCE_CHAT_SITE_USERS_LIST_SHOW' ) ? PSOURCE_CHAT_SITE_USERS_LIST_SHOW : 'avatar',
				'users_list_position'        => defined( 'PSOURCE_CHAT_SITE_USERS_LIST_POSITION' ) ? PSOURCE_CHAT_SITE_USERS_LIST_POSITION : 'none',
				'users_list_style'           => defined( 'PSOURCE_CHAT_SITE_USERS_LIST_STYLE' ) ? PSOURCE_CHAT_SITE_USERS_LIST_STYLE : 'split',
				'log_creation'               => defined( 'PSOURCE_CHAT_SITE_LOG_CREATION' ) ? PSOURCE_CHAT_SITE_LOG_CREATION : 'disabled',
				'log_display'                => defined( 'PSOURCE_CHAT_SITE_LOG_DISPLAY' ) ? PSOURCE_CHAT_SITE_LOG_DISPLAY : 'disabled',
				'invite-info'                => array(),
				'blocked_on_shortcode'       => defined( 'PSOURCE_CHAT_SITE_BLOCKED_ON_SHORTCODE' ) ? PSOURCE_CHAT_SITE_BLOCKED_ON_SHORTCODE : 'disabled',
				'private_reopen_after_exit'  => 'enabled'

			), $this->_chat_options_defaults['page']
			);

			// Special section for Widgets. Based on the Page settings
			$this->_chat_options_defaults['widget'] = wp_parse_args( array(
				'session_type'             => 'widget',
				'box_width'                => defined( 'PSOURCE_CHAT_WIDGET_BOX_WIDTH' ) ? PSOURCE_CHAT_WIDGET_BOX_WIDTH : '100%',
				'box_height'               => defined( 'PSOURCE_CHAT_WIDGET_BOX_HEIGHT' ) ? PSOURCE_CHAT_WIDGET_BOX_HEIGHT : '300px',
				'box_width_mobile_adjust'  => defined( 'PSOURCE_CHAT_WIDGET_BOX_WIDTH_MOBILE_ADJUST' ) ? PSOURCE_CHAT_WIDGET_BOX_WIDTH_MOBILE_ADJUST : 'window',
				'box_height_mobile_adjust' => defined( 'PSOURCE_CHAT_WIDGET_BOX_HEIGHT_MOBILE_ADJUST' ) ? PSOURCE_CHAT_WIDGET_BOX_HEIGHT_MOBILE_ADJUST : 'window',
				'box_new_message_color'    => defined( 'PSOURCE_CHAT_WIDGET_BOX_NEW_MESSAGE_COLOR' ) ? PSOURCE_CHAT_WIDGET_BOX_NEW_MESSAGE_COLOR : '#ff8400',
				'users_list_show'          => defined( 'PSOURCE_CHAT_WIDGET_USERS_LIST_SHOW' ) ? PSOURCE_CHAT_WIDGET_USERS_LIST_SHOW : 'avatar',
				'users_list_position'      => defined( 'PSOURCE_CHAT_WIDGET_USERS_LIST_POSITION' ) ? PSOURCE_CHAT_WIDGET_USERS_LIST_POSITION : 'none',
				'users_list_style'         => defined( 'PSOURCE_CHAT_WIDGET_USERS_LIST_STYLE' ) ? PSOURCE_CHAT_WIDGET_USERS_LIST_STYLE : 'split',
				'box_border_color'         => defined( 'PSOURCE_CHAT_WIDGET_BOX_BORDER_COLOR' ) ? PSOURCE_CHAT_WIDGET_BOX_BORDER_COLOR : '#4b96e2',
				//'box_padding'						=>	'2px',
				'box_border_width'         => defined( 'PSOURCE_CHAT_WIDGET_BOX_BORDER_WIDTH' ) ? PSOURCE_CHAT_WIDGET_BOX_BORDER_WIDTH : '2px',
				'row_avatar_width'         => defined( 'PSOURCE_CHAT_WIDGET_ROW_AVATAR_WIDTH' ) ? PSOURCE_CHAT_WIDGET_ROW_AVATAR_WIDTH : '30px',
				'row_message_input_height' => defined( 'PSOURCE_CHAT_WIDGET_ROW_MESSAGE_INPUT_HEIGHT' ) ? PSOURCE_CHAT_WIDGET_ROW_MESSAGE_INPUT_HEIGHT : '35px',
				'log_creation'             => defined( 'PSOURCE_CHAT_WIDGET_LOG_CREATION' ) ? PSOURCE_CHAT_WIDGET_LOG_CREATION : 'disabled',
				'log_display'              => defined( 'PSOURCE_CHAT_WIDGET_LOG_DISPLAY' ) ? PSOURCE_CHAT_WIDGET_LOG_DISPLAY : 'disabled',
				'blocked_urls_action'      => defined( 'PSOURCE_CHAT_WIDGET_BLOCKED_URLS_ACTION' ) ? PSOURCE_CHAT_WIDGET_BLOCKED_URLS_ACTION : 'exclude',
				'blocked_urls'             => array(),
				'blocked_on_shortcode'     => defined( 'PSOURCE_CHAT_WIDGET_BLOCKED_ON_SHORTCODE' ) ? PSOURCE_CHAT_WIDGET_BLOCKED_ON_SHORTCODE : 'disabled',

			), $this->_chat_options_defaults['page']
			);

			$this->_chat_options_defaults['dashboard'] = wp_parse_args( array(
				'session_type'                    => 'dashboard',
				'dashboard_widget'                => defined( 'PSOURCE_CHAT_DASHBOARD_DASHBOARD_WIDGET' ) ? PSOURCE_CHAT_DASHBOARD_DASHBOARD_WIDGET : 'disabled',
				'dashboard_widget_title'          => __( 'Chat', 'psource-chat' ),
				'dashboard_widget_height'         => defined( 'PSOURCE_CHAT_DASHBOARD_DASHBOARD_WIDGET_HEIGHT' ) ? PSOURCE_CHAT_DASHBOARD_DASHBOARD_WIDGET_HEIGHT : '200px',
				'dashboard_status_widget'         => defined( 'PSOURCE_CHAT_DASHBOARD_DASHBOARD_STATUS_WIDGET' ) ? PSOURCE_CHAT_DASHBOARD_DASHBOARD_STATUS_WIDGET : 'disabled',
				'dashboard_status_widget_title'   => __( 'Chat Status', 'psource-chat' ),
				'dashboard_friends_widget'        => defined( 'PSOURCE_CHAT_DASHBOARD_DASHBOARD_FRIENDS_WIDGET' ) ? PSOURCE_CHAT_DASHBOARD_DASHBOARD_FRIENDS_WIDGET : 'disabled',
				'dashboard_friends_widget_height' => defined( 'PSOURCE_CHAT_DASHBOARD_DASHBOARD_FRIENDS_WIDGET_HEIGHT' ) ? PSOURCE_CHAT_DASHBOARD_DASHBOARD_FRIENDS_WIDGET_HEIGHT : '200px',
				'dashboard_friends_widget_title'  => __( 'Chat Friends', 'psource-chat' ),
				'box_border_color'                => defined( 'PSOURCE_CHAT_DASHBOARD_BOX_BORDER_COLOR' ) ? PSOURCE_CHAT_DASHBOARD_BOX_BORDER_COLOR : '#F1F1F1',
			), $this->_chat_options_defaults['widget']
			);

			$this->_chat_options_defaults['bp-group'] = wp_parse_args( array(
				'session_type'             => 'bp-group',
				'box_width'                => defined( 'PSOURCE_CHAT_BP_BOX_WIDTH' ) ? PSOURCE_CHAT_BP_BOX_WIDTH : '100%',
				'box_height'               => defined( 'PSOURCE_CHAT_BP_BOX_HEIGHT' ) ? PSOURCE_CHAT_BP_BOX_HEIGHT : ' 400px',
				'box_popout'               => defined( 'PSOURCE_CHAT_BP_BOX_POPOUT' ) ? PSOURCE_CHAT_BP_BOX_POPOUT : 'disabled',
				'bottom_corner'            => defined( 'PSOURCE_CHAT_BP_BOTTOM_CORNER' ) ? PSOURCE_CHAT_BP_BOTTOM_CORNER : 'disabled',
				'users_list_show'          => defined( 'PSOURCE_CHAT_BP_USERS_LIST_SHOW' ) ? PSOURCE_CHAT_BP_USERS_LIST_SHOW : 'avatar',
				'users_list_position'      => defined( 'PSOURCE_CHAT_BP_USERS_LIST_POSITION' ) ? PSOURCE_CHAT_BP_USERS_LIST_POSITION : 'right',
				'users_list_style'         => defined( 'PSOURCE_CHAT_BP_USERS_LIST_STYLE' ) ? PSOURCE_CHAT_BP_USERS_LIST_STYLE : 'split',
				'users_list_width'         => defined( 'PSOURCE_CHAT_BP_USERS_LIST_WIDTH' ) ? PSOURCE_CHAT_BP_USERS_LIST_WIDTH : '10%',
				'users_list_avatar_width'  => defined( 'PSOURCE_CHAT_BP_USERS_LIST_AVATAR_WIDTH' ) ? PSOURCE_CHAT_BP_USERS_LIST_AVATAR_WIDTH : '50px',
				'row_message_input_height' => defined( 'PSOURCE_CHAT_BP_ROW_MESSAGE_INPUT_HEIGHT' ) ? PSOURCE_CHAT_BP_ROW_MESSAGE_INPUT_HEIGHT : '45px',
				'box_border_color'         => defined( 'PSOURCE_CHAT_BP_BOX_BORDER_COLOR' ) ? PSOURCE_CHAT_BP_BOX_BORDER_COLOR : '#4b96e2',
				'box_border_width'         => defined( 'PSOURCE_CHAT_BP_BOX_BORDER_WIDTH' ) ? PSOURCE_CHAT_BP_BOX_BORDER_WIDTH : '1px',
				'row_avatar_width'         => defined( 'PSOURCE_CHAT_BP_ROW_AVATAR_WIDTH' ) ? PSOURCE_CHAT_BP_ROW_AVATAR_WIDTH : '30px',
				'row_message_input_height' => defined( 'PSOURCE_CHAT_BP_ROW_MESSAGE_INPUT_HEIGHT' ) ? PSOURCE_CHAT_BP_ROW_MESSAGE_INPUT_HEIGHT : '35px',
				'log_creation'             => defined( 'PSOURCE_CHAT_BP_LOG_CREATION' ) ? PSOURCE_CHAT_BP_LOG_CREATION : 'disabled',
				'log_display'              => defined( 'PSOURCE_CHAT_BP_LOG_DISPLAY' ) ? PSOURCE_CHAT_BP_LOG_DISPLAY : 'disabled',
				'blocked_urls_action'      => defined( 'PSOURCE_CHAT_BP_BLOCKED_URLS_ACTION' ) ? PSOURCE_CHAT_BP_BLOCKED_URLS_ACTION : 'exclude',
				'blocked_urls'             => array(),
			), $this->_chat_options_defaults['page']
			);

			$this->_chat_options_defaults['user-statuses'] = array(
				'available'   => __( 'Online', 'psource-chat' ),
				'unavailable' => __( 'Abwesend', 'psource-chat' ),
				'away'        => __( 'Offline', 'psource-chat' )
			);
			$this->_chat_options_defaults['user-statuses'] = apply_filters( 'psource-chat-user-statuses', $this->_chat_options_defaults['user-statuses'] );

			$this->_chat_options_defaults['global'] = array(
				'session_poll_interval_messages' => defined( 'PSOURCE_CHAT_GLOBAL_SESSION_POLL_INTERVAL_MESSAGES' ) ? PSOURCE_CHAT_GLOBAL_SESSION_POLL_INTERVAL_MESSAGES : '3',
				'session_poll_interval_invites'  => defined( 'PSOURCE_CHAT_GLOBAL_SESSION_POLL_INTERVAL_INVITES' ) ? PSOURCE_CHAT_GLOBAL_SESSION_POLL_INTERVAL_INVITES : '3',
				'session_poll_interval_meta'     => defined( 'PSOURCE_CHAT_GLOBAL_SESSION_POLL_INTERVAL_META' ) ? PSOURCE_CHAT_GLOBAL_SESSION_POLL_INTERVAL_META : '5',
				'session_poll_type'              => defined( 'PSOURCE_CHAT_GLOBAL_SESSION_POLL_TYPE' ) ? PSOURCE_CHAT_GLOBAL_SESSION_POLL_TYPE : 'modern',
				'session_performance'            => 'disabled',
				'blocked_ip_addresses_active'    => defined( 'PSOURCE_CHAT_GLOBAL_BLOCKED_IP_ADDRESSES_ACTIVE' ) ? PSOURCE_CHAT_GLOBAL_BLOCKED_IP_ADDRESSES_ACTIVE : 'disabled',
				'blocked_ip_addresses'           => array( '0.0.0.0' ),
				'blocked_admin_urls_action'      => 'exclude',
				'blocked_admin_urls'             => array(),
				'load_jscss_all'                 => 'enabled',
				'blocked_front_urls_action'      => 'exclude',
				'blocked_front_urls'             => array(),
				'blocked_users'                  => array(),
				'blocked_words_active'           => 'disabled',
				'blocked_ip_message'             => __( 'Dein Konto wurde von der Teilnahme an dieser Chat-Sitzung ausgeschlossen. Bitte wende Dich an den Site-Administrator, um weitere Informationen zu diesem Verbot zu erhalten.', 'psource-chat' ),
				'bp_menu_label'                  => __( 'Gruppen Chat', 'psource-chat' ),
				'bp_menu_slug'                   => 'psource-chat-bp-group',
				'bp_group_show_site'             => defined( 'PSOURCE_CHAT_GLOBAL_BP_GROUP_SHOW_SITE' ) ? PSOURCE_CHAT_GLOBAL_BP_GROUP_SHOW_SITE : 'enabled',
				'bp_group_admin_show_site'       => defined( 'PSOURCE_CHAT_GLOBAL_BP_GROUP_ADMIN_SHOW_SITE' ) ? PSOURCE_CHAT_GLOBAL_BP_GROUP_ADMIN_SHOW_SITE : 'enabled',
				'bp_group_show_widget'           => defined( 'PSOURCE_CHAT_GLOBAL_BP_GROUP_SHOW_WIDGET' ) ? PSOURCE_CHAT_GLOBAL_BP_GROUP_SHOW_WIDGET : 'enabled',
				'bp_group_admin_show_widget'     => defined( 'PSOURCE_CHAT_GLOBAL_BP_GROUP_ADMIN_SHOW_WIDGET' ) ? PSOURCE_CHAT_GLOBAL_BP_GROUP_ADMIN_SHOW_WIDGET : 'enabled',
				'bp_form_background_color'       => defined( 'PSOURCE_CHAT_GLOBAL_BP_FORM_BACKGROUND_COLOR' ) ? PSOURCE_CHAT_GLOBAL_BP_FORM_BACKGROUND_COLOR : '#FDFDFD',
				'bp_form_label_color'            => defined( 'PSOURCE_CHAT_GLOBAL_BP_FORM_LABEL_COLOR' ) ? PSOURCE_CHAT_GLOBAL_BP_FORM_LABEL_COLOR : '#333333',
				'delete_user_messages'           => 'disabled',
				'file_uploads_enabled' 			 => defined( 'PSOURCE_CHAT_GLOBAL_FILE_UPLOADS_ENABLED' ) ? PSOURCE_CHAT_GLOBAL_FILE_UPLOADS_ENABLED : 'disabled',
				'file_uploads_max_size' 		 => defined( 'PSOURCE_CHAT_GLOBAL_FILE_UPLOADS_MAX_SIZE' ) ? PSOURCE_CHAT_GLOBAL_FILE_UPLOADS_MAX_SIZE : 5,
				'file_uploads_allowed_types'	 => defined( 'PSOURCE_CHAT_GLOBAL_FILE_UPLOADS_ALLOWED_TYPES' ) ? PSOURCE_CHAT_GLOBAL_FILE_UPLOADS_ALLOWED_TYPES : 'jpg,jpeg,png,gif,webp,mp4,webm,pdf,doc,docx,txt,zip',

			);

			$blocked_words = array();
			if ( is_file( dirname( __FILE__ ) . '/../lib/bad_words_list.php' ) ) {
				$blocked_words = file( dirname( __FILE__ ) . '/../lib/bad_words_list.php' );
				if ( ( is_array( $blocked_words ) ) && ( count( $blocked_words ) ) ) {
					foreach ( $blocked_words as $_idx => $_val ) {
						$blocked_words[ $_idx ] = trim( $_val );
					}
				}
			}

			$this->_chat_options_defaults['banned']['blocked_words_active']  = 'disabled';
			$this->_chat_options_defaults['banned']['blocked_words']         = $blocked_words;
			$this->_chat_options_defaults['banned']['blocked_words_replace'] = "";

			// User meta defaults for profile and other user specific settings. Saved to the user meta table (not wp_options)
			if ( is_user_logged_in() ) {
				$user_meta_option                          = get_option( 'psource-chat-user-meta', array() );
				// Dynamische Dashboard-Widget-Standardwerte basierend auf Admin-Einstellungen
				$dashboard_options = is_network_admin() ? get_site_option( 'psource-chat-dashboard', array() ) : get_option( 'psource-chat-dashboard', array() );
				$dashboard_widget_default = isset($dashboard_options['dashboard_widget']) && $dashboard_options['dashboard_widget'] == 'enabled' ? 'enabled' : 'disabled';
				$dashboard_status_widget_default = isset($dashboard_options['dashboard_status_widget']) && $dashboard_options['dashboard_status_widget'] == 'enabled' ? 'enabled' : 'disabled';
				$dashboard_friends_widget_default = isset($dashboard_options['dashboard_friends_widget']) && $dashboard_options['dashboard_friends_widget'] == 'enabled' ? 'enabled' : 'disabled';

				$this->_chat_options_defaults['user_meta'] = wp_parse_args( get_option( 'psource-chat-user-meta', array() ),
					array(
						'chat_user_status'                      => 'available',
						'chat_name_display'                     => 'display_name',
						'chat_wp_admin'                         => 'enabled',
						'chat_wp_toolbar'                       => 'enabled',
						'chat_wp_toolbar_show_status'           => 'enabled',
						'chat_wp_toolbar_show_friends'          => 'enabled',
						'chat_users_listing'                    => 'disabled',
						'chat_dashboard_widget'                 => $dashboard_widget_default,
						'chat_dashboard_widget_height'          => '',
						'chat_dashboard_status_widget'          => $dashboard_status_widget_default,
						'chat_dashboard_friends_widget'         => $dashboard_friends_widget_default,
						'chat_dashboard_friends_widget_height'  => '',
						'chat_network_dashboard_widget'         => $dashboard_widget_default,
						'chat_network_dashboard_status_widget'  => $dashboard_status_widget_default,
						'chat_network_dashboard_friends_widget' => $dashboard_friends_widget_default
					)
				);
				if ( has_filter( 'psource-chat-options-defaults' ) ) {
					$this->_chat_options_defaults['user_meta'] = apply_filters( 'psource-chat-options-defaults', 'user_meta', $this->_chat_options_defaults['user_meta'] );
				}
			}

			if ( is_multisite() ) {

				$this->_chat_options_defaults['network-site']                 = wp_parse_args( $this->_chat_options_defaults['site'], array(
						'bottom_corner' => 'disabled'
					)
				);
				$this->_chat_options_defaults['network-site']['session_type'] = 'network-site';
			}
		}


		/* now that plugins are loaded we check if we are activated from the Network */
		function plugins_loaded() {
			if ( ! is_multisite() ) {
				return;
			}

			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}
			// Makes sure the plugin is defined before trying to use it

			if ( is_plugin_active_for_network( 'ps-chat/psource-chat.php' ) ) {
				// Plugin is activated via Network
				$this->_chat_plugin_settings['network_active'] = true;
			}
		}

		/**
		 * Initialize the plugin
		 *
		 * @see        http://codex.wordpress.org/Plugin_API/Action_Reference
		 * @see        http://adambrown.info/p/wp_hooks/hook/init
		 */
		function init() {

			if ( preg_match( '/mu\-plugin/', PLUGINDIR ) > 0 ) {
				load_muplugin_textdomain( 'psource-chat', dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
			} else {
				load_plugin_textdomain( 'psource-chat', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
			}

			$this->chat_performance = psource_chat_start_performance( $this->chat_performance );

			$this->load_configs();

			if ( ( ! defined( 'PSOURCE_CHAT_SHORTINIT' ) ) || ( PSOURCE_CHAT_SHORTINIT != true ) ) {
				$this->check_upgrade();
			}

			$this->chat_localized['settings']                              = array();
			$this->chat_localized['settings']['ajax_url']                  = admin_url( 'admin-ajax.php', 'relative' );
			$this->chat_localized['settings']['plugin_url']                = $this->get_plugin_url( "/" );

			$this->chat_localized['settings']['please_wait']       = __( 'Bitte warte...', 'psource-chat' );
			$this->chat_localized['settings']['row_delete_text']   = __( 'ausblenden', 'psource-chat' );
			$this->chat_localized['settings']['row_undelete_text'] = __( 'einblenden', 'psource-chat' );

			$this->chat_localized['settings']['user_entered_chat']  = __( 'Chat betreten', 'psource-chat' );
			$this->chat_localized['settings']['user_exited_chat']   = __( 'Chat verlassen', 'psource-chat' );
			$this->chat_localized['settings']['user_pending_chat']  = __( 'Ausstehend', 'psource-chat' );
			$this->chat_localized['settings']['user_declined_chat'] = __( 'Abgelehnt', 'psource-chat' );

			$this->chat_localized['settings']['wp_is_mobile'] = wp_is_mobile();

			$this->chat_localized['settings']["session_poll_interval_messages"] = $this->get_option( 'session_poll_interval_messages', 'global' );
			if ( $this->chat_localized['settings']["session_poll_interval_messages"] < 0 ) {
				$this->chat_localized['settings']["session_poll_interval_messages"] = 1;
			}

			$this->chat_localized['settings']["session_poll_interval_invites"] = $this->get_option( 'session_poll_interval_invites', 'global' );
			if ( $this->chat_localized['settings']["session_poll_interval_invites"] < 0 ) {
				$this->chat_localized['settings']["session_poll_interval_invites"] = 3;
			}

			$this->chat_localized['settings']["session_poll_interval_meta"] = $this->get_option( 'session_poll_interval_meta', 'global' );
			if ( $this->chat_localized['settings']["session_poll_interval_meta"] < 0 ) {
				$this->chat_localized['settings']["session_poll_interval_meta"] = 5;
			}

			$this->chat_localized['settings']["session_poll_interval_users"] = 5;
		}

		/**
		 * Load things into the HTML <head></head>
		 *
		 * @see        http://codex.wordpress.org/Plugin_API/Action_Reference
		 * @see        http://adambrown.info/p/wp_hooks/hook/wp_head
		 * Here we are loafing both the front-end and admin header hooks. This cuts down on duplicate coding.
		 */
		function wp_head() {

			$_SHOW_SITE_CHAT = true;

			if ( is_admin() ) {
				if ( $this->_chat_plugin_settings['blocked_urls']['admin'] == true ) {
					$_SHOW_SITE_CHAT = false;
				}
			} else {
				if ( $this->_chat_plugin_settings['blocked_urls']['front'] == true ) {
					$_SHOW_SITE_CHAT = false;
				}
			}

			if ( ! empty( $this->_registered_styles ) ) {
				wp_print_styles( array_values( $this->_registered_styles ) );
			}
			if ( $_SHOW_SITE_CHAT == true ) {

				if ( is_multisite() ) {
					$this->site_content .= $this->chat_network_site_box_container();

					$site_box_height     = psource_chat_check_size_qualifier( $this->get_option( 'box_height', 'network-site' ), array( 'px' ) );
					$site_box_position_v = $this->get_option( 'box_position_v', 'network-site' );
					$site_box_position_h = $this->get_option( 'box_position_h', 'network-site' );
					$site_box_offset_v   = psource_chat_check_size_qualifier( $this->get_option( 'box_offset_v', 'network-site' ), array( 'px' ) );
					$site_box_offset_h   = psource_chat_check_size_qualifier( $this->get_option( 'box_offset_h', 'network-site' ), array( 'px' ) );
					$site_box_spacing_h  = psource_chat_check_size_qualifier( $this->get_option( 'box_spacing_h', 'network-site' ), array( 'px' ) );

					$site_box_shadow        = $this->get_option( 'box_shadow_show', 'network-site' );
					$site_box_shadow_v      = psource_chat_check_size_qualifier( $this->get_option( 'box_shadow_v', 'network-site' ), array( 'px' ) );
					$site_box_shadow_h      = psource_chat_check_size_qualifier( $this->get_option( 'box_shadow_h', 'network-site' ), array( 'px' ) );
					$site_box_shadow_blur   = psource_chat_check_size_qualifier( $this->get_option( 'box_shadow_blur', 'network-site' ), array( 'px' ) );
					$site_box_shadow_spread = psource_chat_check_size_qualifier( $this->get_option( 'box_shadow_spread', 'network-site' ), array( 'px' ) );
					$site_box_shadow_color  = $this->get_option( 'box_shadow_color', 'network-site' );
				}

				if ( empty( $this->site_content ) ) {
					$this->site_content .= $this->chat_site_box_container();

					$site_box_height     = psource_chat_check_size_qualifier( $this->get_option( 'box_height', 'site' ), array( 'px' ) );
					$site_box_position_v = $this->get_option( 'box_position_v', 'site' );
					$site_box_position_h = $this->get_option( 'box_position_h', 'site' );
					$site_box_offset_v   = psource_chat_check_size_qualifier( $this->get_option( 'box_offset_v', 'site' ), array( 'px' ) );
					$site_box_offset_h   = psource_chat_check_size_qualifier( $this->get_option( 'box_offset_h', 'site' ), array( 'px' ) );
					$site_box_spacing_h  = psource_chat_check_size_qualifier( $this->get_option( 'box_spacing_h', 'site' ), array( 'px' ) );

					$site_box_shadow        = $this->get_option( 'box_shadow_show', 'site' );
					$site_box_shadow_v      = psource_chat_check_size_qualifier( $this->get_option( 'box_shadow_v', 'site' ), array( 'px' ) );
					$site_box_shadow_h      = psource_chat_check_size_qualifier( $this->get_option( 'box_shadow_h', 'site' ), array( 'px' ) );
					$site_box_shadow_blur   = psource_chat_check_size_qualifier( $this->get_option( 'box_shadow_blur', 'site' ), array( 'px' ) );
					$site_box_shadow_spread = psource_chat_check_size_qualifier( $this->get_option( 'box_shadow_spread', 'site' ), array( 'px' ) );
					$site_box_shadow_color  = $this->get_option( 'box_shadow_color', 'site' );

				}

				if ( $site_box_position_h == "left" ) {
					$site_box_spacing = "0 " . $site_box_spacing_h . ' 0 0';
				} else {
					$site_box_spacing = "0 0 0 " . $site_box_spacing_h . '';
				}

				$site_box_float      = $site_box_position_h;
				$site_box_position_h = $site_box_position_h . ': ' . $site_box_offset_h . ';';

				$height_offset = 0;
				if ( $site_box_position_v == "bottom" ) {
					$border_width = intval( $this->get_option( 'box_border_width', 'site' ) );
					if ( $border_width > 0 ) {
						$height_offset = psource_chat_check_size_qualifier( $border_width * 2, array( 'px' ) );
					}
				}

				$site_box_position_v = $site_box_position_v . ': ' . $site_box_offset_v . ';';

				echo '<style type="text/css" id="psource-chat-box-site-css">';
				echo 'div.psource-chat-box.psource-chat-box-site {  margin: 0; padding: 0; position: fixed; ' . $site_box_position_h . ' ' . $site_box_position_v . ' z-index: 10000;  margin: ' . $site_box_spacing . '; padding: 0; ';

				if ( $site_box_shadow == "enabled" ) {
					echo 'box-shadow: ' .
					     $site_box_shadow_v . ' ' .
					     $site_box_shadow_h . ' ' .
					     $site_box_shadow_blur . ' ' .
					     $site_box_shadow_spread . ' ' .
					     $site_box_shadow_color . ' ';
				}
				echo ' } ';
				echo '</style>';
			}

			// Are we viewing the chat logs?
			if ( isset( $_GET['chat-show-logs'] ) || ( isset( $_GET['chat-log-id'] ) && intval( $_GET['chat-log-id'] ) ) ) {
				$content_styles = $this->chat_session_box_styles( $this->_chat_options_defaults['page'], 'archive' );
				if ( ! empty( $content_styles ) ) {
					echo $content_styles;
				}
			}
		}

		/**
		 * Admin init logic. Things here will only run when viewing the admin area
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */
		function admin_init() {
			$post_id              = $post = $post_type = $post_type_object = null;
			$_show_tinymce_button = false;

			$url_path = basename( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );
			if ( ! $url_path ) {
				return $_show_tinymce_button;
			}

			// If we are not on a post_type editor or add new form. Return
			if ( ! in_array( $url_path, array( 'post-new.php', 'post.php' ) ) ) {
				return $_show_tinymce_button;
			}

			if ( isset( $_GET['post_type'] ) ) {
				$post_type = $_GET['post_type'];
			} else {
				if ( isset( $_GET['post'] ) ) {
					$post_id = (int) $_GET['post'];
				} elseif ( isset( $_POST['post_ID'] ) ) {
					$post_id = (int) $_POST['post_ID'];
				}
				if ( $post_id ) {
					$post = get_post( $post_id );
					if ( $post ) {
						$post_type = $post->post_type;
					}
				}
			}
			if ( ! $post_type ) {
				$post_type = "post";
			}

			if ( ! get_current_user_id() ) {
				return $_show_tinymce_button;
			}

			$current_user = wp_get_current_user();

			$tinymce_roles = $this->get_option( 'tinymce_roles', 'page' );
			if ( ! $tinymce_roles ) {
				$tinymce_roles = array();
			}

			$tinymce_post_types = $this->get_option( 'tinymce_post_types', 'page' );
			if ( ! $tinymce_post_types ) {
				$tinymce_post_types = array();
			}

			// If the viewed post type is not in our allowed list return.
			if ( ! in_array( $post_type, $tinymce_post_types ) ) {
				return $_show_tinymce_button;
			}

			// The user's role is in the allowed roles set for Chat > Settings Page
			if ( array_intersect( $current_user->roles, $tinymce_roles ) ) {
				$_show_tinymce_button = true;
			} else {

				// If the allowed chat roles does not contain the admin then return;
				if ( array_search( 'administrator', $tinymce_roles ) === false ) {
					return;
				}

				// However, if the 'administrator' role is in our allowed list and the user is super_admin then we are good.
				if ( is_super_admin() ) {
					$_show_tinymce_button = true;
				}
			}

			if ( $_show_tinymce_button === true ) {
				add_filter( "mce_external_plugins", array( &$this, "tinymce_add_plugin" ) );
				add_filter( 'mce_buttons', array( &$this, 'tinymce_register_button' ) );
				//add_filter('mce_external_languages', array(&$this,'tinymce_load_langs'));

			}

			return $_show_tinymce_button;
		}

		function admin_enqueue_scripts() {

			global $wp_version;

			// For some dumb reason WordPress does not include any default jQuery UI styles even for wp-admin
			$css_url = 'http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css';
			if ( is_ssl() ) {
				$css_url = str_replace( 'http://', 'https://', $css_url );
			}

			//Register All the styles
			wp_register_style( 'psource-chat-style', $this->get_plugin_url( '/css/psource-chat-style.css' ), array(), $this->chat_current_version );
			wp_register_style( 'psource-chat-jquery-ui-datepicker-css', $css_url, false, '1.0.0' );

			//Admin Styles
			wp_register_style( 'psource-chat-wpadminbar-style', $this->get_plugin_url( '/css/psource-chat-wpadminbar.css' ), array(), $this->chat_current_version );
			wp_register_style( 'psource-chat-admin-css', $this->get_plugin_url( '/css/psource-chat-admin.css' ), array(), $this->chat_current_version );

			if ( ! version_compare( $wp_version, '3.7.1', '>' ) ) {
				wp_register_style( 'psource-chat-wpadminbar-style-pre-38', $this->get_plugin_url( '/css/psource-chat-wpadminbar-pre-38.css' ), array(), $this->chat_current_version );
			}

			if ( ( isset( $_GET['page'] ) ) && ( $_GET['page'] == 'chat_session_logs' ) ) {
				wp_enqueue_style( 'psource-chat-jquery-ui-datepicker-css' );
				wp_enqueue_script( 'jquery-ui-datepicker' );
			}

			//Register all the Scripts
			wp_register_script( 'psource-chat-admin-js', $this->get_plugin_url( '/js/psource-chat-admin.js' ), array(
				'jquery',
				'jquery-ui-core',
				'jquery-ui-tabs'
			), $this->chat_current_version );
			wp_register_script( 'jquery-cookie', $this->get_plugin_url( '/js/jquery-cookie.js' ), array( 'jquery' ), $this->chat_current_version, true );
			wp_register_script( 'psource-chat-admin-farbtastic-js', $this->get_plugin_url( '/js/psource-chat-admin-farbtastic.js' ), array(), $this->chat_current_version, true );
			wp_register_script( 'psource-chat-admin-js', $this->get_plugin_url( '/js/psource-chat-admin.js' ), array(
				'jquery',
				'jquery-ui-core',
				'jquery-ui-tabs'
			), $this->chat_current_version );
			wp_register_script( 'psource-chat-js', $this->get_plugin_url( '/js/psource-chat.js' ), array( 'jquery' ), $this->chat_current_version, true );

			$screen = get_current_screen();

			//Profile Page
			if ( $screen->id == "profile" ) {

				//Enqueue Chat admin js
				wp_enqueue_script( 'psource-chat-admin-js' );
				$this->_registered_styles['psource-chat-admin-js'] = 'psource-chat-admin-js';

			}

			//Dashboard
			if ( $screen->id == "dashboard" ) {

				$this->_registered_styles['psource-chat-wpadminbar-style'] = 'psource-chat-wpadminbar-style';

				// For older versions of WP (less than 3.8) we add some supplement styles
				if ( ! version_compare( $wp_version, '3.7.1', '>' ) ) {
					$this->_registered_styles['psource-chat-wpadminbar-style-pre-38'] = 'psource-chat-wpadminbar-style-pre-38';
				}
			}

			// if we are showing one of our own settings panels then we don't need to be here since the
			if ( $this->_show_own_admin === true ) {

				wp_enqueue_style( 'farbtastic' );
				$this->_registered_styles['farbtastic'] = 'farbtastic';

				wp_enqueue_script( 'farbtastic' );
				$this->_registered_scripts['farbtastic'] = 'farbtastic';

				/* enqueue our plugin styles */
				$this->_registered_styles['psource-chat-admin-css'] = 'psource-chat-admin-css';

				wp_enqueue_script( 'json2' );
				$this->_registered_scripts['json2'] = 'json2';

				//Include Jquery Cookie
				wp_enqueue_script( 'jquery-cookie' );
				$this->_registered_scripts['jquery-cookie'] = 'jquery-cookie';

				//Include Farbtastic Script
				wp_enqueue_script( 'psource-chat-admin-farbtastic-js' );
				$this->_registered_scripts['psource-chat-admin-farbtastic-js'] = 'psource-chat-admin-farbtastic-js';

				wp_enqueue_script( 'psource-chat-admin-js' );
				$this->_registered_styles['psource-chat-admin-js'] = 'psource-chat-admin-js';

				// The admin can even block chats from our own admin pages.
				if ( $this->_chat_plugin_settings['blocked_urls']['admin'] == false ) {

					$this->_registered_styles['psource-chat-style'] = 'psource-chat-style';

					wp_enqueue_script( 'psource-chat-js' );
					$this->_registered_scripts['psource-chat-js'] = 'psource-chat-js';
				}

				if ( ( isset( $this->user_meta['chat_wp_toolbar'] ) ) && ( $this->user_meta['chat_wp_toolbar'] == "enabled" ) ) {

					$this->_registered_styles['psource-chat-wpadminbar-style'] = 'psource-chat-wpadminbar-style';

					// For older versions of WP (less than 3.8) we add some supplement styles
					if ( ! version_compare( $wp_version, '3.7.1', '>' ) ) {
						$this->_registered_styles['psource-chat-wpadminbar-style-pre-38'] = 'psource-chat-wpadminbar-style-pre-38';
					}
				}

				return;
			}

			// Check of we are on the BuddyPress Groups admin screen within the dashboard
			if ( ( isset( $_GET['page'] ) ) && ( $_GET['page'] == "bp-groups" ) ) {

				wp_enqueue_script( 'json2' );
				$this->_registered_scripts['json2'] = 'json2';

				wp_enqueue_script( 'jquery-ui-core' );
				$this->_registered_scripts['jquery-ui-core'] = 'jquery-ui-core';

				wp_enqueue_script( 'jquery-ui-tabs' );
				$this->_registered_scripts['jquery-ui-tabs'] = 'jquery-ui-tabs';


				$this->_registered_styles['psource-chat-style'] = 'psource-chat-style';
				$this->_registered_styles[]                     = 'psource-chat-admin-css';

				wp_enqueue_script( 'psource-chat-js' );
				$this->_registered_scripts['psource-chat-js'] = 'psource-chat-js';

				wp_enqueue_script( 'psource-chat-admin-js' );
				$this->_registered_scripts['psource-chat-admin-js'] = 'psource-chat-admin-js';

				wp_enqueue_script( 'psource-chat-admin-farbtastic-js' );
				$this->_registered_scripts['psource-chat-admin-farbtastic-js'] = 'psource-chat-admin-farbtastic-js';

				wp_enqueue_script( 'jquery-cookie' );
				$this->_registered_scripts['jquery-cookie'] = 'jquery-cookie';
			}

			if ( $this->_chat_plugin_settings['blocked_urls']['admin'] == true ) {
				return;
			}

			if ( ( isset( $this->user_meta['chat_wp_admin'] ) ) && ( $this->user_meta['chat_wp_admin'] != "enabled" ) ) {
				return;
			}

			//If Admin bar chat settings are enabled
			if ( ( isset( $this->user_meta['chat_wp_toolbar'] ) ) && ( $this->user_meta['chat_wp_toolbar'] == "enabled" ) ) {
				$this->_registered_styles['psource-chat-wpadminbar-style'] = 'psource-chat-wpadminbar-style';

				// For older versions of WP (less than 3.8) we add some supplement styles
				global $wp_version;
				if ( ! version_compare( $wp_version, '3.7.1', '>' ) ) {
					$this->_registered_styles['psource-chat-wpadminbar-style-pre-38'] = 'psource-chat-wpadminbar-style-pre-38';
				}
			}
			$this->_registered_styles['psource-chat-style'] = 'psource-chat-style';

			//Commented out in 2.0.8.6, as it breaks on Gravity forms page
			//wp_enqueue_script( 'psource-chat-js' );
			$this->_registered_scripts['psource-chat-js'] = 'psource-chat-js';

		}

		/**
		 * Enqueue all the scripts and styles we need. Per proper WP methods.
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */
		function wp_enqueue_scripts() {
			//global $bp;
			global $wp_version;

			if ( is_admin() ) {
				return;
			}

			//Register Styles And Scripts
			wp_register_style( 'psource-chat-style', $this->get_plugin_url( '/css/psource-chat-style.css' ), array(), $this->chat_current_version );
			wp_register_style( 'psource-chat-admin-css', $this->get_plugin_url( '/css/psource-chat-admin.css' ), array(), $this->chat_current_version );
			wp_register_style( 'psource-chat-wpadminbar-style', $this->get_plugin_url( '/css/psource-chat-wpadminbar.css' ), array(), $this->chat_current_version );

			if ( ! version_compare( $wp_version, '3.7.1', '>' ) ) {
				wp_register_style( 'psource-chat-wpadminbar-style-pre-38', $this->get_plugin_url( '/css/psource-chat-wpadminbar-pre-38.css' ), array(), $this->chat_current_version );
			}

			//Register scripts
			wp_register_script( 'psource-chat-js', $this->get_plugin_url( '/js/psource-chat.js' ), array( 'jquery' ), $this->chat_current_version, true );
			wp_register_script( 'psource-chat-media-js', $this->get_plugin_url( '/js/psource-chat-media.js' ), array( 'jquery' ), $this->chat_current_version, true );
			wp_register_script( 'psource-chat-upload-js', $this->get_plugin_url( '/js/psource-chat-upload.js' ), array( 'jquery' ), $this->chat_current_version, true );
			wp_register_script( 'psource-chat-admin-js', $this->get_plugin_url( '/js/psource-chat-admin.js' ), array( 'jquery' ), $this->chat_current_version, true );
			wp_register_script( 'psource-chat-admin-farbtastic-js', $this->get_plugin_url( '/js/psource-chat-admin-farbtastic.js' ), array(), $this->chat_current_version, true );

			//$_SCRIPTS_LOADED = false;
			$_BLOCK_URL = false;

			// Why yes we are loading the scripts and styles for admin and non-admin pages. Because we are allowing chat to run under both!

			if ( ( function_exists( 'bp_is_group_admin_screen' ) ) && ( bp_is_group_admin_screen( $this->get_option( 'bp_menu_slug', 'global' ) ) ) ) {

				//$_SCRIPTS_LOADED = true;

				//wp_enqueue_style( 'farbtastic' );
				//$this->_registered_styles['farbtastic'] = 'farbtastic';

				wp_enqueue_script( 'jquery' );
				$this->_registered_scripts['jquery'] = 'jquery';

				wp_enqueue_script( 'json2' );
				$this->_registered_scripts['json2'] = 'json2';

				wp_enqueue_script( 'jquery-ui-core' );
				$this->_registered_scripts['jquery-ui-core'] = 'jquery-ui-core';

				wp_enqueue_script( 'jquery-ui-tabs' );
				$this->_registered_scripts['jquery-ui-tabs'] = 'jquery-ui-tabs';

				$this->_registered_styles['psource-chat-style'] = 'psource-chat-style';
				$this->_registered_styles[]                     = 'psource-chat-admin-css';

				wp_enqueue_script( 'psource-chat-js' );
				$this->_registered_scripts['psource-chat-js'] = 'psource-chat-js';

				wp_enqueue_script( 'psource-chat-media-js' );
				$this->_registered_scripts['psource-chat-media-js'] = 'psource-chat-media-js';

				wp_enqueue_script( 'psource-chat-upload-js' );
				$this->_registered_scripts['psource-chat-upload-js'] = 'psource-chat-upload-js';

				wp_enqueue_script( 'psource-chat-admin-js' );
				$this->_registered_scripts['psource-chat-admin-js'] = 'psource-chat-admin-js';

				wp_enqueue_script( 'psource-chat-admin-farbtastic-js' );
				$this->_registered_scripts['psource-chat-admin-farbtastic-js'] = 'psource-chat-admin-farbtastic-js';


				if ( $this->user_meta['chat_wp_toolbar'] == "enabled" ) {
					$this->_registered_styles['psource-chat-wpadminbar-style'] = 'psource-chat-wpadminbar-style';

					// For older versions of WP (less than 3.8) we add some supplement styles
					if ( ! version_compare( $wp_version, '3.7.1', '>' ) ) {
						$this->_registered_styles['psource-chat-wpadminbar-style-pre-38'] = 'psource-chat-wpadminbar-style-pre-38';
					}
				}

			} else {

				if ( ( isset( $this->user_meta['chat_wp_toolbar'] ) ) && ( $this->user_meta['chat_wp_toolbar'] == "enabled" ) ) {
					$this->_registered_styles['psource-chat-wpadminbar-style'] = 'psource-chat-wpadminbar-style';

					// For older versions of WP (less than 3.8) we add some supplement styles
					if ( ! version_compare( $wp_version, '3.7.1', '>' ) ) {
						$this->_registered_styles['psource-chat-wpadminbar-style-pre-38'] = 'psource-chat-wpadminbar-style-pre-38';
					}
				}

				wp_enqueue_script( 'jquery' );
				wp_enqueue_script( 'json2' );
				$this->_registered_scripts['json2'] = 'json2';

				$this->_registered_styles['psource-chat-style'] = 'psource-chat-style';

				wp_enqueue_script( 'psource-chat-js' );
				$this->_registered_scripts['psource-chat-js'] = 'psource-chat-js';

				wp_enqueue_script( 'psource-chat-media-js' );
				$this->_registered_scripts['psource-chat-media-js'] = 'psource-chat-media-js';

				wp_enqueue_script( 'psource-chat-upload-js' );
				$this->_registered_scripts['psource-chat-upload-js'] = 'psource-chat-upload-js';
			}
		}

		/**
		 * We load the pop-out chat option.
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */
		function load_template() {

			if ( ( isset( $_GET['psource-chat-action'] ) ) && ( ! empty( $_GET['psource-chat-action'] ) ) ) {

				switch ( $_GET['psource-chat-action'] ) {
					case 'pop-out':

						if ( ( isset( $_GET['psource-chat-key'] ) ) && ( ! empty( $_GET['psource-chat-key'] ) ) ) {

							$psource_chat_key = base64_decode( $_GET['psource-chat-key'] );
							if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
								$chat_session = get_option( $psource_chat_key );
							} else {
								$chat_session = get_transient( $psource_chat_key );
							}
							if ( ( ! empty( $chat_session ) ) && ( is_array( $chat_session ) ) ) {
								$this->using_popup_out_template = true;
								if ( $psource_chat_popup_template = locate_template( 'psource-chat-pop-out-' . $chat_session['id'] . '.php' ) ) {
									load_template( $$psource_chat_popup_template );
									die();
								} else if ( $psource_chat_popup_template = locate_template( 'psource-chat-pop-out.php' ) ) {
									load_template( $psource_chat_popup_template );
									die();
								} else {
									load_template( dirname( dirname( __FILE__ ) ) . '/templates/psource-chat-pop-out.php' );
									die();
								}
							}
						}
					break;
				}
			}
		}

		/**
		 * Here we use a class method to get options from our internal settings array. Similar to the WP get_option call.
		 *
		 * @global    none
		 *
		 * @param    $key - The options key.
		 *            $session_type - This is the options group.
		 *
		 * @return    returns found value
		 */
		function get_option( $key, $session_type = 'page' ) {
			if ( isset( $this->_chat_options[ $session_type ][ $key ] ) ) {
				$val = $this->_chat_options[ $session_type ][ $key ];
				if ( is_string( $val ) ) {
					$val = stripslashes( $val );
				}

				return $val;
			} else if ( isset( $this->_chat_options_defaults[ $session_type ][ $key ] ) ) {
				$val = $this->_chat_options_defaults[ $session_type ][ $key ];
				if ( is_string( $val ) ) {
					$val = stripslashes( $val );
				}

				return $val;
			}
		}

		/**
		 * Our footer output is busy. We need to output the logic for the bottom corner chat wrapper. This is a UL element and each chat box added is an <LI>
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */
		function wp_footer() {

			// See $this->admin_footer() for admin footer logic
			if ( is_admin() ) {
				return;
			}

			if ( ( $this->_chat_plugin_settings['blocked_urls']['front'] != true )
			     || ( count( $this->chat_sessions ) > 0 )
			) {

				$this->set_chat_localized();
				wp_print_scripts( array_values( $this->_registered_scripts ) );

				if ( ! empty( $this->site_content ) ) {
					echo $this->site_content;
				}
			} else {
				foreach ( $this->_registered_styles as $_handle ) {
					wp_dequeue_style( $_handle );
				}
				foreach ( $this->_registered_scripts as $_handle ) {
					wp_dequeue_script( $_handle );
				}
			}
		}

		function admin_notices() {

			if ( ( isset( $this->_admin_notice_messages_key ) ) && ( ! empty( $this->_admin_notice_messages_key ) ) ) {

				if ( ( isset( $this->_admin_notice_messages[ $this->_admin_notice_messages_key ] ) )
				     && ( ! empty( $this->_admin_notice_messages[ $this->_admin_notice_messages_key ] ) )
				) {
					?>
					<div id="psource-chat-updated" class="updated below-h2"><p><?php
						echo $this->_admin_notice_messages[ $this->_admin_notice_messages_key ]; ?></p></div><?php
				}
			}

			if ( ( is_multisite() ) && ( is_network_admin() ) ) {
				if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
					require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
				}
				if ( ! is_plugin_active_for_network( 'ps-chat/psource-chat.php' ) ) {
					return;
				}

				if ( ! current_user_can( 'manage_network_options' ) ) {
					return;
				}
				$nag_version = get_site_option( 'psource-chat-version-nag', false );

			} else {
				if ( ! current_user_can( 'manage_options' ) ) {
					return;
				}
				$nag_version = get_option( 'psource-chat-version-nag', false );
			}

			if ( ( is_multisite() ) && ( is_network_admin() ) ) {
				$chat_url_stub  = 'admin.php?page=chat_settings_panel_network_global#psource_chat_interval_panel';
				$chat_admin_url = network_admin_url( $chat_url_stub );
			} else {
				$chat_url_stub  = 'admin.php?page=chat_settings_panel_global#psource_chat_interval_panel';
				$chat_admin_url = admin_url( $chat_url_stub );
			}

			if ( ! $nag_version ) {
				?>
				<div id="psource-chat-nag-error" class="error below-h2">
				<p><?php echo sprintf( __( 'Du hast das PS-Chat Plugin installiert. <strong>Bitte überprüfe die <a href="%s">Abfrageintervall</a> Einstellungen bevor Du den Chat benutzt</strong>. In den meisten Fällen funktionieren die Standardoptionen für Deine Hostkonfiguration. In einigen Einzelfällen ist das Abfrageintervall jedoch zu häufig und kann dazu führen, dass Dein Hosting Dein Konto schließt. Nachdem Du die Chat-Einstellungen überprüft und gespeichert hast, wird diese Meldung nicht mehr angezeigt.', 'psource-chat' ), esc_url( $chat_admin_url ) ) ?></p>
				</div><?php
			} else if ( version_compare( $this->_chat_plugin_settings['options_version'], $nag_version ) > 0 ) {
				?>
				<div id="psource-chat-nag-error" class="error">
				<p><?php echo sprintf( __( 'Das PS-Chat Plugin wurde auf Deiner Webseite aktualisiert. <strong>Bitte überprüfe vor der Verwendung die Einstellungen für das <a href="%s">Abfrageintervall</a></strong>. In den meisten Fällen funktionieren die Standardoptionen für Deine Hostkonfiguration. In einigen Einzelfällen ist das Abfrageintervall jedoch zu häufig und kann dazu führen, dass Dein Hosting Dein Konto schließt. Nachdem Du die Chat-Einstellungen überprüft und gespeichert hast, wird diese Meldung nicht mehr angezeigt.', 'psource-chat' ), esc_url( $chat_admin_url ) ) ?></p>
				</div><?php
			}
		}

		function admin_footer() {

			if ( ( count( $this->_registered_scripts ) ) && ( isset( $this->_registered_scripts['psource-chat-js'] ) ) ) {

				if ( ( ! isset( $_GET['page'] ) ) || ( $_GET['page'] != 'chat_session_logs' ) ) {
					if ( ! empty( $this->site_content ) ) {
						echo $this->site_content;
					}

				}
				$this->set_chat_localized();

				// Moved to wp-head
				//wp_print_styles(array_values($this->_registered_styles));
				wp_print_scripts( array_values( $this->_registered_scripts ) );

			} else if ( $this->_show_own_admin == true ) {
				wp_print_styles( array_values( $this->_registered_styles ) );
				wp_print_scripts( array_values( $this->_registered_scripts ) );
			}
		}

		function set_chat_localized() {
			$poll_type = $this->get_option( 'session_poll_type', 'global' );
			
			// Migrate legacy plugin type to modern default
			if ( $poll_type == "plugin" ) {
				$poll_type = class_exists( 'PSource_Chat_AJAX' ) ? 'modern' : 'wordpress';
				$this->set_option( 'session_poll_type', $poll_type, 'global' );
			}
			
			if ( $poll_type == "modern" && class_exists( 'PSource_Chat_AJAX' ) ) {
				// Use modern AJAX system with REST API
				$ajax_config = PSource_Chat_AJAX::get_js_config();
				$this->chat_localized['settings']['ajax_url'] = $ajax_config['ajax_url'];
				$this->chat_localized['settings']['rest_url'] = $ajax_config['rest_url'];
				$this->chat_localized['settings']['rest_nonce'] = $ajax_config['rest_nonce'];
				$this->chat_localized['settings']['ajax_nonce'] = $ajax_config['ajax_nonce'];
				$this->chat_localized['settings']['poll_url'] = $ajax_config['poll_url'];
				$this->chat_localized['settings']['ajax_type'] = 'modern';
			} else {
				// Standard WordPress AJAX (CMS AJAX)
				$this->chat_localized['settings']["ajax_url"] = admin_url( 'admin-ajax.php', 'relative' );
				$this->chat_localized['settings']['ajax_type'] = 'wordpress';
			}

			$this->chat_localized['settings']['cookiepath']    = COOKIEPATH;
			$this->chat_localized['settings']['cookie_domain'] = COOKIE_DOMAIN;
			//$this->chat_localized['settings']['ABSPATH'] 			= base64_encode(ABSPATH);
			$this->chat_localized['settings']['REQUEST_URI'] = base64_encode( $_SERVER['REQUEST_URI'] );
			$this->chat_localized['settings']['is_admin']    = is_admin() ? true : false;

			//$this->chat_localized['settings']['soundManager-js'] 	= plugins_url('/js/soundmanager2-nodebug-jsmin.js', __FILE__);
			$this->chat_localized['settings']['soundManager-js'] = $this->get_plugin_url( '/js/buzz.min.js' );
			//$this->chat_localized['settings']['cookie-js'] 			= plugins_url('/js/jquery-cookie.js', __FILE__);

			// Need to disable legacy setting.
			$this->chat_localized['settings']['box_resizable'] = false;

			$this->chat_localized['sessions']               = $this->chat_sessions;
			$this->chat_localized['user']                   = $this->chat_user;
			$this->chat_localized['auth']                   = $this->chat_auth;
			$this->chat_localized['auto_scroll']['disable'] = __( 'Autoscroll aus', 'psource-chat' );
			$this->chat_localized['auto_scroll']['enable']  = __( 'Autoscroll an', 'psource-chat' );

			// Upload-Einstellungen hinzufügen
			$this->chat_localized['upload_settings'] = array(
				'enabled' => $this->get_option( 'file_uploads_enabled', 'global' ) === 'enabled',
				'allowed_types' => $this->get_option( 'file_uploads_allowed_types', 'global' ) ?: 'jpg,jpeg,png,gif,webp,mp4,webm,pdf,doc,docx,txt,zip',
				'max_size' => intval( $this->get_option( 'file_uploads_max_size', 'global' ) ?: 5 )
			);

			// Nonce für Upload-Anfragen
			$this->chat_localized['nonce'] = wp_create_nonce( 'psource_chat_nonce' );
			$this->chat_localized['ajax_url'] = admin_url( 'admin-ajax.php' );

			wp_localize_script( 'psource-chat-js', 'psource_chat_localized', $this->chat_localized );
		}

		/**
		 *
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */
		function network_admin_menu() {

			// Planned global settings panel the network admin can control the default settings.

			// For example the bottom corner chat option can be set the enabled or disabled. Then all
			// new sites will inherit this option. Only if chat is network activated. Local activation
			// will not use site settings.

			if ( ! is_multisite() ) {
				return;
			}
			if ( ! is_network_admin() ) {
				return;
			}
			if ( ! is_super_admin() ) {
				return;
			}

			if ( $this->_chat_plugin_settings['network_active'] != true ) {
				return;
			}

			require( dirname( __FILE__ ) . '/../lib/psource_chat_admin_panels.php' );
			$this->_admin_panels = new psource_chat_admin_panels();

			$this->_pagehooks['chat_settings_panel_network_site'] = add_menu_page( _x( "Chat", 'page label', 'psource-chat' ),
				_x( "Chat", 'menu label', 'psource-chat' ),
				'manage_network_options',
				'chat_settings_panel_network_site',
				array( $this->_admin_panels, 'chat_settings_panel_network_site' ),
				'dashicons-format-status'
			//plugin_dir_url( __FILE__ ) .'images/icon/greyscale-16.png'
			);

			$this->_pagehooks['chat_settings_panel_network_site']      = add_submenu_page( 'chat_settings_panel_network_site',
				_x( 'Frontendchat', 'page label', 'psource-chat' ),
				_x( 'Frontendchat', 'menu label', 'psource-chat' ),
				'manage_network_options',
				'chat_settings_panel_network_site',
				array( &$this->_admin_panels, 'chat_settings_panel_network_site' )
			);
			$this->_pagehooks['chat_settings_panel_network_dashboard'] = add_submenu_page( 'chat_settings_panel_network_site',
				_x( 'Dashboardchat', 'page label', 'psource-chat' ),
				_x( 'Dashboardchat', 'menu label', 'psource-chat' ),
				'manage_network_options',
				'chat_settings_panel_network_dashboard',
				array( &$this->_admin_panels, 'chat_settings_panel_dashboard' )
			);

			$this->_pagehooks['chat_settings_panel_network_global'] = add_submenu_page( 'chat_settings_panel_network_site',
				_x( 'Einstellungen', 'page label', 'psource-chat' ),
				_x( 'Einstellungen', 'menu label', 'psource-chat' ),
				'manage_network_options',
				'chat_settings_panel_network_global',
				array( &$this->_admin_panels, 'chat_settings_panel_global' )
			);

			$this->_pagehooks['chat_session_logs_network_site'] = add_submenu_page( 'chat_settings_panel_network_site',
				_x( 'Session Logs', 'page label', 'psource-chat' ),
				_x( 'Session Logs', 'menu label', 'psource-chat' ),
				'manage_network_options',
				'chat_session_logs',
				array( &$this->_admin_panels, 'chat_settings_panel_session_logs' )
			);

			// Hook into the WordPress load page action for our new nav items. This is better then checking page query_str values.
			add_action( 'load-' . $this->_pagehooks['chat_settings_panel_network_site'], array(
				&$this,
				'on_load_panels'
			) );
			add_action( 'load-' . $this->_pagehooks['chat_settings_panel_network_dashboard'], array(
				&$this,
				'on_load_panels'
			) );
			add_action( 'load-' . $this->_pagehooks['chat_settings_panel_network_global'], array(
				&$this,
				'on_load_panels'
			) );
			add_action( 'load-' . $this->_pagehooks['chat_session_logs_network_site'], array(
				&$this,
				'on_load_panels'
			) );
		}

		/**
		 * Standard function to create our admin menus
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 * @see        http://codex.wordpress.org/Adding_Administration_Menus
		 */
		function admin_menu() {

			require( dirname( __FILE__ ) . '/../lib/psource_chat_admin_panels.php' );
			$this->_admin_panels = new psource_chat_admin_panels();

			add_menu_page(
				_x( "PS-Chat", 'page label', 'psource-chat' ),
				_x( "PS-Chat", 'menu label', 'psource-chat' ),
				'manage_options',
				'chat_settings_panel',
				array( $this->_admin_panels, 'chat_settings_panel_page' ),
				'dashicons-format-status',
				10, // Setzen Sie hier die Position des Menüs, z.B. 10
				plugin_dir_url( __FILE__ ) . 'images/icon/greyscale-16.png' // Verschieben des Icons an diese Stelle
			);

			$this->_pagehooks['chat_settings_panel_page'] = add_submenu_page( 'chat_settings_panel',
				_x( 'Beitragschat', 'page label', 'psource-chat' ),
				_x( 'Beitragschat', 'menu label', 'psource-chat' ),
				'manage_options',
				'chat_settings_panel',
				array( &$this->_admin_panels, 'chat_settings_panel_page' )
			);

			//if ((!is_multisite()) || ($this->get_option('bottom_corner', 'network-site') == 'disabled')) {

			$this->_pagehooks['chat_settings_panel_site'] = add_submenu_page( 'chat_settings_panel',
				_x( 'Frontendchat', 'page label', 'psource-chat' ),
				_x( 'Frontendchat', 'menu label', 'menu label', 'psource-chat' ),
				'manage_options',
				'chat_settings_panel_site',
				array( &$this->_admin_panels, 'chat_settings_panel_site' )
			);
			//}

			$this->_pagehooks['chat_settings_panel_widget'] = add_submenu_page( 'chat_settings_panel',
				_x( 'Widgetchat', 'page label', 'psource-chat' ),
				_x( 'Widgetchat', 'menu label', 'menu label', 'psource-chat' ),
				'manage_options',
				'chat_settings_panel_widget',
				array( &$this->_admin_panels, 'chat_settings_panel_widget' )
			);

			$this->_pagehooks['chat_settings_panel_dashboard'] = add_submenu_page( 'chat_settings_panel',
				_x( 'Dashboardchat', 'page label', 'psource-chat' ),
				_x( 'Dashboardchat', 'menu label', 'menu label', 'psource-chat' ),
				'manage_options',
				'chat_settings_panel_dashboard',
				array( &$this->_admin_panels, 'chat_settings_panel_dashboard' )
			);

			$this->_pagehooks['chat_settings_panel_global'] = add_submenu_page( 'chat_settings_panel',
				_x( 'Einstellungen', 'page label', 'psource-chat' ),
				_x( 'Einstellungen', 'menu label', 'menu label', 'psource-chat' ),
				'manage_options',
				'chat_settings_panel_global',
				array( &$this->_admin_panels, 'chat_settings_panel_global' )
			);

			$this->_pagehooks['chat_session_logs'] = add_submenu_page( 'chat_settings_panel',
				_x( 'Session Logs', 'page label', 'psource-chat' ),
				_x( 'Session Logs', 'menu label', 'psource-chat' ),
				'manage_options',
				'chat_session_logs',
				array( &$this->_admin_panels, 'chat_settings_panel_session_logs' )
			);

			// Hook into the WordPress load page action for our new nav items. This is better then checking page query_str values.
			add_action( 'load-' . $this->_pagehooks['chat_settings_panel_page'], array( &$this, 'on_load_panels' ) );
			add_action( 'load-' . $this->_pagehooks['chat_settings_panel_site'], array( &$this, 'on_load_panels' ) );
			add_action( 'load-' . $this->_pagehooks['chat_settings_panel_widget'], array( &$this, 'on_load_panels' ) );
			add_action( 'load-' . $this->_pagehooks['chat_settings_panel_dashboard'], array(
				&$this,
				'on_load_panels'
			) );
			add_action( 'load-' . $this->_pagehooks['chat_settings_panel_global'], array( &$this, 'on_load_panels' ) );
			add_action( 'load-' . $this->_pagehooks['chat_session_logs'], array( &$this, 'on_load_panels' ) );
		}

		/**
		 * Special function called when an of out admin pages are loaded. This way we can load any needed JS/CSS or processing logic
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */
		function on_load_panels() {

			$this->_show_own_admin = true;

			/* These messages are displayed as part of the admin header message see 'admin_notices' WordPress action */
			$this->_admin_notice_messages['success-settings'] = __( "Die Einstellungen wurden aktualisiert.", 'psource-chat' );

			$this->_admin_notice_messages['success-log-delete']  = __( "Das Chat-Sitzungsprotokoll wurde gelöscht.", 'psource-chat' );
			$this->_admin_notice_messages['success-logs-delete'] = __( "Chat-Sitzungsprotokolle wurden gelöscht.", 'psource-chat' );
			$this->_admin_notice_messages['success-log-hide']    = __( "Das Chat-Sitzungsprotokoll wurde ausgeblendet.", 'psource-chat' );
			$this->_admin_notice_messages['success-logs-hide']   = __( "Chat-Sitzungsprotokolle wurden ausgeblendet.", 'psource-chat' );
			$this->_admin_notice_messages['success-log-unhide']  = __( "Das Chat-Sitzungsprotokoll wurde nicht ausgeblendet.", 'psource-chat' );
			$this->_admin_notice_messages['success-logs-unhide'] = __( "Chat-Sitzungsprotokolle wurden nicht ausgeblendet.", 'psource-chat' );

			$this->_admin_notice_messages['success-message-delete']  = __( "Chat Session Log Message wurde gelöscht.", 'psource-chat' );
			$this->_admin_notice_messages['success-messages-delete'] = __( "Chat-Sitzungsprotokollnachrichten wurden gelöscht.", 'psource-chat' );
			$this->_admin_notice_messages['success-message-hide']    = __( "Die Chat-Sitzungsprotokollnachricht wurde ausgeblendet.", 'psource-chat' );
			$this->_admin_notice_messages['success-messages-hide']   = __( "Chat-Sitzungsprotokollnachrichten wurden ausgeblendet.", 'psource-chat' );
			$this->_admin_notice_messages['success-message-unhide']  = __( "Die Chat-Sitzungsprotokollnachricht wurde nicht ausgeblendet.", 'psource-chat' );
			$this->_admin_notice_messages['success-messages-unhide'] = __( "Chat-Sitzungsprotokollnachrichten wurden nicht ausgeblendet.", 'psource-chat' );


			if ( ( isset( $_GET['page'] ) ) && ( $_GET['page'] == "chat_session_logs" ) ) {

				if ( ( isset( $_GET['maction'] ) ) && ( isset( $_GET['_wpnonce'] ) ) && ( wp_verify_nonce( $_GET['_wpnonce'], 'chat-message-item' ) ) ) {

					$_SERVER['REQUEST_URI'] = esc_url_raw( remove_query_arg( array(
						'_wp_http_referer',
						'_wpnonce'
					), $_SERVER['REQUEST_URI'] ) );

					$mid = '';
					if ( isset( $_GET['mid'] ) ) {
						$mid = intval( $_GET['mid'] );
					}

					$maction = '';
					if ( isset( $_GET['maction'] ) ) {
						$maction = esc_attr( $_GET['maction'] );
					}

					$chat_href = esc_url_raw( remove_query_arg( array( '_wpnonce', 'maction', 'mid', 'message' ) ) );
					if ( $maction == "delete" ) {
						$this->chat_session_logs_messages( 'message', 'delete', array( $mid ) );
						$chat_href = add_query_arg( 'message', 'success-message-delete', $chat_href );
						wp_redirect( $chat_href );

					} else if ( ( $maction == "hide" ) || ( $maction == "unhide" ) ) {
						if ( $maction == "hide" ) {
							$this->chat_session_logs_messages( 'message', 'hide', array( $mid ) );
							$chat_href = add_query_arg( 'message', 'success-message-hide', $chat_href );

						} else if ( $maction == "unhide" ) {
							$this->chat_session_logs_messages( 'message', 'unhide', array( $mid ) );
							$chat_href = add_query_arg( 'message', 'success-message-unhide', $chat_href );
						}

						if ( isset( $_GET['lid'] ) ) {
							global $wpdb;
							$sql_str = $wpdb->prepare( "SELECT * FROM " . PSOURCE_Chat::tablename( 'log' ) . " WHERE id=%d", intval( $_GET['lid'] ) );
							if ( is_multisite() ) {
								$sql_str .= " AND blog_id=" . $this->filters['blog_id'];
							}
							$sql_str .= " LIMIT 1";

							$log_item = $wpdb->get_row( $sql_str );
							if ( ( ! empty( $log_item ) )
							     && ( isset( $log_item->chat_id ) ) && ( ! empty( $log_item->chat_id ) )
							     && ( isset( $log_item->session_type ) ) && ( ! empty( $log_item->session_type ) )
							) {
								$transient_key = "chat-session-" . $log_item->chat_id . '-' . $log_item->session_type;

								if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
									$chat_session_transient = get_option( $transient_key );
								} else {
									$chat_session_transient = get_transient( $transient_key );
								}
								if ( ! empty( $chat_session_transient ) ) {
									$this->chat_session_update_message_rows_deleted( $chat_session_transient );
								}
							}
						}
						wp_redirect( $chat_href );
					}

					wp_redirect( $chat_href );

				} else if ( ( isset( $_GET['laction'] ) ) && ( isset( $_GET['_wpnonce'] ) ) && ( wp_verify_nonce( $_GET['_wpnonce'], 'chat-log-item' ) ) ) {

					$_SERVER['REQUEST_URI'] = esc_url_raw( remove_query_arg( array(
						'_wp_http_referer',
						'_wpnonce'
					), $_SERVER['REQUEST_URI'] ) );

					$lid = '';
					if ( isset( $_GET['lid'] ) ) {
						$lid = intval( $_GET['lid'] );
					}

					$laction = '';
					if ( isset( $_GET['laction'] ) ) {
						$laction = esc_attr( $_GET['laction'] );
					}

					$chat_href = esc_url_raw( remove_query_arg( array(
						'_wpnonce',
						'lid',
						'laction',
						'maction',
						'message'
					) ) );
					if ( $laction == "delete" ) {
						$this->chat_session_logs_messages( 'log', 'delete', array( $lid ) );
						$chat_href = add_query_arg( 'message', 'success-log-delete', $chat_href );
						wp_redirect( $chat_href );

					} else if ( $laction == "hide" ) {
						$this->chat_session_logs_messages( 'log', 'hide', array( $lid ) );
						$chat_href = add_query_arg( 'message', 'success-log-hide', $chat_href );
						wp_redirect( $chat_href );

					} else if ( $laction == "unhide" ) {
						$this->chat_session_logs_messages( 'log', 'unhide', array( $lid ) );
						$chat_href = add_query_arg( 'message', 'success-log-unhide', $chat_href );
						wp_redirect( $chat_href );

					} else if ( $laction == "details" ) {
						$per_page = get_user_meta( get_current_user_id(), 'chat_page_chat_session_messages_per_page', true );
						if ( ! $per_page ) {
							$per_page = 20;
						}

						if ( ( isset( $_POST['wp_screen_options']['option'] ) )
						     && ( $_POST['wp_screen_options']['option'] == "chat_page_chat_session_logs_per_page" )
						) {

							if ( isset( $_POST['wp_screen_options']['value'] ) ) {
								$per_page = intval( $_POST['wp_screen_options']['value'] );
								if ( ( ! $per_page ) || ( $per_page < 1 ) ) {
									$per_page = 20;
								}
								update_user_meta( get_current_user_id(), 'chat_page_chat_session_messages_per_page', $per_page );
							}
						}
						add_screen_option( 'per_page', array(
							'label'   => __( 'per Page', 'psource-chat' ),
							'default' => $per_page
						) );
					}
				} else if ( ( isset( $_GET['chat-messages-bulk'] ) ) && ( is_array( $_GET['chat-messages-bulk'] ) ) && ( count( $_GET['chat-messages-bulk'] ) ) && ( isset( $_GET['_wpnonce'] ) ) && ( wp_verify_nonce( $_GET['_wpnonce'], 'bulk-messages' ) ) ) {

					$bulk_action = '';

					if ( ( isset( $_GET['action'] ) ) && ( $_GET['action'] != '-1' ) ) {
						$bulk_action = esc_attr( $_GET['action'] );
					} else if ( ( isset( $_GET['action2'] ) ) && ( $_GET['action2'] != '-1' ) ) {
						$bulk_action = esc_attr( $_GET['action2'] );
					}

					$_SERVER['REQUEST_URI'] = esc_url_raw( remove_query_arg( array(
						'_wp_http_referer',
						'_wpnonce',
						'action',
						'action2',
						'mid'
					), $_SERVER['REQUEST_URI'] ) );

					if ( ( $bulk_action ) && ( ! empty( $bulk_action ) ) ) {
						$chat_href = esc_url_raw( remove_query_arg( array(
							'_wpnonce',
							'maction',
							'message'
						), $_GET['_wp_http_referer'] ) );
						if ( $bulk_action == 'delete' ) {
							$this->chat_session_logs_messages( 'message', 'delete', $_GET['chat-messages-bulk'] );
							$chat_href = add_query_arg( 'message', 'success-messages-delete', $chat_href );
							wp_redirect( $chat_href );

						} else if ( $bulk_action == "hide" ) {
							$this->chat_session_logs_messages( 'message', 'hide', $_GET['chat-messages-bulk'] );
							$chat_href = add_query_arg( 'message', 'success-messages-hide', $chat_href );
							wp_redirect( $chat_href );
						} else if ( $bulk_action == "unhide" ) {
							$this->chat_session_logs_messages( 'message', 'unhide', $_GET['chat-messages-bulk'] );
							$chat_href = add_query_arg( 'message', 'success-messages-unhide', $chat_href );
							wp_redirect( $chat_href );
						}
					}

				} else if ( ( isset( $_GET['chat-logs-bulk'] ) ) && ( is_array( $_GET['chat-logs-bulk'] ) ) && ( count( $_GET['chat-logs-bulk'] ) ) && ( isset( $_GET['_wpnonce'] ) ) && ( wp_verify_nonce( $_GET['_wpnonce'], 'bulk-logs' ) ) ) {

					$bulk_action = '';

					if ( ( isset( $_GET['action'] ) ) && ( $_GET['action'] != '-1' ) ) {
						$bulk_action = esc_attr( $_GET['action'] );
					} else if ( ( isset( $_GET['action2'] ) ) && ( $_GET['action2'] != '-1' ) ) {
						$bulk_action = esc_attr( $_GET['action2'] );
					}

					$_SERVER['REQUEST_URI'] = esc_url_raw( remove_query_arg( array(
						'_wp_http_referer',
						'_wpnonce',
						'action',
						'action2',
						'mid'
					), $_SERVER['REQUEST_URI'] ) );

					if ( ( $bulk_action ) && ( ! empty( $bulk_action ) ) ) {

						$chat_href = esc_url_raw( remove_query_arg( array( '_wpnonce', 'maction', 'message' ) ) );
						if ( $bulk_action == 'delete' ) {
							$this->chat_session_logs_messages( 'log', 'delete', $_GET['chat-logs-bulk'] );
							$chat_href = add_query_arg( 'message', 'success-logs-delete', $chat_href );

						} else if ( $bulk_action == "hide" ) {
							$this->chat_session_logs_messages( 'log', 'hide', $_GET['chat-logs-bulk'] );
							$chat_href = add_query_arg( 'message', 'success-logs-hide', $chat_href );

						} else if ( $bulk_action == "unhide" ) {
							$this->chat_session_logs_messages( 'log', 'unhide', $_GET['chat-logs-bulk'] );
							$chat_href = add_query_arg( 'message', 'success-logs-unhide', $chat_href );
						}
						wp_redirect( $chat_href );

					}
				} else {
					// IF we are here there are no bulk or single actions to take. So we want to make clean URLs so
					// remove all the cruft query string args we don't need. Then redirect to load the page.

					$screen                 = get_current_screen();
					$screen_per_page_option = str_replace( '-', '_', $screen->id . "_per_page" );
					$per_page               = 20;

					if ( ( isset( $_GET['laction'] ) ) && ( $_GET['laction'] == 'details' ) ) {
						$per_page_option = 'chat_page_chat_session_messages_per_page';
					} else {
						$per_page_option = 'chat_page_chat_session_logs_per_page';
					}

					if ( ( isset( $_POST['wp_screen_options']['option'] ) )
					     && ( $_POST['wp_screen_options']['option'] == $screen_per_page_option )
					) {

						if ( isset( $_POST['wp_screen_options']['value'] ) ) {
							$per_page = intval( $_POST['wp_screen_options']['value'] );
							if ( ( ! $per_page ) || ( $per_page < 1 ) ) {
								$per_page = 20;
							}

							update_user_meta( get_current_user_id(), $per_page_option, $per_page );
						}
					}

					if ( ( isset( $_GET['_wp_http_referer'] ) ) || ( isset( $_GET['_wpnonce'] ) ) ) {
						$remove_args_array = array(
							'_wp_http_referer',
							'_wpnonce',
							'chat-filter',
							'action',
							'action2'
						);
						$args_array        = array();
						wp_parse_str( $_SERVER['REQUEST_URI'], $args_array );
						foreach ( $args_array as $_key => $_val ) {
							if ( ( $_key == "paged" ) && ( $_val == 1 ) ) {
								$remove_args_array[] = $_key;
							}

							if ( empty( $_val ) ) {
								$remove_args_array[] = $_key;
							}
						}
						wp_redirect( esc_url_raw( remove_query_arg( $remove_args_array, $_SERVER['REQUEST_URI'] ) ) );
						die();
					}

					$per_page = get_user_meta( get_current_user_id(), $per_page_option, true );
					if ( empty( $per_page ) ) {
						$per_page = 20;
					}
					add_screen_option( 'per_page', array(
						'label'   => __( 'per Page', 'psource-chat' ),
						'default' => $per_page
					) );

					if ( ( isset( $_GET['laction'] ) ) && ( $_GET['laction'] == 'details' ) ) {
						require_once( dirname( __FILE__ ) . '/../lib/psource_chat_admin_session_messages.php' );
						$this->chat_log_list_table = new PSOURCEChat_Session_Messages_Table();

					} else {
						require_once( dirname( __FILE__ ) . '/../lib/psource_chat_admin_session_logs.php' );
						$this->chat_log_list_table = new PSOURCEChat_Session_Logs_Table();

					}
				}
			}

			$this->process_panel_actions();
			//$this->load_configs();

			include_once( dirname( __FILE__ ) . '/../lib/psource_chat_form_sections.php' );

			// Since we are showing one of our admin panels we init the help system.
			include_once( dirname( __FILE__ ) . '/../lib/psource_chat_admin_panels_help.php' );
			psource_chat_panel_help();

			// Add our tool tips.
			if ( ! class_exists( 'PSOURCE_HelpTooltips' ) ) {
				require_once( dirname( __FILE__ ) . '/../lib/class_wd_help_tooltips.php' );
			}
			$this->tips = new PSOURCE_HelpTooltips();
			$this->tips->set_icon_url( $this->get_plugin_url( '/images/information.png' ) );
		}

		/**
		 * Processing logic function. Called from on_load_pages above. This function handles the settings form submit filtering and storage of settings.
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 * @see        http://codex.wordpress.org/Adding_Administration_Menus
		 */
		function process_panel_actions() {
			global $wp_roles;

			if ( isset( $_POST['chat_user_meta'] ) ) {
				update_option( 'psource-chat-user-meta', $_POST['chat_user_meta'] );
				$this->_chat_options_defaults['user_meta'] = $_POST['chat_user_meta'];
			}

			if ( isset( $_POST['chat'] ) ) {

				if ( ( ! isset( $_POST['psource_chat_settings_save_wpnonce'] ) )
				     || ( ! wp_verify_nonce( $_POST['psource_chat_settings_save_wpnonce'], 'psource_chat_settings_save' ) )
				) {
					return;
				}

				$chat_settings = $_POST['chat'];

				if ( isset( $chat_settings['section'] ) ) {
					$chat_section = $chat_settings['section'];
					unset( $chat_settings['section'] );

					// Split off the Banned section since it goes into its own options key
					if ( $chat_section == "global" ) {

						if ( isset( $chat_settings['banned'] ) ) {
							$banned_section = $chat_settings['banned'];
							unset( $chat_settings['banned'] );

							// Need to convert the textarea list of words to an array for easier searching
							if ( isset( $banned_section['blocked_words'] ) ) {
								$banned_section['blocked_words'] = explode( "\n", $banned_section['blocked_words'] );
								foreach ( $banned_section['blocked_words'] as $_idx => $_val ) {
									$_word = trim( $_val );
									if ( ! empty( $_word ) ) {
										$banned_section['blocked_words'][ $_idx ] = wp_kses( $_word, '', '' );
									} else {
										unset( $banned_section['blocked_words'][ $_idx ] );
									}
								}
								update_option( 'psource-chat-banned', $banned_section );
								$this->_chat_options['banned'] = $banned_section;
							}
						}

						if ( isset( $chat_settings['blocked_ip_addresses'] ) ) {
							$chat_settings['blocked_ip_addresses'] = explode( "\n", $chat_settings['blocked_ip_addresses'] );
							foreach ( $chat_settings['blocked_ip_addresses'] as $_idx => $_val ) {
								$_word = trim( $_val );
								if ( ! empty( $_word ) ) {
									$chat_settings['blocked_ip_addresses'][ $_idx ] = wp_kses( $_word, '', '' );
								} else {
									unset( $chat_settings['blocked_ip_addresses'][ $_idx ] );
								}
							}
						}

						if ( isset( $chat_settings['blocked_users'] ) ) {
							$chat_settings['blocked_users'] = explode( "\n", $chat_settings['blocked_users'] );
							foreach ( $chat_settings['blocked_users'] as $_idx => $_val ) {
								$_word = trim( $_val );
								if ( ! empty( $_word ) ) {
									$chat_settings['blocked_users'][ $_idx ] = wp_kses( $_word, '', '' );
								} else {
									unset( $chat_settings['blocked_users'][ $_idx ] );
								}
							}
						}

						if ( isset( $chat_settings['blocked_admin_urls'] ) ) {
							$chat_settings['blocked_admin_urls'] = explode( "\n", $chat_settings['blocked_admin_urls'] );
							foreach ( $chat_settings['blocked_admin_urls'] as $_idx => $_val ) {
								$_word = trim( $_val );
								if ( ! empty( $_word ) ) {
									$chat_settings['blocked_admin_urls'][ $_idx ] = wp_kses( $_word, '', '' );
								} else {
									unset( $chat_settings['blocked_admin_urls'][ $_idx ] );
								}
							}
						}

						if ( isset( $chat_settings['blocked_front_urls'] ) ) {
							$chat_settings['blocked_front_urls'] = explode( "\n", $chat_settings['blocked_front_urls'] );
							foreach ( $chat_settings['blocked_front_urls'] as $_idx => $_val ) {
								$_word = trim( $_val );
								if ( ! empty( $_word ) ) {
									$chat_settings['blocked_front_urls'][ $_idx ] = wp_kses( $_word, '', '' );
								} else {
									unset( $chat_settings['blocked_front_urls'][ $_idx ] );
								}
							}
						}

					} else if ( ( $chat_section == "page" ) || ( $chat_section == "site" ) || ( $chat_section == "widget" ) || ( $chat_section == "dashboard" ) ) {

						// Process the rest.
						if ( ! isset( $chat_settings['login_options'] ) ) {
							$chat_settings['login_options'] = array();
						}

						if ( ! isset( $chat_settings['moderator_roles'] ) ) {
							$chat_settings['moderator_roles'] = array();
						}

						if ( isset( $chat_settings['blocked_urls'] ) ) {
							$chat_settings['blocked_urls'] = explode( "\n", $chat_settings['blocked_urls'] );
							foreach ( $chat_settings['blocked_urls'] as $_idx => $_val ) {
								$_word = trim( $_val );
								if ( ! empty( $_word ) ) {
									$chat_settings['blocked_urls'][ $_idx ] = wp_kses( $_word, '', '' );
								} else {
									unset( $chat_settings['blocked_urls'][ $_idx ] );
								}
							}
						}
					}
					foreach ( $chat_settings as $_idx => $_val ) {
						if ( $_idx == "login_options" ) {
							$chat_settings[ $_idx ] = $_val;
						} else if ( $_idx == "moderator_roles" ) {
							$chat_settings[ $_idx ] = $_val;
						} else if ( is_array( $_val ) ) {
							$chat_settings[ $_idx ] = $_val;
						} else if ( ( $_idx == "noauth_login_message" ) || ( $_idx == "noauth_login_prompt" ) ) {
							$args                   = array(
								//formatting
								'br'     => array(),
								'strong' => array(),
								'em'     => array(),
								'b'      => array(),
								'i'      => array(),
							);
							$chat_settings[ $_idx ] = wp_kses( $_val, $args, '' );
						} else {
							//$chat_settings[$_idx] = $_val;
							$chat_settings[ $_idx ] = wp_kses( $_val, '', '' );
						}
					}

					if ( ( is_multisite() ) && ( is_network_admin() ) ) {
						update_site_option( 'psource-chat-' . $chat_section, $chat_settings );
					} else {
						update_option( 'psource-chat-' . $chat_section, $chat_settings );
					}

					// As the user is saving the settings we update the nag admin notice with the current version.
					if ( ( is_multisite() ) && ( is_network_admin() ) ) {
						update_site_option( 'psource-chat-version-nag', $this->_chat_plugin_settings['options_version'] );
					} else {
						update_option( 'psource-chat-version-nag', $this->_chat_plugin_settings['options_version'] );
					}
					$this->_admin_notice_messages_key = 'success-settings';

					//if (strncasecmp($chat_section, "network-", strlen("network-")) == 0) {
					//	update_site_option('psource-chat-'. $chat_section, $chat_settings);
					//} else {
					//	update_option('psource-chat-'. $chat_section, $chat_settings);
					//}
					$this->_chat_options[ $chat_section ] = $chat_settings;

					//$chat_href = remove_query_arg(array('_wpnonce', 'maction', 'message'));
					//$chat_href = add_query_arg('message', 'success-message-delete', $chat_href);
					//wp_redirect($chat_href);

				}
			}
		}

		/**
		 * This function is called when we init the TinyMCE hook from out init process. This function handles the needed logic to interface
		 * with the WP TinyMCE editor. Specifically, this function is called when the user clicks the chat button on the editor toolbar. This
		 * functin is the gateway to showing the popup settings window.
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */
		function tinymce_options() {

			global $wp_version;

			// Enaueue the WordPress things
			wp_enqueue_style( 'farbtastic' );
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'farbtastic' );

			wp_enqueue_script( 'tiny_mce_popup.js', includes_url() . 'js/tinymce/tiny_mce_popup.js', array(), $wp_version );
			wp_enqueue_script( 'mctabs.js', includes_url() . 'js/tinymce/utils/mctabs.js', array(), $wp_version );
			wp_enqueue_script( 'validate.js', includes_url() . 'js/tinymce/utils/validate.js', array(), $wp_version );

			wp_enqueue_script( 'form_utils.js', includes_url() . 'js/tinymce/utils/form_utils.js', array(), $wp_version );
			wp_enqueue_script( 'editable_selects.js', includes_url() . 'js/tinymce/utils/editable_selects.js', array(), $wp_version );


			// Enqueue the Chat specific things
			wp_register_style( 'psource-chat-admin-css', $this->get_plugin_url( '/css/psource-chat-admin.css' ),
				array(), $this->chat_current_version );
			$this->_registered_styles['psource-chat-admin-css'] = 'psource-chat-admin-css';

			//echo plugins_url('/js/jquery-cookie.js', dirname(__FILE__));
			wp_enqueue_script( 'jquery-cookie', $this->get_plugin_url( '/js/jquery-cookie.js' ), array( 'jquery' ), $this->chat_current_version );
			$this->_registered_scripts['jquery-cookie'] = 'jquery-cookie';

			wp_enqueue_script( 'psource-chat-admin-js', $this->get_plugin_url( '/js/psource-chat-admin.js' ), array( 'jquery' ), $this->chat_current_version, true );
			$this->_registered_scripts['psource-chat-admin-js'] = 'psource-chat-admin-js';

			wp_enqueue_script( 'psource-chat-admin-tinymce-js', $this->get_plugin_url( '/js/psource-chat-admin-tinymce.js' ),
				array( 'jquery', 'jquery-ui-core', 'jquery-ui-tabs', 'farbtastic' ), $this->chat_current_version );
			$this->_registered_scripts['psource-chat-admin-tinymce-js'] = 'psource-chat-admin-tinymce-js';

			wp_enqueue_script( 'psource-chat-admin-farbtastic-js', $this->get_plugin_url( '/js/psource-chat-admin-farbtastic.js' ), array(
				'jquery',
				'farbtastic'
			), $this->chat_current_version, true );
			$this->_registered_scripts['psource-chat-admin-farbtastic-js'] = 'psource-chat-admin-farbtastic-js';

			// Add our tool tips.
			if ( ! class_exists( 'PSOURCE_HelpTooltips' ) ) {
				require_once( dirname( __FILE__ ) . '/../lib/class_wd_help_tooltips.php' );
			}
			$this->tips = new PSOURCE_HelpTooltips();
			$this->tips->set_icon_url( $this->get_plugin_url( '/images/information.png' ) );

			include_once( dirname( __FILE__ ) . '/../lib/psource_chat_form_sections.php' );
			include_once( dirname( __FILE__ ) . '/../lib/psource_chat_admin_panels_help.php' );
			include_once( dirname( __FILE__ ) . '/../lib/psource_chat_admin_tinymce.php' );
		}


		/**
		 * Called when the User profile is to be edited. This function adds some fields to the profile form specific to our chat plugin.
		 *
		 * @global    none
		 *
		 * @param    $user - This is the object of the user being edited.
		 *
		 * @return    none
		 */
		function chat_edit_user_profile( $user = '' ) {

			$this->load_configs();

			if ( ! $user ) {
				global $current_user;
				$user = $current_user;
			}

			$user_meta = get_user_meta( $user->ID, 'psource-chat-user', true );
			if ( ! $user_meta ) {
				$user_meta = array();
			}
			$user_meta['chat_user_status'] = psource_chat_get_user_status( $user->ID );
			$user_meta                     = wp_parse_args( $user_meta, $this->_chat_options_defaults['user_meta'] );

			?>
			<h3><?php _e( 'Chat Settings', 'psource-chat' ); ?></h3>

			<table class="form-table">
				<tr>
					<th>
						<label
							for="psource_chat_status"><?php _e( 'Set Chat status', 'psource-chat' ); ?></label>
					</th>
					<td>
						<select name="psource_chat_user_settings[chat_user_status]" id="psource_chat_status">
							<?php
							foreach ( $this->_chat_options['user-statuses'] as $status_key => $status_label ) {
								if ( $status_key == 'away' ) {
									continue;
								}

								if ( $status_key == $user_meta['chat_user_status'] ) {
									$selected = ' selected="selected" ';
								} else {
									$selected = '';
								}

								?>
								<option
								value="<?php echo $status_key; ?>" <?php echo $selected; ?>><?php echo $status_label; ?></option><?php
							}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<th>
						<label
							for="psource_chat_name_display"><?php _e( 'In Chat-Sitzungen wird der Name angezeigt als', 'psource-chat' ); ?></label>
					</th>
					<td>
						<select name="psource_chat_user_settings[chat_name_display]" id="psource_chat_name_display">
							<option
								value="display_name" <?php if ( $user_meta['chat_name_display'] == 'display_name' ) {
								echo ' selected="selected" ';
							} ?>><?php echo __( 'Anzeigename', 'psource-chat' ) . ": " . $user->display_name; ?></option>
							<option value="user_login" <?php if ( $user_meta['chat_name_display'] == 'user_login' ) {
								echo ' selected="selected" ';
							} ?>><?php echo __( 'Benutzer-Anmeldung', 'psource-chat' ) . ": " . $user->user_login; ?></option>
						</select>
					</td>
				</tr>
				<?php
				if ( current_user_can( 'list_users' ) ) {
					?>
					<tr>
						<th>
							<label
								for="psource_chat_users_listing"><?php _e( 'Spalte "Chat-Status anzeigen" in der Liste <br />"Benutzer > Alle Benutzer"?', 'psource-chat' ); ?></label>
						</th>
						<td>
							<select name="psource_chat_user_settings[chat_users_listing]"
							        id="psource_chat_users_listing">
								<option value="enabled"<?php if ( $user_meta['chat_users_listing'] == 'enabled' ) {
									echo ' selected="selected" ';
								} ?>><?php
									_e( 'Aktiviert', 'psource-chat' ); ?></option>
								<option value="disabled"<?php if ( $user_meta['chat_users_listing'] == 'disabled' ) {
									echo ' selected="selected" ';
								} ?>><?php
									_e( 'Deaktiviert', 'psource-chat' ); ?></option>
							</select>
						</td>
					</tr>
				<?php } ?>

				<tr>
					<th>
						<label
							for="psource_chat_wp_admin"><?php _e( 'Chats im Dashboard anzeigen	', 'psource-chat' ); ?></label>
					</th>
					<td>
						<select name="psource_chat_user_settings[chat_wp_admin]" id="psource_chat_wp_admin">
							<option value="enabled"<?php if ( $user_meta['chat_wp_admin'] == 'enabled' ) {
								echo ' selected="selected" ';
							} ?>><?php
								_e( 'Aktviert', 'psource-chat' ); ?></option>
							<option value="disabled"<?php if ( $user_meta['chat_wp_admin'] == 'disabled' ) {
								echo ' selected="selected" ';
							} ?>><?php
								_e( 'Deaktiviert', 'psource-chat' ); ?></option>
						</select>

						<p class="description"><?php _e( 'Dadurch werden alle Chat-Funktionen deaktiviert, einschließlich des WordPress-Symbolleistenmenüs, der Dashboard-Widgets usw.', 'psource-chat' ); ?></p>
					</td>
				</tr>
				<tr class="psource_chat_wp_admin_display" <?php if ( $user_meta['chat_wp_admin'] != 'enabled' ) {
					echo ' style="display:none;" ';
				} ?>>
					<th>
						<label
							for="psource_chat_wp_toolbar"><?php _e( 'Chat WordPress Symbolleistenmenü anzeigen?', 'psource-chat' ); ?></label>
					</th>
					<td>
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

						<div
							id="psource_chat_wp_toolbar_options" <?php if ( $user_meta['chat_wp_toolbar'] == 'disabled' ) {
							echo ' style="display:none;" ';
						} ?> >
							<p>
								<select name="psource_chat_user_settings[chat_wp_toolbar_show_status]"
								        id="psource_chat_wp_toolbar_show_status">
									<option
										value="enabled"<?php if ( $user_meta['chat_wp_toolbar_show_status'] == 'enabled' ) {
										echo ' selected="selected" ';
									} ?>><?php _e( 'Aktiviert', 'psource-chat' ); ?></option>
									<option
										value="disabled"<?php if ( $user_meta['chat_wp_toolbar_show_status'] == 'disabled' ) {
										echo ' selected="selected" ';
									} ?>><?php _e( 'Deaktiviert', 'psource-chat' ); ?></option>
								</select>
								<label
									for="psource_chat_wp_toolbar_show_status"><?php _e( 'Zeige Deinen Chat-Status im WordPress-Symbolleistenmenü an?', 'psource-chat' ); ?></label>
							</p>

							<p>
								<select name="psource_chat_user_settings[chat_wp_toolbar_show_friends]"
								        id="psource_chat_wp_toolbar_show_friends">
									<option
										value="enabled"<?php if ( $user_meta['chat_wp_toolbar_show_friends'] == 'enabled' ) {
										echo ' selected="selected" ';
									} ?>><?php _e( 'Aktiviert', 'psource-chat' ); ?></option>
									<option
										value="disabled"<?php if ( $user_meta['chat_wp_toolbar_show_friends'] == 'disabled' ) {
										echo ' selected="selected" ';
									} ?>><?php _e( 'Deaktiviert', 'psource-chat' ); ?></option>
								</select>
								<label
									for="psource_chat_wp_toolbar_show_friends"><?php _e( 'Zeige Deine Chat-Freunde im WordPress-Symbolleistenmenü?', 'psource-chat' ); ?></label>
							</p>
						</div>

					</td>
				</tr>


				<?php
				if ( $this->_chat_options['dashboard']['dashboard_widget'] == 'enabled' ) {

					if ( ( ( isset( $user->allcaps['level_10'] ) ) && ( $user->allcaps['level_10'] == 1 ) )
					     || ( array_intersect( $this->_chat_options['dashboard']['login_options'], $user->roles ) )
					) {
						?>
						<tr class="psource_chat_wp_admin_display" <?php if ( $user_meta['chat_wp_admin'] != 'enabled' ) {
							echo ' style="display:none;" ';
						} ?>>
							<th><label for="psource_chat_dashboard_widget"><?php
									_e( 'Chat-Widget im Dashboard anzeigen', 'psource-chat' ); ?></label></th>
							<td>
								<?php
								if ( is_network_admin() ) {
									$item_key = 'chat_network_dashboard_widget';
								} else {
									$item_key = 'chat_dashboard_widget';
								}
								?>
								<select name="psource_chat_user_settings[<?php echo $item_key; ?>]"
								        id="psource_chat_dashboard_widget">
									<option value="enabled"<?php if ( $user_meta[ $item_key ] == 'enabled' ) {
										echo ' selected="selected" ';
									} ?>><?php
										_e( 'Aktiviert', 'psource-chat' ); ?></option>
									<option value="disabled"<?php if ( $user_meta[ $item_key ] == 'disabled' ) {
										echo ' selected="selected" ';
									} ?>><?php
										_e( 'Deaktiviert', 'psource-chat' ); ?></option>
								</select>

								<div
									id="psource_chat_dashboard_widget_options" <?php if ( $user_meta[ $item_key ] == 'disabled' ) {
									echo ' style="display:none;" ';
								} ?>
								<?php
								if ( is_network_admin() ) {
									$item_option_key = 'chat_network_dashboard_widget_height';
								} else {
									$item_option_key = 'chat_dashboard_widget_height';
								}
								?>

								<p>
									<label
										for="psource_chat_dashboard_widget_height"><?php _e( 'Höhe des Chat-Widgets', 'psource-chat' ); ?></label><br/><input
										name="psource_chat_user_settings[<?php echo $item_option_key; ?>]"
										id="psource_chat_dashboard_widget_height"
										value="<?php echo $user_meta[ $item_option_key ]; ?>"/> <?php _e( 'Standard ist', 'psource-chat' ) ?> <?php echo $this->_chat_options['dashboard']['dashboard_widget_height'] ?>
								</p>
								</div>
							</td>
						</tr>
						<?php
					}
				}
				?>

				<?php
				if ( $this->_chat_options['dashboard']['dashboard_status_widget'] == 'enabled' ) {
					//if (((isset($user->allcaps['level_10'])) && ($user->allcaps['level_10'] == 1))
					// || (array_intersect($this->_chat_options['dashboard']['login_options'], $user->roles))) {

					?>
					<tr class="psource_chat_wp_admin_display" <?php if ( $user_meta['chat_wp_admin'] != 'enabled' ) {
						echo ' style="display:none;" ';
					} ?>>
						<th><label for="psource_chat_dashboard_status_widget"><?php
								_e( 'Chat-Status-Widget im Dashboard anzeigen', 'psource-chat' ); ?></label></th>
						<td>
							<?php
							if ( is_network_admin() ) {
								$item_key = 'chat_network_dashboard_status_widget';
							} else {
								$item_key = 'chat_dashboard_status_widget';
							}
							?>
							<select name="psource_chat_user_settings[<?php echo $item_key; ?>]"
							        id="psource_chat_dashboard_status_widget">
								<option value="enabled"<?php if ( $user_meta[ $item_key ] == 'enabled' ) {
									echo ' selected="selected" ';
								} ?>><?php
									_e( 'Aktiviert', 'psource-chat' ); ?></option>
								<option value="disabled"<?php if ( $user_meta[ $item_key ] == 'disabled' ) {
									echo ' selected="selected" ';
								} ?>><?php
									_e( 'Deaktiviert', 'psource-chat' ); ?></option>
							</select>
						</td>
					</tr>
					<?php
					//}
				}
				?>

				<?php
				if ( $this->_chat_options['dashboard']['dashboard_friends_widget'] == 'enabled' ) {
					global $bp;
					if ( ( empty( $bp ) ) && ( ! is_plugin_active( 'friends/friends.php' ) )
					     && ( ( is_multisite() ) && ( ! is_plugin_active_for_network( 'friends/friends.php' ) ) )
					) {

					} else {
						//if (((isset($user->allcaps['level_10'])) && ($user->allcaps['level_10'] == 1))
						// || (array_intersect($this->_chat_options['dashboard']['login_options'], $user->roles))) {

						?>
						<tr class="psource_chat_wp_admin_display" <?php if ( $user_meta['chat_wp_admin'] != 'enabled' ) {
							echo ' style="display:none;" ';
						} ?>>
							<th><label for="psource_chat_dashboard_friends_widget"><?php
									_e( 'Chat-Freunde-Widget im Dashboard anzeigen', 'psource-chat' ); ?></label>
							</th>
							<td>
								<?php
								if ( is_network_admin() ) {
									$item_key = 'chat_network_dashboard_friends_widget';
								} else {
									$item_key = 'chat_dashboard_friends_widget';
								}
								?>
								<select name="psource_chat_user_settings[<?php echo $item_key; ?>]"
								        id="psource_chat_dashboard_friends_widget">
									<option value="enabled"<?php if ( $user_meta[ $item_key ] == 'enabled' ) {
										echo ' selected="selected" ';
									} ?>><?php
										_e( 'Aktiviert', 'psource-chat' ); ?></option>
									<option value="disabled"<?php if ( $user_meta[ $item_key ] == 'disabled' ) {
										echo ' selected="selected" ';
									} ?>><?php
										_e( 'Deaktiviert', 'psource-chat' ); ?></option>
								</select><br/>

								<div
									id="psource_chat_dashboard_friends_widget_options" <?php if ( $user_meta[ $item_key ] == 'disabled' ) {
									echo ' style="display:none;" ';
								} ?>
								<?php
								if ( is_network_admin() ) {
									$item_option_key = 'chat_network_dashboard_friends_widget_height';
								} else {
									$item_option_key = 'chat_dashboard_friends_widget_height';
								}
								?>

								<p>
									<label
										for="psource_chat_dashboard_friends_widget_height"><?php _e( 'Min. Höhe des Chat-Freunde-Widgets', 'psource-chat' ); ?></label><br/><input
										name="psource_chat_user_settings[<?php echo $item_option_key; ?>]"
										id="psource_chat_dashboard_friends_widget_height"
										value="<?php echo $user_meta[ $item_option_key ]; ?>"/> <?php _e( 'Standard ist', 'psource-chat' ) ?> <?php echo $this->_chat_options['dashboard']['dashboard_friends_widget_height'] ?>
								</p>
								</div>
							</td>
						</tr>
						<?php
					}
				}
				?>
			</table>
			<?php
		}

		/**
		 * Called when the User profile is saved. This function looks for our specific form fields added in the 'chat_edit_user_profile' function and adds those
		 * to the user's meta settings.
		 *
		 * @global    none
		 *
		 * @param    $user_id - This ID of the user profile we are saving.
		 *
		 * @return    none
		 */
		function chat_save_user_profile( $user_id = '' ) {
			if ( ! $user_id ) {
				return;
			}
			if ( ! isset( $_POST['psource_chat_user_settings'] ) ) {
				return;
			}

			if ( isset( $_POST['psource_chat_user_settings']['chat_user_status'] ) ) {
				$chat_user_status = esc_attr( $_POST['psource_chat_user_settings']['chat_user_status'] );
				if ( isset( $this->_chat_options['user-statuses'][ $chat_user_status ] ) ) {
					psource_chat_update_user_status( $user_id, $chat_user_status );
				}
				unset( $_POST['psource_chat_user_settings']['chat_user_status'] );
			}

			$user_meta = get_user_meta( $user_id, 'psource-chat-user', true );
			if ( ! $user_meta ) {
				$user_meta = array();
			}

			$user_meta = wp_parse_args( $user_meta, $this->_chat_options_defaults['user_meta'] );
			$user_meta = wp_parse_args( $_POST['psource_chat_user_settings'], $user_meta );

			update_user_meta( $user_id, 'psource-chat-user', $user_meta );
		}


		function chat_delete_user( $id, $reassign = null ) {
			global $wpdb;

			// Check if we are to delete message for deleted WP users.
			if ( $this->get_option( 'delete_user_messages', 'global' ) != 'enabled' ) {
				return;
			}

			$deleted_user_auth_hash = md5( $id );

			if ( $reassign != null ) {
				$reassign_user_auth_hash = md5( $reassign );

				$sql_str = $wpdb->prepare( "UPDATE `" . PSOURCE_Chat::tablename( 'message' ) . "` SET auth_hash=%s WHERE auth_hash = %s;", $reassign_user_auth_hash, $deleted_user_auth_hash );
			} else {

				$sql_str = $wpdb->prepare( "DELETE FROM `" . PSOURCE_Chat::tablename( 'message' ) . "` WHERE auth_hash = %s;", $deleted_user_auth_hash );
			}
			//error_log(__FUNCTION__ .": sql_str: [". $sql_str ."]");

			$sql_str = $wpdb->prepare( "DELETE FROM `" . PSOURCE_Chat::tablename( 'users' ) . "` WHERE auth_hash = %s;", $deleted_user_auth_hash );
			$wpdb->query( $sql_str );
		}

		/**
		 * Used when the user (admin) wants to add the chat status to all users on the Users > All Users listing table.
		 *
		 * @global    none
		 *
		 * @param    $columns - An array of columns used for the table output.
		 *
		 * @return    $columns - We add our unique columns array and return. This is a filter!
		 */
		function chat_manage_users_columns( $columns ) {

			if ( current_user_can( 'list_users' ) ) {

				//$psource_chat_user_settings = get_user_meta( get_current_user_id(), 'psource-chat-user', true);
				if ( ( isset( $this->user_meta['chat_users_listing'] ) ) && ( $this->user_meta['chat_users_listing'] == "enabled" ) ) {
					if ( ! isset( $columns['psource-chat-status'] ) ) {
						$columns['psource-chat-status'] = __( 'Chat Status', 'psource-chat' );
					}
				}
			}

			return $columns;
		}

		/**
		 * Used to display the custom column output for the user's row. This function works in coordination with the 'chat_manage_users_columns' function.
		 *
		 * @global    none
		 *
		 * @param    $output - will be blank.
		 *            $column_name - Name (key) of the column we are to output. The key is set in the 'chat_manage_users_columns' function
		 *            $friend_user_id - This is the ID of the user for the given row.
		 *
		 * @return    $output - Will be a complete output for this cell.
		 */
		function chat_manage_users_custom_column( $output, $column_name, $friend_user_id ) {

			if ( ( current_user_can( 'list_users' ) ) && ( $column_name == 'psource-chat-status' ) ) {

				if ( $friend_user_id != get_current_user_id() ) { // Make sure we don't check ourselves
					$output .= psource_chat_get_chat_status_label( get_current_user_id(), $friend_user_id );
				}
			}

			return $output;
		}

		/**
		 * Process short code
		 *
		 * @global    object $post
		 * @global    array $chat_localized Localized strings and options
		 * @return    string                    Content
		 */
		function process_chat_shortcode( $atts ) {
			global $post, $current_user, $wpdb, $wp_roles;

			if ( ( ! isset( $atts['id'] ) ) || ( $atts['id'] == '' ) ) {

				if ( ( isset( $post->ID ) ) && ( intval( $post->ID ) )
				     && ( isset( $post->post_type ) ) && ( ! empty( $post->post_type ) )
				) {
					$atts['id'] = $post->post_type . '-' . $post->ID;
				} else {
					return;
				}
			}

			// Logic stands that if the atts key is not found then we don't add it. But if the key exists but is
			// empty we add in out defaults BEFORE processing.
			$control_arrays = array( 'login_options', 'moderator_roles' );
			foreach ( $control_arrays as $control_item ) {
				if ( ( ! isset( $atts[ $control_item ] ) ) || ( empty( $atts[ $control_item ] ) ) ) {
					continue;
				} else if ( is_string( $atts[ $control_item ] ) ) {
					$atts[ $control_item ] = explode( ',', $atts[ $control_item ] );
					if ( count( $atts[ $control_item ] ) ) {
						foreach ( $atts[ $control_item ] as $_idx => $_val ) {
							$atts[ $control_item ][ $_idx ] = trim( $_val );
						}
					}
				}
			}

			if ( ( isset( $atts['session_type'] ) ) && ( $atts['session_type'] == 'site' ) ) {
				$atts                         = $this->convert_config( 'site', $atts );
				$chat_session                 = wp_parse_args( $atts, $this->_chat_options['site'] );
				$chat_session['session_type'] = 'site';

				// If the network bottom corner chat is enabled we want to disable the site bottom corner. Too many bottom corner chats.
				if ( $this->_chat_plugin_settings['network_active'] == true ) {

					if ( $this->get_option( 'bottom_corner', 'network-site' ) == 'enabled' ) {
						$chat_session['bottom_corner'] = 'disabled';
					}
					if ( $this->get_option( 'bottom_corner_wpadmin', 'network-site' ) == 'enabled' ) {
						$chat_session['bottom_corner_wpadmin'] = 'disabled';
					}
				}

			} else if ( ( isset( $atts['session_type'] ) ) && ( $atts['session_type'] == 'private' ) ) {
				//$atts = $this->convert_config('site', $atts);
				$chat_session                 = wp_parse_args( $atts, $this->_chat_options['site'] );
				$chat_session['session_type'] = 'private';

				if ( empty( $atts['box_title'] ) ) {
					$chat_session['box_title'] = __( 'Privat', 'psource-chat' );
				}

			} else if ( ( isset( $atts['session_type'] ) ) && ( $atts['session_type'] == 'network-site' ) ) {
				//$atts = $this->convert_config('network-site', $atts);
				$chat_session                 = wp_parse_args( $atts, $this->_chat_options['network-site'] );
				$chat_session['session_type'] = 'network-site';

				if ( empty( $atts['box_title'] ) ) {
					$chat_session['box_title'] = __( 'Netzwerk', 'psource-chat' );
				}

			} else if ( ( isset( $atts['session_type'] ) ) && ( $atts['session_type'] == 'dashboard' ) ) {
				//$atts = $this->convert_config('dasahboard', $atts);
				$chat_session                 = wp_parse_args( $atts, $this->_chat_options['dashboard'] );
				$chat_session['session_type'] = 'dashboard';

				//if (empty($atts['box_title']))
				//	$chat_session['box_title']			= __('Group Chat', 'psource-chat');

			} else if ( ( isset( $atts['session_type'] ) ) && ( $atts['session_type'] == 'widget' ) ) {
				//$atts = $this->convert_config('widget', $atts);
				$chat_session                 = wp_parse_args( $atts, $this->_chat_options['widget'] );
				$chat_session['session_type'] = 'widget';
			} else if ( ( isset( $atts['session_type'] ) ) && ( $atts['session_type'] == 'bp-group' ) ) {
				$chat_session                 = wp_parse_args( $atts, $this->_chat_options['page'] );
				$chat_session['session_type'] = 'bp-group';
			} else {

				$chat_session                 = wp_parse_args( $atts, $this->_chat_options['page'] );
				$chat_session['session_type'] = 'page';
			}

			$chat_session['id'] = $atts['id'];
			if ( $chat_session['session_type'] == 'private' ) {
				$chat_session['blog_id'] = 0;
			} else if ( ( ( is_multisite() ) && ( $chat_session['session_type'] == 'network-site' ) ) || ( is_network_admin() ) ) {
				$chat_session['blog_id'] = 0;
			} else {
				global $blog_id;
				//$chat_session['blog_id'] 	= $wpdb->blogid;
				$chat_session['blog_id'] = $blog_id;
			}

			if ( isset( $chat_session['log_display'] ) && ( $chat_session['log_display'] == "enabled-link-above" || $chat_session['log_display'] == "enabled-link-below" ) ) {
				$chat_session['log_display_hide_session'] = "hide";
			}

			$chat_session['last_row_id'] = "__EMPTY__";
			$this->chat_session_update_message_rows_deleted( $chat_session );

			// If we have the old 'current_user' array item we convert it to use the 'wp-roles' WP user roles sub-array.
			$current_user_idx = array_search( 'current_user', $chat_session['login_options'] );
			if ( $current_user_idx !== false ) {
				unset( $chat_session['login_options'][ $current_user_idx ] );

				if ( count( $wp_roles ) ) {
					foreach ( $wp_roles->roles as $role_slug => $role ) {
						if ( array_search( $role_slug, $chat_session['login_options'] ) === false ) {
							$chat_session['login_options'][] = $role_slug;
						}
					}
				}
			}

			// double check that we have at least one WP user roles in our login_options
			//if ( count( $wp_roles ) ) {
			if ( empty( $wp_roles ) ) {
				if ( ! array_intersect( $chat_session['login_options'], array_keys( $wp_roles->role_names ) ) ) {
					foreach ( $wp_roles->roles as $role_slug => $role ) {
						if ( isset( $role['capabilities']['level_10'] ) ) {
							$chat_session['login_options'][] = $role_slug;
						}
					}
				}
			}

			if ( $chat_session['session_type'] != "private" ) {
				/* php8 fix */
				//if ( ( count( $wp_roles ) ) && ( count( $chat_session['moderator_roles'] ) ) ) {
				if ( ( count( array($wp_roles ) ) ) && ( count( $chat_session['moderator_roles'] ) ) ) {
					foreach ( $wp_roles->roles as $role_slug => $role ) {
						if ( ( isset( $role['capabilities']['level_10'] ) ) && ( ! in_array( $role_slug, $chat_session['moderator_roles'] ) ) ) {
							$chat_settings['moderator_roles'][] = $role_slug;
						}
					}
				}

				// If the chat_auth type is not yet set. check the allowed login_options. If we are not allowing non-WP
				// user then abort. No use showing the chat
				if ( ! isset( $this->chat_auth['type'] ) ) {
					if ( ! $this->use_public_auth( $chat_session ) ) {
						return false;
					}
				} else {
					// Need to check the user.
					// Check for WordPress users
					if ( $this->chat_auth['type'] == "wordpress" ) {

						// If the chat_auth[type] says 'wordpress' but the user is not actually WP authenticated...
						if ( ! is_user_logged_in() ) {
							$this->chat_auth         = array();
							$this->chat_auth['type'] = 'invalid';
						} else {
							global $bp;
							// Are we viewing a BP page?

							if ( ( isset( $bp->groups->current_group->id ) ) && ( intval( $bp->groups->current_group->id ) ) ) {
								if ( ( ! isset( $bp->groups->current_group->user_has_access ) ) || ( $bp->groups->current_group->user_has_access != 1 ) ) {
									//log_chat_message(__FUNCTION__ .": ". __LINE__ .": here");;
									return false;
								}
							} else {
								// roles not set! Should not happen. Abort. Danger. Danger
								if ( ! isset( $current_user->roles ) ) {
									return false;
								}

								// For Multisite where the current user is NOT superadmin...
								if ( ( is_multisite() ) && ( ! is_super_admin() ) ) {
									global $blog_id;

									// We chat the user's blogs to see if they have acces to the current site.
									$user_blogs = get_blogs_of_user( $current_user->ID );

									// Check if the current blog_id is part of the user's visibility.
									if ( isset( $user_blogs[ $blog_id ] ) ) {

										// If it is then check the user's role against allowed roles
										if ( ! array_intersect( $chat_session['login_options'], $current_user->roles ) ) {
											return false;
										}
									} else if ( in_array( 'network', $chat_session['login_options'] ) === false ) {
										return false;
									}
								} else {
									if ( ! is_super_admin() ) {
										// If the current user's role(s) are not found in the login_options abort.
										if ( ! array_intersect( $chat_session['login_options'], $current_user->roles ) ) {
											return false;
										}
									}
								}
							}
						}
					} else {
						// Check for other: Public
						if ( ! in_array( $this->chat_auth['type'], $chat_session['login_options'] ) ) {
							return false;
						}
					}
				}
			}
			//log_chat_message(__FUNCTION__ .": ". __LINE__ .": here");;

			$box_font_style = "";
			if ( ! empty( $chat_session['box_font_family'] ) ) {
				if ( isset( $this->_chat_options_defaults['fonts_list'][ $chat_session['box_font_family'] ] ) ) {
					$box_font_style .= 'font-family: ' . $this->_chat_options_defaults['fonts_list'][ $chat_session['box_font_family'] ] . ';';
				}
			}
			if ( ! empty( $chat_session['box_font_size'] ) ) {
				$box_font_style .= 'font-size: ' . psource_chat_check_size_qualifier( $chat_session['box_font_size'] ) . ';';
			}
			$chat_session['box_font_style'] = $box_font_style;

			$row_font_style = "";
			if ( ! empty( $chat_session['row_font_family'] ) ) {
				if ( isset( $this->_chat_options_defaults['fonts_list'][ $chat_session['row_font_family'] ] ) ) {
					$row_font_style .= 'font-family: ' . $this->_chat_options_defaults['fonts_list'][ $chat_session['row_font_family'] ] . ';';
				}
			}
			if ( ! empty( $chat_session['row_font_size'] ) ) {
				$row_font_style .= 'font-size: ' . psource_chat_check_size_qualifier( $chat_session['row_font_size'] ) . ';';
			}
			$chat_session['row_font_style'] = $row_font_style;

			$row_message_input_font_style = "";
			if ( ! empty( $chat_session['row_message_input_font_family'] ) ) {
				if ( isset( $this->_chat_options_defaults['fonts_list'][ $chat_session['row_message_input_font_family'] ] ) ) {
					$row_message_input_font_style .= 'font-family: ' . $this->_chat_options_defaults['fonts_list'][ $chat_session['row_message_input_font_family'] ] . ';';
				}
			}
			if ( ! empty( $chat_session['row_message_input_font_size'] ) ) {
				$row_message_input_font_style .= 'font-size: ' . psource_chat_check_size_qualifier( $chat_session['row_message_input_font_size'] ) . ';';
			}
			$chat_session['row_message_input_font_style'] = $row_message_input_font_style;

			$users_list_font_style = '';
			if ( ! empty( $chat_session['users_list_font_family'] ) ) {
				if ( isset( $this->_chat_options_defaults['fonts_list'][ $chat_session['users_list_font_family'] ] ) ) {
					$users_list_font_style .= 'font-family: ' . $this->_chat_options_defaults['fonts_list'][ $chat_session['users_list_font_family'] ] . ';';
				}
			}
			if ( ! empty( $chat_session['users_list_font_size'] ) ) {
				$users_list_font_style .= 'font-size: ' . psource_chat_check_size_qualifier( $chat_session['users_list_font_size'] ) . ';';
			}
			$chat_session['users_list_font_style'] = $users_list_font_style;


			// Need to check all the input size type fields to make sure there is proper qualifiers (px, pt, em, %)

			$chat_session['box_width']        = psource_chat_check_size_qualifier( $chat_session['box_width'] );
			$chat_session['box_height']       = psource_chat_check_size_qualifier( $chat_session['box_height'] );
			$chat_session['box_border_width'] = psource_chat_check_size_qualifier( $chat_session['box_border_width'] );
			//$chat_session['box_padding'] 				= psource_chat_check_size_qualifier($chat_session['box_padding']);
			$chat_session['row_spacing']              = psource_chat_check_size_qualifier( $chat_session['row_spacing'] );
			$chat_session['row_border_width']         = psource_chat_check_size_qualifier( $chat_session['row_border_width'] );
			$chat_session['row_message_input_height'] = psource_chat_check_size_qualifier( $chat_session['row_message_input_height'] );
			$chat_session['row_avatar_width']         = psource_chat_check_size_qualifier( $chat_session['row_avatar_width'], array( 'px' ) );

			$chat_session['users_list_width']        = psource_chat_check_size_qualifier( $chat_session['users_list_width'], array(
				'px',
				'%'
			) );
			$chat_session['users_list_avatar_width'] = psource_chat_check_size_qualifier( $chat_session['users_list_avatar_width'], array( 'px' ) );

			$chat_session['row_message_input_length'] = intval( $chat_session['row_message_input_length'] );
			$chat_session['log_limit']                = intval( $chat_session['log_limit'] );

			if ( $chat_session['log_limit'] == 0 ) {
				$chat_session['log_limit'] = 100;
			}

			// Enfore the user list threshold is not too low
			$chat_session['users_list_threshold_delete'] = intval( $chat_session['users_list_threshold_delete'] );
			if ( $chat_session['users_list_threshold_delete'] < 20 ) {
				$chat_session['users_list_threshold_delete'] = 20;
			}

			if ( $chat_session['users_list_position'] != "none" ) {
				if ( ( $chat_session['users_list_position'] == "right" ) || ( $chat_session['users_list_position'] == "left" ) ) {

					if ( $chat_session['users_list_position'] == "right" ) {
						$chat_session['show_messages_position'] = "left";
					} else if ( $chat_session['users_list_position'] == "left" ) {
						$chat_session['show_messages_position'] = "right";
					}

					$user_list_width                     = intval( $chat_session['users_list_width'] );
					$chat_session['show_messages_width'] = 100 - $user_list_width . "%";
					$chat_session['users_list_width']    = intval( $chat_session['users_list_width'] ) . "%";
				} else if ( ( $chat_session['users_list_position'] == "above" ) || ( $chat_session['users_list_position'] == "below" ) ) {

					//if ($chat_session['users_list_position'] == "above") {
					//	$chat_session['show_messages_position'] 	= "right";
					//} else if ($chat_session['users_list_position'] == "below") {
					//	$chat_session['show_messages_position'] 	= "left";
					//}
					$chat_session['show_messages_position'] = "left"; //for CSS float: positioning

					$chat_session['users_list_height'] = $chat_session['users_list_width'];
					//$chat_session['users_list_width']			= "100%";
					$chat_session['show_messages_width'] = "100%";
				}

			} else {
				$chat_session['users_list_width']       = "0%";
				$chat_session['show_messages_position'] = "left";
				$chat_session['show_messages_width']    = "100%";
			}

			$chat_session['ip_address'] = ( isset( $_SERVER['HTTP_X_FORWARD_FOR'] ) ) ? $_SERVER['HTTP_X_FORWARD_FOR'] : $_SERVER['REMOTE_ADDR'];

			// Setup the session in the user's meta information which will store into a cookie on JS loading.
			if ( ! isset( $this->chat_user[ $chat_session['id'] ] ) ) {
				$this->chat_user[ $chat_session['id'] ] = $this->chat_user['__global__'];
				if ( $chat_session['session_type'] == 'site' ) {
					if ( isset( $chat_session['status_max_min'] ) ) {
						$this->chat_user[ $chat_session['id'] ]['status_max_min'] = $chat_session['status_max_min'];
					}
				} else {
					$this->chat_user[ $chat_session['id'] ]['status_max_min'] = 'max';
				}
			}

			if ( psource_chat_is_moderator( $chat_session ) ) {
				$chat_session['moderator'] = "yes";
			} else {
				$chat_session['moderator'] = "no";
			}

			$chat_session['session_status'] = $this->chat_session_get_meta( $chat_session, 'session_status' );

			if ( ( $chat_session['session_type'] == 'page' ) || ( $chat_session['session_type'] == 'bp-group' ) ) {
				if ( $chat_session['session_type'] == 'page' ) {
					$chat_session['session_title'] = get_the_title();
					$chat_session['session_url']   = get_the_permalink();

				} else if ( $chat_session['session_type'] == 'bp-group' ) {
					global $bp;
					$current_group                 = $bp->groups->current_group;
					$chat_session['session_title'] = apply_filters_ref_array( 'bp_get_group_name', array(
						$current_group->name,
						$current_group
					) );
					$chat_session['session_url']   = bp_get_group_permalink( $current_group ) . $this->get_option( 'bp_menu_slug', 'global' );
				}
			}

			if ( ! isset( $this->chat_sessions[ $chat_session['id'] ] ) ) {
				$this->chat_sessions[ $chat_session['id'] ] = array();
			}

			$this->chat_sessions[ $chat_session['id'] ] = $chat_session;

			//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			// End of settings logic. Now to build the boxes.
			//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

			$content = '';

			if ( ( isset( $_GET['chat-show-logs'] ) ) || ( ( isset( $_GET['chat-log-id'] ) ) && ( intval( $_GET['chat-log-id'] ) ) ) ) {

				if ( $chat_session['log_display_hide_session'] == "show" ) {
					// For most chats we simple add an empty div on page load. This empty div is populated by the 'init' AJAX call.
					// But for private chats we are already doing AJAX. So we build out the full chat box.
					if ( ( $chat_session['session_type'] == "private" ) || ( $chat_session['session_type'] == "network-site" ) ) {
						$content .= $this->chat_session_build_box( $chat_session );
					}

					$content = $this->chat_box_container( $chat_session, $content );

					if ( ( $chat_session['log_display'] == "enabled-list-above" ) || ( $chat_session['log_display'] == "enabled-link-above" ) ) {
						$content = $this->chat_logs_container( $chat_session ) . $content;
					}

					if ( ( $chat_session['log_display'] == "enabled-list-below" ) || ( $chat_session['log_display'] == "enabled-link-below" ) ) {
						$content = $content . $this->chat_logs_container( $chat_session );
					}

				} else if ( $chat_session['log_display_hide_session'] == "hide" ) {
					$content = $this->chat_logs_container( $chat_session );
				}
			} else {
				// For most chats we simple add an empty div on page load. This empty div is populated by the 'init' AJAX call.
				// But for private chats we are already doing AJAX. So we build out the full chat box.
				if ( ( $chat_session['session_type'] == "private" ) || ( $chat_session['session_type'] == "network-site" ) ) {
					$content = $this->chat_session_build_box( $chat_session );
				}

				$content = $this->chat_box_container( $chat_session, $content );

				//echo "chat_session log_display[". $chat_session['log_display'] ."]<br />";
				if ( isset( $chat_session['log_display'] ) && ( $chat_session['log_display'] == "enabled-list-above" || $chat_session['log_display'] == "enabled-link-above" ) ) {
					$content = $this->chat_logs_container( $chat_session ) . $content;
				}

				if ( isset( $chat_session['log_display'] ) && ( $chat_session['log_display'] == "enabled-list-below" || $chat_session['log_display'] == "enabled-link-below" ) ) {
					$content = $content . $this->chat_logs_container( $chat_session );
				}

			}

			// In some cases when viewing the live chat in wp-admin logs we don't want to update the transient since this
			// may effect some settings.
			if ( ( ! isset( $chat_session['update_transient'] ) ) || ( $chat_session['update_transient'] == 'enabled' ) ) {
				$transient_key = "chat-session-" . $chat_session['id'] . '-' . $chat_session['session_type'];
				if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
					update_option( $transient_key, $chat_session );
				} else {
					set_transient( $transient_key, $chat_session, 60 * 60 * 24 );
				}
			}

			return $content;
		}

		function process_chat_archive_shortcode( $atts ) {
			$chat_session       = wp_parse_args( $atts, $this->_chat_options['page'] );
			$chat_session['id'] = $atts['id'];
			if ( ( is_multisite() ) && ( $chat_session['session_type'] == 'network-site' ) ) {
				$chat_session['blog_id'] = 0;
			} else {
				global $blog_id;
				//$chat_session['blog_id'] 	= $wpdb->blogid;
				$chat_session['blog_id'] = $blog_id;
			}

			if ( isset( $_GET['set_archive_log_id'] ) ) {
				//echo "in set_archive_log_id<br />";
				global $wpdb;

				$logs = $this->get_archives( $chat_session );
			}
		}

		/**
		 * Displays the chat logs archive listing below the chat box.
		 *
		 * @global    none
		 *
		 * @param    $chat_session - This is out master settings instance.
		 *
		 * @return    $content - The output of the archives table. Will be echoed at some other point.
		 */
		function chat_logs_container( $chat_session ) {
			global $bp;

			$content     = '';
			$return_link = '';

			if ( ( $chat_session['session_type'] != "page" ) && ( $chat_session['session_type'] != "bp-group" ) ) {
				return $content;
			}

			if ( $this->using_popup_out_template == true ) {
				return $content;
			}

			if ( ! isset( $chat_session['log_display_role_level'] ) ) {
				$chat_session['log_display_role_level'] = "public";
			}

			// if the log_display_role is NOT public then we need to check the current user' srole to see if they are allowed
			if ( ( $chat_session['log_display_role_level'] != 'public' ) && ( ! is_super_admin() ) ) {
				global $current_user;

				if ( ( isset( $bp->groups->current_group->id ) ) && ( intval( $bp->groups->current_group->id ) ) ) {
					if ( $chat_session['log_display_role_level'] == 'group_admins' ) {
						if ( ! groups_is_user_admin( bp_loggedin_user_id(), $bp->groups->current_group->id ) ) {
							return $content;
						}

					} else if ( $chat_session['log_display_role_level'] == 'group_mods' ) {
						if ( ! groups_is_user_mod( bp_loggedin_user_id(), $bp->groups->current_group->id ) ) {
							return $content;
						}

					} else if ( $chat_session['log_display_role_level'] == 'group_members' ) {
						if ( ( ! isset( $bp->groups->current_group->user_has_access ) ) || ( $bp->groups->current_group->user_has_access != 1 ) ) {
							return $content;
						}

					} else {
						return $content;
					}

				} else {
					$current_user_role_level = psource_chat_get_user_role_highest_level( $current_user->allcaps );
					if ( ! $current_user_role_level ) {
						$current_user_role_level = 0;
					}

					$log_user_role_level = intval( str_replace( 'level_', '', $chat_session['log_display_role_level'] ) );
					if ( ! $log_user_role_level ) {
						$log_user_role_level = 0;
					}

					// If the current user level is less than our limit return the empty content.
					if ( ( $log_user_role_level > $current_user_role_level ) || ( $log_user_role_level == 0 ) ) {
						return $content;
					}
				}
			}

			$date_content       = '';
			$chat_session_dates = $this->get_archives( $chat_session );

			if ( ( $chat_session_dates ) && ( is_array( $chat_session_dates ) ) ) {
				krsort( $chat_session_dates );

				foreach ( $chat_session_dates as $chat_session_date ) {

					$query_args   = array(
						'chat-log-id' => $chat_session_date->id,
					);
					$archive_href = esc_url( add_query_arg( $query_args ) );

					$date_str = date_i18n( get_option( 'date_format' ) . ' ' .
					                       get_option( 'time_format' ), strtotime( $chat_session_date->start ) + get_option( 'gmt_offset' ) * 3600, false ) .
					            ' - ' . date_i18n( get_option( 'date_format' ) . ' ' .
					                               get_option( 'time_format' ), strtotime( $chat_session_date->end ) + get_option( 'gmt_offset' ) * 3600, false );

					if ( isset( $_GET['chat-log-id'] ) && $_GET['chat-log-id'] == $chat_session_date->id ) {
						$date_content .= '<li>' . $date_str . ' <a href="' . esc_url( remove_query_arg( 'chat-log-id' ) ) . '">' . __( 'Geschlossen', 'psource-chat' ) . "</a>";

						if ( isset( $_GET['chat-log-id'] ) && $_GET['chat-log-id'] == $chat_session_date->id ) {
							$chat_session_log                 = $chat_session;
							$chat_session_log['session_type'] = "log";

							$chat_session['since']     = strtotime( $chat_session_date->start );
							$chat_session['end']       = strtotime( $chat_session_date->end );
							$chat_session['log_limit'] = 0;
							$chat_session['orderby']   = 'ASC';
							$chat_session['archived']  = array( 'yes' );

							$chat_log_rows = $this->chat_session_get_messages( $chat_session );

							if ( ( $chat_log_rows ) && ( is_array( $chat_log_rows ) ) && ( count( $chat_log_rows ) ) ) {
								$chat_rows_content = '';
								foreach ( $chat_log_rows as $row ) {
									$chat_rows_content .= $this->chat_session_build_row( $row, $chat_session_log );
								}
								if ( strlen( $chat_rows_content ) ) {
									$date_content .= '<div id="psource-chat-box-archive" class="psource-chat-box"><div class="psource-chat-module-messages-list" >' . $chat_rows_content . '</div></div>';
								}
							}
						}
					} else {
						$date_content .= '<li><a class="chat-log-link" style="text-decoration: none;" href="' . $archive_href . '">' . $date_str . '</a>';
					}
					$date_content .= '</li>';
				}
			}
			if ( empty( $date_content ) ) {
				$date_content = "<p>" . __( 'Für diese Sitzung wurden keine Chat-Protokolle gefunden', 'psource-chat' ) . '</p>';
			} else {
				$date_content = "<ul>" . $date_content . "</ul>";
			}

			if ( ( $chat_session['log_display'] == "enabled-list-above" ) || ( $chat_session['log_display'] == "enabled-list-below" ) ) {

				//if (isset($_GET['chat-log-id']))
				//	$return_link = ' <a href="'. remove_query_arg(array('chat-log-id')) .'">'. __('Return to Chat', 'psource-chat') .'</a>';
				//else
				//	$return_link = '';

				//$content .= '<div id="psource-chat-logs-wrap-'. $chat_session['id'].'" class="psource-chat-logs-wrap"><p><strong>' .
				//	$chat_session['log_display_label'] . '</strong>'. $return_link .'</p>' . $date_content . '</div>';
				$content .= '<div id="psource-chat-logs-wrap-' . $chat_session['id'] . '" class="psource-chat-logs-wrap"><p><strong>' .
				            $chat_session['log_display_label'] . '</strong></p>' . $date_content . '</div>';

				//$content .= $this->chat_session_box_styles($chat_session, 'archive');

			} else if ( ( $chat_session['log_display'] == "enabled-link-above" ) || ( $chat_session['log_display'] == "enabled-link-below" ) ) {

				if ( isset( $_GET['chat-show-logs'] ) ) {

					$return_link = '<a href="' . esc_url( remove_query_arg( array(
							'chat-log-id',
							'chat-show-logs'
						) ) ) . '">' . __( 'Zurück zum Chat', 'psource-chat' ) . '</a>';

					$content .= '<div id="psource-chat-logs-wrap-' . $chat_session['id'] . '" class="psource-chat-logs-wrap"><p>' . $return_link . '</p>' . $date_content . '</div>';
				} else {

					$chat_link = ' <a href="' . esc_url( add_query_arg( 'chat-show-logs', '' ) ) . '">' . $chat_session['log_display_label'] . '</a>';

					$content .= '<div id="psource-chat-logs-wrap-' . $chat_session['id'] . '" class="psource-chat-logs-wrap"><p>' . $chat_link . '</p></div>';
				}
			}

			return $content;
		}

		/**
		 * Adds the CSS/Style output specific to the chat_session. Each chat_session can be different. So we need to output the CSS after each chat box with all the
		 * specifics for colors, fonts, widths, etc.
		 *
		 * @global    none
		 *
		 * @param    $chat_session - This is out master settings instance.
		 *
		 * @return    $content - The output of the styles. Will be echoed at some other point.
		 */
		function chat_session_box_styles( $chat_session, $id_override = '' ) {
			$content = '';
			// echo $chat_session;
			if ( empty( $id_override ) ) {
				$CSS_prefix = '#psource-chat-box-' . $chat_session['id'];
				$content .= '<style type="text/css" id="psource-chat-box-' . $chat_session['id'] . '-css">';

			} else {
				$CSS_prefix = '#psource-chat-box-' . $id_override;
				$content .= '<style type="text/css" id="psource-chat-box-' . $id_override . '-css">';
			}

			$content .= $CSS_prefix . ' {
				height: ' . $chat_session['box_height'] . ';
				width: ' . $chat_session['box_width'] . ';
				color: ' . $chat_session['box_text_color'] . ';
				background-color: ' . $chat_session['box_background_color'] . '; ' . $chat_session['box_font_style'] . ';
				border: ' . $chat_session['box_border_width'] . ' solid ' . $chat_session['box_border_color'] . '; } ';

			$content .= $CSS_prefix . ' div.psource-chat-module-header {
				background-color: ' . $chat_session['box_border_color'] . '; } ';
			if ( $chat_session['users_list_position'] != "none" ) {

				$content .= $CSS_prefix . ' div.psource-chat-module-users-list {
				background-color: ' . $chat_session['users_list_background_color'] . ';
				overflow-y: auto; overflow-x: hidden; } ';
				if ( ( $chat_session['users_list_position'] == "left" ) || ( $chat_session['users_list_position'] == "right" ) ) {
					$content .= $CSS_prefix . ' div.psource-chat-module-users-list {
					width: ' . $chat_session['users_list_width'] . ';
					float: ' . $chat_session['users_list_position'] . '; } ';
				} else if ( ( $chat_session['users_list_position'] == "above" ) || ( $chat_session['users_list_position'] == "below" ) ) {
					$content .= $CSS_prefix . ' div.psource-chat-module-users-list {
					height: ' . $chat_session['users_list_height'] . '; } ';
				}

				if ( ! empty( $chat_session['users_list_header'] ) ) {
					$users_list_header_font_style = '';
					if ( ! empty( $chat_session['users_list_header_font_family'] ) ) {
						if ( isset( $this->_chat_options_defaults['fonts_list'][ $chat_session['users_list_header_font_family'] ] ) ) {
							$users_list_header_font_style .= 'font-family: ' .
							                                 $this->_chat_options_defaults['fonts_list'][ $chat_session['users_list_header_font_family'] ] . ';';
						}
					}
					if ( ! empty( $chat_session['users_list_header_font_size'] ) ) {
						$users_list_header_font_style .= 'font-size: ' . psource_chat_check_size_qualifier( $chat_session['users_list_header_font_size'] ) . ';';
					}
					$content .= $CSS_prefix . ' div.psource-chat-module-users-list .psource-chat-users-list-header {
					color: ' . $chat_session['users_list_header_color'] . ';
					' . $users_list_header_font_style . '; } ';

				}
				if ( $chat_session['users_list_show'] == "avatar" ) {

					$content .= $CSS_prefix . ' div.psource-chat-module-users-list ul li.psource-chat-user a {
						width: ' . $chat_session['users_list_avatar_width'] . ';
						height:' . $chat_session['users_list_avatar_width'] . ';
						text-decoration: none;
						display: block; } ';

					if ( ( isset( $chat_session['users_list_avatar_border_width'] ) ) && ( ! empty( $chat_session['users_list_avatar_border_width'] ) ) ) {
						$users_list_avatar_width = psource_chat_check_size_qualifier( $chat_session['users_list_avatar_border_width'] );
					} else {
						$users_list_avatar_width = '0';
					}
					$content .= $CSS_prefix . ' div.psource-chat-module-users-list ul li.psource-chat-user a img {
						width: ' . $chat_session['users_list_avatar_width'] . ';
						height:' . $chat_session['users_list_avatar_width'] . ';
						border-width: ' . $users_list_avatar_width . ';
						border-style: solid;
						} ';

					if ( ( isset( $chat_session['users_list_moderator_avatar_border_color'] ) ) && ( ! empty( $chat_session['users_list_moderator_avatar_border_color'] ) ) ) {
						$users_list_moderator_avatar_border_color = $chat_session['users_list_moderator_avatar_border_color'];

					} else if ( ( isset( $chat_session['users_list_background_color'] ) ) && ( ! empty( $chat_session['users_list_background_color'] ) ) ) {
						$users_list_moderator_avatar_border_color = $chat_session['users_list_background_color'];
					}
					if ( $users_list_moderator_avatar_border_color ) {
						$content .= $CSS_prefix . ' div.psource-chat-module-users-list ul.psource-chat-moderators li.psource-chat-user a img {
						border-color: ' . $users_list_moderator_avatar_border_color . '; }';
					}


					if ( ( isset( $chat_session['users_list_user_avatar_border_color'] ) ) && ( ! empty( $chat_session['users_list_user_avatar_border_color'] ) ) ) {
						$users_list_user_avatar_border_color = $chat_session['users_list_user_avatar_border_color'];

					} else if ( ( isset( $chat_session['users_list_background_color'] ) ) && ( ! empty( $chat_session['users_list_background_color'] ) ) ) {
						$users_list_user_avatar_border_color = $chat_session['users_list_background_color'];
					}
					if ( $users_list_user_avatar_border_color ) {
						$content .= $CSS_prefix . ' div.psource-chat-module-users-list ul.psource-chat-users li.psource-chat-user a img {
						border-color: ' . $users_list_user_avatar_border_color . '; }';
					}


				} else if ( $chat_session['users_list_show'] == "name" ) {
					$content .= $CSS_prefix . ' div.psource-chat-module-users-list ul.psource-chat-moderators li.psource-chat-user a {
					color: ' . $chat_session['users_list_moderator_color'] . ';
					text-decoration: none;
					' . $chat_session['users_list_font_style'] . '; } ';
					$content .= $CSS_prefix . ' div.psource-chat-module-users-list ul.psource-chat-users li.psource-chat-user a {
					color: ' . $chat_session['users_list_name_color'] . ';
					text-decoration: none;
					' . $chat_session['users_list_font_style'] . '; } ';
				}
			}
			$content .= $CSS_prefix . ' div.psource-chat-module-messages-list {
				width: ' . $chat_session['show_messages_width'] . ';
				background-color: ' . $chat_session['row_area_background_color'] . ';
				float: ' . $chat_session['show_messages_position'] . '; } ';

			$content .= $CSS_prefix . ' div.psource-chat-module-messages-list div.psource-chat-row {
				background-color:' . $chat_session['row_background_color'] . ';
				border-top:' . $chat_session['row_border_width'] . ' solid ' . $chat_session['row_border_color'] . ';
				border-bottom:' . $chat_session['row_border_width'] . ' solid ' . $chat_session['row_border_color'] . ';
				border-left: 0; border-right: 0;
				margin-bottom: ' . $chat_session['row_spacing'] . '; } ';

			if ( ( isset( $this->chat_auth['auth_hash'] ) ) && ( ! empty( $this->chat_auth['auth_hash'] ) ) ) {
				$content .= $CSS_prefix . '.psource-chat-box-moderator div.psource-chat-module-messages-list div.psource-chat-row-auth_hash-' . $this->chat_auth['auth_hash'] . ' ul.psource-chat-row-footer  li.psource-chat-user-invite, #psource-chat-box-' . $chat_session['id'] . '.psource-chat-box-moderator div.psource-chat-module-messages-list div.psource-chat-row-auth_hash-' . $this->chat_auth['auth_hash'] . ' ul.psource-chat-row-footer  li.psource-chat-admin-actions-item-block-ip  { display:none; } ';
			}

			$content .= $CSS_prefix . ' div.psource-chat-module-messages-list div.psource-chat-row a.psource-chat-user-avatar {
			display: block; width: ' . $chat_session['row_avatar_width'] . '; height: ' . $chat_session['row_avatar_width'] . '; }';

			$content .= $CSS_prefix . ' div.psource-chat-module-messages-list div.psource-chat-row a.psource-chat-user-avatar img {
			border: 0; width: ' . $chat_session['row_avatar_width'] . '; height: ' . $chat_session['row_avatar_width'] . '; }';

			if ( empty( $chat_session['row_date_color'] ) ) {
				$chat_session['row_date_color'] = $chat_session['row_background_color'];
			}

			if ( $chat_session['row_date'] == "enabled" ) {
				if ( ! empty( $chat_session['row_date_text_color'] ) ) {
					$content .= $CSS_prefix . ' div.psource-chat-module-messages-list div.psource-chat-row span.date {
					color: ' . $chat_session['row_date_text_color'] . '; } ';
				}
				if ( ! empty( $chat_session['row_date_color'] ) ) {
					$content .= $CSS_prefix . ' div.psource-chat-module-messages-list div.psource-chat-row span.date {
					background-color:' . $chat_session['row_date_color'] . '; } ';
				}
			}

			if ( $chat_session['row_time'] == "enabled" ) {
				if ( ! empty( $chat_session['row_date_text_color'] ) ) {
					$content .= $CSS_prefix . ' div.psource-chat-module-messages-list div.psource-chat-row span.time {
					color: ' . $chat_session['row_date_text_color'] . '; } ';
				}
				if ( ! empty( $chat_session['row_date_color'] ) ) {
					$content .= $CSS_prefix . ' div.psource-chat-module-messages-list div.psource-chat-row span.time {
					background-color:' . $chat_session['row_date_color'] . '; } ';
				}
			}

			if ( ( $chat_session['row_name_avatar'] == "name" ) || ( $chat_session['row_name_avatar'] == "name-avatar" ) ) {
				$content .= $CSS_prefix . ' div.psource-chat-module-messages-list div.psource-chat-row a.psource-chat-user-name {
				color: ' . $chat_session['row_name_color'] . '; } ';

				$content .= $CSS_prefix . ' div.psource-chat-module-messages-list div.psource-chat-row-moderator a.psource-chat-user-name {
				color: ' . $chat_session['row_moderator_name_color'] . '; } ';
			}

			$content .= $CSS_prefix . ' div.psource-chat-module-messages-list div.psource-chat-row code {
				background-color:' . $chat_session['row_code_color'] . '; } ';

			$content .= $CSS_prefix . ' div.psource-chat-module-messages-list div.psource-chat-row p.psource-chat-message {
				color:' . $chat_session['row_text_color'] . ';
				' . $chat_session['row_font_style'] . '; } ';

			$content .= $CSS_prefix . ' ul.psource-chat-actions-menu ul.psource-chat-actions-settings-menu {
				background-color: ' . $chat_session['box_border_color'] . ';
				border: 0px;
			}';

			if ( ! empty( $chat_session['box_border_color'] ) ) {
				$content .= $CSS_prefix . ' ul.psource-chat-actions-menu ul.psource-chat-actions-settings-menu li {
				background-color: ' . $chat_session['box_border_color'] . ';
				border-left: 1px solid ' . $chat_session['box_text_color'] . ';
				border-right: 1px solid ' . $chat_session['box_text_color'] . ';
				border-bottom: 1px solid ' . $chat_session['box_text_color'] . ';
			}';
			}

			$content .= $CSS_prefix . ' ul.psource-chat-actions-menu ul.psource-chat-actions-settings-menu a {
				color: ' . $chat_session['box_text_color'] . ';
				background-color: ' . $chat_session['box_border_color'] . '
			}';

			$content .= $CSS_prefix . ' ul.psource-chat-actions-menu ul.psource-chat-actions-settings-menu a:hover {
				color: ' . $chat_session['box_border_color'] . ';
				background-color: ' . $chat_session['box_text_color'] . '
			}';

			$content .= $CSS_prefix . ' div.psource-chat-module-message-area textarea.psource-chat-send {
				height: ' . $chat_session['row_message_input_height'] . ';
				background-color: ' . $chat_session['row_message_input_background_color'] . ';
				color: ' . $chat_session['row_message_input_text_color'] . '; ' . $chat_session['row_message_input_font_style'] . ';
				resize: ' . $chat_session['row_message_input_lock'] . ';
			}';

			$content .= $CSS_prefix . ' div.psource-chat-module-message-area textarea.psource-chat-send::-webkit-input-placeholder {
			color: ' . $chat_session['row_message_input_text_color'] . '; }';

			// set the placeholder text color to match the actual color for text.
			$content .= $CSS_prefix . ' div.psource-chat-module-message-area textarea.psource-chat-send::-webkit-input-placeholder {
			color: ' . $chat_session['row_message_input_text_color'] . '; }';
			$content .= $CSS_prefix . ' div.psource-chat-module-message-area textarea.psource-chat-send:-moz-placeholder {
			color: ' . $chat_session['row_message_input_text_color'] . '; }';
			$content .= $CSS_prefix . ' div.psource-chat-module-message-area textarea.psource-chat-send::-moz-placeholder {
			color: ' . $chat_session['row_message_input_text_color'] . '; }';
			$content .= $CSS_prefix . ' div.psource-chat-module-message-area textarea.psource-chat-send:-ms-input-placeholder {
			color: ' . $chat_session['row_message_input_text_color'] . '; }';


			$content .= $CSS_prefix . ' div.psource-chat-module-message-area ul.psource-chat-send-meta {
				background-color: ' . $chat_session['box_border_color'] . '; }';

			$content .= $CSS_prefix . ' div.psource-chat-module-message-area ul.psource-chat-send-meta li.psource-chat-action-menu-item-file-upload {
				cursor: pointer;
				float: left !important;
				margin-left: 5px !important;
				margin-right: 0 !important;
			}
			' . $CSS_prefix . ' div.psource-chat-module-message-area ul.psource-chat-send-meta li.psource-chat-action-menu-item-file-upload a {
				color: #666 !important;
				text-decoration: none !important;
				display: inline-block !important;
				padding: 4px !important;
				background: transparent !important;
				border: none !important;
				border-radius: 3px !important;
				box-shadow: none !important;
				outline: none !important;
				transition: all 0.2s ease !important;
			}
			' . $CSS_prefix . ' div.psource-chat-module-message-area ul.psource-chat-send-meta li.psource-chat-action-menu-item-file-upload a:hover {
				color: #333 !important;
				background: rgba(0,0,0,0.05) !important;
				text-decoration: none !important;
			}
			' . $CSS_prefix . ' div.psource-chat-module-message-area ul.psource-chat-send-meta li.psource-chat-action-menu-item-file-upload a:focus {
				outline: none !important;
				box-shadow: none !important;
			}
			' . $CSS_prefix . ' div.psource-chat-module-message-area ul.psource-chat-send-meta li.psource-chat-action-menu-item-file-upload svg {
				vertical-align: middle !important;
				width: 16px !important;
				height: 16px !important;
				display: block !important;
			}
			';

			// Add modern emoji picker styles if enabled
			if ( isset( $this->emoji_system ) && $chat_session['box_emoticons'] == "enabled" ) {
				$content .= $this->emoji_system->get_emoji_picker_styles();
			}

			$content .= '</style>';

			return $content;
		}

		/**
		 * Adds the User list module to the chat box. The module is just a div container displayed within the outer chat box div
		 *
		 * @global    none
		 *
		 * @param    $chat_session - This is out master settings instance.
		 *
		 * @return    $content - The output of the styles. Will be echoed at some other point.
		 */
		function chat_session_users_list_module( $chat_session, $echo = false ) {
			$content = '';

			$content_class = 'psource-chat-module-users-list';
			$content_class .= ' psource-chat-users-list-position-' . $chat_session['users_list_position'];
			$content_class .= ' psource-chat-users-list-style-' . $chat_session['users_list_style'];
			$content_class .= ' psource-chat-users-list-show-' . $chat_session['users_list_show'];

			$session_status_style = '';

			if ( strlen( $chat_session['users_list_header'] ) ) {
				$content .= '<p class="psource-chat-users-list-header">' . $chat_session['users_list_header'] . '</p>';
			}
			$content = $this->chat_session_module_wrap( $chat_session, $content, $content_class, $session_status_style );

			return $content;
		}

		/**
		 * Adds the status module to the chat box. The module is just a div container displayed within the outer chat box div
		 *
		 * @global    none
		 *
		 * @param    $chat_session - This is out master settings instance.
		 *
		 * @return    $content - The output of the styles. Will be echoed at some other point.
		 */
		function chat_session_status_module( $chat_session ) {

			$content = '';

			$content .= '<p class="chat-session-status-closed" style="text-align: center; font-weight:bold;">' . $chat_session['session_status_message'] . '</p>';

			$session_status_style = '';
			$content              = $this->chat_session_module_wrap( $chat_session, $content, 'psource-chat-module-session-status', $session_status_style );

			return $content;
		}

		/**
		 *
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */
		function chat_session_generic_message_module( $chat_session ) {
			$content = '';

			$content .= '<p style="text-align: center; font-weight:bold;"></p>';

			$session_status_style = 'display:none;';
			$content              = $this->chat_session_module_wrap( $chat_session, $content, 'psource-chat-module-session-generic-message', $session_status_style );

			return $content;
		}

		/**
		 *
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */
		function chat_session_user_status_message_module( $chat_session ) {
			$content = '';

			$content .= '<p style="text-align: center; font-weight:bold;"></p>';

			$session_status_style = 'display:none;';
			$content              = $this->chat_session_module_wrap( $chat_session, $content, 'psource-chat-session-user-status-message', $session_status_style );

			return $content;
		}


		/**
		 * Adds the bannedstatus module to the chat box. The module is just a div container displayed within the outer chat box div
		 *
		 * @global    none
		 *
		 * @param    $chat_session - This is out master settings instance.
		 *
		 * @return    $content - The output of the styles. Will be echoed at some other point.
		 */
		function chat_session_banned_status_module( $chat_session ) {

			$content = '';

			$content .= '<p class="chat-session-status-closed" style="text-align: center; font-weight:bold;">' .
			            nl2br( $this->get_option( 'blocked_ip_message', 'global' ) ) . '</p>';

			$session_status_style = 'display:none;';

			$content = $this->chat_session_module_wrap( $chat_session, $content, 'psource-chat-module-banned-status', $session_status_style );

			return $content;
		}


	function chat_session_buttonbar_module($chat_session) {
		$content = '';

		if ($chat_session['buttonbar'] == 'enabled') {
			$content .= '<script type="text/javascript">edToolbar("psource-chat-send-'. $chat_session['id']. '");</script>';
			$content = $this->chat_session_module_wrap($chat_session, $content);
		}
		return $content;
	}


		/**
		 * Adds the status module to the chat box. The module is just a div container displayed within the outer chat box div
		 *
		 * @global    none
		 *
		 * @param    $chat_session - This is out master settings instance.
		 *
		 * @return    $content - The output of the styles. Will be echoed at some other point.
		 */
		function chat_session_logout_module( $chat_session ) {
			$content = '';

			$content .= '<input type="button" value="' . __( 'Abmelden', 'psource-chat' ) . '" name="chat-logout-submit"
		 	class="chat-logout-submit" id="chat-logout-submit-' . $chat_session['id'] . '" />';

			$content = $this->chat_session_module_wrap( $chat_session, $content, 'psource-chat-module-logout', 'display:none;' );

			return $content;
		}

		/**
		 * Adds the message area module to the chat box. The module is just a div container displayed within the outer chat box div. The
		 * message area module contains the textarea as well as the footer buttons for emoticons, sound on/off and char count for entry limit.
		 *
		 * @global    none
		 *
		 * @param    $chat_session - This is out master settings instance.
		 *
		 * @return    $content - The output of the styles. Will be echoed at some other point.
		 */
		function chat_session_message_area_module( $chat_session ) {
			$content = '';
			if ( ( $chat_session['session_status'] != "open" ) && ( ! psource_chat_is_moderator( $chat_session ) ) ) {
				$display_style = ' style="display:none;" ';
			} else {
				$display_style = ' style="display:block;" ';
			}

			if ( intval( $chat_session['row_message_input_length'] ) > 0 ) {
				$textarea_max_length = ' maxlength="' . intval( $chat_session['row_message_input_length'] ) . '" ';
			} else {
				$textarea_max_length = '';
			}

			$content .= '<textarea id="psource-chat-send-' . $chat_session['id'] . '" class="psource-chat-send" ' . $textarea_max_length . ' rows="5" placeholder="' . __( 'Tippe Deine Chatnachricht hier', 'psource-chat' ) . '"></textarea>';


			if ( ( $chat_session['box_send_button_enable'] == "enabled" )
			     || ( ( $chat_session['box_send_button_enable'] == "mobile_only" ) && ( $this->chat_localized['settings']['wp_is_mobile'] == true ) )
			) {
				$content .= '<button id="psource-chat-send-button-' . $chat_session['id'] . '" class="psource-chat-send-button">' . $chat_session['box_send_button_label'] . '</button>';
			}

			if ( ( $chat_session['box_emoticons'] == "enabled" ) || ( $chat_session['box_sound'] == "enabled" ) || ( intval( $chat_session['row_message_input_length'] ) > 0 ) || ( $chat_session['file_uploads_enabled'] == "enabled" && $this->get_option( 'file_uploads_enabled', 'global' ) == 'enabled' ) ) {

				$content .= '<ul class="psource-chat-send-meta">';

				if ( intval( $chat_session['row_message_input_length'] ) > 0 ) {
					$content .= '<li class="psource-chat-send-input-length"><span class="psource-chat-character-count">0</span>/' .
					            intval( $chat_session['row_message_input_length'] ) . '</li>';
				}

				if ( $chat_session['box_sound'] == "enabled" ) {
					$content .= '<li class="psource-chat-action-menu-item-sound-on"><a href="#" class="psource-chat-action-sound" title="' .
					            __( 'Schalte Chat-Sound aus', 'psource-chat' ) . '"><img height="16" width="16" src="' . $this->get_plugin_url( '/images/sound-on.png' ) . '" alt="' . __( 'Schalte Chat-Sound aus', 'psource-chat' ) . '" class="psource-chat-sound-on" title="' . __( 'Schalte Chat-Sound aus', 'psource-chat' ) . '" /></a></li>';

					$content .= '<li class="psource-chat-action-menu-item-sound-off"><a href="#" class="psource-chat-action-sound" title="' .
					            __( 'Schalte Chat-Sound ein', 'psource-chat' ) . '"><img height="16" width="16" src="' . $this->get_plugin_url( '/images/sound-off.png' ) . '" alt="' . __( 'Schalte Chat-Sound ein', 'psource-chat' ) . '" class="psource-chat-sound-off" title="' . __( 'Schalte Chat-Sound ein', 'psource-chat' ) . '" /></a></li>';
				}
				// Modern emoji picker using the new modular system
				if ( $chat_session['box_emoticons'] == "enabled" ) {
					// Use the new emoji system
					if ( ! isset( $this->emoji_system ) ) {
						require_once plugin_dir_path( __FILE__ ) . 'class-psource-chat-emoji.php';
						$this->emoji_system = new PSource_Chat_Emoji( $this->get_plugin_url() );
					}
					
					$content .= $this->emoji_system->generate_emoji_picker( $chat_session );
				}

				// File upload button
				if ( $chat_session['file_uploads_enabled'] == "enabled" && $this->get_option( 'file_uploads_enabled', 'global' ) == 'enabled' ) {
					$content .= '<li class="psource-chat-action-menu-item-file-upload">';
					$content .= '<a href="#" class="psource-chat-upload-button" title="' . __( 'Datei hochladen', 'psource-chat' ) . '">';
					$content .= '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">';
					$content .= '<path d="M16.5,6V17.5A4,4 0 0,1 12.5,21.5A4,4 0 0,1 8.5,17.5V5A2.5,2.5 0 0,1 11,2.5A2.5,2.5 0 0,1 13.5,5V15.5A1,1 0 0,1 12.5,16.5A1,1 0 0,1 11.5,15.5V6H10V15.5A2.5,2.5 0 0,0 12.5,18A2.5,2.5 0 0,0 15,15.5V5A4,4 0 0,0 11,1A4,4 0 0,0 7,5V17.5A5.5,5.5 0 0,0 12.5,23A5.5,5.5 0 0,0 18,17.5V6H16.5Z"/>';
					$content .= '</svg>';
					$content .= '</a>';
					$content .= '<input type="file" id="psource-chat-file-input-' . $chat_session['id'] . '" class="psource-chat-file-input" style="display: none;" multiple>';
					$content .= '</li>';
				}

				$content .= '</ul>';
			}

			$container_style = '';
			$content         = $this->chat_session_module_wrap( $chat_session, $content, 'psource-chat-module-message-area', $container_style );

			return $content;
		}

		/**
		 * Adds the login module to the chat box. The module is just a div container displayed within the outer chat box div.
		 * The login module is a container for the public login form
		 *
		 * @global    none
		 *
		 * @param    $chat_session - This is out master settings instance.
		 *
		 * @return    $content - The output of the styles. Will be echoed at some other point.
		 */
		function chat_session_login_module( $chat_session ) {
			$content = '';

			// Nur die öffentliche Authentifizierung verwenden
			if ( $this->use_public_auth( $chat_session ) ) {
				$content .= $this->chat_login_public( $chat_session );
			}

			// Wrapper für das Login-Modul erstellen
			$container_style = 'display:none;';
			$content = $this->chat_session_module_wrap( $chat_session, $content, 'psource-chat-module-login', $container_style );

			return $content;
		}

		/**
		 * Adds the login prompt module to the chat box. The module is just a div container displayed within the outer chat box div
		 *
		 * @global    none
		 *
		 * @param    $chat_session - This is out master settings instance.
		 *
		 * @return    $content - The output of the styles. Will be echoed at some other point.
		 */
		function chat_session_login_prompt_module( $chat_session ) {
			$content = '';

			if ( ! empty( $chat_session['noauth_login_prompt'] ) ) {
				$content = '<p>' . $chat_session['noauth_login_prompt'] . '</p>';

				return $this->chat_session_module_wrap( $chat_session, $content, 'psource-chat-module-login-prompt' );
			}
		}

		/**
		 * Adds the invite prompt module to the chat box. The module is just a div container displayed within the outer chat box div.
		 * The invite prompt module is used when one user initiates a private chat with another user. The invited user will see this
		 * invite prompt asking if they accept the invite.
		 *
		 * @global    none
		 *
		 * @param    $chat_session - This is out master settings instance.
		 *
		 * @return    $content - The output of the styles. Will be echoed at some other point.
		 */
		function chat_session_invite_prompt_module( $chat_session ) {
			$content = '';

			if ( ( $chat_session['session_type'] == "private" ) && ( ! psource_chat_is_moderator( $chat_session ) ) ) {
				if ( isset( $chat_session['invite-info']['message']['host'] ) ) {
					if ( ( isset( $chat_session['invite-info']['message']['host']['avatar'] ) ) && ( ! empty( $chat_session['invite-info']['message']['host']['avatar'] ) ) ) {
						$avatar = '<img alt="' . $chat_session['invite-info']['message']['host']['name'] . '" height="' . intval( $chat_session['row_avatar_width'] ) . '" src="' . $chat_session['invite-info']['message']['host']['avatar'] . '" class="psource-chat-avatar photo" />';
					}

					$content .= '<p class="invite-avatar-wrapper">' . $avatar . '</p>';
					$content .= '<p class="invite-chat-message">' . $chat_session['invite-info']['message']['host']['name'] . ' ' . __( 'möchte Gespräch beginnen', 'psource-chat' ) . '</p>';
					$content .= '<p class="psource-chat-invite-buttons"><button class="psource-chat-invite-accept" type="button" style="background: green; color:white; text-transform: uppercase;">' . __( 'Annehmen', 'psource-chat' ) . '</button><button class="psource-chat-invite-declined" type="button" style="background: red; color:white; text-transform: uppercase;">' . __( 'Ablehnen', 'psource-chat' ) . '</button></p>';
				} else {
					$content .= '<p>' . __( 'Du wurdest zu einem privaten Chat eingeladen', 'psource-chat' ) . '</p>';
				}

				return $this->chat_session_module_wrap( $chat_session, $content, 'psource-chat-module-invite-prompt' );
			}
		}

		/**
		 * This is the main chat box outer container. From the chat_session settings various 'class' values are determined.
		 *
		 * @global    none
		 *
		 * @param    $chat_session - This is out master settings instance.
		 *
		 * @return    $content - The output of the styles. Will be echoed at some other point.
		 */
		function chat_box_container( $chat_session, $content = '' ) {
			$chat_box_style = 'display:none;';
			$chat_box_class = "psource-chat-box";

			$chat_box_class .= " psource-chat-box-" . $chat_session['session_type'];

			if ( psource_chat_is_moderator( $chat_session ) ) {
				$chat_box_class .= " psource-chat-box-moderator";
			}

			if ( ( isset( $chat_session['box_class'] ) ) && ( ! empty( $chat_session['box_class'] ) ) ) {
				$chat_box_class .= ' ' . $chat_session['box_class'];
			}

			if ( $this->get_option( 'blocked_ip_addresses_active', 'global' ) == "enabled" ) {
				//$chat_box_class .= " psource-chat-box-ip-address-". str_replace('.', '-', $chat_session['ip_address']);

				if ( ( ! isset( $this->_chat_options['global']['blocked_ip_addresses'] ) )
				     || ( empty( $this->_chat_options['global']['blocked_ip_addresses'] ) )
				) {
					$this->_chat_options['global']['blocked_ip_addresses'] = array();
				}

				if ( ( array_search( $chat_session['ip_address'], $this->_chat_options['global']['blocked_ip_addresses'] ) !== false )
				     && ( ! psource_chat_is_moderator( $chat_session ) ) && ( $chat_session['session_type'] != "private" )
				) {
					$chat_box_class .= " psource-chat-session-ip-blocked";
				}
			}

			if ( ( $chat_session['session_type'] == 'private' )
			     || ( $chat_session['session_type'] == 'site' )
			     || ( $chat_session['session_type'] == 'network-site' )
			) {

				// For private chat box we also add the 'psource-chat-box-site' for processing and CSS purpose
				$chat_box_class .= " psource-chat-box-site psource-chat-box-can-minmax";

				if ( psource_chat_is_moderator( $chat_session ) ) {
					$chat_box_class .= " psource-chat-box-invite-accepted";
				} elseif ( ! empty( $chat_session['invite-info']['message'] ) ) {
					$chat_box_class .= " psource-chat-box-invite-" . $chat_session['invite-info']['message']['invite-status'];
				}

				if ( $this->chat_user[ $chat_session['id'] ]['status_max_min'] == "max" ) {
					$chat_box_class .= " psource-chat-box-max";
				} else {
					$chat_box_class .= " psource-chat-box-min";
				}
			} else {
				$chat_box_class .= " psource-chat-box-max";
			}


			if ( $chat_session['box_sound'] == "enabled" ) {

				if ( $this->chat_user[ $chat_session['id'] ]['sound_on_off'] == "on" ) {
					$chat_box_class .= " psource-chat-box-sound-on";
				} else {
					$chat_box_class .= " psource-chat-box-sound-off";
				}
			}

			if ( $chat_session['session_status'] == "open" ) {
				$chat_box_class .= " psource-chat-session-open";
			} else {
				$chat_box_class .= " psource-chat-session-closed";
			}

			if ( $chat_session['session_type'] == "log" ) {
				$content = '<div id="psource-chat-box-' . $chat_session['id'] . '" style="' . $chat_box_style . '" class="' . $chat_box_class . '">' . $content . '</div>';
			} else {
				$content = '<div id="psource-chat-box-' . $chat_session['id'] . '" style="' . $chat_box_style . '" class="' . $chat_box_class . '">' . $content . '</div>';
			}

			return $content;
		}

		/**
		 *
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */
		function chat_site_box_container( $content = '' ) {
			global $bp;

			// We don't want to add the container on our pop-out template.
			if ( $this->using_popup_out_template == true ) {
				return;
			}

			$site_chat_box = '';

			if ( ! is_admin() ) {
				if ( $this->get_option( 'bottom_corner', 'site' ) == 'enabled' ) {
					$_SHOW_SITE_CHAT = true;
				} else {
					$_SHOW_SITE_CHAT = false;
				}

				if ( $_SHOW_SITE_CHAT == true ) {

					// Are we viewing a BuddyPress Group pages?
					if ( ( isset( $bp->groups->current_group->id ) ) && ( intval( $bp->groups->current_group->id ) ) ) {

						// Are we viewing the Group Admin screen?
						$bp_group_admin_url_path = parse_url( bp_get_group_admin_permalink( $bp->groups->current_group ), PHP_URL_PATH );
						$request_url_path        = parse_url( get_option( 'siteurl' ) . $_SERVER['REQUEST_URI'], PHP_URL_PATH );

						if ( ( ! empty( $request_url_path ) ) && ( ! empty( $bp_group_admin_url_path ) )
						     && ( substr( $request_url_path, 0, strlen( $bp_group_admin_url_path ) ) == $bp_group_admin_url_path )
						) {
							if ( $this->get_option( 'bp_group_admin_show_site', 'global' ) != "enabled" ) {
								$_SHOW_SITE_CHAT = false;
							}
						} else {
							if ( $this->get_option( 'bp_group_show_site', 'global' ) != "enabled" ) {
								$_SHOW_SITE_CHAT = false;
							}
						}
					} else {
						if ( $this->_chat_plugin_settings['blocked_urls']['site'] == true ) {
							$_SHOW_SITE_CHAT = false;
						} else {
							if ( $this->get_option( 'blocked_on_shortcode', 'site' ) == 'enabled' ) {
								global $post;
								if ( ( ! empty( $post->post_content ) ) && ( strstr( $post->post_content, '[chat ' ) !== false ) ) {
									$_SHOW_SITE_CHAT = false;
								}
							}
						}
					}
				}

			} else {
				if ( $this->get_option( 'bottom_corner_wpadmin', 'site' ) == 'enabled' ) {
					$_SHOW_SITE_CHAT = true;
				} else {
					$_SHOW_SITE_CHAT = false;
				}

				if ( $this->_chat_plugin_settings['blocked_urls']['admin'] == true ) {
					$_SHOW_SITE_CHAT = false;
				}

				if ( $this->_chat_plugin_settings['blocked_urls']['site'] == true ) {
					$_SHOW_SITE_CHAT = false;
				}
			}
			if ( $_SHOW_SITE_CHAT == true ) {
				$atts = array(
					'id'           => 'bottom_corner',
					'session_type' => 'site'
				);

				$atts = wp_parse_args( $atts, $this->_chat_options['site'] );
				$content .= $this->process_chat_shortcode( $atts );
			}

			return $content;
		}

		function chat_network_site_box_container( $content = '' ) {
			global $bp;

			// We don't want to add the container on our pop-out template.
			if ( $this->using_popup_out_template == true ) {
				return;
			}

			$site_chat_box = '';

			if ( ! is_admin() ) {
				if ( $this->get_option( 'bottom_corner', 'network-site' ) == 'enabled' ) {
					$_SHOW_SITE_CHAT = true;
				} else {
					$_SHOW_SITE_CHAT = false;
				}

				if ( $_SHOW_SITE_CHAT == true ) {

					// Are we viewing a BuddyPress Group pages?
					if ( ( isset( $bp->groups->current_group->id ) ) && ( intval( $bp->groups->current_group->id ) ) ) {

						// Are we viewing the Group Admin screen?
						$bp_group_admin_url_path = parse_url( bp_get_group_admin_permalink( $bp->groups->current_group ), PHP_URL_PATH );
						$request_url_path        = parse_url( get_option( 'siteurl' ) . $_SERVER['REQUEST_URI'], PHP_URL_PATH );

						if ( ( ! empty( $request_url_path ) ) && ( ! empty( $bp_group_admin_url_path ) )
						     && ( substr( $request_url_path, 0, strlen( $bp_group_admin_url_path ) ) == $bp_group_admin_url_path )
						) {
							if ( $this->get_option( 'bp_group_admin_show_site', 'global' ) != "enabled" ) {
								$_SHOW_SITE_CHAT = false;
							}
						} else {
							if ( $this->get_option( 'bp_group_show_site', 'global' ) != "enabled" ) {
								$_SHOW_SITE_CHAT = false;
							}
						}
					} else {
						if ( $this->_chat_plugin_settings['blocked_urls']['site'] == true ) {
							$_SHOW_SITE_CHAT = false;
						} else {
							if ( $this->get_option( 'blocked_on_shortcode', 'site' ) == 'enabled' ) {
								global $post;
								if ( ( ! empty( $post->post_content ) ) && ( strstr( $post->post_content, '[chat ' ) !== false ) ) {
									$_SHOW_SITE_CHAT = false;
								}
							}
						}
					}
				}

			} else {
				if ( $this->get_option( 'bottom_corner_wpadmin', 'network-site' ) == 'enabled' ) {
					$_SHOW_SITE_CHAT = true;
				} else {
					$_SHOW_SITE_CHAT = false;
				}

				if ( $this->_chat_plugin_settings['blocked_urls']['admin'] == true ) {
					$_SHOW_SITE_CHAT = false;
				}

				if ( $this->_chat_plugin_settings['blocked_urls']['site'] == true ) {
					$_SHOW_SITE_CHAT = false;
				}
			}

			if ( $_SHOW_SITE_CHAT == true ) {
				$atts = array(
					'id'           => 'network-site',
					'session_type' => 'network-site'
				);

				$atts = wp_parse_args( $atts, $this->_chat_options['network-site'] );
				$content .= $this->process_chat_shortcode( $atts );
			}

			return $content;
		}

		/**
		 * Adds the header module to the chat box. The module is just a div container displayed within the outer chat box div.
		 * The header module contains the top bar for the chat which include the title and gear/settings action menu.
		 *
		 * @global    none
		 *
		 * @param    $chat_session - This is out master settings instance.
		 *
		 * @return    $content - The output of the styles. Will be echoed at some other point.
		 */
		function chat_box_header_container( $chat_session ) {
			$content = '';

			$chat_header_images = '';
			if ( ! isset( $this->chat_user[ $chat_session['id'] ] ) ) {
				return $content;
			}

			if ( $this->chat_user[ $chat_session['id'] ]['status_max_min'] == "max" ) {
				$chat_style_min = "display:block;";
				$chat_style_max = "display:none;";
			} else {
				$chat_style_min = "display:none;";
				$chat_style_max = "display:block;";
			}

			$chat_action_menu = $this->chat_session_settings_action_menu( $chat_session );

			$chat_header_actions = '<div class="psource-chat-module-header-actions"><ul class="psource-chat-actions-menu">';

			if ( $chat_session['session_type'] != "bp-group" ) {
				$chat_header_images .= '<span class="psource-chat-min psource-chat-icon-minimize" style="' . $chat_style_min . '" title="' . __( 'Minimiere Chat', 'psource-chat' ) . '"></span>';
				$chat_header_images .= '<span class="psource-chat-max psource-chat-icon-maximize" style="' . $chat_style_max . '" title="' . __( 'Maximiere Chat', 'psource-chat' ) . '"></span>';
				$chat_header_actions .= '<li class="psource-chat-action-item psource-chat-min-max"><a href="#">' . $chat_header_images . '</a></li>';
			}
			if ( $this->chat_user[ $chat_session['id'] ]['status_max_min'] == "max" ) {
				$chat_style_settings = '';
			} else {
				$chat_style_settings = "display:none;";
			}

			$chat_header_actions .= '<li class="psource-chat-action-item psource-chat-actions-settings" style="' . $chat_style_settings . '"><a href="#" class="psource-chat-actions-settings-button"><span class="psource-chat-icon-settings"></span></a>' . $chat_action_menu . '</li>';

			//$transient_key = "chat-session-". $chat_session['blog_id'] ."-". $chat_session['id'] .'-'. $chat_session['session_type'];
			$transient_key = "chat-session-" . $chat_session['id'] . '-' . $chat_session['session_type'];            if ( $chat_session['box_popout'] == "enabled" ) {
                $chat_header_actions .= '<li class="psource-chat-action-item psource-chat-actions-settings-pop-out"><a title="' . __( 'Eigenes Fenster', 'psource-chat' ) . '" href="' . add_query_arg( array(
                    'psource-chat-action' => 'pop-out',
                    'psource-chat-key'    => base64_encode( $transient_key )
                ), get_option( 'siteurl' ) ) . '" class="psource-chat-action-pop-out"><span class="psource-chat-icon-popout"></span></a></li>';
                $chat_header_actions .= '<li class="psource-chat-action-item psource-chat-actions-settings-pop-in"><a title="' . __( 'Original-Chat', 'psource-chat' ) . '" href="' . add_query_arg( array(
                    'psource-chat-action' => 'pop-in',
                    'psource-chat-id'     => base64_encode( $chat_session['id'] )
                ), get_option( 'siteurl' ) ) . '" class="psource-chat-action-pop-out"><span class="psource-chat-icon-popin"></span></a></li>';
            }

			$chat_header_actions .= '</ul></div>';

			$chat_title = '';
			if ( ( strlen( $chat_session['box_title'] ) ) && ( $chat_session['session_type'] != 'widget' ) ) {

				$chat_title = urldecode( $chat_session['box_title'] );

				if ( $chat_session['session_type'] == "private" ) {
					$chat_title .= ' <span class="psource-chat-private-attendees"></span>';
				}
			}

			if ( $chat_session['session_status'] == "open" ) {
				$chat_title_status = __( 'Offen', 'psource-chat' );
			} else {
				$chat_title_status = __( 'Geschlossen', 'psource-chat' );
			}

			$content .= '<div class="psource-chat-module-header-title">';
			if ( ( $chat_session['session_type'] == 'private' ) || ( $chat_session['session_type'] == 'site' ) || ( $chat_session['session_type'] == 'network-site' ) ) {
				if ( $chat_session['session_type'] == 'private' ) {
					$content .= '<a title="' . __( 'Privatchat', 'psource-chat' ) . '" href="#">';
				} else if ( $chat_session['session_type'] == 'site' ) {
					$content .= '<a title="' . __( 'Gruppenchat', 'psource-chat' ) . '" href="#">';
				} else if ( $chat_session['session_type'] == 'network-site' ) {
					$content .= '<a title="' . __( 'Netzwerkgruppen-Chat', 'psource-chat' ) . '" href="#">';
				}

			}
			$content .= '<span class="psource-chat-title-count"></span>';
			$content .= '<span class="psource-chat-title-text">' . $chat_title . '</span>';

			if ( ( $chat_session['session_type'] == 'private' ) || ( $chat_session['session_type'] == 'site' ) || ( $chat_session['session_type'] == 'network-site' ) ) {
				$content .= '</a>';
			}

			$content .= '</div>';
			$content .= $chat_header_actions;

			return $content;
		}

		/**
		 * Adds the settings action menu. The module is just a div container displayed within the outer chat box div.
		 * This function is called from the header module function and is used to build out the settings gear menu.
		 *
		 * @global    none
		 *
		 * @param    $chat_session - This is out master settings instance.
		 *
		 * @return    $content - The output of the styles. Will be echoed at some other point.
		 */
		function chat_session_settings_action_menu( $chat_session ) {
			$chat_action_menu = '<ul class="psource-chat-actions-settings-menu">';

			// Login/Logout Status ermitteln und nur relevante Option anzeigen
			$is_logged_in = false;
			$show_auth_option = true; // Erstmal immer anzeigen für Debug
			
			if ( ( isset( $this->chat_auth['type'] ) ) && ( ! empty( $this->chat_auth['type'] ) ) ) {
				if ( $this->chat_auth['type'] === 'wordpress' ) {
					// WordPress User ist automatisch eingeloggt - für jetzt noch anzeigen
					$is_logged_in = true;
					$show_auth_option = true; // Ändern wir später
				} else {
					// Anderer Auth-Type - User ist eingeloggt
					$is_logged_in = true;
					$show_auth_option = true;
				}
			} else {
				// Kein Auth-Type - User ist nicht eingeloggt
				$is_logged_in = false;
				$show_auth_option = true;
			}

			// Debug: Immer die entsprechende Option anzeigen
			if ( $is_logged_in ) {
				// User ist eingeloggt - Option zum Abmelden anzeigen
				$chat_action_menu .= '<li class="psource-chat-action-menu-item-auth-toggle logged-in"><a href="#" class="psource-chat-action-logout" title="' . __( 'Vom Chat abmelden', 'psource-chat' ) . '"><span class="psource-chat-icon-logout"></span>' . __( 'Abmelden', 'psource-chat' ) . '</a></li>';
			} else {
				// User ist nicht eingeloggt - Option zum Anmelden anzeigen
				$chat_action_menu .= '<li class="psource-chat-action-menu-item-auth-toggle logged-out"><a href="#" class="psource-chat-action-login" title="' . __( 'Im Chat anmelden', 'psource-chat' ) . '"><span class="psource-chat-icon-login"></span>' . __( 'Anmelden', 'psource-chat' ) . '</a></li>';
			}

			if ( $chat_session['session_type'] == "private" ) {
				$chat_action_menu .= '<li class="psource-chat-action-menu-item-exit"><a href="#" class="psource-chat-action-exit">' .
				__( 'Chat verlassen', 'psource-chat' ) . '</a></li>';
			}

			if ( $chat_session['box_sound'] == "enabled" ) {
				// Aktueller Sound-Status prüfen
				$current_sound_status = isset( $this->chat_user[ $chat_session['id'] ]['sound_on_off'] ) ? $this->chat_user[ $chat_session['id'] ]['sound_on_off'] : 'on';
				
				if ( $current_sound_status == "on" ) {
					// Sound ist AN - Option zum Ausschalten anzeigen
					$chat_action_menu .= '<li class="psource-chat-action-menu-item-sound-toggle sound-active"><a title="' . __( 'Chat-Sound ausschalten', 'psource-chat' ) . '"
					href="#" class="psource-chat-action-sound" data-action="off"><span class="psource-chat-icon-sound-on"></span>' . __( 'Sound ausschalten', 'psource-chat' ) . '</a></li>';
				} else {
					// Sound ist AUS - Option zum Einschalten anzeigen
					$chat_action_menu .= '<li class="psource-chat-action-menu-item-sound-toggle sound-inactive"><a title="' . __( 'Chat-Sound einschalten', 'psource-chat' ) . '"
					href="#" class="psource-chat-action-sound" data-action="on"><span class="psource-chat-icon-sound-off"></span>' . __( 'Sound einschalten', 'psource-chat' ) . '</a></li>';
				}
			}

			if ( psource_chat_is_moderator( $chat_session ) ) {

				// Chat-Status Toggle (nur für Moderatoren und nicht-private Chats)
				if ( $chat_session['session_type'] != "private" ) {
					$current_session_status = isset( $chat_session['session_status'] ) ? $chat_session['session_status'] : 'open';
					
					if ( $current_session_status == "open" ) {
						// Chat ist OFFEN - Option zum Schließen anzeigen
						$chat_action_menu .= '<li class="psource-chat-action-menu-item-session-toggle session-open"><a href="#" class="psource-chat-action-session-closed" title="' . __( 'Chat für andere Benutzer schließen', 'psource-chat' ) . '"><span class="psource-chat-icon-lock-open"></span>' . __( 'Chat schließen', 'psource-chat' ) . '</a></li>';
					} else {
						// Chat ist GESCHLOSSEN - Option zum Öffnen anzeigen
						$chat_action_menu .= '<li class="psource-chat-action-menu-item-session-toggle session-closed"><a href="#" class="psource-chat-action-session-open" title="' . __( 'Chat für andere Benutzer öffnen', 'psource-chat' ) . '"><span class="psource-chat-icon-lock-closed"></span>' . __( 'Chat öffnen', 'psource-chat' ) . '</a></li>';
					}
				}

				$chat_action_menu .= '<li class="psource-chat-action-menu-item-session-clear"><a href="#" title="' . __( 'Alle Chat-Nachrichten unwiderruflich löschen', 'psource-chat' ) . '"
				class="psource-chat-action-session-clear"><span class="psource-chat-icon-delete"></span>' .
				__( 'Chat löschen', 'psource-chat' ) . '</a></li>';

				if ( $chat_session['session_type'] != "private" ) {

					if ( isset( $chat_session['log_creation'] ) && $chat_session['log_creation'] == 'enabled' ) {
						$chat_action_menu .= '<li class="psource-chat-action-menu-item-session-archive"><a href="#" title="' . __( 'Chat-Verlauf als Archiv speichern', 'psource-chat' ) . '" class="psource-chat-action-session-archive"><span class="psource-chat-icon-archive"></span>' .
						__( 'Archiviere Chat', 'psource-chat' ) . '</a></li>';
					}
				}
			}
			//Disable Auto Scroll
			$chat_action_menu .= '<li class="manage-auto-scroll" data-auto_scroll="on"><a href="#" title="' . __( 'Automatisches Scrollen deaktivieren', 'psource-chat' ) . '"><span class="psource-chat-icon-scroll"></span>' . __( 'Autoscroll aus', 'psource-chat' ) . '</a></li>';
			$chat_action_menu .= '</ul>';

			return $chat_action_menu;
		}

		/**
		 * Generic utility function call from all module functions. This function builds the actual HTML output for the module.
		 *
		 * @global    none
		 *
		 * @param    $chat_session - This is out master settings instance.
		 *
		 * @return    $content - The output of the styles. Will be echoed at some other point.
		 */
		function chat_session_module_wrap( $chat_session, $content = '', $extra_class = '', $extra_style = '' ) {

			$wrapper_class = "psource-chat-module";
			if ( ! empty( $extra_class ) ) {
				$wrapper_class .= " " . $extra_class;
			}
			if ( ! empty( $wrapper_class ) ) {
				$wrapper_class = ' class="' . $wrapper_class . '"';
			}
			$wrapper_style = '';
			if ( ! empty( $extra_style ) ) {
				$wrapper_style .= " " . $extra_style;
			}
			if ( ! empty( $wrapper_style ) ) {
				$wrapper_style = ' style="' . $wrapper_style . '"';
			}

			$content = '<div' . $wrapper_class . ' ' . $wrapper_style . '>' . $content . '</div>';

			return $content;
		}

		/**
		 * Given a chat message from the database. This function builds the row output.
		 *
		 * @global    none
		 *
		 * @param    $row - The DB object containing all the message information
		 *            $chat_session - This is out master settings instance.
		 *
		 * @return    $content - The output of the styles. Will be echoed at some other point.
		 */
		function chat_session_build_row( $row, $chat_session ) {

			$message = stripslashes( $row->message );

			$row_class = "psource-chat-row";
			if ( $row->moderator == 'yes' ) {
				$row_class .= " psource-chat-row-moderator";
			} else {
				$row_class .= " psource-chat-row-user";

				if ( is_email( $row->avatar ) ) {
					$row_class .= " psource-chat-row-user-" . str_replace( array(
							'@',
							'.'
						), '-', strtolower( $row->avatar ) );
				}
			}

			if ( ( isset( $row->auth_hash ) ) && ( ! empty( $row->auth_hash ) ) ) {
				$row_class .= " psource-chat-row-auth_hash-" . $row->auth_hash;

			}

			$row_class .= " psource-chat-row-ip-" . str_replace( '.', '-', $row->ip_address );
			//$row_class .= " psource-chat-row-ip-". base64_encode($row->ip_address);

			if ( $this->get_option( 'blocked_ip_addresses_active', 'global' ) == "enabled" ) {
				if ( array_search( $row->ip_address, $this->get_option( 'blocked_ip_addresses', 'global' ) ) !== false ) {
					$row_class .= " psource-chat-row-ip-blocked";
				}
			}

			$row_text = '';
			$row_text .= '<div id="psource-chat-row-' . strtotime( $row->timestamp ) . '-' . $row->id . '" class="' . $row_class . '">';

			$row_avatar_name = '';
			if ( empty( $row->avatar ) ) {
				$row->avatar = "http://0.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536?s=96";
			}
			$row->avatar = esc_url( $row->avatar );
			if ( $chat_session['row_name_avatar'] == 'avatar' ) {
				if ( ( isset( $row->avatar ) ) && ( ! empty( $row->avatar ) ) ) {
					$avatar = '<img alt="' . $row->name . '" src="' . $row->avatar . '" class="psource-chat-user psource-chat-user-avatar" height="' .
					          intval( $chat_session['row_avatar_width'] ) . '" />';
					$row_avatar_name .= '<a class="psource-chat-user psource-chat-user-avatar" title="@' . $row->name . '" href="#">' . $avatar . '</a>';
				}

			} else if ( $chat_session['row_name_avatar'] == "name" ) {

				$row_avatar_name .= '<a class="psource-chat-user psource-chat-user-name" title="@' . $row->name . '" href="#">' . $row->name . '</a>';
			} else if ( $chat_session['row_name_avatar'] == "name-avatar" ) {
				if ( ( isset( $row->avatar ) ) && ( ! empty( $row->avatar ) ) ) {
					$avatar = '<img alt="' . $row->name . '" src="' . $row->avatar . '" class="psource-chat-user psource-chat-user-avatar" height="' .
					          intval( $chat_session['row_avatar_width'] ) . '" />';
					$row_avatar_name .= '<a class="psource-chat-user psource-chat-user-avatar" title="@' . $row->name . '" href="#">' . $avatar . '</a>';
				}
				$row_avatar_name .= '<a class="psource-chat-user psource-chat-user-name" title="@' . $row->name . '" href="#">' . $row->name . '</a>';
			}


			$row_date_time = '';
			if ( $chat_session['row_date'] == 'enabled' ) {
				if ( isset( $chat_session['row_date_format'] ) ) {
					$row_date_format = $chat_session['row_date_format'];
				} else {
					$row_date_format = get_option( 'date_format' );
				}

				//Convert to timezone
				$date = get_date_from_gmt( $row->timestamp, $row_date_format );

				//Translate
				$date = date_i18n( $row_date_format, strtotime( $date ) );
				$row_date_time .= '<span class="date new">' . $date . '</span>';
			}

			if ( $chat_session['row_time'] == 'enabled' ) {
				if ( ! empty( $row_date_time ) ) {
					$row_date_time .= " ";
				}
				if ( isset( $chat_session['row_time_format'] ) ) {
					$row_time_format = $chat_session['row_time_format'];
				} else {
					$row_time_format = get_option( 'time_format' );
				}

				$row_date_time .= '<span class="time">' . get_date_from_gmt( $row->timestamp, $row_time_format ) . '</span>';
			}
			if ( ! empty( $row_date_time ) ) {
				$row_date_time = "<br />" . $row_date_time;
			}

			// Apply media content filter for display
			$message = apply_filters( 'psource_chat_display_message', $message, $row );
			
			$row_text .= '<p class="psource-chat-message">' . $row_avatar_name . ' ' . convert_smilies( $message ) . $row_date_time . '</p>';


//			if (($chat_session['row_date'] == 'enabled')
//			 || ($chat_session['row_time'] == 'enabled')
//			 || (psource_chat_is_moderator($chat_session))) {

			if ( $chat_session['box_moderator_footer'] == "enabled" ) {

				$row_text .= '<ul class="psource-chat-row-footer">';

				$this->chat_localized['settings']["row_delete_text"]   = __( 'ausblenden', 'psource-chat' );
				$this->chat_localized['settings']["row_undelete_text"] = __( 'einblenden', 'psource-chat' );

				//if (($chat_session['session_type'] != "log") && ($row->moderator != "yes")) {
				if ( $chat_session['session_type'] != "log" ) {

					$row_text .= '<li class="psource-chat-admin-actions-item psource-chat-user-invite"><a class="psource-chat-user-invite" rel="' . $row->auth_hash . '" title="' . __( 'Zu privaten Chat einladen:', 'psource-chat' ) . ' ' . $row->name . '" href="#"><span class="action"><img height="10" src="' . $this->get_plugin_url( '/images/padlock-icon-th.png' ) . '" alt=""/></span></a></li>';

					$row_text .= '<li class="psource-chat-admin-actions-item psource-chat-admin-actions-item-delete"><a class="psource-chat-admin-actions-item-delete" title="' . __( 'moderiere diese Nachricht', 'psource-chat' ) . '" href="#"><span  class="action">' . $this->chat_localized['settings']["row_delete_text"] . '</span></a></li>';

					if ( ( $this->get_option( 'blocked_ip_addresses_active', 'global' ) == "enabled" )
					     && ( $chat_session['blocked_ip_addresses_active'] == "enabled" )
					) {

						$row_text .= '<li class="psource-chat-admin-actions-item psource-chat-admin-actions-item-block-ip"><a
							 class="psource-chat-admin-actions-item-block-ip" title="' . __( 'moderiere IP-Adresse:', 'psource-chat' ) .
						             $row->ip_address . '" rel="' . $row->ip_address . '" href="#"><span class="action">' . $row->ip_address . '</span></a></li>';
					}
					/*
						$row_text .= '<li class="psource-chat-admin-actions-item psource-chat-admin-actions-item-block-user"><a
						 	class="psource-chat-admin-actions-item-block-user"
							title="'. __('moderate user:', 'psource-chat') . $row->name .'" rel="'. $row->auth_hash .'"
						 	href="#"><span class="action">'. $row->name .'</span></a></li>';
*/
					//$row_text .= '</ul>'; // End of ul.psource-chat-admin-actions
				}

				$row_text .= '</ul>'; // End of row footer span
			}
			$row_text .= '</div>'; // End of Row
			return $row_text;
		}

		/**
		 *
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */
		function chat_session_messages_list_module( $chat_session ) {
			$content = '';

			$content = '<div class="psource-chat-module psource-chat-module-messages-list" >' . $content . '</div>';

			return $content;
		}

		/**
		 *
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */
		function use_public_auth( $chat_session ) {
			return in_array( 'public_user', $chat_session['login_options'] );
		}

		/**
		 *
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */
		function chat_login_public( $chat_session ) {
			$content = '';

			if ( $this->use_public_auth( $chat_session ) ) {

				$content .= '<div class="login-message">' . $this->get_option( 'noauth_login_message', $chat_session['session_type'] ) . '</div>';

				$content .= '<div id="chat-login-wrap-' . $chat_session['id'] . '" class="chat-login-wrap">';
				$content .= '<p class="psource-chat-login-error" style="color: #FF0000; display:none;"></p>';
				$content .= '<label class="psource-chat-login-label" for="chat-login-name-' . $chat_session['id'] . '">' . __( 'Name', 'psource-chat' ) . '<br /></label><input id="chat-login-name-' . $chat_session['id'] . '" style="width: 90%" name="psource-chat-login-name" class="psource-chat-login-name" type="text" placeholder="' . __( 'Name eingeben', 'psource-chat' ) . '"/><br />';
				$content .= '<label class="psource-chat-login-label" for="chat-login-email-' . $chat_session['id'] . '">' . __( 'Email', 'psource-chat' ) . '<br /></label><input id="chat-login-email-' . $chat_session['id'] . '" style="width: 90%" name="psource-chat-login-email" class="psource-chat-login-email" type="text" placeholder="' . __( 'Email eingeben', 'psource-chat' ) . '"/><br />';

				$content .= '<p class="psource-chat-login-buttons"><button class="psource-chat-login-submit" type="button">' . __( 'Login', 'psource-chat' ) . '</button><button class="psource-chat-login-cancel" type="button">' . __( 'Abbrechen', 'psource-chat' ) . '</button></p>';

				$content .= '</div>';
			}

			return $content;
		}

		/**
		 * @see        http://codex.wordpress.org/TinyMCE_Custom_Buttons
		 */
		function tinymce_register_button( $buttons ) {
			array_push( $buttons, "separator", "chat" );

			return $buttons;
		}

		/**
		 * @see        http://codex.wordpress.org/TinyMCE_Custom_Buttons
		 */
		function tinymce_load_langs( $langs ) {
			$langs["chat"] = $this->get_plugin_url( '/tinymce/langs/langs.php' );

			return $langs;
		}

		/**
		 * @see        http://codex.wordpress.org/TinyMCE_Custom_Buttons
		 */
		function tinymce_add_plugin( $plugin_array ) {
			$plugin_array['chat'] = $this->get_plugin_url( '/tinymce/editor_plugin.js' );

			return $plugin_array;
		}

		/**
		 * Helper method to get plugin URL with correct path
		 */
		private function get_plugin_url($path = '') {
			return plugins_url($path, dirname(__FILE__));
		}

		function chat_init() {
			$reply_data             = array();
			$reply_data['sessions'] = array();

			foreach ( $this->chat_sessions as $chat_id => $chat_session ) {
				$reply_data['sessions'][ $chat_id ]         = array();
				$reply_data['sessions'][ $chat_id ]['html'] = $this->chat_session_build_box( $chat_session );

				// We load the box CSS via the AJAX call. This helps when bots are hitting the page.
				$reply_data['sessions'][ $chat_id ]['css'] = $this->chat_session_box_styles( $chat_session );
			}

			// We only show performance output to admin users.
			if ( ( $this->get_option( 'session_performance', 'global' ) == 'enabled' ) && ( current_user_can( 'manage_options' ) ) ) {
				$this->chat_performance    = psource_chat_end_performance( $this->chat_performance );
				$reply_data['performance'] = $this->chat_performance;
			}

			wp_send_json( $reply_data );
			die();
		}

		function chat_message_send() {
			$reply_data                = array();
			$reply_data['errorStatus'] = false;
			$reply_data['errorText']   = '';

			if ( ( ! isset( $_POST['chat_messages'] ) ) || ( ! count( $_POST['chat_messages'] ) ) ) {
				$reply_data['errorText']   = "chat_messages missing";
				$reply_data['errorStatus'] = true;
				wp_send_json( $reply_data );
				die();
			}

			// Double check the user's authentication. Seems some users can login with multiple tabs. If they log out of one tab they
			// should not be able to post via the other tab.
			if ( ! isset( $this->chat_auth['type'] ) ) {
				$reply_data['errorText']   = "Unknown user type";
				$reply_data['errorStatus'] = true;
				wp_send_json( $reply_data );
				die();
			}

			foreach ( $_POST['chat_messages'] as $chat_id => $chat_messages ) {

				if ( ! isset( $this->chat_sessions[ $chat_id ] ) ) {
					continue;
				}
				if ( ( ! is_array( $chat_messages ) ) || ( ! count( $chat_messages ) ) ) {
					continue;
				}

				$chat_session = $this->chat_sessions[ $chat_id ];

				if ( ! isset( $reply_data['chat_messages'][ $chat_id ] ) ) {
					$reply_data['chat_messages'][ $chat_id ] = array();
				}

				foreach ( $chat_messages as $chat_message_idx => $chat_message ) {
					$reply_data['chat_messages'][ $chat_id ][ $chat_message_idx ] = false;

					//$chat_message = urldecode($chat_message);
					$chat_message = stripslashes( $chat_message );

					// Replace the chr(10) Line feed (not the chr(13) carraige return) with a placeholder. Will be replaced with
					// real <br /> after filtering This is done so when we convert text within [code][/code] the <br /> are not
					// converted to entities. Because we want the code to be formatted
					$chat_message = str_replace( chr( 10 ), "[[CR]]", $chat_message );

					// In case the user entered HTML <code></code> instead of [code][/code]
					$chat_message = str_replace( "<code>", "[code]", $chat_message );
					$chat_message = str_replace( "</code>", "[/code]", $chat_message );

					// We also can accept backtick quoted text and convert to [code][/code]
					$chat_message = preg_replace( '/`(.*?)`/', '[code]$1[/code]', $chat_message );

					// Now split out the [code][/code] sections.
					//preg_match_all("|\[code\](.*)\[/code\]|s", $chat_message, $code_out);
					preg_match_all( "~\[code\](.+?)\[/code\]~si", $chat_message, $code_out );
					if ( ( $code_out ) && ( is_array( $code_out ) ) && ( is_array( $code_out[0] ) ) && ( count( $code_out[0] ) ) ) {
						foreach ( $code_out[0] as $code_idx => $code_str_original ) {
							if ( ! isset( $code_out[1][ $code_idx ] ) ) {
								continue;
							}

							// Here we replace our [code][/code] block or text in the message with placeholder [code-XXX] where XXX
							// is the index (0,1,2,3, etc.) Again we do this because in the next step we will strip out all HTML not
							// allowed. We want to protect any HTML within the code block
							// which will be converted to HTML entities after the filtering.
							$chat_message = str_replace( $code_str_original, '[code-' . $code_idx . ']', $chat_message );
						}
					}

					// First strip all the tags!
					$allowed_protocols = array();
					$allowed_html      = array();
					$chat_message      = wp_kses( $chat_message, $allowed_html, $allowed_protocols );

					// Not needed since we remove all HTML from the message. For now.
					//$chat_message = balanceTags($chat_message);

					// If the user enters something that liiks like a link (http://, ftp://, etc) it will be made clickable
					// in that is will be wrapped in an anchor, etc. The the link tarket will be set so clicking it will open
					// in a new window
					$chat_message = links_add_target( make_clickable( $chat_message ) );

					// Now that we can filtered the text outside the [code][/code] we want to convert the code section HTML to entities since it
					// will be viewed that way by other users.
					if ( ( $code_out ) && ( is_array( $code_out ) ) && ( is_array( $code_out[0] ) ) && ( count( $code_out[0] ) ) ) {
						foreach ( $code_out[0] as $code_idx => $code_str_original ) {
							if ( ! isset( $code_out[1][ $code_idx ] ) ) {
								continue;
							}

							$code_str_replace = "<code>" . htmlentities2( $code_out[1][ $code_idx ], ENT_QUOTES | ENT_XHTML ) . "</code>";
							$chat_message     = str_replace( '[code-' . $code_idx . ']', $code_str_replace, $chat_message );
						}
					}

					// Finally convert any of our CR placeholders to HTML breaks.
					$chat_message = str_replace( "[[CR]]", '<br />', $chat_message );

					// Just as a precaution. After processing we may end up with double breaks. So we convert to single.
					$chat_message = str_replace( "<br /><br />", '<br />', $chat_message );


					// End message filtering

					if ( $chat_message == '' ) {
						continue;
					}

					// Truncate the message IF the max length is set
					if ( ! psource_chat_is_moderator( $chat_session ) ) {
						if ( ( $chat_session['row_message_input_length'] > 0 ) && ( strlen( $chat_message ) > $chat_session['row_message_input_length'] ) ) {
							$chat_message = substr( $chat_message, 0, $chat_session['row_message_input_length'] );
						}
					}

					// Process bad words, if enabled
					if ( $chat_session['blocked_words_active'] == "enabled" ) {
						$chat_message = str_ireplace( $this->_chat_options['banned']['blocked_words'],
							$this->_chat_options['banned']['blocked_words_replace'], $chat_message );
					}

					// Process media content (links, images, videos)
					$chat_message = apply_filters( 'psource_chat_before_save_message', $chat_message, $chat_session );

					$ret = $this->chat_session_send_message( $chat_message, $chat_session );
					if ( ! empty( $ret ) ) {
						$reply_data['chat_messages'][ $chat_id ][ $chat_message_idx ] = true;
					}

					if ( ( $chat_session['session_type'] == 'private' ) && ( $this->_chat_options['site']['private_reopen_after_exit'] == 'enabled' ) ) {
						$this->psource_chat_set_private_archive_status( $chat_session );
					}

				}
			}
			wp_send_json( $reply_data );
			die();
		}

		function chat_user_login() {
			$reply_data                = array();
			$reply_data['errorStatus'] = false;
			$reply_data['errorText']   = '';

			if ( ! isset( $_POST['user_info'] ) ) {

				$reply_data['errorStatus'] = true;
				$reply_data['errorText']   = __( 'missing POST user_info', 'psource-chat' );

				wp_send_json( $reply_data );
				die();
			}
			$user_info = $_POST['user_info'];

			switch ( $user_info['type'] ) {
				case 'public_user':
					if ( ( ! isset( $user_info['name'] ) ) || ( ! isset( $user_info['email'] ) ) ) {

						$reply_data['errorText']   = __( 'Bitte gültigen Namen und eine gültige E-Mail-Adresse angeben.', 'psource-chat' );
						$reply_data['errorStatus'] = true;

						wp_send_json( $reply_data );
						die();
					}
					$user_info['name']  = esc_attr( $user_info['name'] );
					$user_info['email'] = esc_attr( $user_info['email'] );
					if ( ( empty( $user_info['name'] ) ) || ( empty( $user_info['email'] ) ) || ( ! is_email( $user_info['email'] ) ) ) {

						$reply_data['errorText']   = __( 'Bitte gültigen Namen und eine gültige E-Mail-Adresse angeben.', 'psource-chat' );
						$reply_data['errorStatus'] = true;

						wp_send_json( $reply_data );
						die();
					}

					$user_name_id = username_exists( $user_info['name'] );
					if ( $user_name_id ) {

						$reply_data['errorText']   = __( 'Name bereits vergeben', 'psource-chat' );
						$reply_data['errorStatus'] = true;

						wp_send_json( $reply_data );
						die();
					}
					$user_name_id = email_exists( $user_info['email'] );
					if ( $user_name_id ) {

						$reply_data['errorText']   = __( 'Email bereits registriert.', 'psource-chat' );
						$reply_data['errorStatus'] = true;

						wp_send_json( $reply_data );
						die();
					}
					// Use the modern avatar system for user info
					$user_info['avatar'] = PSource_Chat_Avatar::get_chat_avatar( 
						$user_info['id'], 
						$user_info['email'], 
						$user_info['name'] 
					);

					$user_info['ip_address'] = ( isset( $_SERVER['HTTP_X_FORWARD_FOR'] ) ) ? $_SERVER['HTTP_X_FORWARD_FOR'] : $_SERVER['REMOTE_ADDR'];
					$user_info['auth_hash']  = md5( $user_info['name'] . $user_info['email'] . $user_info['ip_address'] );
					$reply_data['user_info'] = $user_info;
					break;

					default:
				break;
			}
			wp_send_json( $reply_data );
			die();
		}

		/**
		 * Check db for new messages and update all the chat sessions
		 */
		function chat_message_update() {
			$reply_data = array();

			$reply_data['errorStatus'] = false;
			$reply_data['errorText']   = '';

			$reply_data['sessions'] = array();
			$reply_data['invites']  = array();

			if ( isset( $_POST['timers'] ) ) {
				$timers = $_POST['timers'];
			} else {
				$timers = array();
			}
			// We first want to grab the invites for the users. This will setup the extra items in the $this->chat_sessions reference. Then later
			// in this section we will also add the rows and meta updates for the new invite box.
			if ( ( $this->using_popup_out_template == false )
			     && ( isset( $timers['invites'] ) )
			     && ( $timers['invites'] == 1 )
				/* && ($this->user_meta['chat_user_status'] == 'available') */
			) {

				$invites = $this->chat_session_get_invites_new();
				if ( ( is_array( $invites ) ) && ( ! empty( $invites ) ) ) {
					$reply_data['invites'] = $invites;
				}
			}

			$this->chat_auth['ip_address'] = ( isset( $_SERVER['HTTP_X_FORWARD_FOR'] ) ) ? $_SERVER['HTTP_X_FORWARD_FOR'] : $_SERVER['REMOTE_ADDR'];

			foreach ( $this->chat_sessions as $chat_id => $chat_session ) {

				// Now process the meta information. Session Status, Deleted Row IDs and Session Users
				$reply_data['sessions'][ $chat_id ]['meta'] = array();

				if ( ! isset( $this->chat_sessions_meta[ $chat_id ] ) ) {
					$this->chat_sessions_meta[ $chat_id ] = $this->chat_session_get_meta( $chat_session );
				}

				if ( ( ( isset( $timers['messages'] ) ) && ( $timers['messages'] == 1 ) ) || ( $chat_session['session_type'] == 'private' ) ) {

					if ( ! isset( $chat_session['last_row_id'] ) ) {
						$chat_session['last_row_id'] = "__EMPTY__";
					}
					$users_active = $this->chat_session_get_active_users( $chat_session );

					$reply_data['sessions'][ $chat_id ]['rows'] = array();

					//For private chats, check user invitation status before getting new messages
					if ( $chat_session['session_type'] == 'private' ) {
						//if not moderator, check for invite status, other wise set it as accepted
						if ( $chat_session['invite-info']['message']['host']['auth_hash'] !== $this->chat_auth['auth_hash'] ) {
							$invitation_status = $chat_session['invite-info']['message']['invite-status'];
						} else {
							//Check if other user has exited the chat
							if ( ! empty( $users_active['users'] ) ) {

								foreach ( $users_active['users'] as $user ) {
									if ( $user['connect_status'] == 'exited' ) {
										$reply_data['sessions'][ $chat_id ]['errorStatus'] = true;
										$reply_data['sessions'][ $chat_id ]['errorText']   = __( 'Der Benutzer hat die Einladung zum privaten Chat abgelehnt.', 'psource-chat' );
										$reply_data['sessions'][ $chat_id ]['errorText']   = apply_filters( 'wc_chat_decline_message', $reply_data['sessions'][ $chat_id ]['errorText'] );
									}
									break;
								}
							}
							$invitation_status = 'accepted';
						}
						//makes sure, the invitee has accepted invitation before showing it
						if ( $invitation_status == 'accepted' ) {

							$new_rows = $this->chat_session_get_message_new( $chat_session );
						} else {
							$new_rows = '';
						}
						if ( $invitation_status == 'exited' ) {
							unset( $reply_data['sessions'][ $chat_id ] );
							continue;
						}
					} else {
						$new_rows = $this->chat_session_get_message_new( $chat_session );
					}

					if ( ( $new_rows ) && ( count( $new_rows ) ) ) {

						// Init the reply last_row_id with what was sent.
						$reply_data['sessions'][ $chat_id ]['last_row_id'] = $chat_session['last_row_id'];

						$_LAST_ROW_UPDATED = false;
						foreach ( $new_rows as $row_idx => $row ) {
							if ( ( intval( $chat_session['last_row_id'] ) > 0 ) && ( $row->id == $chat_session['last_row_id'] ) ) {
								continue;
							}

							$reply_data['sessions'][ $chat_id ]['rows'][ strtotime( $row->timestamp ) . "-" . $row->id ] =
								$this->chat_session_build_row( $row, $chat_session );

							// Then update the last_row_id based on the higher row->id values returned
							if ( $_LAST_ROW_UPDATED == false ) {
								$reply_data['sessions'][ $chat_id ]['last_row_id'] = $row->id;
								$_LAST_ROW_UPDATED                                 = true;
							}
						}

						if ( count( $reply_data['sessions'][ $chat_id ]['rows'] ) ) {
							ksort( $reply_data['sessions'][ $chat_id ]['rows'] );
						}

					} else {
						$reply_data['sessions'][ $chat_id ]['rows'] = "__EMPTY__";
					}
					$reply_data['sessions'][ $chat_id ]['meta'] = $this->chat_session_update_meta_log( $chat_session );
				}

				if ( ( ( isset( $timers['meta'] ) ) && ( $timers['meta'] == 1 ) ) || ( $chat_session['session_type'] == 'private' ) ) {

					$reply_data['sessions'][ $chat_id ]['global']               = $this->chat_session_update_global_log( $chat_session );
					$reply_data['sessions'][ $chat_id ]['meta']['users-active'] = $users_active;
					$this->chat_session_users_update_polltime( $chat_session );
				}

			}

			// We only show performance output to admin users.
			if ( ( $this->get_option( 'session_performance', 'global' ) == 'enabled' ) && ( current_user_can( 'manage_options' ) ) ) {
				$this->chat_performance    = psource_chat_end_performance( $this->chat_performance );
				$reply_data['performance'] = $this->chat_performance;
			}

			wp_send_json( $reply_data );
			die();
		}

		function chat_meta_leave_private_session() {
			$reply_data                = array();
			$reply_data['errorStatus'] = false;
			$reply_data['errorText']   = '';
			$reply_data['sessions']    = array();

			// Get Private chats
			if ( ( ! isset( $this->chat_auth['auth_hash'] ) ) || ( empty( $this->chat_auth['auth_hash'] ) ) ) {
				$reply_data['errorStatus'] = true;
				$reply_data['errorText']   = __( 'Invalid auth_hash', 'psource-chat' );

				wp_send_json( $reply_data );
				die();
			}

			foreach ( $this->chat_sessions as $chat_id => $chat_session ) {

				$reply_data['sessions'][ $chat_id ] = $chat_id;
				$this->psource_chat_update_user_invite_status( 'exited', $chat_id, $this->chat_auth['auth_hash'], 'yes' );
			}
			wp_send_json( $reply_data );
			die();
		}

		function chat_messages_clear() {
			global $wpdb;

			$reply_data                = array();
			$reply_data['errorStatus'] = false;
			$reply_data['errorText']   = '';

			// If the user doesn't have a type
			if ( ! isset( $this->chat_auth['type'] ) ) {
				$reply_data['errorStatus'] = true;
				$reply_data['errorText']   = __( 'Invalid chat_auth [type]', 'psource-chat' );

				wp_send_json( $reply_data );
				die();
			} else if ( $this->chat_auth['type'] != "wordpress" ) {
				$reply_data['errorStatus'] = true;
				$reply_data['errorText']   = __( 'Invalid chat_auth [type]', 'psource-chat' );

				wp_send_json( $reply_data );
				die();
			}

			if ( ! is_user_logged_in() ) {
				$reply_data['errorStatus'] = true;
				$reply_data['errorText']   = __( 'Invalid chat_auth [type]', 'psource-chat' );

				wp_send_json( $reply_data );
				die();
			}

			foreach ( $this->chat_sessions as $chat_id => $chat_session ) {

				if ( psource_chat_is_moderator( $chat_session ) ) {

					$sql_str = $wpdb->prepare( "DELETE FROM `" . PSOURCE_Chat::tablename( 'message' ) . "` WHERE blog_id = %d AND chat_id = %s AND archived IN ('no') AND session_type = %s;", $chat_session['blog_id'], $chat_session['id'], $chat_session['session_type'] );
					$wpdb->query( $sql_str );
					//log_chat_message(__FUNCTION__ .": [". $sql_str ."]");

					// Upload-Dateien für diese Session auch löschen (Datenschutz)
					if ( class_exists( 'PSource_Chat_Upload' ) ) {
						PSource_Chat_Upload::cleanup_session_files( $chat_session['id'] );
					}

					$this->chat_session_set_meta( $chat_session, 'last_row_id', '__EMPTY__' );
					$this->chat_session_set_meta( $chat_session, 'log_row_id', '__EMPTY__' );
					$this->chat_session_update_message_rows_deleted( $chat_session );
				} else {
					$reply_data['errorStatus'] = true;
					$reply_data['errorText']   = __( 'Not moderator', 'psource-chat' );
					die();
				}
			}
			wp_send_json( $reply_data );
			die();
		}

		function chat_messages_archive() {
			$reply_data                = array();
			$reply_data['errorStatus'] = false;
			$reply_data['errorText']   = '';

			// If the user doesn't have a type
			if ( ! isset( $this->chat_auth['type'] ) ) {
				$reply_data['errorStatus'] = true;
				$reply_data['errorText']   = __( 'Invalid chat_auth [type]', 'psource-chat' );

				wp_send_json( $reply_data );
				die();

			} else if ( $this->chat_auth['type'] != "wordpress" ) {
				$reply_data['errorStatus'] = true;
				$reply_data['errorText']   = __( 'Invalid chat_auth [type]', 'psource-chat' );

				wp_send_json( $reply_data );
				die();
			}

			if ( ! is_user_logged_in() ) {
				$reply_data['errorStatus'] = true;
				$reply_data['errorText']   = __( 'Invalid chat_auth [type]', 'psource-chat' );

				wp_send_json( $reply_data );
				die();
			}

			foreach ( $this->chat_sessions as $chat_id => $chat_session ) {

				if ( psource_chat_is_moderator( $chat_session ) ) {

					$this->chat_session_archive_messages( $chat_session );
				}
			}
			wp_send_json( $reply_data );
			die();
		}

		function chat_session_moderate_status() {
			$reply_data                = array();
			$reply_data['errorStatus'] = false;
			$reply_data['errorText']   = '';

			$chat_id = 0;

			if ( ! isset( $_POST['chat_session'] ) ) {

				$reply_data['errorStatus'] = true;
				$reply_data['errorText']   = __( 'Invalid chat_session', 'psource-chat' );

				wp_send_json( $reply_data );
				die();
			}
			$chat_session = $_POST['chat_session'];
			$chat_id      = esc_attr( $chat_session['id'] );
			if ( $chat_id == '' ) {
				$reply_data['errorStatus'] = true;
				$reply_data['errorText']   = __( 'Invalid chat_id', 'psource-chat' );

				wp_send_json( $reply_data );
				die();
			}

			if ( ! isset( $_POST['chat_session_status'] ) ) {
				$reply_data['errorStatus'] = true;
				$reply_data['errorText']   = __( 'Invalid chat_session_status', 'psource-chat' );

				wp_send_json( $reply_data );
				die();
			}

			$chat_session_status = esc_attr( $_POST['chat_session_status'] );
			if ( ( $chat_session_status != "open" ) && ( $chat_session_status != "closed" ) ) {
				$reply_data['errorStatus'] = true;
				$reply_data['errorText']   = __( 'Invalid chat_session_status', 'psource-chat' );

				wp_send_json( $reply_data );
				die();
			}

			// If the user doesn't have a type
			if ( ! isset( $this->chat_auth['type'] ) ) {
				$reply_data['errorStatus'] = true;
				$reply_data['errorText']   = __( 'Invalid chat_auth [type]', 'psource-chat' );

				wp_send_json( $reply_data );
				die();
			} else if ( $this->chat_auth['type'] != "wordpress" ) {
				$reply_data['errorStatus'] = true;
				$reply_data['errorText']   = __( 'Invalid chat_auth [type]', 'psource-chat' );

				wp_send_json( $reply_data );
				die();

			}

			if ( ! is_user_logged_in() ) {
				$reply_data['errorStatus'] = true;
				$reply_data['errorText']   = __( 'Invalid chat_auth [type]', 'psource-chat' );

				wp_send_json( $reply_data );
				die();

			}

			if ( ! psource_chat_is_moderator( $chat_session ) ) {
				$reply_data['errorStatus'] = true;
				$reply_data['errorText']   = __( 'not moderator', 'psource-chat' );

				wp_send_json( $reply_data );
				die();
			}

			$this->chat_session_set_meta( $chat_session, 'session_status', $chat_session_status );

			wp_send_json( $reply_data );
			die();
		}

		function chat_session_moderate_message() {
			global $wpdb;

			if ( ! isset( $_POST['chat_id'] ) ) {
				wp_send_json_error();
				die();
			}
			$chat_id = $_POST['chat_id'];

			if ( $chat_id == '' ) {
				wp_send_json_error();
				die();
			}

			if ( ! isset( $_POST['row_id'] ) ) {
				wp_send_json_error();
				die();
			}
			list( $row_time, $row_id ) = explode( '-', $_POST['row_id'] );
			//$row_id = intval($_POST['row_id']);
			if ( ( empty( $row_time ) ) || ( empty( $row_id ) ) ) {
				wp_send_json_error();
				die();
			}

			//log_chat_message(__FUNCTION__ .": row_time[". $row_time ."] row_id[". $row_id ."]");


			if ( ! isset( $_POST['moderate_action'] ) ) {
				wp_send_json_error();
				die();
			}
			$moderate_action = esc_attr( $_POST['moderate_action'] );
			if ( empty( $moderate_action ) ) {
				wp_send_json_error();
				die();
			}

			if ( ! isset( $_POST['chat_session'] ) ) {
				wp_send_json_error();
				die();
			}
			$chat_session = $_POST['chat_session'];

			// If the user doesn't have a type
			if ( ! isset( $this->chat_auth['type'] ) ) {
				wp_send_json_error();
				die();
			} else if ( $this->chat_auth['type'] != "wordpress" ) {
				wp_send_json_error();
				die();
			}

			if ( ! is_user_logged_in() ) {
				wp_send_json_error();
				die();
			}

			if ( ! psource_chat_is_moderator( $chat_session ) ) {
				wp_send_json_error();
				die();
			}


			$row_date = date( 'Y-m-d H:i:s', $row_time );

			$sql_str = $wpdb->prepare( "SELECT id, deleted FROM `" . PSOURCE_Chat::tablename( 'message' )
			                           . "` WHERE id = %d AND blog_id = %d AND chat_id = %s AND timestamp = %s LIMIT 1;",
				$row_id, $chat_session['blog_id'], $chat_id, $row_date );


			$chat_row = $wpdb->get_row( $sql_str );

			if ( ( $chat_row ) && ( isset( $chat_row->deleted ) ) ) {
				$chat_row_deleted_new = '';

				if ( $moderate_action == "delete" ) {
					$chat_row_deleted_new = 'yes';
				} else if ( $moderate_action == "undelete" ) {
					$chat_row_deleted_new = 'no';
				}

				if ( ! empty( $chat_row_deleted_new ) ) {
					$sql_str = $wpdb->prepare( "UPDATE `" . PSOURCE_Chat::tablename( 'message' )
					                           . "` SET deleted=%s WHERE id=%d AND blog_id = %d AND chat_id = %s LIMIT 1;",
						$chat_row_deleted_new, $chat_row->id, $chat_session['blog_id'], $chat_id );

					$wpdb->get_results( $sql_str );

					$this->chat_session_update_message_rows_deleted( $chat_session );

					wp_send_json_success();
					die();
				}
			}
		}

		function chat_session_moderate_ip_address() {
			global $wpdb;

			if ( ! isset( $_POST['chat_id'] ) ) {
				die();
			}
			$chat_id = esc_attr( $_POST['chat_id'] );
			if ( $chat_id == '' ) {
				die();
			}

			if ( ! isset( $_POST['ip_address'] ) ) {
				die();
			}
			$ip_address = esc_attr( $_POST['ip_address'] );
			if ( empty( $ip_address ) ) {
				die();
			}

			if ( ! isset( $_POST['moderate_action'] ) ) {
				die();
			}
			$moderate_action = esc_attr( $_POST['moderate_action'] );
			if ( empty( $moderate_action ) ) {
				die();
			}

			if ( ! isset( $_POST['chat_session'] ) ) {
				die();
			}
			$chat_session = $_POST['chat_session'];

			// If the user doesn't have a type
			if ( ! isset( $this->chat_auth['type'] ) ) {
				die();
			} else if ( $this->chat_auth['type'] != "wordpress" ) {
				die();
			}

			if ( ! is_user_logged_in() ) {
				die();
			}

			if ( ! psource_chat_is_moderator( $chat_session ) ) {
				die();
			}

			if ( $this->get_option( 'blocked_ip_addresses_active', 'global' ) != "enabled" ) {
				die();
			}

			if ( ( ! isset( $this->_chat_options['global']['blocked_ip_addresses'] ) )
			     || ( empty( $this->_chat_options['global']['blocked_ip_addresses'] ) )
			) {
				$this->_chat_options['global']['blocked_ip_addresses'] = array();
			}

			if ( $moderate_action == "block-ip" ) {

				$this->_chat_options['global']['blocked_ip_addresses'][] = $ip_address;
				$this->_chat_options['global']['blocked_ip_addresses']   = array_unique( $this->_chat_options['global']['blocked_ip_addresses'] );

				update_option( 'psource-chat-global', $this->_chat_options['global'] );

			} else if ( $moderate_action == "unblock-ip" ) {

				$arr_idx = array_search( $ip_address, $this->_chat_options['global']['blocked_ip_addresses'] );
				if ( ( $arr_idx !== false ) && ( isset( $this->_chat_options['global']['blocked_ip_addresses'][ $arr_idx ] ) ) ) {
					unset( $this->_chat_options['global']['blocked_ip_addresses'][ $arr_idx ] );
					update_option( 'psource-chat-global', $this->_chat_options['global'] );
				}
			}
			wp_send_json_success();
			die();
		}

		function chat_session_moderate_user() {
			global $wpdb;

			if ( ! isset( $_POST['chat_id'] ) ) {
				die();
			}
			$chat_id = esc_attr( $_POST['chat_id'] );
			if ( $chat_id == '' ) {
				die();
			}

			if ( ! isset( $_POST['moderate_item'] ) ) {
				die();
			}
			$moderate_item = esc_attr( $_POST['moderate_item'] );
			if ( empty( $moderate_item ) ) {
				die();
			}

			if ( ! isset( $_POST['moderate_action'] ) ) {
				die();
			}
			$moderate_action = esc_attr( $_POST['moderate_action'] );
			if ( empty( $moderate_action ) ) {
				die();
			}

			if ( ! isset( $_POST['chat_session'] ) ) {
				die();
			}
			$chat_session = $_POST['chat_session'];

			// If the user doesn't have a type
			if ( ! isset( $this->chat_auth['type'] ) ) {
				die();
			} else if ( $this->chat_auth['type'] != "wordpress" ) {
				die();
			}

			if ( ! is_user_logged_in() ) {
				die();
			}

			if ( ! psource_chat_is_moderator( $chat_session ) ) {
				die();
			}

			if ( $moderate_action == "block-user" ) {

				$this->_chat_options['global']['blocked_users'][] = $moderate_item;
				$this->_chat_options['global']['blocked_users']   = array_unique( $this->_chat_options['global']['blocked_users'] );

				update_option( 'psource-chat-global', $this->_chat_options['global'] );

			} else if ( $moderate_action == "unblock-user" ) {

				$arr_idx = array_search( $moderate_item, $this->_chat_options['global']['blocked_users'] );
				if ( ( $arr_idx !== false ) && ( isset( $this->_chat_options['global']['blocked_users'][ $arr_idx ] ) ) ) {
					unset( $this->_chat_options['global']['blocked_users'][ $arr_idx ] );

					update_option( 'psource-chat-global', $this->_chat_options['global'] );
				}
			}
			wp_send_json_success();
			die();
		}

		/**
		 * Adds the details of host and invitee for private chat to chat message table
		 */
		function chat_session_invite_private() {
			global $wpdb, $blog_id;

			// We ONLY allow logged in users to perform private invites
			if ( ! is_user_logged_in() ) {
				wp_send_json_error();

				return;
			}

			/** Check for Auth hash */
			if ( md5( get_current_user_id() ) != $this->chat_auth['auth_hash'] ) {
				wp_send_json_error();

				return;
			}
			$user_from_hash = $this->chat_auth['auth_hash'];

			if ( ( ! isset( $_REQUEST['psource-chat-to-user'] ) ) || ( empty( $_REQUEST['psource-chat-to-user'] ) ) ) {
				wp_send_json_error();

				return;
			}
			$user_to_hash = esc_attr( $_REQUEST['psource-chat-to-user'] );

			$private_invite_nonce = time();
			$chat_id              = "private-" . $private_invite_nonce;

			$invitation                  = array();
			$invitation['host']          = array();
			$invitation['host']          = $this->chat_auth;
			$invitation['invite-status'] = 'accepted';

			// IF we have a previous private chat we do a number of setup tasks
			$sql_str = $wpdb->prepare( "SELECT invite_from.chat_id, invite_from.id invite_from_id, invite_to.id invite_to_id FROM " . PSOURCE_Chat::tablename( 'message' ) . " as invite_from INNER JOIN " . PSOURCE_Chat::tablename( 'message' ) . " as invite_to ON invite_from.chat_id=invite_to.chat_id AND invite_to.auth_hash = %s WHERE invite_from.blog_id = %d AND invite_from.session_type=%s AND invite_from.auth_hash=%s ORDER BY invite_from.timestamp ASC LIMIT 1", $user_to_hash, 0, 'invite', $user_from_hash );
			//echo "sql_str[". $sql_str ."]<br />";

			$invites = $wpdb->get_row( $sql_str );

			if ( ! empty( $invites ) ) {

				// IF we have a previous private chat we do a number of setup tasks

				// For the user sending the invite. We update the message with the 'no' archived status and fill in the invitation.
				if ( isset( $invites->invite_from_id ) ) {
					$sql_str = $wpdb->prepare( "UPDATE " . PSOURCE_Chat::tablename( 'message' ) . " SET archived = %s, message = %s, moderator = %s, user_type = %s WHERE id = %d AND blog_id = %d AND chat_id = %s AND auth_hash = %s LIMIT 1", 'no', serialize( $invitation ), 'yes', $this->chat_auth['type'], $invites->invite_from_id, 0, $invites->chat_id, $user_from_hash );
					//echo "sql_str[". $sql_str ."]<br />";
					$wpdb->get_results( $sql_str );
				}

				// For the user receiving the invite. We update the message with the 'no' archived status and fill in the invitation. The invitation
				// Contains information like who did the invite.
				if ( isset( $invites->invite_to_id ) ) {
					$invitation['id']            = $invites->invite_from_id;
					$invitation['invite-status'] = 'pending';

					$sql_str = $wpdb->prepare( "UPDATE " . PSOURCE_Chat::tablename( 'message' ) . " SET archived = %s, message = %s, moderator = %s, user_type = %s WHERE id = %d AND blog_id = %d AND chat_id = %s AND auth_hash = %s LIMIT 1", 'no', serialize( $invitation ), 'no', '', $invites->invite_to_id, 0, $invites->chat_id, $user_to_hash );
					//echo "sql_str[". $sql_str ."]<br />";
					$wpdb->get_results( $sql_str );
				}

				// Then we un-archive the previous messages if any
				$sql_str = $wpdb->prepare( "UPDATE " . PSOURCE_Chat::tablename( 'message' ) . " SET archived = %s WHERE blog_id = %d AND chat_id = %s AND session_type = %s", 'no', 0, $invites->chat_id, 'private' );
				//echo "sql_str[". $sql_str ."]<br />";
				$wpdb->get_results( $sql_str );

				// Lastly, we then unarchive the log reference.
				$sql_str = $wpdb->prepare( "UPDATE " . PSOURCE_Chat::tablename( 'log' ) . " SET archived = %s WHERE blog_id = %d AND chat_id = %s AND session_type = %s LIMIT 1", 'no', 0, $invites->chat_id, 'private' );
				//echo "sql_str[". $sql_str ."]<br />";
				$wpdb->get_results( $sql_str );

			} else if ( empty( $invites ) ) {

				/**
				 * Add details of host
				 */
				/**
				 * Invite status of host
				 */
				$sql_str = $wpdb->prepare( "INSERT INTO " . PSOURCE_Chat::tablename( 'message' ) .
				                           " (`blog_id`, `chat_id`, `session_type`, `timestamp`, `name`, `avatar`, `auth_hash`, `ip_address`, `message`, `moderator`, `deleted`, `archived`, `log_id`, `user_type`) VALUES(%d, %s, %s, NOW(), %s, %s, %s, %s, %s, %s, %s, %s, %d, %s);",
					0, $chat_id, 'invite', $this->chat_auth['name'], $this->chat_auth['avatar'], $user_from_hash,
					$this->chat_auth['ip_address'], serialize( $invitation ), 'yes', 'no', 'no', 0, $this->chat_auth['type'] );

				$wpdb->get_results( $sql_str );

				//If the row was inserted, insert the details of invitee
				if ( intval( $wpdb->insert_id ) ) {

					$invitation['id'] = $wpdb->insert_id;
					/**
					 * Invite status of invitee
					 */
					$invitation['invite-status'] = 'pending';

					/**
					 * Add details of invitee
					 */
					$user_moderator = "no";

					//Check for existing invitation from same user
					$sql_str = $wpdb->prepare( "SELECT * FROM " . PSOURCE_Chat::tablename( 'message' ) . " WHERE blog_id = %d AND chat_id=%s AND session_type=%s AND auth_hash=%s AND archived = %s ORDER BY timestamp ASC", 0, $chat_id, 'invite', $user_to_hash, 'no' );

					$invites = $wpdb->get_results( $sql_str );

					//there is no pending invitation, add new
					if ( empty( $invites ) ) {

						$sql_str = $wpdb->prepare( "INSERT INTO " . PSOURCE_Chat::tablename( 'message' ) .
						                           " (`blog_id`, `chat_id`, `session_type`, `timestamp`, `name`, `avatar`, `auth_hash`, `ip_address`, `message`, `moderator`, `deleted`, `archived`, `log_id`) VALUES(%d, %s, %s, NOW(), %s, %s, %s, %s, %s, %s, %s, %s, %d);",
							0, $chat_id, 'invite', '', '', $user_to_hash, '', serialize( $invitation ), 'no', 'no', 'no', 0 );

						$wpdb->get_results( $sql_str );
					}
				}
			}
			wp_send_json_success();
			die();
		}

		function chat_update_user_status() {
			if ( ! is_user_logged_in() ) {
				return;
			}

			$user_id = get_current_user_id();
			if ( md5( $user_id ) == $this->chat_auth['auth_hash'] ) {
				if ( isset( $_POST['psource-chat-user-status'] ) ) {
					$new_status = esc_attr( $_POST['psource-chat-user-status'] );
					if ( isset( $this->_chat_options['user-statuses'][ $new_status ] ) ) {
						psource_chat_update_user_status( $user_id, $new_status );
						wp_send_json_success();
					}
				}
			}
			die();
		}

		function chat_invite_update_user_status() {
			$chat_id = esc_attr( $_POST['chat-id'] );
			if ( ! $chat_id ) {
				die();
			}

			if ( ( ! isset( $this->chat_auth['auth_hash'] ) ) || ( empty( $this->chat_auth['auth_hash'] ) ) ) {
				die();
			}

			$invite_status = esc_attr( $_POST['invite-status'] );
			if ( ( $invite_status != 'accepted' ) && ( ( $invite_status != 'declined' ) ) ) {
				$invite_status = 'declined';
			}

			global $wpdb;
			$sql_str = $wpdb->prepare( "SELECT * FROM " . PSOURCE_Chat::tablename( 'message' ) . " WHERE session_type=%s AND auth_hash=%s AND archived IN('no') ORDER BY timestamp ASC", 'invite', $this->chat_auth['auth_hash'] );

			$invite_chats = $wpdb->get_results( $sql_str );
			if ( ! empty( $invite_chats ) ) {
				foreach ( $invite_chats as $invite_chat ) {
					if ( $invite_chat->chat_id != $chat_id ) {
						continue;
					}
					$invite_info                  = unserialize( $invite_chat->message );
					$invite_info['invite-status'] = $invite_status;

					$sql_str = $wpdb->prepare( "UPDATE " . PSOURCE_Chat::tablename( 'message' ) . " SET `message`= %s, `user_type`=%s WHERE id=%d", serialize( $invite_info ), $this->chat_auth['type'], $invite_chat->id );
					//log_chat_message(__FUNCTION__ .": [". $sql_str ."]");

					$wpdb->query( $sql_str );
					//Update transient
					$transient_key = "chat-session-" . $chat_id . '-private';
					if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
						$transient = get_option( $transient_key );
					} else {
						$transient = get_transient( $transient_key );
					}

					if ( ! empty( $transient ) ) {
						//Change invite status
						$transient['invite-info']['message']['invite-status'] = $invite_status;
						if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
							update_option( $transient_key, $transient );
						} else {
							set_transient( $transient_key, $transient, 60 * 60 * 24 );
						}
					}
				}
			}
			wp_send_json_success();
			die();


		}

		/**
		 * Process chat requests
		 *
		 * Mostly copied from process.php
		 *
		 * @global    object $current_user
		 *
		 * @param    string $return Return? 'yes' or 'no'
		 *
		 * @return    string            If $return is yes will return the output else echo
		 */
		function process_chat_actions( $return = 'no' ) {
			if ( ! isset( $_POST['function'] ) ) {
				die();
			}
			$function = $_POST['function'];

			switch ( $function ) {

				case 'chat_init':
					$this->chat_init();
					break;

				case 'chat_message_send':
					$this->chat_message_send();
					break;

				case 'chat_user_login':
					$this->chat_user_login();
					break;

				case 'chat_messages_update':
					$this->chat_message_update();
					break;

				case 'chat_meta_leave_private_session':
					$this->chat_meta_leave_private_session();

					break;

				case 'chat_messages_clear':
					$this->chat_messages_clear();

					break;

				case 'chat_messages_archive':
					$this->chat_messages_archive();
					break;

				case 'chat_session_moderate_status':
					$this->chat_session_moderate_status();

					break;

				case 'chat_session_moderate_message':
					$this->chat_session_moderate_message();
					break;

				case 'chat_session_moderate_ipaddress':
					$this->chat_session_moderate_ip_address();
					break;


				case 'chat_session_moderate_user':
					$this->chat_session_moderate_user();

					break;

				case 'chat_session_invite_private':
					$this->chat_session_invite_private();

					break;

				case 'chat_update_user_status':
					$this->chat_update_user_status();

					break;
				case 'chat_invite_update_user_status':
					$this->chat_invite_update_user_status();

					break;
			}
		}

		/**
		 *
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */
		function chat_session_build_box( $chat_session ) {
			$content = '';

			$chat_session['session_status'] = $this->chat_session_get_meta( $chat_session, 'session_status' );

			if ( $chat_session['session_type'] != "dashboard" ) {
				$content_tmp = $this->chat_box_header_container( $chat_session );
				$content     = $this->chat_session_module_wrap( $chat_session, $content_tmp, 'psource-chat-module-header' );
			}

			$content .= $this->chat_session_status_module( $chat_session );
			$content .= $this->chat_session_generic_message_module( $chat_session );
			$content .= $this->chat_session_user_status_message_module( $chat_session );
			$content .= $this->chat_session_login_prompt_module( $chat_session );
			$content .= $this->chat_session_invite_prompt_module( $chat_session );

			if ( $chat_session['box_input_position'] == "top" ) {
				$content .= $this->chat_session_message_area_module( $chat_session );
			}

			if ( ( $chat_session['users_list_position'] == "above" ) || ( $chat_session['users_list_position'] == "left" ) ) {
				$content .= $this->chat_session_users_list_module( $chat_session );
				$content .= $this->chat_session_messages_list_module( $chat_session );
			} else if ( ( $chat_session['users_list_position'] == "below" ) || ( $chat_session['users_list_position'] == "right" ) ) {
				$content .= $this->chat_session_messages_list_module( $chat_session );
				$content .= $this->chat_session_users_list_module( $chat_session );
			} else if ( $chat_session['users_list_position'] == "none" ) {
				$content .= $this->chat_session_messages_list_module( $chat_session );
			}

			$content .= $this->chat_session_login_module( $chat_session );
			$content .= $this->chat_session_banned_status_module( $chat_session );

			if ( $chat_session['box_input_position'] == "bottom" ) {
				$content .= $this->chat_session_message_area_module( $chat_session );
			}

			//$content .= $this->chat_session_box_styles($chat_session);

			return $content;
		}

		/**
		 *
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */
		function chat_session_get_invites_new() {
			global $wpdb;

			$invite_sessions = array();

			if ( ! isset( $this->chat_auth['auth_hash'] ) ) {
				return $invite_sessions;
			}

			// We want to exclude existing chat_session IDs.
			$chat_id_str = '';
			foreach ( $this->chat_sessions as $chat_id => $chat_session ) {
				if ( strlen( $chat_id_str ) ) {
					$chat_id_str .= ",";
				}
				$chat_id_str .= "'" . esc_attr( $chat_id ) . "'";
			}
			if ( ! empty( $chat_id_str ) ) {
				$chat_id_str = " AND chat_id NOT IN (" . $chat_id_str . ") ";
			}

			if ( ! empty( $this->user_meta['chat_user_status'] ) && $this->user_meta['chat_user_status'] != 'available' ) {
				$chat_id_str .= " AND moderator='yes' ";
			}

			$sql_str = $wpdb->prepare( "SELECT * FROM " . PSOURCE_Chat::tablename( 'message' ) . " WHERE session_type=%s AND auth_hash=%s AND archived IN('no') " . $chat_id_str . " ORDER BY timestamp ASC", 'invite', $this->chat_auth['auth_hash'] );

			$invite_chats = $wpdb->get_results( $sql_str, ARRAY_A );
			if ( ! empty( $invite_chats ) ) {
				foreach ( $invite_chats as $invite_chat ) {

					if ( ( empty( $invite_chat['name'] ) ) || ( empty( $invite_chat['avatar'] ) ) || ( empty( $invite_chat['ip_address'] ) ) ) {

						$sql_str = $wpdb->prepare( "UPDATE " . PSOURCE_Chat::tablename( 'message' ) . " SET `name` = %s, `avatar` = %s, `ip_address` = %s, `user_type` = %s WHERE `id`=%d LIMIT 1", $this->chat_auth['name'], $this->chat_auth['avatar'], $this->chat_auth['ip_address'], $this->chat_auth['type'], $invite_chat['id'] );
						$wpdb->query( $sql_str );
						//log_chat_message(__FUNCTION__ .": [". $sql_str ."]");
					}

					if ( ( isset( $invite_chat['message'] ) ) && ( ! empty( $invite_chat['message'] ) ) ) {
						$invite_chat['message'] = maybe_unserialize( $invite_chat['message'] );

					} else {
						$invite_info = array();
					}

					if ( ! isset( $this->chat_sessions[ $invite_chat['chat_id'] ] ) ) {

						$atts                         = array();
						$atts['id']                   = $invite_chat['chat_id'];
						$atts['session_type']         = 'private';
						$atts['box_moderator_footer'] = 'disabled';
						//$atts['box_title']				= __('Private', 'psource-chat') .'<span class="psource-chat-private-attendees"></span>';
						$atts['box_title'] = __( '(P)', 'psource-chat' ) .'<span class="psource-chat-private-attendees"></span>';

						if ( ! isset( $invite_chat['message']['invite-status'] ) ) {
							$invite_chat['message']['invite-status'] = "pending";
						}

						$atts['invite-info'] = $invite_chat;

						$content = $this->process_chat_shortcode( $atts );
						//Check for content and if chat session exists and not exited
						if ( ( ! empty( $content ) ) && ( isset( $this->chat_sessions[ $invite_chat['chat_id'] ] ) ) && $invite_chat['message']['invite-status'] != 'exited' ) {
							$invite_sessions[ $invite_chat['chat_id'] ]['html'] = $content;

							$invite_sessions[ $invite_chat['chat_id'] ]['css'] = $this->chat_session_box_styles( $this->chat_sessions[ $invite_chat['chat_id'] ] );

							$invite_sessions[ $invite_chat['chat_id'] ]['session'] = $this->chat_sessions[ $invite_chat['chat_id'] ];

							if ( isset( $this->chat_user[ $invite_chat['chat_id'] ] ) ) {
								$invite_sessions[ $invite_chat['chat_id'] ]['user'] = $this->chat_user[ $invite_chat['chat_id'] ];
							} else {
								$invite_sessions[ $invite_chat['chat_id'] ]['user'] = $this->chat_user['__global__'];
							}

							$invite_sessions[ $invite_chat['chat_id'] ]['user']['invite-status']    = $invite_chat['message'];
							$invite_sessions[ $invite_chat['chat_id'] ]['user']['invite-moderator'] = $invite_chat['moderator'];
						}
					}
				}
			}

			return $invite_sessions;
		}

		/**
		 * @todo: Check the functionality and add description
		 *
		 * @param $chat_session
		 */
		function psource_chat_set_private_archive_status( $chat_session ) {
			global $wpdb;

			$sql_str = $wpdb->prepare( "SELECT * FROM " . PSOURCE_Chat::tablename( 'message' ) . " WHERE chat_id=%s AND session_type=%s", $chat_session['id'], 'invite' );

			$invite_chats = $wpdb->get_results( $sql_str );
			if ( ! empty( $invite_chats ) ) {
				foreach ( $invite_chats as $invite_chat ) {
					$message = maybe_unserialize( $invite_chat->message );

					$message['invite-status'] = ! empty( $message['invite-status'] ) ? $message['invite-status'] : 'pending';

					$sql_str = "UPDATE " . PSOURCE_Chat::tablename( 'message' ) . " SET message= '" . maybe_serialize( $message ) . "', archived='no' WHERE id=" . $invite_chat->id;
					$wpdb->query( $sql_str );
				}
			}
		}

		function psource_chat_update_user_invite_status( $invite_status, $chat_id, $auth_hash = '', $invite_archived = '' ) {
			global $wpdb;

			if ( empty( $auth_hash ) ) {
				if ( isset( $this->chat_auth['auth_hash'] ) ) {
					$auth_hash = $this->chat_auth['auth_hash'];
				} else {
					return;
				}
			}

			$sql_str     = $wpdb->prepare( "SELECT * FROM " . PSOURCE_Chat::tablename( 'message' ) . " WHERE chat_id=%s AND session_type=%s AND auth_hash=%s LIMIT 1",
				$chat_id, 'invite', $auth_hash );
			$invite_chat = $wpdb->get_row( $sql_str );
			if ( ! empty( $invite_chat ) ) {
				if ( ( isset( $invite_chat->message ) ) && ( ! empty( $invite_chat->message ) ) ) {
					$invite_chat->message = maybe_unserialize( $invite_chat->message );
				}
				$invite_chat->message['invite-status'] = $invite_status;

				if ( empty( $invite_archived ) ) {
					$invite_archived = $invite_chat->archived;
				}

				$sql_str = "UPDATE " . PSOURCE_Chat::tablename( 'message' ) . " SET message= '" . maybe_serialize( $invite_chat->message ) . "', archived='" . $invite_archived . "' WHERE id=" . $invite_chat->id;
				$wpdb->query( $sql_str );
			}
		}

		/**
		 *
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */
		function chat_session_get_message_new( $chat_session ) {

			if ( ( ! isset( $chat_session['last_row_id'] ) ) || ( $chat_session['last_row_id'] == "__EMPTY__" ) ) {
				$chat_session['last_row_id'] = 0;
			}

			if ( ! isset( $chat_session['last_row_compare'] ) ) {
				$chat_session['last_row_compare'] = '>=';
			}

			if ( $chat_session['last_row_id'] > 0 ) {
				$chat_session['log_limit'] = 0;
			}

			if ( ! isset( $chat_session['archived'] ) ) {
				$chat_session['archived'] = array( 'no' );
			}

			if ( ! isset( $chat_session['deleted'] ) ) {
				$chat_session['deleted'] = array( 'yes', 'no' );
			}

			return $this->chat_session_get_messages( $chat_session, false );
		}

		/**
		 *
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */
		function chat_session_update_message_rows_deleted( $chat_session ) {

			$chat_session['last_row_id'] = 0;
			$chat_session['log_limit']   = 0;
			$chat_session['archived']    = array( 'no' );
			$chat_session['deleted']     = array( 'yes' );

			$deleted_rows = array();

			$rows_tmp = $this->chat_session_get_messages( $chat_session );
			if ( ( $rows_tmp ) && ( count( $rows_tmp ) ) ) {

				foreach ( $rows_tmp as $row ) {
					if ( $row->deleted == "yes" ) {
						$deleted_rows[] = strtotime( $row->timestamp ) . "-" . $row->id;
					}
				}
			}

			if ( ! count( $deleted_rows ) ) {
				$deleted_rows = "__EMPTY__";
			}

			$this->chat_session_set_meta( $chat_session, 'deleted_rows', $deleted_rows );
		}

		/**
		 *
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */
		function chat_session_update_meta_log( $chat_session, $meta_data = array() ) {
			global $wpdb;

			if ( ! isset( $meta_data['deleted-rows'] ) ) {
				$meta_data['deleted-rows'] = $this->chat_session_get_meta( $chat_session, 'deleted_rows' );
			}

			if ( ! isset( $meta_data['session-status'] ) ) {

				if ( $chat_session['session_type'] == "private" ) {
					$sql_str                     = $wpdb->prepare( "SELECT archived FROM " . PSOURCE_Chat::tablename( 'message' ) . " WHERE chat_id=%s AND session_type=%s AND auth_hash=%s LIMIT 1", $chat_session['id'], 'invite', $this->chat_auth['auth_hash'] );
					$chat_status                 = $wpdb->get_row( $sql_str );
					$meta_data['session-status'] = ! empty( $chat_status->archived ) ? $chat_status->archived : '';
				} else {
					$meta_data['session-status'] = $this->chat_session_get_meta( $chat_session, 'session_status' );
				}
			}

			return $meta_data;
		}

		/**
		 *
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */
		function chat_session_update_global_log( $chat_session, $global_data = array() ) {

			if ( ! isset( $global_data['blocked-ip-addresses'] ) ) {
				$global_data['blocked-ip-addresses'] = $this->chat_session_get_blocked_ip_addresses( $chat_session );
			}

			if ( ! isset( $global_data['blocked-users'] ) ) {
				$global_data['blocked-users'] = $this->chat_session_get_blocked_users( $chat_session );
			}

			return $global_data;
		}

		/**
		 *
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */
		function chat_session_users_update_polltime( $chat_session ) {
			global $wpdb;

			if ( ! isset( $chat_session['id'] ) ) {
				return;
			}

			// IF the user has not logged in yet. Ignore for now.
			if ( ( ! isset( $this->chat_auth['auth_hash'] ) ) || ( empty( $this->chat_auth['auth_hash'] ) ) ) {
				return;
			}

			$blog_id         = $chat_session['blog_id'];
			$chat_id         = $chat_session['id'];
			$user_name       = trim( $this->chat_auth['name'] );
			$user_avatar     = ! empty( $this->chat_auth['avatar'] ) ? trim( $this->chat_auth['avatar'] ) : '';
			$user_hash       = trim( $this->chat_auth['auth_hash'] );
			$user_ip_address = trim( $chat_session['ip_address'] );
			$user_type       = trim( $this->chat_auth['type'] );
			$user_moderator  = trim( $chat_session['moderator'] );
			if ( $user_moderator != "yes" ) {
				$user_moderator = "no";
			}

			if ( ( isset( $user_name ) ) && ( strlen( $user_name ) ) ) {
				$sql_str = $wpdb->prepare( "INSERT INTO " . PSOURCE_Chat::tablename( 'users' ) .
				                           " (blog_id, chat_id, auth_hash, name, avatar, moderator, last_polled, entered, ip_address, user_type)
			VALUES(%d, %s, %s, %s, %s, %s, NOW(), NOW(), %s, %s)
			ON DUPLICATE KEY UPDATE last_polled = NOW();",
					$blog_id, $chat_id, $user_hash, $user_name, $user_avatar, $user_moderator, $user_ip_address, $user_type );
				//log_chat_message(__FUNCTION__ .": [". $sql_str ."]");

				//echo "sql_str=[". $sql_str ."]<br />";

				$wpdb->get_results( $sql_str );
			}

			return;
		}

		/**
		 *
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */
		function chat_session_get_active_users( $chat_session ) {
			global $wpdb;

			if ( isset( $chat_session['users_list_avatar_width'] ) ) {
				$avatar_size = intval( $chat_session['users_list_avatar_width'] );
			}
			if ( ( ! isset( $avatar_size ) ) || ( $avatar_size < 1 ) || ( $avatar_size > 100 ) ) {
				$avatar_size = 50;
			}

			$users_data               = array();
			$users_data['moderators'] = array();
			$users_data['users']      = array();

			if ( $chat_session['session_type'] == 'private' ) {

				$sql_str = $wpdb->prepare( "SELECT invites.name, invites.moderator, invites.ip_address, invites.auth_hash, invites.archived, invites.message, users.last_polled FROM " . PSOURCE_Chat::tablename( 'message' ) . " as invites LEFT JOIN " . PSOURCE_Chat::tablename( 'users' ) . " as users ON invites.auth_hash=users.auth_hash AND invites.blog_id=users.blog_id AND invites.chat_id=users.chat_id WHERE invites.blog_id = %d AND invites.chat_id=%s AND invites.session_type=%s", 0, $chat_session['id'], 'invite' );

				$users = $wpdb->get_results( $sql_str );
				if ( ( $users ) && ( count( $users ) ) ) {
					foreach ( $users as $user ) {

						$_tmp = array();
						//$_tmp['id'] 			= $user->id;
						$_tmp['name']      = $user->name;
						$_tmp['moderator'] = $user->moderator;
						$_tmp['ip']        = $user->ip_address;
						$_tmp['auth_hash'] = $user->auth_hash;
						$_tmp['archived']  = $user->archived;

						if ( ! empty( $user->last_polled ) ) {

							if ( ( isset( $user->message ) ) && ( ! empty( $user->message ) ) ) {
								$invitation = maybe_unserialize( $user->message );
								if ( ( isset( $invitation['invite-status'] ) ) && ( ! empty( $invitation['invite-status'] ) ) ) {
									$_tmp['connect_status'] = esc_attr( $invitation['invite-status'] );
								}
							}
						} else {
							$_tmp['connect_status'] = 'pending';
						}

						if ( $user->moderator == 'yes' ) {
							$users_data['moderators'][ $_tmp['auth_hash'] ] = $_tmp;

						} else if ( $user->moderator == 'no' ) {
							$users_data['users'][ $_tmp['auth_hash'] ] = $_tmp;
						}
					}
				}

			} else {

				$chat_delete_threshold = intval( $chat_session['users_list_threshold_delete'] );
				if ( ! $chat_delete_threshold ) {
					$chat_delete_threshold = 20;
				}

				$sql_str = $wpdb->prepare( "DELETE FROM " . PSOURCE_Chat::tablename( 'users' ) . " WHERE chat_id=%s AND
			last_polled < TIMESTAMPADD(SECOND, -" . $chat_delete_threshold . ", NOW());", $chat_session['id'] );
				//log_chat_message(__FUNCTION__ .": [". $sql_str ."]");
				$wpdb->query( $sql_str );


				$sql_str = $wpdb->prepare( "SELECT * FROM " . PSOURCE_Chat::tablename( 'users' ) . " WHERE chat_id=%s AND blog_id=%d AND last_polled > TIMESTAMPADD(SECOND, -%d, NOW()) ORDER BY name ASC", $chat_session['id'], $chat_session['blog_id'], $chat_delete_threshold );

				$users = $wpdb->get_results( $sql_str );
				if ( ( $users ) && ( count( $users ) ) ) {

					foreach ( $users as $user ) {
						$_tmp = array();
						//$_tmp['id'] 			= $user->id;
						$_tmp['name']           = $user->name;
						$_tmp['moderator']      = $user->moderator;
						$_tmp['ip']             = $user->ip_address;
						$_tmp['auth_hash']      = $user->auth_hash;
						$_tmp['connect_status'] = 'accepted';

						if ( ( isset( $user->avatar ) ) && ( strlen( $user->avatar ) ) ) {
							$avatar         = '<img alt="' . $user->name . '" style="width: ' . $avatar_size . '; height: ' . $avatar_size . ';" width="' . $avatar_size . '" src="' . $user->avatar . '" class="avatar photo" />';
							$_tmp['avatar'] = $avatar;
						}
						if ( $_tmp['moderator'] == "yes" ) {
							$users_data['moderators'][ $_tmp['auth_hash'] ] = $_tmp;
						} else {
							$users_data['users'][ $_tmp['auth_hash'] ] = $_tmp;
						}
					}
				}
			}

			return $users_data;
		}

		/**
		 *
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */
		function chat_session_get_blocked_ip_addresses( $chat_session ) {

			$blocked_ip_addresses = array();

			if ( ( $this->get_option( 'blocked_ip_addresses_active', 'global' ) == "enabled" )
			     && ( $chat_session['blocked_ip_addresses_active'] == "enabled" )
			) {

				$blocked_ip_addresses = $this->get_option( 'blocked_ip_addresses', 'global' );
				if ( empty( $blocked_ip_addresses ) ) {
					return array();
				} else if ( ! is_array( $blocked_ip_addresses ) ) {
					$blocked_ip_addresses = array( $blocked_ip_addresses );
				}
			}

			return $blocked_ip_addresses;
		}

		/**
		 *
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */
		function chat_session_get_blocked_users( $chat_session ) {

			$blocked_users = array();

			$blocked_users = $this->get_option( 'blocked_users', 'global' );
			if ( empty( $blocked_users ) ) {
				return array();
			} else if ( ! is_array( $blocked_users ) ) {
				return array( $blocked_users );
			} else {
				return $blocked_users;
			}
		}


		/**
		 * Get message
		 *
		 * @global    object $wpdb
		 * @global    int $blog_id
		 *
		 * @param    int $chat_id Chat ID
		 * @param    int $since Start Unix timestamp
		 * @param    int $end End Unix timestamp
		 * @param    string $archived Archived? 'yes' or 'no'
		 */
		function chat_session_get_messages( $chat_session ) {
			global $wpdb;

			if ( ( isset( $chat_session['since'] ) ) && ( $chat_session['since'] > 0 ) ) {
				$since_timestamp = date( 'Y-m-d H:i:s', $chat_session['since'] );
			} else {
				$since_timestamp = 0;
			}

			if ( ( isset( $chat_session['end'] ) ) && ( $chat_session['end'] > 0 ) ) {
				$end_timestamp = date( 'Y-m-d H:i:s', $chat_session['end'] );
			} else {
				$end_timestamp = 0;
			}

			if ( isset( $chat_session['orderby'] ) ) {
				$orderby = $chat_session['orderby'];
			} else {
				$orderby = "DESC";
			}

			if ( ! isset( $chat_session['last_row_compare'] ) ) {
				$chat_session['last_row_compare'] = ' > ';
			}

			$archived_str = "";
			if ( ! isset( $chat_session['archived'] ) ) {
				$chat_session['archived'] = array( 'no' );
			}

			if ( ( isset( $chat_session['archived'] ) ) && ( count( $chat_session['archived'] ) ) ) {
				foreach ( $chat_session['archived'] as $_val ) {
					if ( strlen( $archived_str ) ) {
						$archived_str .= ",";
					}
					$archived_str .= "'" . $_val . "'";
				}
			}

			$deleted_str = "";
			if ( ! isset( $chat_session['deleted'] ) ) {
				$chat_session['deleted'] = array( 'no' );
			}

			if ( ( isset( $chat_session['deleted'] ) ) && ( count( $chat_session['deleted'] ) ) ) {
				foreach ( $chat_session['deleted'] as $_val ) {
					if ( strlen( $deleted_str ) ) {
						$deleted_str .= ",";
					}
					$deleted_str .= "'" . $_val . "'";
				}
			}

			if ( $end_timestamp > 0 ) {
				$sql_str = $wpdb->prepare( "SELECT * FROM `" . PSOURCE_Chat::tablename( 'message' ) . "` WHERE " .
				                           " blog_id = %s " .
				                           " AND chat_id = %s " .
				                           " AND session_type=%s " .
				                           " AND archived IN ( " . $archived_str . " ) " .
				                           " AND deleted IN ( " . $deleted_str . " ) " .
				                           " AND timestamp BETWEEN '" . $since_timestamp . "' AND '" . $end_timestamp . "' " .
				                           " ORDER BY timestamp " . $orderby, $chat_session['blog_id'], $chat_session['id'], $chat_session['session_type'] );
			} else {
				$sql_str = $wpdb->prepare( "SELECT * FROM `" . PSOURCE_Chat::tablename( 'message' ) . "` WHERE 1=1 " .
				                           " AND id " . $chat_session['last_row_compare'] . " " . $chat_session['last_row_id'] . " " .
				                           " AND blog_id = %s " .
				                           " AND chat_id = %s " .
				                           " AND session_type=%s " .
				                           " AND archived IN ( " . $archived_str . " ) " .
				                           " AND deleted IN ( " . $deleted_str . " ) " .
				                           " ORDER BY timestamp " . $orderby, $chat_session['blog_id'], $chat_session['id'], $chat_session['session_type'] );
				if ( intval( $chat_session['log_limit'] ) > 0 ) {
					$sql_str .= " LIMIT " . intval( $chat_session['log_limit'] );
				}
			}

			return $wpdb->get_results( $sql_str );
		}

		/**
		 * Send the message
		 *
		 * @global    object $wpdb
		 * @global    int $blog_id
		 *
		 * @param    int $chat_id Chat ID
		 * @param    string $name Name
		 * @param    string $avatar URL or e-mail
		 * @param    string $message Payload message
		 * @param    string $moderator Moderator
		 */
		function chat_session_send_message( $message, $chat_session ) {
			global $wpdb;

			//$wpdb->real_escape = true;

			//$time_stamp = date("Y-m-d H:i:s");
			$time_stamp_seconds  = time();
			$time_stamp_formated = date( "Y-m-d H:i:s", $time_stamp_seconds );

			$blog_id       = $chat_session['blog_id'];
			$chat_id       = $chat_session['id'];
			$session_type  = trim( $chat_session['session_type'] );
			$name          = trim( $this->chat_auth['name'] );
			$user_avatar = isset($this->chat_auth['avatar']) ? trim($this->chat_auth['avatar']) : '';
			$auth_hash     = trim( $this->chat_auth['auth_hash'] );
			$user_type     = trim( $this->chat_auth['type'] );
			$ip_address    = ( isset( $_SERVER['HTTP_X_FORWARD_FOR'] ) ) ? $_SERVER['HTTP_X_FORWARD_FOR'] : $_SERVER['REMOTE_ADDR'];
			$message       = trim( $message );
			$moderator_str = trim( $chat_session['moderator'] );

			if ( $message == '' ) {
				return false;
			}

			$log_row_id = $this->chat_session_get_meta( $chat_session, 'log_row_id' );
			//echo "log_row_id[". $log_row_id ."]<br />";
			// If we don't find a record we insert a new one
			if ( ( empty( $log_row_id ) ) || ( $log_row_id == "__EMPTY__" ) ) {
				$sql_str = $wpdb->prepare( "INSERT INTO " . PSOURCE_Chat::tablename( 'log' ) . " (`blog_id`, `chat_id`, `session_type`, `start`, `end`, `box_title`, `archived`) VALUES (%d, %s, %s, %s, %s, %s, %s);", $chat_session['blog_id'], $chat_session['id'], $chat_session['session_type'], $time_stamp_formated, '', $chat_session['box_title'], 'no' );
				//echo "sql_str[". $sql_str ."]<br />";
				//die();

				$ret = $wpdb->query( $sql_str );
				if ( ( isset( $wpdb->insert_id ) ) && ( $wpdb->insert_id > 0 ) ) {
					$this->chat_session_set_meta( $chat_session, 'log_row_id', $wpdb->insert_id );
					$log_row_id = $wpdb->insert_id;
				}
			}

			// If DB charset is not utf8mb4, emojis needs to be encoded as html entities.
			if ( ! strpos( $wpdb->charset, 'mb4' ) && function_exists( 'wp_encode_emoji' ) ) {
				$message = wp_encode_emoji( $message );
			}

			$sql_str = $wpdb->prepare( "INSERT INTO " . PSOURCE_Chat::tablename( 'message' ) . "
					(`blog_id`, `chat_id`, `session_type`, `timestamp`, `name`, `avatar`, `auth_hash`, `ip_address`, `message`, `moderator`, `deleted`, `archived`, `log_id`, `user_type`) VALUES (%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, %s);", $blog_id, $chat_id, $session_type, $time_stamp_formated, $name, $user_avatar, $auth_hash, $ip_address, $message, $moderator_str, 'no', 'no', $log_row_id, $user_type );

			$ret = $wpdb->query( $sql_str );
			if ( ( isset( $wpdb->insert_id ) ) && ( $wpdb->insert_id > 0 ) ) {
				$this->chat_session_set_meta( $chat_session, 'last_row_id', $wpdb->insert_id );

				return $wpdb->insert_id;
			}
		}

		function chat_session_archive_messages( $chat_session ) {
			global $wpdb;

			$sql_str = $wpdb->prepare( "SELECT * FROM `" . PSOURCE_Chat::tablename( 'message' ) . "` WHERE blog_id = %d AND chat_id = %s AND session_type = %s AND archived IN ('no') ORDER BY timestamp ASC;", $chat_session['blog_id'], $chat_session['id'], $chat_session['session_type'] );

			$chat_messages = $wpdb->get_results( $sql_str );
			if ( ( $chat_messages ) && ( count( $chat_messages ) ) ) {

				$chat_summary                 = array();
				$chat_summary['session_type'] = $chat_session['session_type'];

				foreach ( $chat_messages as $id => $chat_message ) {
					$chat_summary['id'][] = $chat_message->id;
					if ( $id == 0 ) {
						$chat_summary['start'] = $chat_message->timestamp;
					}
					$chat_summary['end'] = $chat_message->timestamp;
				}

				$chat_log_id = 0;

				// Need to check the log table for an existing unarchived record record.
				$sql_str  = $wpdb->prepare( "SELECT id FROM " . PSOURCE_Chat::tablename( 'log' )
				                            . " WHERE blog_id = %d AND chat_id = %s AND archived = %s LIMIT 1", $chat_session['blog_id'], $chat_session['id'], 'no' );
				$chat_log = $wpdb->get_row( $sql_str );

				// If we don't find a record we insert a new one
				if ( ( isset( $chat_log ) ) && ( isset( $chat_log->id ) ) && ( ! empty( $chat_log->id ) ) ) {
					$chat_log_id = $chat_log->id;

					$sql_str = $wpdb->prepare( "UPDATE " . PSOURCE_Chat::tablename( 'log' ) . " SET `blog_id` = %d, `chat_id`= %s, `session_type` = %s, `start` = %s, `end` = %s, `box_title` = %s, `archived` = %s WHERE id = %d LIMIT 1;",
						$chat_session['blog_id'],
						$chat_session['id'],
						$chat_summary['session_type'],
						$chat_summary['start'],
						$chat_summary['end'],
						$chat_session['box_title'],
						'yes',
						$chat_log_id
					);
					$wpdb->query( $sql_str );
				}

				if ( empty( $chat_log_id ) ) {
					$sql_str = $wpdb->prepare( "INSERT INTO " . PSOURCE_Chat::tablename( 'log' ) . "
							(`blog_id`, `chat_id`, `session_type`, `start`, `end`, `box_title`, `archived`) VALUES (%d, %s, %s, %s, %s, %s, %s);",
						$chat_session['blog_id'],
						$chat_session['id'],
						$chat_summary['session_type'],
						$chat_summary['start'],
						$chat_summary['end'],
						'',
						'no'
					);
					$wpdb->query( $sql_str );

					// Then get the inserted row id
					if ( ( $wpdb->insert_id ) && ( ! empty( $wpdb->insert_id ) ) ) {
						$chat_log_id = $wpdb->insert_id;
					}
				}

				// IF we have aquired or created a new chat_log_id we update the message rows with the log_id
				if ( ! empty( $chat_log_id ) ) {
					$sql_str = $wpdb->prepare( "UPDATE `" . PSOURCE_Chat::tablename( 'message' ) . "` set archived = 'yes', log_id=%d WHERE blog_id = %d AND chat_id = %s AND id IN(" . join( ',', array_values( $chat_summary['id'] ) ) . ") AND archived = 'no';", $chat_log_id, $chat_session['blog_id'], $chat_session['id'], $chat_summary['start'], $chat_summary['end'] );

					$wpdb->query( $sql_str );

					$this->chat_session_set_meta( $chat_session, 'last_row_id', '__EMPTY__' );
					$this->chat_session_set_meta( $chat_session, 'log_row_id', '__EMPTY__' );
					$this->chat_session_update_message_rows_deleted( $chat_session );
				}
			}
		}


		/**
		 * Get a list of archives for the given chat
		 *
		 * @global    object $wpdb
		 * @global    int $blog_id
		 *
		 * @param    int $chat_id Chat ID
		 *
		 * @return    array                List of archives
		 */
		function get_archives( $chat_session ) {
			global $wpdb, $blog_id, $site_id;

			if ( ! isset( $chat_session['deleted'] ) ) {
				$chat_session['deleted'] = 'no';
			}
			if ( ! isset( $chat_session['archived'] ) ) {
				$chat_session['archived'] = 'yes';
			}

			$sql_where_str = "WHERE 1=1 ";


			if ( ( isset( $chat_session['blog_id'] ) ) && ( ! empty( $chat_session['blog_id'] ) ) ) {
				$sql_where_str .= $wpdb->prepare( " AND blog_id = %d ", $chat_session['blog_id'] );
			} else {
				$chat_session['blog_id'] = $blog_id;
			}
			if ( ( isset( $chat_session['deleted'] ) ) && ( ! empty( $chat_session['deleted'] ) ) ) {
				$sql_where_str .= $wpdb->prepare( " AND deleted = %s ", $chat_session['deleted'] );
			}
			if ( ( isset( $chat_session['archived'] ) ) && ( ! empty( $chat_session['archived'] ) ) ) {
				$sql_where_str .= $wpdb->prepare( " AND archived = %s ", $chat_session['archived'] );
			}
			if ( ( isset( $chat_session['id'] ) ) && ( ! empty( $chat_session['id'] ) ) ) {
				$sql_where_str .= $wpdb->prepare( " AND chat_id = %s ", $chat_session['id'] );
			}
			$sql_str = "SELECT * FROM `" . PSOURCE_Chat::tablename( 'log' ) . "` " . $sql_where_str;

			if ( ( isset( $chat_session['log_display_limit'] ) ) && ( ! empty( $chat_session['log_display_limit'] ) ) ) {
				$log_display_limit = intval( $chat_session['log_display_limit'] );
				if ( $log_display_limit > 0 ) {
					$sql_str .= " LIMIT " . $log_display_limit;
				}
			}
			//echo "sql_str=[". $sql_str ."]<br />";
			//log_chat_message(__FUNCTION__ .": [". $sql_str ."]");

			return $wpdb->get_results( $sql_str );
		}

		function chat_session_logs_messages( $table, $action, $ids = array() ) {
			global $wpdb;

			$sql_str = '';
			switch ( $table ) {
				case 'log':
					if ( $action == 'hide' ) {
						$sql_str = "UPDATE `" . PSOURCE_Chat::tablename( 'log' ) . "` SET `deleted`='yes' WHERE id IN(" . implode( ',', $ids ) . ")";
						$ret     = $wpdb->query( $sql_str );

					} else if ( $action == "unhide" ) {
						$sql_str = "UPDATE `" . PSOURCE_Chat::tablename( 'log' ) . "` SET `deleted`='no' WHERE id IN(" . implode( ',', $ids ) . ")";
						$ret     = $wpdb->query( $sql_str );

					} else if ( $action == "delete" ) {
						$sql_str .= "DELETE FROM `" . PSOURCE_Chat::tablename( 'log' ) . "` WHERE id IN(" . implode( ',', $ids ) . "); ";
						$ret = $wpdb->query( $sql_str );

						$sql_str = "DELETE FROM `" . PSOURCE_Chat::tablename( 'message' ) . "` WHERE log_id IN(" . implode( ',', $ids ) . "); ";
						$ret     = $wpdb->query( $sql_str );
					}
					break;

				case 'message':
					if ( $action == 'hide' ) {
						$sql_str = "UPDATE `" . PSOURCE_Chat::tablename( 'message' ) . "` SET `deleted`='yes' WHERE id IN(" . implode( ',', $ids ) . ")";
						$ret     = $wpdb->query( $sql_str );

					} else if ( $action == "unhide" ) {
						$sql_str = "UPDATE `" . PSOURCE_Chat::tablename( 'message' ) . "` SET `deleted`='no' WHERE id IN(" . implode( ',', $ids ) . ")";
						$ret     = $wpdb->query( $sql_str );

					} else if ( $action == "delete" ) {
						$sql_str = "DELETE FROM `" . PSOURCE_Chat::tablename( 'message' ) . "` WHERE id IN(" . implode( ',', $ids ) . "); ";
						$ret     = $wpdb->query( $sql_str );
					}
					break;

				default:
					break;
			}

			return true;
		}

		/**
		 * Used to prime the last_row_id meta field for the session. Either set to the highest row ID or __EMPTY__
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */
		function chat_session_get_last_row_id( $chat_session ) {

			$chat_session['last_row_id'] = 0;
			$chat_session['log_limit']   = 1;
			$chat_session['archived']    = array( 'no' );
			$chat_session['deleted']     = array( 'yes', 'no' );
			$chat_session['orderby']     = 'DESC';

			$rows = $this->chat_session_get_messages( $chat_session, false );
			if ( isset( $rows[0] ) ) {
				$this->chat_session_set_meta( $chat_session, 'last_row_id', $rows[0]->id );

				return $rows[0]->id;
			} else {
				$this->chat_session_set_meta( $chat_session, 'last_row_id', '__EMPTY__' );

				return '__EMPTY__';
			}
		}

		function chat_session_verify_meta( $chat_session, $chat_session_meta ) {
			global $wpdb;

			if ( ( isset( $chat_session_meta['log_row_id'] ) ) && ( $chat_session_meta['log_row_id'] != '__EMPTY__' ) && ( $chat_session_meta['log_row_id'] != 0 ) ) {
				$sql_str = $wpdb->prepare( "SELECT id FROM " . PSOURCE_Chat::tablename( 'log' ) . " WHERE id=%d AND blog_id = %d AND chat_id = %s",
					$chat_session_meta['log_row_id'], $chat_session['blog_id'], $chat_session['id'] );
				//echo "sql_str[". $sql_str ."]<br />";
				$chat_log = $wpdb->get_col( $sql_str );

				if ( ( ! isset( $chat_log->id ) ) || ( empty( $chat_log->id ) ) ) {
					$sql_str = $wpdb->prepare( "INSERT INTO " . PSOURCE_Chat::tablename( 'log' ) . " (`blog_id`, `chat_id`, `session_type`, `start`, `end`, `box_title`, `archived`) VALUES (%d, %s, %s, %s, %s, %s, %s);",
						$chat_session['blog_id'],
						$chat_session['id'],
						$chat_session['session_type'],
						$time_stamp_formated,
						'',
						$chat_session['box_title'],
						'no' );
					//echo "sql_str[". $sql_str ."]<br />";
					//die();

					$ret = $wpdb->query( $sql_str );
					if ( ( isset( $wpdb->insert_id ) ) && ( $wpdb->insert_id > 0 ) ) {
						$this->chat_session_set_meta( $chat_session, 'log_row_id', $wpdb->insert_id );
						$log_row_id = $wpdb->insert_id;
					}
				}
			}
		}

		function chat_session_get_log_row_id( $chat_session ) {
			global $wpdb;

			$sql_str  = $wpdb->prepare( "SELECT id FROM " . PSOURCE_Chat::tablename( 'log' ) . " WHERE blog_id = %d AND chat_id = %s AND archived = %s", $chat_session['blog_id'], $chat_session['id'], 'no' );
			$chat_log = $wpdb->get_row( $sql_str );

			if ( ( empty( $chat_log ) ) || ( ! isset( $chat_log->id ) ) ) {
				return '0';
			} else {
				return $chat_log->id;
			}
		}

		/**
		 *
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */
		function chat_session_get_meta( $chat_session, $key = '' ) {

			$transient_key = "chat-meta-" . $chat_session['id'] . '-' . $chat_session['session_type'];
			if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
				$chat_meta = get_option( $transient_key );
			} else {
				$chat_meta = get_transient( $transient_key );
			}
			if ( ! $chat_meta ) {
				$chat_meta = array();
			}

			if ( ! isset( $chat_meta['session_status'] ) ) {
				$chat_meta['session_status'] = "open";
			}

			if ( ! isset( $chat_meta['last_row_id'] ) ) {
				$chat_meta['last_row_id'] = $this->chat_session_get_last_row_id( $chat_session );
			}

			if ( ! isset( $chat_meta['log_row_id'] ) ) {
				$chat_meta['log_row_id'] = $this->chat_session_get_log_row_id( $chat_session );
			}

			if ( ! isset( $chat_meta['deleted_rows'] ) ) {
				$chat_meta['deleted_rows'] = array();
			}

			if ( empty( $key ) ) {
				return $chat_meta;
			} else if ( isset( $chat_meta[ $key ] ) ) {
				return $chat_meta[ $key ];
			} else {
				return array();
			}
		}

		/**
		 *
		 *
		 * @global    none
		 *
		 * @param    none
		 *
		 * @return    none
		 */
		function chat_session_set_meta( $chat_session, $key = '', $meta = '' ) {

			$transient_key = "chat-meta-" . $chat_session['id'] . '-' . $chat_session['session_type'];
			if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
				$chat_meta = get_option( $transient_key );
			} else {
				$chat_meta = get_transient( $transient_key );
			}
			//$chat_meta = get_option($transient_key);
			if ( ! $chat_meta ) {
				$chat_meta = array();
			}

			if ( $key ) {
				//log_chat_message(__FUNCTION__ .": key[". $key ."] meta[". $meta."]");

				$chat_meta[ $key ] = $meta;
				if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
					update_option( $transient_key, $chat_meta );
				} else {
					set_transient( $transient_key, $chat_meta, 60 * 60 * 24 );
				}
			}
		}

		// Probably a better way to do this coming soon. The logic here is IF the user is viewing the chat session
		// from within the wp-admin session logs they can load the live open chat session. If that is happening
		// We want to override some of the display settings. so for example consider the actual chat session is
		// a widget or bottom corner chat. The widget and height would be very small. So we want to override
		// the widget/height for the display of the chat but but still maintain data integrity. We admin can post
		// to the message message which will appear within the live chat session as well as here.
		function chat_session_show_via_logs( $chat_session ) {
			$_FLAG_VIEW_ADMIN = false;

			if ( is_admin() ) {
				if ( ( isset( $_GET['page'] ) ) && ( $_GET['page'] == "chat_session_logs" )
				     && ( isset( $_GET['chat_id'] ) ) && ( $_GET['chat_id'] == $chat_session['id'] )
				     && ( isset( $_GET['session_type'] ) ) && ( $_GET['session_type'] == $chat_session['session_type'] )
				) {
					$_FLAG_VIEW_ADMIN = true;
				}
			} else {
				$REFERER_args = array();
				if ( isset( $_POST['psource-chat-settings-request-uri'] ) ) {
					$request_uri = base64_decode( $_POST['psource-chat-settings-request-uri'] );

					wp_parse_str( parse_url( $request_uri, PHP_URL_QUERY ), $REFERER_args );
				}
				if ( ( isset( $REFERER_args['page'] ) ) && ( $REFERER_args['page'] == "chat_session_logs" )
				     && ( isset( $REFERER_args['chat_id'] ) ) && ( $REFERER_args['chat_id'] == $chat_session['id'] )
				     && ( isset( $REFERER_args['session_type'] ) ) && ( $REFERER_args['session_type'] == $chat_session['session_type'] )
				) {
					$_FLAG_VIEW_ADMIN = true;
				}
			}

			if ( $_FLAG_VIEW_ADMIN == false ) {
				return $chat_session;
			}

			$chat_session['box_width']          = '100%';
			$chat_session['box_height']         = '500px';
			$chat_session['box_popout']         = 'disabled';
			$chat_session['box_input_position'] = 'top';

			$chat_session['row_date']        = 'enabled';
			$chat_session['row_time']        = 'enabled';
			$chat_session['row_name_avatar'] = 'name-avatar';

			$chat_session['login_options'] = array();

			$chat_session['log_display'] = 'disabled';
			$chat_session['log_limit']   = '';

			$chat_session['users_list_show']     = 'avatar';
			$chat_session['users_list_position'] = 'right';
			$chat_session['users_list_style']    = 'split';

			$chat_session['users_list_width'] = '10%';

			$chat_session['show_messages_position'] = 'left';
			$chat_session['show_messages_width']    = '70%';

			$chat_session_after = apply_filters( 'chat_logs_show_session', $chat_session );

			// Basically, don't trust users filtering out the key settings for the chat
			$chat_session_after['blog_id']          = $chat_session['blog_id'];
			$chat_session_after['id']               = $chat_session['id'];
			$chat_session_after['session_type']     = $chat_session['session_type'];
			$chat_session_after['update_transient'] = 'disabled';

			return $chat_session_after;
		}

		function unset_cookies() {
			if ( is_user_logged_in() ) { ?>

				<script type="text/javascript">
					jQuery(document).ready(function () {
						var auth_name = 'psource-chat-auth';
						var value = ' ';
						var expires = '; expires=Thu, 01 Jan 1970 00:00:00 GMT';
						var path = '; path= /';
						var domain = '; domain=.dilliboss.com';
						var secure = '; ';
						var name = 'psource-chat-user';
						document.cookie = [auth_name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
						document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
					});
				</script><?php
			}

			return;
		}
	}
} // End of class_exists()