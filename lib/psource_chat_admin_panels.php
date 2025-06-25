<?php
if ( ! class_exists( "psource_chat_admin_panels" ) ) {
	class psource_chat_admin_panels {

		/**
		 * The PHP5 Class constructor. Used when an instance of this class is needed.
		 * Sets up the initial object environment and hooks into the various WordPress
		 * actions and filters.
		 *
		 * @since 1.0.0
		 * @uses $this->_settings array of our settings
		 * @uses $this->_admin_notice_messages array of admin header message texts.
		 *
		 * @param none
		 *
		 * @return self
		 */
		function __construct() {

		}

		/**
		 * The old-style PHP Class constructor. Used when an instance of this class
		 * is needed. If used (PHP4) this function calls the PHP5 version of the constructor.
		 *
		 * @since 2.0.0
		 *
		 * @param none
		 *
		 * @return self
		 */
		function psource_chat_admin_panels() {
			$this->__construct();
		}

		function chat_settings_panel_page() {
			global $psource_chat;

			$form_section = "page";
			?>
			<div id="psource-chat-wrap" class="wrap psource-chat-wrap-settings-page">
				<h2><?php _e( 'Chateinstellungen Seiten', 'psource-chat' ); ?></h2>

				<form method="post" id="psource-chat-settings-form" action="?page=<?php echo $_GET['page']; ?>">

					<p><?php _e( 'Die folgenden Einstellungen werden verwendet, um die Inline-Chat-Shortcodes zu steuern, die auf Posts, Seiten usw. angewendet werden. Hier kannst Du Standardoptionen einrichten. Überschreibe diese Standardoptionen nicht nur mit Shortcode-Parametern für den jeweiligen Beitrag, die Seite usw..', 'psource-chat' ); ?></p>

					<div id="chat_tab_pane" class="chat_tab_pane">
						<ul>
							<li id="chat_box_appearance_tab"><a href="#chat_box_appearance_panel"><span><?php
										_e( 'Box Aussehen', 'psource-chat' ); ?></span></a></li>
							<li id="chat_messages_appearance_tab"><a href="#chat_messages_appearance_panel"><span><?php
										_e( 'Nachrichten Darstellung', 'psource-chat' ); ?></span></a>
							</li>
							<li id="chat_messages_input_tab"><a href="#chat_messages_input_panel"><span><?php
										_e( 'Eingabebox', 'psource-chat' ); ?></span></a></li>
							<li id="chat_users_list_tab"><a href="#chat_users_list_panel"><span><?php
										_e( 'Benutzerliste', 'psource-chat' ); ?></span></a></li>
							<li id="chat_authentication_tab"><a href="#chat_authentication_panel"><span><?php
										_e( 'Autentifizierung', 'psource-chat' ); ?></span></a></li>
							<li id="psource_chat_timymce_buttom_tab"><a href="#psource_chat_timymce_buttom_panel"><span><?php
										_e( 'WYSIWYG Button', 'psource-chat' ); ?></span></a></li>
							<li id="chat_advanced_tab"><a href="#chat_advanced_panel"><span><?php
										_e( 'Erweitert', 'psource-chat' ); ?></span></a></li>
						</ul>
						<div id="chat_box_appearance_panel" class="panel">
							<?php psource_chat_form_section_container( $form_section ); ?>
						</div>
						<div id="chat_messages_appearance_panel" class="panel">
							<?php psource_chat_form_section_messages_wrapper( $form_section ); ?>
							<?php psource_chat_form_section_messages_rows( $form_section ); ?>
						</div>
						<div id="chat_messages_input_panel" class="panel">
							<?php psource_chat_form_section_messages_input( $form_section ); ?>
							<?php psource_chat_form_section_messages_send_button( $form_section ); ?>
						</div>
						<div id="chat_users_list_panel" class="panel">
							<?php psource_chat_users_list( $form_section ); ?>
							<?php psource_chat_form_section_user_enter_exit_messages( $form_section ); ?>
						</div>
						<div id="chat_authentication_panel" class="chat_panel">
							<?php psource_chat_form_section_login_options( $form_section ); ?>
							<?php psource_chat_form_section_login_view_options( $form_section ); ?>
							<?php psource_chat_form_section_moderator_roles( $form_section ); ?>
						</div>
						<div id="psource_chat_timymce_buttom_panel" class="chat_panel">
							<?php psource_chat_form_section_tinymce_button_post_types( $form_section ); ?>
							<?php psource_chat_form_section_tinymce_button_roles( $form_section ); ?>
						</div>
						<div id="chat_advanced_panel" class="chat_panel">
							<?php psource_chat_form_section_logs( $form_section ); ?>
							<?php psource_chat_form_section_logs_limit( $form_section ); ?>
							<?php psource_chat_form_section_session_messages( $form_section ); ?>

							<?php if ( $psource_chat->get_option( 'blocked_ip_addresses_active', 'global' ) == "enabled" ) {
								psource_chat_form_section_blocked_ip_addresses( $form_section );
							}
							psource_chat_form_section_blocked_words( $form_section );
							?>
						</div>
					</div>
					<input type="hidden" name="chat[section]" value="<?php echo $form_section; ?>"/>
					<?php wp_nonce_field( 'psource_chat_settings_save', 'psource_chat_settings_save_wpnonce' ); ?>
					<p class="submit"><input type="submit" name="Submit" class="button-primary"
					                         value="<?php _e( 'Änderungen speichern', 'psource-chat' ) ?>"/>
					</p>
				</form>
			</div>
			<?php
		}

		function chat_settings_panel_site() {
			global $psource_chat;

			$form_section = "site";

			?>
			<div id="psource-chat-wrap" class="wrap psource-chat-wrap-settings-page">
				<h2><?php _e( 'Chateinstellungen Webseite', 'psource-chat' ); ?></h2>

				<form method="post" id="psource-chat-settings-form" action="?page=<?php echo $_GET['page']; ?>">

					<p><?php _e( 'Die folgenden Einstellungen werden verwendet, um die Einstellungen für die untere Ecke und den privaten Chatbereich zu steuern.', 'psource-chat' ); ?></p>
					<?php if ( is_multisite() ) {
						?>
						<p><?php _e( 'Unter Multisite kann der Chat in der unteren Ecke im Netzwerk aktiviert werden. In diesem Fall wird der Chat in der unteren Ecke der lokalen Site ersetzt.', 'psource-chat' ); ?></p><?php
					} ?>
					<div id="chat_tab_pane" class="chat_tab_pane">
						<ul>
							<li id="chat_bottom_corner_tab"><a href="#chat_bottom_corner_panel" class="current"><span><?php
										_e( 'Untere Ecke', 'psource-chat' ); ?></span></a></li>
							<li id="chat_box_appearance_tab"><a href="#chat_box_appearance_panel" class="current"><span><?php
										_e( 'Box Aussehen', 'psource-chat' ); ?></span></a></li>
							<li id="chat_box_position_tab"><a href="#chat_box_position_panel"><span><?php
										_e( 'Box Position', 'psource-chat' ); ?></span></a></li>
							<li id="chat_messages_appearance_tab"><a href="#chat_messages_appearance_panel"><span><?php
										_e( 'Nachrichten Darstellung', 'psource-chat' ); ?></span></a>
							</li>
							<li id="chat_messages_input_tab"><a href="#chat_messages_input_panel"><span><?php
										_e( 'Nachrichteneingabe', 'psource-chat' ); ?></span></a></li>
							<li id="chat_users_list_tab"><a href="#chat_users_list_panel"><span><?php
										_e( 'Benutzerliste', 'psource-chat' ); ?></span></a></li>
							<li id="chat_authentication_tab"><a href="#chat_authentication_panel"><span><?php
										_e( 'Autentifikation', 'psource-chat' ); ?></span></a></li>
							<li id="chat_advanced_tab"><a href="#chat_advanced_panel"><span><?php
										_e( 'Erweitert', 'psource-chat' ); ?></span></a></li>
						</ul>
						<div id="chat_bottom_corner_panel" class="panel current">
							<?php psource_chat_form_section_bottom_corner( $form_section ); ?>
						</div>
						<div id="chat_box_appearance_panel" class="panel">
							<?php psource_chat_form_section_container( $form_section ); ?>
						</div>
						<div id="chat_box_position_panel" class="panel">
							<?php psource_chat_form_section_site_position( $form_section ); ?>
						</div>
						<div id="chat_messages_appearance_panel" class="panel">
							<?php psource_chat_form_section_messages_wrapper( $form_section ); ?>
							<?php psource_chat_form_section_messages_rows( $form_section ); ?>
						</div>
						<div id="chat_messages_input_panel" class="panel">
							<?php psource_chat_form_section_messages_input( $form_section ); ?>
							<?php psource_chat_form_section_messages_send_button( $form_section ); ?>
						</div>
						<div id="chat_users_list_panel" class="panel">
							<?php psource_chat_users_list( $form_section ); ?>
							<?php psource_chat_form_section_user_enter_exit_messages( $form_section ); ?>
						</div>
						<div id="chat_authentication_panel" class="chat_panel">
							<?php psource_chat_form_section_login_options( $form_section ); ?>
							<?php psource_chat_form_section_login_view_options( $form_section ); ?>
							<?php psource_chat_form_section_moderator_roles( $form_section ); ?>
						</div>
						<div id="chat_advanced_panel" class="chat_panel">
							<?php psource_chat_form_section_logs( $form_section ); ?>
							<?php psource_chat_form_section_logs_limit( $form_section ); ?>
							<?php psource_chat_form_section_session_messages( $form_section ); ?>

							<?php if ( $psource_chat->get_option( 'blocked_ip_addresses_active', 'global' ) == "enabled" ) {
								psource_chat_form_section_blocked_ip_addresses( $form_section );
							}
							psource_chat_form_section_blocked_words( $form_section );
							?>
							<?php psource_chat_form_section_block_urls_site( $form_section ); ?>
						</div>
					</div>
					<input type="hidden" name="chat[section]" value="<?php echo $form_section; ?>"/>
					<?php wp_nonce_field( 'psource_chat_settings_save', 'psource_chat_settings_save_wpnonce' ); ?>
					<p class="submit"><input type="submit" name="Submit" class="button-primary"
					                         value="<?php _e( 'Änderungen speichern', 'psource-chat' ) ?>"/>
					</p>

				</form>
			</div>
			<?php
		}

		function chat_settings_panel_widget() {
			global $psource_chat;

			$form_section = "widget";

			?>
			<div id="psource-chat-wrap" class="wrap psource-chat-wrap-settings-page">
				<h2><?php _e( 'Chateinstellungen Widget', 'psource-chat' ); ?></h2>

				<form method="post" id="psource-chat-settings-form" action="?page=<?php echo $_GET['page']; ?>">

					<p><?php _e( 'Die folgenden Einstellungen werden verwendet, um alle Chat-Widgets zu steuern.', 'psource-chat' ); ?></p>

					<div id="chat_tab_pane" class="chat_tab_pane">
						<ul>
							<li id="chat_box_appearance_tab"><a href="#chat_box_appearance_panel" class="current"><span><?php
										_e( 'Box Aussehen', 'psource-chat' ); ?></span></a></li>
							<li id="chat_messages_appearance_tab"><a href="#chat_messages_appearance_panel"><span><?php
										_e( 'Nachricht Aussehen', 'psource-chat' ); ?></span></a>
							</li>
							<li id="chat_messages_input_tab"><a href="#chat_messages_input_panel"><span><?php
										_e( 'Nachrichteneingabe', 'psource-chat' ); ?></span></a></li>
							<li id="chat_users_list_tab"><a href="#chat_users_list_panel"><span><?php
										_e( 'Benutzerliste', 'psource-chat' ); ?></span></a></li>
							<li id="chat_authentication_tab"><a href="#chat_authentication_panel"><span><?php
										_e( 'Authentication', 'psource-chat' ); ?></span></a></li>
							<li id="chat_advanced_tab"><a href="#chat_advanced_panel"><span><?php
										_e( 'Erweitert', 'psource-chat' ); ?></span></a></li>
						</ul>
						<div id="chat_box_appearance_panel" class="panel current">
							<?php psource_chat_form_section_container( $form_section ); ?>
						</div>
						<div id="chat_messages_appearance_panel" class="panel">
							<?php psource_chat_form_section_messages_wrapper( $form_section ); ?>
							<?php psource_chat_form_section_messages_rows( $form_section ); ?>
						</div>
						<div id="chat_messages_input_panel" class="panel">
							<?php psource_chat_form_section_messages_input( $form_section ); ?>
							<?php psource_chat_form_section_messages_send_button( $form_section ); ?>
						</div>
						<div id="chat_users_list_panel" class="panel">
							<?php psource_chat_users_list( $form_section ); ?>
							<?php psource_chat_form_section_user_enter_exit_messages( $form_section ); ?>
						</div>
						<div id="chat_authentication_panel" class="chat_panel">
							<?php psource_chat_form_section_login_options( $form_section ); ?>
							<?php psource_chat_form_section_login_view_options( $form_section ); ?>
							<?php psource_chat_form_section_moderator_roles( $form_section ); ?>
						</div>
						<div id="chat_advanced_panel" class="chat_panel">
							<?php psource_chat_form_section_logs( $form_section ); ?>
							<?php psource_chat_form_section_logs_limit( $form_section ); ?>
							<?php psource_chat_form_section_session_messages( $form_section ); ?>

							<?php if ( $psource_chat->get_option( 'blocked_ip_addresses_active', 'global' ) == "enabled" ) {
								psource_chat_form_section_blocked_ip_addresses( $form_section );
							}
							psource_chat_form_section_blocked_words( $form_section );
							?>
							<?php psource_chat_form_section_block_urls_widget( $form_section ); ?>
						</div>
					</div>
					<input type="hidden" name="chat[section]" value="<?php echo $form_section; ?>"/>
					<?php wp_nonce_field( 'psource_chat_settings_save', 'psource_chat_settings_save_wpnonce' ); ?>
					<p class="submit"><input type="submit" name="Submit" class="button-primary"
					                         value="<?php _e( 'Änderungen speichern', 'psource-chat' ) ?>"/>
					</p>
				</form>
			</div>
			<?php
		}

		function chat_settings_panel_buddypress() {
			global $psource_chat;

			$form_section = "bp-group";
			?>
			<div id="psource-chat-wrap" class="wrap psource-chat-wrap-settings-page">
				<h2><?php _e( 'Gruppenchat Einstellungen', 'psource-chat' ); ?></h2>

				<?php if ( version_compare( bp_get_version(), '1.8' ) < 0 ) { ?>
				<form method="post" id="psource-chat-settings-form" action="?page=<?php echo $_GET['page']; ?>">
					<?php } ?>

					<?php
					include_once( dirname( dirname( __FILE__ ) ) . '/lib/psource_chat_form_sections.php' );
					include_once( dirname( dirname( __FILE__ ) ) . '/lib/psource_chat_admin_panels_help.php' );
					?>

					<div id="chat_tab_pane" class="chat_tab_pane">
						<ul>
							<li id="chat_box_appearance_tab"><a href="#chat_box_appearance_panel"><span><?php
										_e( 'Box Aussehen', 'psource-chat' ); ?></span></a></li>
							<li id="chat_messages_appearance_tab"><a href="#chat_messages_appearance_panel"><span><?php
										_e( 'Nachrichten Aussehen', 'psource-chat' ); ?></span></a>
							</li>
							<li id="chat_messages_input_tab"><a href="#chat_messages_input_panel"><span><?php
										_e( 'Nachrichteneingabe', 'psource-chat' ); ?></span></a></li>
							<li id="chat_users_list_tab"><a href="#chat_users_list_panel"><span><?php
										_e( 'Benutzerliste', 'psource-chat' ); ?></span></a></li>
							<li id="chat_advanced_tab"><a href="#chat_advanced_panel"><span><?php
										_e( 'Erweitert', 'psource-chat' ); ?></span></a></li>
						</ul>
						<div id="chat_box_appearance_panel" class="panel">
							<?php psource_chat_form_section_information( $form_section ); ?>
							<?php psource_chat_form_section_container( $form_section ); ?>
						</div>
						<div id="chat_messages_appearance_panel" class="panel">
							<?php psource_chat_form_section_messages_wrapper( $form_section ); ?>
							<?php psource_chat_form_section_messages_rows( $form_section ); ?>
						</div>
						<div id="chat_messages_input_panel" class="panel">
							<?php psource_chat_form_section_messages_input( $form_section ); ?>
							<?php psource_chat_form_section_messages_send_button( $form_section ); ?>
						</div>
						<div id="chat_users_list_panel" class="panel">
							<?php psource_chat_users_list( $form_section ); ?>
							<?php psource_chat_form_section_user_enter_exit_messages( $form_section ); ?>
						</div>
						<div id="chat_advanced_panel" class="chat_panel">
							<?php psource_chat_form_section_logs( $form_section ); ?>
							<?php psource_chat_form_section_logs_limit( $form_section ); ?>
							<?php psource_chat_form_section_session_messages( $form_section ); ?>

							<?php if ( $psource_chat->get_option( 'blocked_ip_addresses_active', 'global' ) == "enabled" ) {
								psource_chat_form_section_blocked_ip_addresses( $form_section );
							}
							psource_chat_form_section_blocked_words( $form_section ); ?>
						</div>
					</div>
					<input type="hidden" name="chat[section]" value="<?php echo $form_section; ?>"/>
					<?php wp_nonce_field( 'psource_chat_settings_save', 'psource_chat_settings_save_wpnonce' ); ?>

					<?php /* if (!is_admin()) { ?>
						<p class="submit"><input type="submit" name="Submit" class="button-primary"
							value="<?php _e('Save Changes', 'psource-chat') ?>" /></p>
					<?php } */
					?>

					<?php if ( version_compare( bp_get_version(), '1.8' ) < 0 ) { ?>
				</form>
			<?php } ?>
				<style type="text/css">

					#psource-chat-wrap .ui-tabs-panel.ui-widget-content {
						background-color: <?php echo $psource_chat->get_option('bp_form_background_color', 'global'); ?> !important;
					}

					#psource-chat-wrap fieldset table td.chat-label-column {
						color: <?php echo $psource_chat->get_option('bp_form_label_color', 'global'); ?> !important;
					}
				</style>
			</div>
			<?php
		}

		function chat_settings_panel_global() {
			global $psource_chat;

			$buddypress_active = false;
			if ( is_plugin_active( 'buddypress/bp-loader.php' ) ) {
				$buddypress_active = true;
			} else if ( ( is_multisite() ) && ( is_plugin_active_for_network( 'buddypress/bp-loader.php' ) ) ) {
				$buddypress_active = true;
			}

			$form_section = "global";
			?>
			<div id="psource-chat-wrap" class="wrap psource-chat-wrap-settings-page">
				<?php if ( is_network_admin() ) { ?>
					<h2><?php _e( 'Chat-Einstellungen Netzwerk Allgemein', 'psource-chat' ); ?></h2>
				<?php } else { ?>
					<h2><?php _e( 'Chat Einstellungen', 'psource-chat' ); ?></h2>
				<?php } ?>

				<form method="post" id="psource-chat-settings-form" action="?page=<?php echo $_GET['page']; ?>">

					<p><?php _e( 'Die folgenden Einstellungen werden für alle Chat-Sitzungstypen verwendet (Seite, Site, Privat, Support).',
							'psource-chat' ); ?></p>

					<div id="chat_tab_pane" class="chat_tab_pane">
						<ul>
							<li id="psource_chat_interval_tab"><a href="#psource_chat_interval_panel"><span><?php
										_e( 'Abfrageintervalle', 'psource-chat' ); ?></span></a></li>
							<?php if ( ! is_network_admin() ) { ?>
								<li id="psource_chat_file_uploads_tab">
									<a href="#psource_chat_file_uploads_panel"><span><?php
											_e( 'Datei-Uploads', 'psource-chat' ); ?></span></a></li>
								<li id="psource_chat_blocked_ip_addresses_tab">
									<a href="#psource_chat_blocked_ip_addresses_panel"><span><?php
											_e( 'Blockiere IP/User', 'psource-chat' ); ?></span></a></li>
								<li id="psource_chat_blocked_words_tab">
									<a href="#psource_chat_blocked_words_panel"><span><?php
											_e( 'Blockierte Wörter', 'psource-chat' ); ?></span></a></li>
								<li id="psource_chat_wp_tab"><a href="#psource_chat_blocked_urls"><span><?php
											_e( 'Blockierte URLs', 'psource-chat' ); ?></span></a></li>
								<li id="chat_wpadmin_tab"><a href="#chat_wpadmin_panel"><span><?php
											_e( 'Dashboard', 'psource-chat' ); ?></span></a></li>
								<?php if ( $buddypress_active ) { ?>
									<li>
										<a href="#psource_chat_buddypress_panel"><span><?php _e( 'BuddyPress', 'psource-chat' ); ?></span></a>
									</li>
								<?php } ?>
							<?php } ?>
						</ul>
						<div id="psource_chat_interval_panel" class="chat_panel current">
							<?php psource_chat_form_section_polling_interval( $form_section ); ?>

							<?php if ( ! is_network_admin() ) { ?>
								<?php psource_chat_form_section_polling_content( $form_section ); ?>
							<?php } ?>
							<?php psource_chat_form_section_performance_content( $form_section ); ?>
						</div>
						<?php if ( ! is_network_admin() ) { ?>
							<div id="psource_chat_file_uploads_panel" class="chat_panel">
								<?php psource_chat_form_section_file_uploads_global( 'global' ); ?>
							</div>
							<div id="psource_chat_blocked_ip_addresses_panel" class="chat_panel">
								<?php psource_chat_form_section_blocked_ip_addresses_global( $form_section ); ?>
								<?php psource_chat_form_section_block_users_global( 'global' ); ?>
							</div>
							<div id="psource_chat_blocked_words_panel" class="chat_panel">
								<?php psource_chat_form_section_blocked_words_global( 'banned' ); ?>
							</div>
							<div id="psource_chat_blocked_urls" class="chat_panel">
								<?php psource_chat_form_section_blocked_urls_admin( 'global' ); ?>
								<?php psource_chat_form_section_blocked_urls_front( 'global' ); ?>
							</div>
							<div id="chat_wpadmin_panel" class="panel">
								<?php psource_chat_form_section_wpadmin( $form_section ); ?>
							</div>
							<?php if ( $buddypress_active ) { ?>
								<div id="psource_chat_buddypress_panel" class="chat_panel">
									<p class="info"><?php _e( 'In diesem Abschnitt wird gesteuert, wie der Chat im BuddyPress-System funktioniert. Dies sind globale Einstellungen, die sich auf alle Gruppen auswirken', 'psource-chat' ); ?></p>
									<?php psource_chat_form_section_buddypress_group_information( $form_section ); ?>
									<?php psource_chat_form_section_buddypress_group_hide_site( $form_section ); ?>
									<?php psource_chat_form_section_buddypress_group_hide_widget( $form_section ); ?>
									<?php psource_chat_form_section_buddypress_group_admin_colors( $form_section ); ?>
								</div>
							<?php } ?>
						<?php } ?>
					</div>
					<input type="hidden" name="chat[section]" value="<?php echo $form_section; ?>"/>
					<?php wp_nonce_field( 'psource_chat_settings_save', 'psource_chat_settings_save_wpnonce' ); ?>
					<p class="submit"><input type="submit" name="Submit" class="button-primary"
					                         value="<?php _e( 'Änderungen speichern', 'psource-chat' ) ?>"/>
					</p>
				</form>
			</div>
			<?php
		}

		function chat_settings_panel_session_logs() {
			global $wpdb, $psource_chat;

			if ( isset( $_GET['message'] ) ) {
				$message_idx = esc_attr( $_GET['message'] );
				if ( isset( $psource_chat->_admin_notice_messages[ $message_idx ] ) ) {
					?>
					<div id='chat-warning' class='updated fade'>
					<p><?php echo $psource_chat->_admin_notice_messages[ $message_idx ]; ?></p></div><?php
				}
			}
			if ( ( isset( $_GET['laction'] ) ) && ( $_GET['laction'] == "show" ) ) {
				?>
				<div id="psource-chat-messages-listing-panel"
				     class="wrap psource-chat-wrap psource-chat-wrap-settings-page">
					<?php //screen_icon('psource-chat'); ?>
					<h2><?php _ex( "Chat-Sitzung", "Page Title", 'psource-chat' ); ?></h2>

					<p>
						<a href="admin.php?page=chat_session_logs"><?php _e( 'Return to Logs', 'psource-chat' ); ?></a>
					</p>
					<?php
					if ( ( isset( $_GET['chat_id'] ) ) && ( ! empty( $_GET['chat_id'] ) ) ) {
						$chat_id = esc_attr( $_GET['chat_id'] );
					} else {
						die();
					}
					if ( ( isset( $_GET['session_type'] ) ) && ( ! empty( $_GET['session_type'] ) ) ) {
						$session_type = esc_attr( $_GET['session_type'] );
					} else {
						die();
					}

					$transient_key = "chat-session-" . $chat_id . '-' . $session_type;
					if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
						$chat_session_transient = get_option( $transient_key );
					} else {
						$chat_session_transient = get_transient( $transient_key );
					}
					if ( ( ! empty( $chat_session_transient ) ) && ( is_array( $chat_session_transient ) ) ) {
						$chat_session_transient                     = $psource_chat->chat_session_show_via_logs( $chat_session_transient );
						$chat_session_transient['update_transient'] = 'disabled';
						$chat_session_transient['box_class']        = '';
						// Always make sure to keep the chat_id, session_type
						echo $psource_chat->process_chat_shortcode( $chat_session_transient );

					}

					?>
				</div>
				<?php

			} else if ( ( isset( $_GET['laction'] ) ) && ( $_GET['laction'] == "details" ) ) {
				?>
				<div id="psource-chat-messages-listing-panel"
				     class="wrap psource-chat-wrap psource-chat-wrap-settings-page">
					<?php //screen_icon('psource-chat'); ?>
					<h2><?php _ex( "Chat-Sitzungsnachrichten", "Page Title", 'psource-chat' ); ?></h2>

					<p>
						<a href="admin.php?page=chat_session_logs"><?php _e( 'Zurück zu den Protokollen', 'psource-chat' ); ?></a>
					</p>
					<?php
					//require_once( dirname(__FILE__) . '/psource_chat_admin_session_messages.php');
					//$this->_logs_table = new PSOURCEChat_Session_Messages_Table( );
					$psource_chat->chat_log_list_table->prepare_items();

					if ( ( isset( $psource_chat->chat_log_list_table->log_item->deleted ) ) && ( $psource_chat->chat_log_list_table->log_item->deleted == 'yes' ) ) {
						?>
						<div id='chat-error' class='error fade'>
						<p><?php _e( 'Diese gesamte Chat-Sitzung ist als ausgeblendet markiert. Es wird nicht in öffentlichen Protokollen angezeigt. Du kannst weiterhin einzelne Nachrichten unten ein-/ausblenden oder löschen.', 'psource-chat' ); ?></p>
						</div><?php
					}

					?>
					<form id="psource-chat-edit-listing" action="" method="get">
						<input type="hidden" name="page" value="chat_session_logs"/>
						<?php if ( isset( $_GET['chat_id'] ) ) { ?>
							<input type="hidden" name="chat_id" value="<?php echo $_GET['chat_id']; ?>"/>
						<?php } ?>
						<?php if ( isset( $_GET['lid'] ) ) { ?>
							<input type="hidden" name="lid" value="<?php echo $_GET['lid']; ?>"/>
						<?php } ?>
						<?php if ( isset( $_GET['laction'] ) ) { ?>
							<input type="hidden" name="laction" value="<?php echo $_GET['laction']; ?>"/>
						<?php } ?>
						<?php // The WP_List_table class automatically adds a _wpnonce field with the secret 'bulk-'+ args[plural] as in 'bulk-logs' or 'bulk-messages'. So no need to add another nonce field to the form?>

						<?php $psource_chat->chat_log_list_table->search_box( __( 'Nachrichten suchen' ), 'chat-search' ); ?>
						<?php $psource_chat->chat_log_list_table->display(); ?>
					</form>
				</div>
				<?php
			} else {
				?>
				<div id="psource-chat-messages-listing-panel"
				     class="wrap psource-chat-wrap psource-chat-wrap-settings-page">
					<?php //screen_icon('psource-chat'); ?>
					<h2><?php _ex( "Chat-Sitzungsprotokolle", "Page Title", 'psource-chat' ); ?></h2>

					<p><?php _ex( "", 'page description', 'psource-chat' ); ?></p>
					<?php
					//require_once( dirname(__FILE__) . '/psource_chat_admin_session_logs.php');
					//$this->_logs_table = new PSOURCEChat_Session_Logs_Table( );
					$psource_chat->chat_log_list_table->prepare_items();
					?>
					<form id="chat-edit-listing" action="?page=chat_session_logs" method="get">
						<input type="hidden" name="page" value="chat_session_logs"/>
						<?php $psource_chat->chat_log_list_table->search_box( __( 'Logs durchsuchen' ), 'chat-search' ); ?>
						<?php $psource_chat->chat_log_list_table->display(); ?>
						<?php // The WP_List_table class automatically adds a _wpnonce field with the secret 'bulk-'+ args[plural] as in 'bulk-logs' or 'bulk-messages'. So no need to add another nonce field to the form?>

					</form>
				</div>
				<?php
			}
		}

		function chat_settings_panel_network_site() {
			global $psource_chat;

			$form_section = "network-site";

			?>
			<div id="psource-chat-wrap" class="wrap psource-chat-wrap-settings-page">
				<h2><?php _e( 'Chat-Einstellungen Netzwerkseite', 'psource-chat' ); ?></h2>

				<form method="post" id="psource-chat-settings-form" action="?page=<?php echo $_GET['page']; ?>">

					<p><?php _e( 'Die folgenden Einstellungen werden verwendet, um die untere Ecke für alle Standorte in der Multisite-Umgebung zu steuern. Dieser Chat in der unteren Ecke ist global, dh, die Nachrichten sind auf allen Websites innerhalb der URLs des Multisite-Netzwerks gleich. Nach der Aktivierung ersetzt dieses Chatfeld in der unteren Ecke des Netzwerks das Chatfeld in der unteren Ecke der Site.', 'psource-chat' ); ?></p>

					<div id="chat_tab_pane" class="chat_tab_pane">
						<ul>
							<li id="chat_bottom_corner_tab"><a href="#chat_bottom_corner_panel" class="current"><span><?php
										_e( 'Untere Ecke', 'psource-chat' ); ?></span></a></li>
							<li id="chat_box_appearance_tab"><a href="#chat_box_appearance_panel" class="current"><span><?php
										_e( 'Box Aussehen', 'psource-chat' ); ?></span></a></li>
							<li id="chat_box_position_tab"><a href="#chat_box_position_panel"><span><?php
										_e( 'Box Position', 'psource-chat' ); ?></span></a></li>
							<li id="chat_messages_appearance_tab"><a href="#chat_messages_appearance_panel"><span><?php
										_e( 'Nachricht Aussehen', 'psource-chat' ); ?></span></a>
							</li>
							<li id="chat_messages_input_tab"><a href="#chat_messages_input_panel"><span><?php
										_e( 'Nachrichteneingabe', 'psource-chat' ); ?></span></a></li>
							<li id="chat_users_list_tab"><a href="#chat_users_list_panel"><span><?php
										_e( 'Benutzerliste', 'psource-chat' ); ?></span></a></li>
							<li id="chat_authentication_tab"><a href="#chat_authentication_panel"><span><?php
										_e( 'Authentifizierung', 'psource-chat' ); ?></span></a></li>
							<li id="chat_advanced_tab"><a href="#chat_advanced_panel"><span><?php
										_e( 'Erweitert', 'psource-chat' ); ?></span></a></li>
						</ul>
						<div id="chat_bottom_corner_panel" class="panel current">
							<?php psource_chat_form_section_bottom_corner( $form_section ); ?>
						</div>
						<div id="chat_box_appearance_panel" class="panel">
							<?php psource_chat_form_section_container( $form_section ); ?>
						</div>
						<div id="chat_box_position_panel" class="panel">
							<?php psource_chat_form_section_site_position( $form_section ); ?>
						</div>
						<div id="chat_messages_appearance_panel" class="panel">
							<?php psource_chat_form_section_messages_wrapper( $form_section ); ?>
							<?php psource_chat_form_section_messages_rows( $form_section ); ?>
						</div>
						<div id="chat_messages_input_panel" class="panel">
							<?php psource_chat_form_section_messages_input( $form_section ); ?>
							<?php psource_chat_form_section_messages_send_button( $form_section ); ?>
						</div>
						<div id="chat_users_list_panel" class="panel">
							<?php psource_chat_users_list( $form_section ); ?>
							<?php psource_chat_form_section_user_enter_exit_messages( $form_section ); ?>
						</div>
						<div id="chat_authentication_panel" class="chat_panel">
							<?php psource_chat_form_section_login_options( $form_section ); ?>
							<?php //psource_chat_form_section_login_view_options($form_section); ?>
							<?php psource_chat_form_section_moderator_roles( $form_section ); ?>
						</div>
						<div id="chat_advanced_panel" class="chat_panel">
							<?php //psource_chat_form_section_logs($form_section); ?>
							<?php psource_chat_form_section_logs_limit( $form_section ); ?>
							<?php psource_chat_form_section_session_messages( $form_section ); ?>

							<?php if ( $psource_chat->get_option( 'blocked_ip_addresses_active', 'global' ) == "enabled" ) {
								psource_chat_form_section_blocked_ip_addresses( $form_section );
							} ?>

						</div>
					</div>
					<input type="hidden" name="chat[section]" value="<?php echo $form_section; ?>"/>
					<?php wp_nonce_field( 'psource_chat_settings_save', 'psource_chat_settings_save_wpnonce' ); ?>
					<p class="submit"><input type="submit" name="Submit" class="button-primary"
					                         value="<?php _e( 'Änderungen speichern', 'psource-chat' ) ?>"/>
					</p>

				</form>
			</div>
			<?php
		}

		function chat_settings_panel_dashboard() {
			global $psource_chat;

			$form_section = "dashboard";

			?>
			<div id="psource-chat-wrap" class="wrap psource-chat-wrap-settings-page">
				<?php if ( is_network_admin() ) { ?>
					<h2><?php _e( 'Chat-Einstellungen Netzwerk-Dashboard-Widgets', 'psource-chat' ); ?></h2>
				<?php } else { ?>
					<h2><?php _e( 'Chat-Einstellungen Dashboard-Widgets', 'psource-chat' ); ?></h2>
				<?php } ?>
				<form method="post" id="psource-chat-settings-form" action="?page=<?php echo $_GET['page']; ?>">

					<?php if ( is_network_admin() ) { ?>
						<p><?php _e( 'Dieser Abschnitt steuert die Sichtbarkeit von Netzwerk-Dashboard-Chat-Widgets', 'psource-chat' ); ?></p>
					<?php } else { ?>
						<p><?php _e( 'Dieser Abschnitt steuert die Sichtbarkeit von Dashboard-Chat-Widgets', 'psource-chat' ); ?></p>
					<?php } ?>
					<div id="chat_tab_pane" class="chat_tab_pane">
						<ul>
							<li id="chat_widgets_tab"><a href="#chat_widgets_panel" class="current"><span><?php
										_e( 'Widgets', 'psource-chat' ); ?></span></a></li>

							<li id="chat_box_appearance_tab"><a href="#chat_box_appearance_panel"><span><?php
										_e( 'Box Aussehen', 'psource-chat' ); ?></span></a></li>
							<li id="chat_messages_appearance_tab"><a href="#chat_messages_appearance_panel"><span><?php
										_e( 'Nachricht Aussehen', 'psource-chat' ); ?></span></a>
							</li>
							<li id="chat_messages_input_tab"><a href="#chat_messages_input_panel"><span><?php
										_e( 'Nachrichteneingabe', 'psource-chat' ); ?></span></a></li>
							<li id="chat_users_list_tab"><a href="#chat_users_list_panel"><span><?php
										_e( 'Benutzerliste', 'psource-chat' ); ?></span></a></li>

							<?php if ( ! is_network_admin() ) { ?>
								<li id="chat_authentication_tab"><a href="#chat_authentication_panel"><span><?php
											_e( 'Authentifizierung', 'psource-chat' ); ?></span></a>
								</li>
							<?php } ?>
							<li id="chat_advanced_tab"><a href="#chat_advanced_panel"><span><?php
										_e( 'Erweitert', 'psource-chat' ); ?></span></a></li>
						</ul>
						<div id="chat_widgets_panel" class="panel current">
							<?php psource_chat_form_section_dashboard( $form_section ); ?>
						</div>

						<div id="chat_box_appearance_panel" class="panel">
							<?php psource_chat_form_section_container( $form_section ); ?>
						</div>
						<div id="chat_messages_appearance_panel" class="panel">
							<?php psource_chat_form_section_messages_wrapper( $form_section ); ?>
							<?php psource_chat_form_section_messages_rows( $form_section ); ?>
						</div>
						<div id="chat_messages_input_panel" class="panel">
							<?php psource_chat_form_section_messages_input( $form_section ); ?>
							<?php psource_chat_form_section_messages_send_button( $form_section ); ?>
						</div>
						<div id="chat_users_list_panel" class="panel">
							<?php psource_chat_users_list( $form_section ); ?>
							<?php psource_chat_form_section_user_enter_exit_messages( $form_section ); ?>
						</div>

						<?php if ( ! is_network_admin() ) { ?>
							<div id="chat_authentication_panel" class="chat_panel">
								<?php psource_chat_form_section_login_options( $form_section ); ?>
								<?php //psource_chat_form_section_login_view_options($form_section); ?>
								<?php psource_chat_form_section_moderator_roles( $form_section ); ?>
							</div>
						<?php } ?>
						<div id="chat_advanced_panel" class="chat_panel">
							<?php psource_chat_form_section_logs( $form_section ); ?>
							<?php psource_chat_form_section_logs_limit( $form_section ); ?>
						</div>
					</div>
					<input type="hidden" name="chat[section]" value="<?php echo $form_section; ?>"/>
					<?php wp_nonce_field( 'psource_chat_settings_save', 'psource_chat_settings_save_wpnonce' ); ?>
					<p class="submit"><input type="submit" name="Submit" class="button-primary"
					                         value="<?php _e( 'Änderungen speichern', 'psource-chat' ) ?>"/>
					</p>

				</form>
			</div>
			<?php
		}

	}
}