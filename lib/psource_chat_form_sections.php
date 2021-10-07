<?php

function psource_chat_form_section_logs( $form_section = 'page' ) {
	global $psource_chat, $wp_roles;
	?>
	<fieldset>
		<legend><?php _e( "Chatprotokolle", $psource_chat->translation_domain ); ?></legend>

		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column">
					<label for="chat_log_creation"><?php _e( "Protokollerstellung", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select id="chat_log_creation" name="chat[log_creation]">
						<option value="enabled" <?php print ( $psource_chat->get_option( 'log_creation', $form_section ) == 'enabled' ) ? 'selected="selected"' : ''; ?>><?php _e( "Aktiviert", $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php print ( $psource_chat->get_option( 'log_creation', $form_section ) == 'disabled' ) ? 'selected="selected"' : ''; ?>><?php _e( "Deaktivert", $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'log_creation', 'tip' ); ?></td>
			</tr>
			<?php if ( ( $form_section != "widget" ) && ( $form_section != 'dashboard' ) ) { ?>
				<tr>
					<td class="chat-label-column">
						<label for="chat_log_display"><?php _e( "Protokollanzeige", $psource_chat->translation_domain ); ?></label>
					</td>
					<td class="chat-value-column">
						<select id="chat_log_display" name="chat[log_display]">
							<option value="disabled" <?php print ( $psource_chat->get_option( 'log_display', $form_section ) == 'disabled' ) ? 'selected="selected"' : ''; ?>><?php _e( "Deaktiviert", $psource_chat->translation_domain ); ?></option>
							<optgroup label="<?php _e( 'Link zur Protokollseite.', $psource_chat->translation_domain ); ?>">
								<option value="enabled-list-above" <?php print ( $psource_chat->get_option( 'log_display', $form_section ) == 'enabled-list-above' ) ? 'selected="selected"' : ''; ?>><?php _e( "Aktiviert - Auflistung über dem Chat", $psource_chat->translation_domain ); ?></option>
								<option value="enabled-list-below" <?php print ( $psource_chat->get_option( 'log_display', $form_section ) == 'enabled-list-below' ) ? 'selected="selected"' : ''; ?>><?php _e( "Aktiviert - Auflistung unter dem Chat", $psource_chat->translation_domain ); ?></option>
							</optgroup>
							<optgroup label="<?php _e( 'Links shown on chat page', $psource_chat->translation_domain ); ?>">
								<option value="enabled-link-above" <?php print ( $psource_chat->get_option( 'log_display', $form_section ) == 'enabled-link-above' ) ? 'selected="selected"' : ''; ?>><?php _e( "Aktiviert - Auflistung über dem Chat", $psource_chat->translation_domain ); ?></option>
								<option value="enabled-link-below" <?php print ( $psource_chat->get_option( 'log_display', $form_section ) == 'enabled-link-below' ) ? 'selected="selected"' : ''; ?>><?php _e( "Aktiviert - Auflistung unter dem Chat", $psource_chat->translation_domain ); ?></option>
							</optgroup>
						</select>
					</td>
					<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'log_display', 'tip' ); ?></td>
				</tr>
				<tr>
					<td class="chat-label-column">
						<label for="chat_log_display_label"><?php _e( "Chatprotokoll Label", $psource_chat->translation_domain ); ?></label>
					</td>
					<td class="chat-value-column">
						<input type="text" id="chat_log_display_label" name="chat[log_display_label]"
							value="<?php print $psource_chat->get_option( 'log_display_label', $form_section ); ?>"/>
					</td>
					<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'log_display_label', 'tip' ); ?></td>
				</tr>
				<tr>
					<td class="chat-label-column">
						<label for="chat_log_display_limit"><?php _e( "Anzahl der Archiveinträge, die in der Liste angezeigt werden sollen", $psource_chat->translation_domain ); ?></label>
					</td>
					<td class="chat-value-column">
						<input type="text" id="chat_log_display_limit" name="chat[log_display_limit]"
							value="<?php print $psource_chat->get_option( 'log_display_limit', $form_section ); ?>"/>
					</td>
					<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'log_display_limit', 'tip' ); ?></td>
				</tr>
				<tr>
					<td class="chat-label-column">
						<label for="chat_log_display_hide_session"><?php _e( "Beim Anzeigen von Archivdetails. Hauptchat ein-/ausblenden?", $psource_chat->translation_domain ); ?></label>
					</td>
					<td class="chat-value-column">
						<select id="chat_log_display_hide_session" name="chat[log_display_hide_session]">
							<option value="show" <?php print ( $psource_chat->get_option( 'log_display_hide_session', $form_section ) == 'show' ) ? 'selected="selected"' : ''; ?>><?php _e( "Zeigen", $psource_chat->translation_domain ); ?></option>
							<option value="hide" <?php print ( $psource_chat->get_option( 'log_display_hide_session', $form_section ) == 'hide' ) ? 'selected="selected"' : ''; ?>><?php _e( "Verbergen", $psource_chat->translation_domain ); ?></option>
						</select>
					</td>
					<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'log_display_hide_session', 'tip' ); ?></td>
				</tr>

				<?php
				$log_display_levels = array();
				if ( $form_section == "bp-group" ) {
					$log_display_levels['group_members'] = __( "Gruppenmitglieder", $psource_chat->translation_domain );
					$log_display_levels['group_mods']    = __( "Gruppenmods und Admins", $psource_chat->translation_domain );
					$log_display_levels['group_admins']  = __( "Nur Gruppenadmins", $psource_chat->translation_domain );

					if ( count( $log_display_levels ) ) {
						?>
						<tr>
							<td class="chat-label-column">
								<label for="chat_log_display_role_level"><?php _e( "Beschränke die Anzeige nach Gruppenmitgliedsebene",
										$psource_chat->translation_domain ); ?></label></td>
							<td class="chat-value-column">
								<select id="chat_log_display_role_level" name="chat[log_display_role_level]">
									<?php
									foreach ( $log_display_levels as $role_level_key => $role_level_display ) {
										$selected = '';
										if ( $role_level_key == $psource_chat->get_option( 'log_display_role_level', $form_section ) ) {
											$selected = ' selected="selected" ';
										}
										?>
										<option <?php echo $selected; ?> value="<?php echo $role_level_key; ?>"><?php
										echo $role_level_display ?></option><?php
									}
									?>
								</select>
							</td>
							<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'log_display_bp_level', 'tip' ); ?></td>
						</tr>
					<?php
					}

				} else {
					/*if ( count( $wp_roles ) ) { //php8 fix */
					if ( count( array($wp_roles ) ) ) {
						foreach ( $wp_roles->roles as $role_slug => $role ) {
							$role_level = psource_chat_get_user_role_highest_level( $role['capabilities'] );
							if ( ! isset( $log_display_levels[ 'level_' . $role_level ] ) ) {
								$log_display_levels[ 'level_' . $role_level ] = "Level " . $role_level . ": " . $role['name'];
							} else {
								$log_display_levels[ 'level_' . $role_level ] .= ", " . $role['name'];
							}
						}
					}
					ksort( $log_display_levels, SORT_NUMERIC );
					krsort( $log_display_levels, SORT_NUMERIC );
					//echo "log_display_levels<pre>"; print_r($log_display_levels); echo "</pre>";
					//echo "selected_role_level [". $psource_chat->get_option('log_display_role_level', $form_section) ."]<br />";
					if ( count( $log_display_levels ) ) {
						?>
						<tr>
							<td class="chat-label-column">
								<label for="chat_log_display_role_level"><?php _e( "Beschränke die Anzeige nach Benutzerrollenebene",
										$psource_chat->translation_domain ); ?></label></td>
							<td class="chat-value-column">
								<select id="chat_log_display_role_level" name="chat[log_display_role_level]">
									<optgroup label="<?php _e( 'WordPress-Benutzerrollenebenen', $psource_chat->translation_domain ); ?>">
										<?php
										foreach ( $log_display_levels as $role_level_key => $role_level_display ) {
											$selected = '';
											if ( $role_level_key == $psource_chat->get_option( 'log_display_role_level', $form_section ) ) {
												$selected = ' selected="selected" ';
											}
											?>
											<option <?php echo $selected; ?> value="<?php echo $role_level_key; ?>"><?php
											echo $role_level_display ?></option><?php
										}
										?>
									</optgroup>
									<option value="public" <?php print ( $psource_chat->get_option( 'log_display_role_level',
											$form_section ) == 'public' ) ? 'selected="selected"' : ''; ?>><?php _e( "Öffentlich, Facebook, Twitter",
											$psource_chat->translation_domain ); ?></option>
								</select>
							</td>
							<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'log_display_role_level', 'tip' ); ?></td>
						</tr>
					<?php
					}
				}
			}

			?>
		</table>
	</fieldset>
<?php
}

function psource_chat_form_section_logs_limit( $form_section = 'page' ) {
	global $psource_chat;

	?>
	<fieldset>
		<legend><?php _e( "Nachrichten Anzeigelimit", $psource_chat->translation_domain ); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column chat-label-column-top">
					<label for="chat_log_limit"><?php _e( "Begrenzt Nachrichten anzeigen", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" id="chat_log_limit" name="chat[log_limit]"
						value="<?php print $psource_chat->get_option( 'log_limit', $form_section ); ?>"/><br/>
					<span class="description"><?php _e( "Standard 100. Für alle leer lassen.", $psource_chat->translation_domain ); ?></span>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'log_limit', 'tip' ); ?></td>
			</tr>
		</table>
	</fieldset>
<?php
}

function psource_chat_form_section_session_messages( $form_section = "page" ) {
	global $psource_chat;

	?>
	<fieldset>
		<legend><?php _e( "Sitzungsnachricht", $psource_chat->translation_domain ); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column">
					<label for="chat_session_status_message"><?php _e( "Sitzung geschlossen Nachricht", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" name="chat[session_status_message]" id="chat_session_status_message"
						value="<?php print $psource_chat->get_option( 'session_status_message', $form_section ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'session_status_message', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_session_cleared_message"><?php _e( "Sitzung gelöscht Nachricht", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" name="chat[session_cleared_message]" id="chat_session_cleared_message"
						value="<?php print $psource_chat->get_option( 'session_cleared_message', $form_section ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'session_cleared_message', 'tip' ); ?></td>
			</tr>
           
			
		<tr> 
			<td class="chat-label-column">
				<label for="chat_session_status_auto_close"><?php _e("Wird automatisch geschlossen, wenn kein Moderator anwesend ist.", $psource_chat->translation_domain); ?></label>
			</td>
			<td class="chat-value-column">
				<select id="chat_session_status_auto_close" name="chat[session_status_auto_close]" >
					<option value="yes" <?php print ($psource_chat->get_option('session_status_auto_close', $form_section) == 'yes')?'selected="selected"':''; ?>><?php _e("JA", $psource_chat->translation_domain); ?></option>
					<option value="no" <?php print ($psource_chat->get_option('session_status_auto_close', $form_section) == 'no')?'selected="selected"':''; ?>><?php _e("NEIN", $psource_chat->translation_domain); ?></option>
				</select>

			</td>
			<td class="chat-help-column"><?php echo psource_chat_get_help_item('session_status_auto_close', 'tip'); ?></td>
		</tr>
<?php 
			?>
		</table>
	</fieldset>
<?php
}

function psource_chat_form_section_fonts( $form_section = 'page' ) {
	global $psource_chat;

	?>
	<fieldset>
		<legend><?php _e( "Schriften", $psource_chat->translation_domain ); ?></legend>

		<table border="0" cellpadding="4" cellspacing="0">

			<tr>
				<td class="chat-label-column">
					<label for="chat_font_family"><?php _e( "Schrift", $psource_chat->translation_domain ); ?></label></td>
				<td class="chat-value-column">
					<select id="chat_font_family" name="chat[font_family]">
						<option value=""><?php _e( "-- Vererbt vom Thema --", $psource_chat->translation_domain ); ?></option>
						<?php foreach ( $psource_chat->_chat_options_defaults['fonts_list'] as $font_name => $font ) { ?>
							<option value="<?php print $font; ?>" <?php print ( $psource_chat->get_option( 'font_family', $form_section ) == $font ) ? 'selected="selected"' : ''; ?> ><?php print $font_name; ?></option>
						<?php } ?>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'font_family', 'tip' ); ?></td>
			</tr>

			<tr>
				<td class="chat-label-column">
					<label for="chat_font_size"><?php _e( "Schriftgröße", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<?php $font_size = trim( $psource_chat->get_option( 'font_size', $form_section ) ); ?>
					<input type="text" name="chat[font_size]" id="chat_font_size"
						value="<?php echo ( ! empty( $font_size ) ) ? psource_chat_check_size_qualifier( $font_size ) : ''; ?>"
						placeholder="<?php echo psource_chat_get_help_item( 'font_size', 'placeholder' ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'font_size', 'tip' ); ?></td>
			</tr>
		</table>
	</fieldset>
<?php
}

function psource_chat_form_section_site_position( $form_section = 'site' ) {
	global $psource_chat;
	//echo "chat<pre>"; print_r($psource_chat); echo "</pre>";

	?>
	<fieldset>
		<legend><?php _e( "Chat Box Position", $psource_chat->translation_domain ); ?></legend>
		<p class="info"><?php _e( 'Steuert die Position der Seiten-Chat-Felder. Diese Felder enthalten den Chat in der unteren Ecke sowie private Chats. Wenn Du rechts auswählst, werden die einzelnen Chat-Felder von rechts nach links angezeigt.', $psource_chat->translation_domain ); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column">
					<label for="chat_box_position_h"><?php _e( "Position Horizontal", $psource_chat->translation_domain ); ?></label><br/>
				</td>
				<td class="chat-value-column">
					<select id="chat_box_position_h" name="chat[box_position_h]">
						<option value="right" <?php print ( $psource_chat->get_option( 'box_position_h', $form_section ) == 'right' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Rechts', $psource_chat->translation_domain ); ?></option>
						<option value="left" <?php print ( $psource_chat->get_option( 'box_position_h', $form_section ) == 'left' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Links', $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_position_h', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_box_position_v"><?php _e( "Position Vertikal", $psource_chat->translation_domain ); ?></label><br/>
				</td>
				<td class="chat-value-column">
					<select id="chat_box_position_v" name="chat[box_position_v]">
						<option value="top" <?php print ( $psource_chat->get_option( 'box_position_v', $form_section ) == 'top' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Oben', $psource_chat->translation_domain ); ?></option>
						<option value="bottom" <?php print ( $psource_chat->get_option( 'box_position_v', $form_section ) == 'bottom' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Unten', $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_position_v', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_box_position_mobile"><?php _e( "Position für Mobile Endgeräte", $psource_chat->translation_domain ); ?></label><br/>
				</td>
				<td class="chat-value-column">
					<select id="chat_box_position_adjust_mobile" name="chat[box_position_adjust_mobile]">
						<option value="enabled" <?php selected( $psource_chat->get_option( 'box_position_adjust_mobile', $form_section ), 'enabled' ); ?>><?php _e( 'Aktiviert', $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php selected( $psource_chat->get_option( 'box_position_adjust_mobile', $form_section ), 'disabled' ); ?>><?php _e( 'Deaktiviert', $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_position_adjust_mobile', 'tip' ); ?></td>
			</tr>
		</table>
	</fieldset>

	<fieldset>
		<legend><?php _e( "Positionsversatz der Chatbox", $psource_chat->translation_domain ); ?></legend>
		<p class="info"><?php _e( 'Ermöglicht das Bereitstellen von Offsets, um die Chat-Boxen vom Standardrand des Bildschirms zu entfernen. Wenn Du beispielsweise die Position oben nach unten/rechts setzt, kann dies zu Konflikten mit anderen Fußzeilenleisten mit fester Position führen. Du kannst also den vertikalen Versatz unten auf 20 Pixel oder einen bestimmten Wert einstellen, um zu verhindern, dass andere feste Elemente ausgeblendet werden. ', $psource_chat->translation_domain ); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column">
					<label for="chat_box_offset_h"><?php _e( "Position für linke oder rechte Ecke", $psource_chat->translation_domain ); ?></label><br/>
				</td>
				<td class="chat-value-column">
					<input type="text" name="chat[box_offset_h]" id="chat_box_offset_h"
						value="<?php echo psource_chat_check_size_qualifier( $psource_chat->get_option( 'box_offset_h', $form_section ), array( 'px' ) ); ?>"
						placeholder="<?php echo psource_chat_get_help_item( 'box_offset_h', 'placeholder' ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_offset_h', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_box_offset_v"><?php _e( "Position zu der Ober-/Unterkante", $psource_chat->translation_domain ); ?></label><br/>
				</td>
				<td class="chat-value-column">
					<input type="text" name="chat[box_offset_v]" id="chat_box_offset_v"
						value="<?php echo psource_chat_check_size_qualifier( $psource_chat->get_option( 'box_offset_v', $form_section ), array( 'px' ) ); ?>"
						placeholder="<?php echo psource_chat_get_help_item( 'chat_box_offset_v', 'placeholder' ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_offset_v', 'tip' ); ?></td>
			</tr>
		</table>
	</fieldset>


	<fieldset>
		<legend><?php _e( "Chat Box Abstand", $psource_chat->translation_domain ); ?></legend>
		<p class="info"><?php _e( 'Steuert den horizontalen Abstand zwischen mehreren Chat-Feldern', $psource_chat->translation_domain ); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column">
					<label for="chat_box_spacing_h"><?php _e( "Abstand", $psource_chat->translation_domain ); ?></label><br/>
				</td>
				<td class="chat-value-column">
					<input type="text" name="chat[box_spacing_h]" id="chat_box_spacing_h"
						value="<?php print $psource_chat->get_option( 'box_spacing_h', $form_section ); ?>"
						placeholder="<?php echo psource_chat_get_help_item( 'box_spacing_h', 'placeholder' ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_spacing_h', 'tip' ); ?></td>
			</tr>
		</table>
	</fieldset>
	<?php  ?>
	<fieldset>
		<legend><?php _e("Chat Box Größe veränderbar", $psource_chat->translation_domain); ?></legend>
		<p class="info"><?php _e('Ermöglicht Benutzern das Ändern der Größe der Seiten-Chat-Felder. Dadurch werden die JavaScript-Bibliotheken der jQuery-Benutzeroberfläche geladen, was sich auf die Ladezeit der Seite und/oder auf Konflikte mit Deinem Theme auswirken kann.', $psource_chat->translation_domain); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column">
				<label for="chat_box_spacing_h"><?php _e("Die Größe des Chat-Fensters kann geändert werden", $psource_chat->translation_domain); ?></label><br />
			</td>
			<td class="chat-value-column">
				<select id="chat_box_resizable" name="chat[box_resizable]">
					<option value="enabled" <?php print ($psource_chat->get_option('box_resizable', $form_section) == 'enabled')?'selected="selected"':'';
						?>><?php _e('Aktiviert', $psource_chat->translation_domain); ?></option>
					<option value="disabled" <?php print ($psource_chat->get_option('box_resizable', $form_section) == 'disabled')?'selected="selected"':'';
						?>><?php _e('Deaktiviert', $psource_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo psource_chat_get_help_item('box_resizable', 'tip'); ?></td>
		</tr>
		</table>
	</fieldset>
<?php 
	?>
	<fieldset>
		<legend><?php _e( "Chat Box Schatten", $psource_chat->translation_domain ); ?></legend>
		<p class="info"><?php _e( 'Steuert den horizontalen Abstand zwischen mehreren Chat-Feldern', $psource_chat->translation_domain ); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column">
					<label for="chat_box_shadow_show"><?php _e( "Anzeige Box Schatten", $psource_chat->translation_domain ); ?></label><br/>
				</td>
				<td class="chat-value-column">
					<select id="chat_box_shadow_show" name="chat[box_shadow_show]">
						<option value="enabled" <?php print ( $psource_chat->get_option( 'box_shadow_show', $form_section ) == 'enabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Aktiviert', $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php print ( $psource_chat->get_option( 'box_shadow_show', $form_section ) == 'disabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Deaktiviert', $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_shadow_show', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_box_shadow_v"><?php _e( "Vertikale rechte Ecke", $psource_chat->translation_domain ); ?></label><br/>
				</td>
				<td class="chat-value-column">
					<input type="text" name="chat[box_shadow_v]" id="chat_box_shadow_v"
						value="<?php echo psource_chat_check_size_qualifier( $psource_chat->get_option( 'box_shadow_v', $form_section ), array( 'px' ) ); ?>"
						placeholder="<?php echo psource_chat_get_help_item( 'box_shadow_v', 'placeholder' ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_shadow_v', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_box_shadow_h"><?php _e( "Horizontale Unterkante", $psource_chat->translation_domain ); ?></label><br/>
				</td>
				<td class="chat-value-column">
					<input type="text" name="chat[box_shadow_h]" id="chat_box_shadow_h"
						value="<?php echo psource_chat_check_size_qualifier( $psource_chat->get_option( 'box_shadow_h', $form_section ), array( 'px' ) ); ?>"
						placeholder="<?php echo psource_chat_get_help_item( 'box_shadow_h', 'placeholder' ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_shadow_h', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_box_shadow_blur"><?php _e( "Schattenschärfe", $psource_chat->translation_domain ); ?></label><br/>
				</td>
				<td class="chat-value-column">
					<input type="text" name="chat[box_shadow_blur]" id="chat_box_shadow_blur"
						value="<?php echo psource_chat_check_size_qualifier( $psource_chat->get_option( 'box_shadow_blur', $form_section ), array( 'px' ) ); ?>"
						placeholder="<?php echo psource_chat_get_help_item( 'box_shadow_blur', 'placeholder' ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_shadow_blur', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_box_shadow_spread"><?php _e( "Schattenausbreitung", $psource_chat->translation_domain ); ?></label><br/>
				</td>
				<td class="chat-value-column">
					<input type="text" name="chat[box_shadow_spread]" id="chat_box_shadow_spread"
						value="<?php echo psource_chat_check_size_qualifier( $psource_chat->get_option( 'box_shadow_spread', $form_section ), array( 'px' ) ); ?>"
						placeholder="<?php echo psource_chat_get_help_item( 'box_shadow_spread', 'placeholder' ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_shadow_spread', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_box_shadow_color"><?php _e( 'Schattenfarbe',
							$psource_chat->translation_domain ); ?></label></td>
				<td class="chat-value-column">
					<input type="text" id="chat_box_shadow_color" name="chat[box_shadow_color]" class="pickcolor_input"
						value="<?php echo $psource_chat->get_option( 'box_shadow_color', $form_section ); ?>"
						data-default-color="<?php echo $psource_chat->get_option( 'box_shadow_color', $form_section ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_shadow_color', 'tip' ); ?></td>
			</tr>
		</table>
	</fieldset>

<?php
}

function psource_chat_form_section_login_options( $form_section = 'page' ) {
	global $psource_chat, $wp_roles;

	?>
	<fieldset>
		<legend><?php _e( "Anmeldeoptionen", $psource_chat->translation_domain ); ?> - <?php
			_e( "Authentifizierungsmethoden, die Benutzer verwenden können", $psource_chat->translation_domain ); ?></legend>

		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column-wide">
					<p class="info">
						<strong><?php _e( "Für WordPress-Benutzer anzeigen. Schließt automatisch Admin- und SuperAdmin-Benutzer ein. Der Benutzer muss bereits authentifiziert sein.", $psource_chat->translation_domain ); ?></strong>
					</p>
					<ul id="psource-chat-login-options-list-wp" class="psource-chat-user-roles-list">

						<?php
						/*if ( count( $wp_roles ) ) { //fix php8 */
						if ( count( array($wp_roles ) ) ) {
							foreach ( $wp_roles->roles as $role_slug => $role ) {
								$checked  = '';
								$disabled = '';

								if ( isset( $role['capabilities']['level_10'] ) ) {
									//if (is_multisite())
									//	$role['name'] .= ' - '. __('Includes Super Admins', $psource_chat->translation_domain);
									$checked  = ' checked="checked" ';
									$disabled = ' disabled="disabled" ';

								} else if ( in_array( $role_slug, $psource_chat->get_option( 'login_options', $form_section ) ) !== false ) {
									$checked = ' checked="checked" ';
								} else if ( in_array( 'current_user', $psource_chat->get_option( 'login_options', $form_section ) ) !== false ) {
									$checked = ' checked="checked" ';
								}
								?>
								<li><input type="checkbox" id="chat_login_options_<?php echo $role_slug; ?>"
									<?php echo $checked; ?> <?php echo $disabled; ?>
									name="chat[login_options][]" class="chat_login_options" value="<?php print $role_slug; ?>"
									/> <label><?php echo $role['name']; ?></label></li><?php
							}
						}
						?>
					</ul>

					<?php if ( ( is_multisite() ) && ( $form_section != "network-site" ) ) { ?>
						<p class="info">
							<strong><?php _e( "Für Netzwerkbenutzer anzeigen. Wenn das Kontrollkästchen deaktiviert ist, können nur WordPress-Benutzer mit Zugriff auf den aktuellen Blog die Chat-Sitzung sehen.", $psource_chat->translation_domain ); ?></strong>
						</p>
						<ul id="psource-chat-login-options-list-network" class="psource-chat-user-roles-list">
							<li>
								<input type="checkbox" id="chat_login_options_network"
									name="chat[login_options][]" class="chat_login_options" value="network"
									<?php print ( in_array( 'network', $psource_chat->get_option( 'login_options', $form_section ) ) > 0 ) ? 'checked="checked"' : ''; ?>
									/> <label><?php _e( 'Netzwerkbenutzer',
										$psource_chat->translation_domain ); ?></label>
							</li>
						</ul>
					<?php } ?>
					<?php if ( $form_section != 'dashboard' ) { ?>
						<p class="info">
							<strong><?php _e( "Andere Anmeldeoptionen:", $psource_chat->translation_domain ); ?></strong>
						</p>
						<ul id="psource-chat-login-options-list-other" class="psource-chat-user-roles-list">

							<li><input type="checkbox" id="chat_login_options_public_user"
									name="chat[login_options][]" class="chat_login_options" value="public_user"
									<?php print ( in_array( 'public_user', $psource_chat->get_option( 'login_options', $form_section ) ) > 0 ) ? 'checked="checked"' : ''; ?>
									/> <label><?php _e( 'Öffentliche Benutzer', $psource_chat->translation_domain ); ?></label>
							</li>

							<?php if ( $form_section != "network-site" ) { ?>
								<li><input type="checkbox" id="chat_login_options_facebook"
										name="chat[login_options][]" class="chat_login_options" value="facebook"
										<?php print ( ! $psource_chat->is_facebook_setup() ) ? 'disabled="disabled"' : ''; ?>
										<?php print ( in_array( 'facebook', $psource_chat->get_option( 'login_options', $form_section ) ) > 0 ) ? 'checked="checked"' : ''; ?> />
									<label><?php _e( 'Facebook', $psource_chat->translation_domain ); ?></label> <a
										href="admin.php?page=chat_settings_panel_global#psource_chat_facebook_panel"><?php
										_e( 'Einrichten', $psource_chat->translation_domain ) ?></a></li>
							<?php } ?>
						</ul>
					<?php } ?>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'login_options', 'tip' ); ?></td>
			</tr>
		</table>
	</fieldset>
<?php
}

function psource_chat_form_section_information( $form_section = 'page' ) {
	global $psource_chat;

	?>
	<fieldset>
		<legend><?php _e( 'Chat Box Information', $psource_chat->translation_domain ); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column">
					<label for="chat_box_title"><?php _e( "Titel", $psource_chat->translation_domain ); ?></label></td>
				<td class="chat-value-column">
					<input type="text" id="chat_box_title" name="chat[box_title]" value="<?php echo $psource_chat->get_option( 'box_title', $form_section ); ?>"
						size="5" placeholder="<?php echo psource_chat_get_help_item( 'box_title', 'placeholder' ); ?>"/>

					<?php /* ?><p class="info"><?php _e('Title will be displayed in chat bar above messages', $psource_chat->translation_domain); ?></p><?php */ ?>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_title', 'tip' ); ?></td>
			</tr>
		</table>
	</fieldset>
<?php
}

function psource_chat_form_section_container( $form_section = 'page' ) {
	global $psource_chat;
	?>
	<fieldset>
		<legend><?php _e( 'Chat Box Container', $psource_chat->translation_domain ); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column">
					<label for="chat_box_width"><?php _e( "Breite", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" id="chat_box_width" name="chat[box_width]" class="size psource-chat-input-with-select" size="5"
						value="<?php echo psource_chat_check_size_qualifier( $psource_chat->get_option( 'box_width', $form_section ) ); ?>"
						placeholder="<?php echo psource_chat_get_help_item( 'box_width', 'placeholder' ); ?>"/>

					<select id="chat_box_width_mobile_adjust" name="chat[box_width_mobile_adjust]" class="psource-chat-input-with-select">
						<option value=""><?php _e( "-- Anpassen für Mobile Endgeräte --", $psource_chat->translation_domain ); ?></option>
						<option value="window" <?php selected( $psource_chat->get_option( 'box_width_mobile_adjust', $form_section ), 'window' ) ?> ><?php _e( 'Window width', $psource_chat->translation_domain ); ?></option>
						<option value="full" <?php selected( $psource_chat->get_option( 'box_width_mobile_adjust', $form_section ), 'full' ) ?> ><?php _e( 'Full width', $psource_chat->translation_domain ); ?></option>
					</select>

				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_width', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_box_height"><?php _e( "Höhe", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" id="chat_box_height" name="chat[box_height]" class="size psource-chat-input-with-select" size="5"
						value="<?php echo psource_chat_check_size_qualifier( $psource_chat->get_option( 'box_height', $form_section ) ); ?>"
						placeholder="<?php echo psource_chat_get_help_item( 'box_height', 'placeholder' ); ?>"/>

					<select id="chat_box_height_mobile_adjust" name="chat[box_height_mobile_adjust]" class="psource-chat-input-with-select">
						<option value=""><?php _e( "-- Anpassen für Mobile Endgeräte --", $psource_chat->translation_domain ); ?></option>
						<option value="window" <?php selected( $psource_chat->get_option( 'box_height_mobile_adjust', $form_section ), 'window' ) ?> ><?php _e( 'Fensterhöhe', $psource_chat->translation_domain ); ?></option>
						<option value="full" <?php selected( $psource_chat->get_option( 'box_height_mobile_adjust', $form_section ), 'full' ) ?> ><?php _e( 'Volle Höhe', $psource_chat->translation_domain ); ?></option>
					</select>

				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_height', 'tip' ); ?></td>
			</tr>


			<tr>
				<td class="chat-label-column">
					<label for="chat_box_font_family"><?php _e( "Schrift", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select id="chat_box_font_family" name="chat[box_font_family]">
						<option value=""><?php _e( "-- Vererbt vom Thema --", $psource_chat->translation_domain ); ?></option>
						<?php foreach ( $psource_chat->_chat_options_defaults['fonts_list'] as $font_name => $font ) { ?>
							<option value="<?php print $font_name; ?>" <?php print ( $psource_chat->get_option( 'box_font_family', $form_section ) == $font_name ) ? 'selected="selected"' : ''; ?> ><?php print $font_name; ?></option>
						<?php } ?>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_font_family', 'tip' ); ?></td>
			</tr>

			<tr>
				<td class="chat-label-column">
					<label for="chat_box_font_size"><?php _e( "Schriftgröße", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<?php $box_font_size = trim( $psource_chat->get_option( 'box_font_size', $form_section ) ); ?>
					<input type="text" name="chat[box_font_size]" id="chat_box_font_size"
						value="<?php echo ( ! empty( $box_font_size ) ) ? psource_chat_check_size_qualifier( $box_font_size ) : ''; ?>"
						placeholder="<?php echo psource_chat_get_help_item( 'box_font_size', 'placeholder' ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_font_size', 'tip' ); ?></td>
			</tr>

			<tr>
				<td class="chat-label-column">
					<label for="chat_box_text_color"><?php _e( 'Text', $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" id="chat_box_text_color" name="chat[box_text_color]" class="pickcolor_input"
						value="<?php echo $psource_chat->get_option( 'box_text_color', $form_section ); ?>"
						data-default-color="<?php echo $psource_chat->get_option( 'box_text_color', $form_section ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_text_color', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_box_background_color"><?php _e( 'Hintergrund', $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" id="chat_box_background_color" name="chat[box_background_color]" class="pickcolor_input"
						value="<?php echo $psource_chat->get_option( 'box_background_color', $form_section ); ?>"
						data-default-color="<?php echo $psource_chat->get_option( 'box_background_color', $form_section ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_background_color', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_box_border_color"><?php _e( 'Rahmenfarbe', $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" id="chat_box_border_color" name="chat[box_border_color]" class="pickcolor_input"
						value="<?php echo $psource_chat->get_option( 'box_border_color', $form_section ); ?>"
						data-default-color="<?php echo $psource_chat->get_option( 'box_border_color', $form_section ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_border_color', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column"><label for="chat_box_border_width"><?php _e( 'Rahmenbreite',
							$psource_chat->translation_domain ); ?></label></td>
				<td class="chat-value-column">
					<input type="text" id="chat_box_border_width" name="chat[box_border_width]"
						value="<?php echo $psource_chat->get_option( 'box_border_width', $form_section ); ?>"
						placeholder="<?php echo psource_chat_get_help_item( 'box_border_width', 'placeholder' ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_border_width', 'tip' ); ?></td>
			</tr>
			<?php /* ?>
		<tr>
			<td class="chat-label-column"><label for="chat_box_padding"><?php _e('Element Padding',
				$psource_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_box_padding" name="chat[box_padding]"
					value="<?php echo $psource_chat->get_option('box_padding', $form_section); ?>"
					placeholder="<?php echo psource_chat_get_help_item('box_padding', 'placeholder'); ?>" />
			</td>
			<td class="chat-help-column"><?php echo psource_chat_get_help_item('box_padding', 'tip'); ?></td>
		</tr>
<?php */
			?>
			<tr>
				<td class="chat-label-column">
					<label for="chat_box_sound"><?php _e( "Sound", $psource_chat->translation_domain ); ?></label></td>
				<td class="chat-value-column">
					<select id="chat_box_sound" name="chat[box_sound]">
						<option value="enabled" <?php print ( $psource_chat->get_option( 'box_sound', $form_section ) == 'enabled' ) ? 'selected="selected"' : ''; ?>><?php
							_e( "Aktiviert", $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php print ( $psource_chat->get_option( 'box_sound', $form_section ) == 'disabled' ) ? 'selected="selected"' : ''; ?>><?php
							_e( "Deaktiviert", $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_sound', 'tip' ); ?></td>
			</tr>

			<tr>
				<td class="chat-label-column">
					<label for="chat_box_popup"><?php _e( "Erlaube Chat Pop out/in", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select id="chat_box_popout" name="chat[box_popout]">
						<option value="enabled" <?php print ( $psource_chat->get_option( 'box_popout', $form_section ) == 'enabled' ) ? 'selected="selected"' : ''; ?>><?php
							_e( "Aktiviert", $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php print ( $psource_chat->get_option( 'box_popout', $form_section ) == 'disabled' ) ? 'selected="selected"' : ''; ?>><?php
							_e( "Deaktiviert", $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_popout', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_box_moderator_footer"><?php _e( "Moderator-Nachricht in der Fußzeile anzeigen", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select id="chat_box_moderator_footer" name="chat[box_moderator_footer]">
						<option value="enabled" <?php print ( $psource_chat->get_option( 'box_moderator_footer', $form_section ) == 'enabled' ) ? 'selected="selected"' : ''; ?>><?php
							_e( "Aktiviert", $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php print ( $psource_chat->get_option( 'box_moderator_footer', $form_section ) == 'disabled' ) ? 'selected="selected"' : ''; ?>><?php
							_e( "Deaktiviert", $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_moderator_footer', 'tip' ); ?></td>
			</tr>

			<?php /* if ($form_section == "site") { ?>
		<tr>
			<td class="chat-label-column"><label for="chat_box_new_message_color"><?php _e('New Message Border Color ', $psource_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_box_new_message_color" name="chat[box_new_message_color]" class="pickcolor_input"
					value="<?php echo $psource_chat->get_option('box_new_message_color', $form_section); ?>"
					data-default-color="<?php echo $psource_chat->get_option('box_new_message_color', $form_section); ?>" />
			</td>
			<td class="chat-help-column"><?php echo psource_chat_get_help_item('box_new_message_color', 'tip'); ?></td>
		</tr>
		<?php } */
			?>

		</table>
	</fieldset>
<?php
}

function psource_chat_form_section_messages_wrapper( $form_section = 'page' ) {
	global $psource_chat;
	?>
	<fieldset>
		<legend><?php _e( 'Chatfenster Darstellung', $psource_chat->translation_domain ); ?></legend>

		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column">
					<label for="chat_row_area_background_color"><?php _e( 'Chatfenster Hintergrund',
							$psource_chat->translation_domain ); ?></label></td>
				<td class="chat-value-column">
					<input type="text" id="chat_row_area_background_color" name="chat[row_area_background_color]" class="pickcolor_input"
						value="<?php echo $psource_chat->get_option( 'row_area_background_color', $form_section ); ?>"
						data-default-color="<?php echo $psource_chat->get_option( 'row_area_background_color', $form_section ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'row_area_background_color', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column"><label for="chat_row_background_color"><?php _e( 'Nachrichten Hintergrund',
							$psource_chat->translation_domain ); ?></label></td>
				<td class="chat-value-column">
					<input type="text" id="chat_row_background_color" name="chat[row_background_color]" class="pickcolor_input"
						value="<?php echo $psource_chat->get_option( 'row_background_color', $form_section ); ?>"
						data-default-color="<?php echo $psource_chat->get_option( 'row_background_color', $form_section ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'row_background_color', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_row_spacing"><?php _e( 'Abstand zwischen Nachrichten', $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" id="chat_row_spacing" name="chat[row_spacing]"
						value="<?php echo psource_chat_check_size_qualifier( $psource_chat->get_option( 'row_spacing', $form_section ) ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'row_spacing', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_row_border_color"><?php _e( 'Farbe des Nachrichtenrahmens', $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" id="chat_row_border_color" name="chat[row_border_color]" class="pickcolor_input"
						value="<?php echo $psource_chat->get_option( 'row_border_color', $form_section ); ?>"
						data-default-color="<?php echo $psource_chat->get_option( 'row_border_color', $form_section ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'row_border_color', 'tip' ); ?></td>
			</tr>

			<tr>
				<td class="chat-label-column"><label for="chat_row_border_width"><?php _e( 'Breite des Nachrichtenrahmens',
							$psource_chat->translation_domain ); ?></label></td>
				<td class="chat-value-column">
					<input type="text" id="chat_row_border_width" name="chat[row_border_width]"
						value="<?php echo psource_chat_check_size_qualifier( $psource_chat->get_option( 'row_border_width', $form_section ) ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'row_border_width', 'tip' ); ?></td>
			</tr>
			<?php /* ?>
		<tr>
			<td class="chat-label-column"><label for="chat_background_highlighted_color"><?php _e('Highlighted Background',
			 	$psource_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_background_highlighted_color" name="chat[background_highlighted_color]" class="pickcolor_input"
					value="<?php echo $psource_chat->get_option('background_highlighted_color', $form_section); ?>"
					data-default-color="<?php echo $psource_chat->get_option('background_highlighted_color', $form_section); ?>" />
			</td>
			<td class="chat-help-column"><?php echo psource_chat_get_help_item('background_highlighted_color', 'tip'); ?></td>
		</tr>
<?php */
			?>
		</table>
	</fieldset>
<?php
}

function psource_chat_form_section_messages_rows( $form_section = 'page' ) {
	global $psource_chat;
	?>
	<fieldset>
		<legend><?php _e( 'Chat-Nachrichtenelemente', $psource_chat->translation_domain ); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">

			<tr>
				<td class="chat-label-column">
					<label for="chat_row_font_family"><?php _e( "Schrift", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select id="chat_row_font_family" name="chat[row_font_family]">
						<option value=""><?php _e( "-- Vererbt vom Theme --", $psource_chat->translation_domain ); ?></option>
						<?php foreach ( $psource_chat->_chat_options_defaults['fonts_list'] as $font_name => $font ) { ?>
							<option value="<?php print $font_name; ?>" <?php print ( $psource_chat->get_option( 'row_font_family', $form_section ) == $font_name ) ? 'selected="selected"' : ''; ?> ><?php print $font_name; ?></option>
						<?php } ?>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'row_font_family', 'tip' ); ?></td>
			</tr>

			<tr>
				<td class="chat-label-column">
					<label for="chat_row_font_size"><?php _e( "Schriftgröße", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<?php
					$row_font_size = trim( $psource_chat->get_option( 'row_font_size', $form_section ) );
					?>
					<input type="text" name="chat[row_font_size]" id="chat_row_font_size"
						value="<?php echo ( ! empty( $row_font_size ) ) ? psource_chat_check_size_qualifier( $row_font_size ) : ''; ?>"
						placeholder="<?php echo psource_chat_get_help_item( 'row_font_size', 'placeholder' ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'row_font_size', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_row_text_color"><?php _e( 'Nachrichtentextfarbe', $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" id="chat_row_text_color" name="chat[row_text_color]" class="pickcolor_input"
						value="<?php echo $psource_chat->get_option( 'row_text_color', $form_section ); ?>"
						data-default-color="<?php echo $psource_chat->get_option( 'row_text_color', $form_section ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'row_text_color', 'tip' ); ?></td>
			</tr>
			<?php $row_name_avatar = $psource_chat->get_option( 'row_name_avatar', $form_section ); ?>
			<tr>
				<td class="chat-label-column">
					<label for="chat_row_name_avatar"><?php _e( "Zeige Avatar/Name", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select id="chat_row_name_avatar" name="chat[row_name_avatar]">
						<option value="avatar" <?php print ( $row_name_avatar == 'avatar' ) ? 'selected="selected"' : ''; ?>><?php
							_e( "Avatar", $psource_chat->translation_domain ); ?></option>
						<option value="name" <?php print ( $row_name_avatar == 'name' ) ? 'selected="selected"' : ''; ?>><?php
							_e( "Name", $psource_chat->translation_domain ); ?></option>
						<option value="name-avatar" <?php print ( $row_name_avatar == 'name-avatar' ) ? 'selected="selected"' : ''; ?>><?php
							_e( "Avatar und Name", $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php print ( $row_name_avatar == 'disabled' ) ? 'selected="selected"' : ''; ?>><?php
							_e( "Nichts", $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'row_name_avatar', 'tip' ); ?></td>
			</tr>
			<tr id="chat_row_name_color_tr" <?php if ( ( $row_name_avatar != "name" ) && ( $row_name_avatar != "name-avatar" ) ) {
				echo ' style="display:none" ';
			} ?> >
				<td class="chat-label-column">
					<label for="chat_row_name_color"><?php _e( 'Benutzername Farbe', $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" id="chat_row_name_color" name="chat[row_name_color]" class="pickcolor_input"
						value="<?php echo $psource_chat->get_option( 'row_name_color', $form_section ); ?>"
						data-default-color="<?php echo $psource_chat->get_option( 'row_name_color', $form_section ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'row_name_color', 'tip' ); ?></td>
			</tr>
			<tr id="chat_row_moderator_name_color_tr" <?php if ( ( $row_name_avatar != "name" ) && ( $row_name_avatar != "name-avatar" ) ) {
				echo ' style="display:none" ';
			} ?>>
				<td class="chat-label-column">
					<label for="chat_row_moderator_name_color"><?php _e( 'Moderator Name Farbe',
							$psource_chat->translation_domain ); ?></label></td>
				<td class="chat-value-column">
					<input type="text" id="chat_row_moderator_name_color" name="chat[row_moderator_name_color]" class="pickcolor_input"
						value="<?php echo $psource_chat->get_option( 'row_moderator_name_color', $form_section ); ?>"
						data-default-color="<?php echo $psource_chat->get_option( 'row_moderator_name_color', $form_section ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'row_moderator_name_color', 'tip' ); ?></td>
			</tr>
			<tr id="chat_row_avatar_width_tr" <?php if ( $row_name_avatar != "avatar" ) {
				echo ' style="display:none" ';
			} ?>>
				<td class="chat-label-column"><label for="chat_row_avatar_width"><?php _e( 'Benutzer-Avatar-Breite',
							$psource_chat->translation_domain ); ?></label></td>
				<td class="chat-value-column">
					<input type="text" id="chat_row_avatar_width" name="chat[row_avatar_width]"
						value="<?php echo psource_chat_check_size_qualifier( $psource_chat->get_option( 'row_avatar_width', $form_section ), array( 'px' ) ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'row_avatar_width', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_row_date"><?php _e( "Datum anzeigen", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select id="chat_row_date" name="chat[row_date]">
						<option value="enabled" <?php print ( $psource_chat->get_option( 'row_date', $form_section ) == 'enabled' ) ? 'selected="selected"' : ''; ?>><?php _e( "Aktiviert", $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php print ( $psource_chat->get_option( 'row_date', $form_section ) == 'disabled' ) ? 'selected="selected"' : ''; ?>><?php _e( "Deaktiviert", $psource_chat->translation_domain ); ?></option>
					</select>
					<input id="chat_row_date_format" type="text" name="chat[row_date_format]" value="<?php echo $psource_chat->get_option( 'row_date_format', $form_section ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'row_date', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_row_time"><?php _e( "Zeitstempel anzeigen", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select id="chat_row_time" name="chat[row_time]">
						<option value="enabled" <?php print ( $psource_chat->get_option( 'row_time', $form_section ) == 'enabled' ) ? 'selected="selected"' : ''; ?>><?php _e( "Aktiviert", $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php print ( $psource_chat->get_option( 'row_time', $form_section ) == 'disabled' ) ? 'selected="selected"' : ''; ?>><?php _e( "Deaktiviert", $psource_chat->translation_domain ); ?></option>
					</select>
					<input id="chat_row_time_format" type="text" name="chat[row_time_format]" value="<?php echo $psource_chat->get_option( 'row_time_format', $form_section ); ?>"/>

				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'row_time', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_row_date_text_color"><?php _e( 'Datum/Uhrzeit Textfarbe', $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" id="chat_row_date_text_color" name="chat[row_date_text_color]" class="pickcolor_input"
						value="<?php echo $psource_chat->get_option( 'row_date_text_color', $form_section ); ?>"
						data-default-color="<?php echo $psource_chat->get_option( 'row_date_text_color', $form_section ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'row_date_text_color', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_row_date_color"><?php _e( 'Datum/Uhrzeit Text Hintergrund', $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" id="chat_row_date_color" name="chat[row_date_color]" class="pickcolor_input"
						value="<?php echo $psource_chat->get_option( 'row_date_color', $form_section ); ?>"
						data-default-color="<?php echo $psource_chat->get_option( 'row_date_color', $form_section ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'row_date_color', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_row_code_color"><?php _e( 'CODE Text Farbe', $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" id="chat_row_code_color" name="chat[row_code_color]" class="pickcolor_input"
						value="<?php echo $psource_chat->get_option( 'row_code_color', $form_section ); ?>"
						data-default-color="<?php echo $psource_chat->get_option( 'row_code_color', $form_section ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'row_code_color', 'tip' ); ?></td>
			</tr>
		</table>
	</fieldset>
<?php
}

function psource_chat_form_section_messages_input( $form_section = 'page' ) {
	global $psource_chat;

	//echo "row_message_input_lock[". $psource_chat->get_option('row_message_input_lock', $form_section) ."]<br />";
	?>
	<fieldset>
		<legend><?php _e( 'Chat-Nachrichteneingabe', $psource_chat->translation_domain ); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column">
					<label for="chat_box_input_position"><?php _e( "Platzierung der Nachrichteneingabe", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select id="chat_box_input_position" name="chat[box_input_position]">
						<option value="top" <?php print ( $psource_chat->get_option( 'box_input_position', $form_section ) == 'top' ) ? 'selected="selected"' : ''; ?>><?php
							_e( "Oben - Neueste Nachrichten oben", $psource_chat->translation_domain ); ?></option>
						<option value="bottom" <?php print ( $psource_chat->get_option( 'box_input_position', $form_section ) == 'bottom' ) ? 'selected="selected"' : ''; ?>><?php
							_e( "Unten - Neueste Nachrichten unten", $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_input_position', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_row_message_input_height"><?php _e( 'Eingabefenster Höhe', $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" id="chat_row_message_input_height" name="chat[row_message_input_height]"
						value="<?php echo psource_chat_check_size_qualifier( $psource_chat->get_option( 'row_message_input_height', $form_section ), array( 'px' ) ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'row_message_input_height', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_row_message_input_lock"><?php _e( 'Verhindere dass Benutzer die Höhe ändern', $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select id="chat_row_message_input_lock" name="chat[row_message_input_lock]">
						<option value="vertical" <?php print ( $psource_chat->get_option( 'row_message_input_lock', $form_section ) == 'vertical' ) ? 'selected="selected"' : ''; ?>><?php _e( "Vertikal - Ermöglicht dem Benutzer, nur die Höhe des Textbereichs zu ändern", $psource_chat->translation_domain ); ?></option>
						<option value="none" <?php print ( $psource_chat->get_option( 'row_message_input_lock', $form_section ) == 'none' ) ? 'selected="selected"' : ''; ?>><?php _e( "Keine - Höhe der Textbereichseingabe sperren", $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'row_message_input_lock', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_row_message_input_length"><?php _e( 'Max Zeichen', $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" id="chat_row_message_input_length" name="chat[row_message_input_length]"
						value="<?php echo $psource_chat->get_option( 'row_message_input_length', $form_section ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'row_message_input_length', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_row_message_input_font_family"><?php _e( "Schrift", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select id="chat_row_message_input_font_family" name="chat[row_message_input_font_family]">
						<option value=""><?php _e( "-- vererbt vom Theme --", $psource_chat->translation_domain ); ?></option>
						<?php foreach ( $psource_chat->_chat_options_defaults['fonts_list'] as $font_name => $font ) { ?>
							<option value="<?php print $font_name; ?>" <?php print ( $psource_chat->get_option( 'row_message_input_font_family', $form_section ) == $font_name ) ? 'selected="selected"' : ''; ?> ><?php print $font_name; ?></option>
						<?php } ?>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'row_message_input_font_family', 'tip' ); ?></td>
			</tr>

			<tr>
				<td class="chat-label-column">
					<label for="chat_row_message_input_font_size"><?php _e( "Schriftgröße", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" name="chat[row_message_input_font_size]" id="chat_row_message_input_font_size"
						value="<?php print $psource_chat->get_option( 'row_message_input_font_size', $form_section ); ?>"
						placeholder="<?php echo psource_chat_get_help_item( 'row_message_input_font_size', 'placeholder' ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'row_message_input_font_size', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column"><label for="chat_row_message_input_text_color"><?php _e( 'Textfarbe',
							$psource_chat->translation_domain ); ?></label></td>
				<td class="chat-value-column">
					<input type="text" id="chat_row_message_input_text_color" name="chat[row_message_input_text_color]" class="pickcolor_input"
						value="<?php echo $psource_chat->get_option( 'row_message_input_text_color', $form_section ); ?>"
						data-default-color="<?php echo $psource_chat->get_option( 'row_message_input_text_color', $form_section ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'row_message_input_text_color', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_row_message_input_background_color"><?php _e( 'Hintergrundfarbe',
							$psource_chat->translation_domain ); ?></label></td>
				<td class="chat-value-column">
					<input type="text" id="chat_row_message_input_background_color" name="chat[row_message_input_background_color]" class="pickcolor_input"
						value="<?php echo $psource_chat->get_option( 'row_message_input_background_color', $form_section ); ?>"
						data-default-color="<?php echo $psource_chat->get_option( 'row_message_input_background_color', $form_section ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'row_message_input_background_color', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_box_emoticons"><?php _e( "Emoticons", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select id="chat_box_emoticons" name="chat[box_emoticons]">
						<option value="enabled" <?php print ( $psource_chat->get_option( 'box_emoticons', $form_section ) == 'enabled' ) ? 'selected="selected"' : ''; ?>><?php
							_e( "Aktviert", $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php print ( $psource_chat->get_option( 'box_emoticons', $form_section ) == 'disabled' ) ? 'selected="selected"' : ''; ?>><?php
							_e( "Deaktiviert", $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_emoticons', 'tip' ); ?></td>
			</tr>


			<?php  ?>
		<tr>
			<td class="chat-label-column"><label for="chat_buttonbar"><?php _e("Buttonleiste", $psource_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_buttonbar" name="chat[buttonbar]" >
					<option value="enabled" <?php print ($psource_chat->get_option('buttonbar', $form_section) == 'enabled')?'selected="selected"':''; ?>><?php
					 	_e("Aktiviert", $psource_chat->translation_domain); ?></option>
					<option value="disabled" <?php print ($psource_chat->get_option('buttonbar', $form_section) == 'disabled')?'selected="selected"':''; ?>><?php
					 _e("Deaktiviert", $psource_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo psource_chat_get_help_item('buttonbar', 'tip'); ?></td>
		</tr>
<?php 
			?>

		</table>
	</fieldset>
<?php
}


function psource_chat_form_section_messages_send_button( $form_section = 'page' ) {
	global $psource_chat;

	?>
	<fieldset>
		<legend><?php _e( 'Nachricht Sendenschaltfläche', $psource_chat->translation_domain ); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column">
					<label for="chat_send_button_enable"><?php _e( "Sendenschaltfläche anzeigen", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select id="chat_box_send_button_enable" name="chat[box_send_button_enable]">
						<option value="enabled" <?php selected( $psource_chat->get_option( 'box_send_button_enable', $form_section ), 'enabled' ) ?>><?php
							_e( "Aktiviert", $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php selected( $psource_chat->get_option( 'box_send_button_enable', $form_section ), 'disabled' ) ?>><?php
							_e( "Deaktiviert", $psource_chat->translation_domain ); ?></option>
						<option value="mobile_only" <?php selected( $psource_chat->get_option( 'box_send_button_enable', $form_section ), 'mobile_only' ) ?>><?php
							_e( "Nur mobile Endgeräte", $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'send_button_enable', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_box_send_button_position"><?php _e( "Sendenschaltfläche Position", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select id="chat_box_send_button_position" name="chat[box_send_button_position]">
						<option value="none" <?php selected( $psource_chat->get_option( 'box_send_button_position', $form_section ), 'none' ) ?>><?php
							_e( "Keine - Vom Theme erben", $psource_chat->translation_domain ); ?></option>
						<option value="right" <?php selected( $psource_chat->get_option( 'box_send_button_position', $form_section ), 'right' ) ?>><?php
							_e( "Rechts - Die Schaltfläche befindet sich rechts neben der Nachrichteneingabe.", $psource_chat->translation_domain ); ?></option>
						<option value="below" <?php selected( $psource_chat->get_option( 'box_send_button_position', $form_section ), 'below' ) ?>><?php
							_e( "Unten - Die Schaltfläche befindet sich unterhalb der Nachrichteneingabe", $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_send_button_position', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_box_send_button_label"><?php _e( "Sendenschaltfläche Beschriftung", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" name="chat[box_send_button_label]" id="chat_box_send_button_label"
						value="<?php print $psource_chat->get_option( 'box_send_button_label', $form_section ); ?>"
						placeholder="<?php echo psource_chat_get_help_item( 'box_send_button_label', 'placeholder' ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_send_button_label', 'tip' ); ?></td>
			</tr>

		</table>
	</fieldset>
<?php
}


function psource_chat_form_section_moderator_roles( $form_section = 'page' ) {
	global $psource_chat, $wp_roles;
	?>
	<fieldset>
		<legend><?php _e( 'Moderatoren Rollen', $psource_chat->translation_domain ); ?>
			- <?php _e( "Wähle aus, welche Rollen Moderatoren sind",
				$psource_chat->translation_domain ); ?></legend>

		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column-wide">
					<p class="info">
						<strong><?php _e( "Schließt automatisch Admin- und SuperAdmin-Benutzer ein", $psource_chat->translation_domain ); ?></strong>
					</p>

					<ul id="psource-chat-moderator-roles-list" class="psource-chat-user-roles-list">

						<?php
						/*if ( count( $wp_roles ) ) { //php8 fix */
						if ( count( array($wp_roles ) ) ) {
							foreach ( $wp_roles->roles as $role_slug => $role ) {
								$checked  = '';
								$disabled = '';

								if ( isset( $role['capabilities']['level_10'] ) ) {
									//if (is_multisite())
									//	$role['name'] .= ' - '. __('Includes Super Admins', $psource_chat->translation_domain);
									$checked  = ' checked="checked" ';
									$disabled = ' disabled="disabled" ';
									?>
									<input type="hidden" name="chat[moderator_roles][]" value="<?php print $role_slug; ?>" /><?php
								} else if ( in_array( $role_slug, $psource_chat->get_option( 'moderator_roles', $form_section ) ) !== false ) {
									$checked = ' checked="checked" ';
								}
								?>
								<li><input type="checkbox" id="chat_moderator_roles_<?php print $role_slug; ?>"
									name="chat[moderator_roles][]" class="chat_moderator_roles" value="<?php print $role_slug; ?>"
									<?php echo $checked; ?> <?php echo $disabled; ?> />
								<label><?php echo $role['name']; ?></label></li><?php

							}
						}
						?>
					</ul>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'moderator_roles', 'tip' ); ?></td>
			</tr>
			<?php ?>
		<tr>
			<td class="chat-label-column-wide">
				<label for="chat_box_input_moderator_hide"><?php _e('Nachrichteneingabe ausblenden, wenn kein Moderator vorhanden ist', $psource_chat->translation_domain); ?></label><br />

				<select id="chat_box_input_moderator_hide" name="chat[box_input_moderator_hide]" >
					<option value="enabled" <?php print ($psource_chat->get_option('box_input_moderator_hide', $form_section) == 'enabled')?'selected="selected"':''; ?>><?php
					 	_e("Aktiviert", $psource_chat->translation_domain); ?></option>
					<option value="disabled" <?php print ($psource_chat->get_option('box_input_moderator_hide', $form_section) == 'disabled')?'selected="selected"':''; ?>><?php
					 	_e("Deaktivert", $psource_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo psource_chat_get_help_item('box_input_moderator_hide', 'tip'); ?></td>
		</tr>
		<tr>
			<?php
				$box_input_moderator_hide_label = $psource_chat->get_option('box_input_moderator_hide_label', $form_section);
			?>
			<td class="chat-label-column-wide">
				<label for="chat_box_input_moderator_hide_label"><?php _e('Nachricht welche Benutzern angezeigt wird, wenn kein Moderator anwesend ist', $psource_chat->translation_domain); ?></label><br />
				<input name="chat[box_input_moderator_hide_label]" id="chat_box_input_moderator_hide_label" style="width: 100%" value="<?php echo $box_input_moderator_hide_label ?>" />
			</td>
			<td class="chat-help-column"><?php echo psource_chat_get_help_item('box_input_moderator_hide_label', 'tip'); ?></td>
		</tr>
<?php 
			?>
		</table>
	</fieldset>
<?php
}

function psource_chat_form_section_login_view_options( $form_section = 'page' ) {
	global $psource_chat;

	$noauth_view          = $psource_chat->get_option( 'noauth_view', $form_section );
	$noauth_login_message = $psource_chat->get_option( 'noauth_login_message', $form_section );
	$noauth_login_prompt  = $psource_chat->get_option( 'noauth_login_prompt', $form_section );

	$noauth_view_options = array(
		'default'    => __( 'Standard - Chat-Nachrichten und Benutzerlisten.', $psource_chat->translation_domain ),
		'login-only' => __( 'Anmeldeformular - Nur Chat-Anmeldeformular', $psource_chat->translation_domain ),
		'no-login'   => __( 'Keine Anmeldung - Chat-Nachricht anzeigen, aber keine Anmeldeoption', $psource_chat->translation_domain )
	);

	?>
	<fieldset>
		<legend><?php _e( 'Was wird nicht authentifizierten Benutzern angezeigt?', $psource_chat->translation_domain ); ?></legend>

		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column-wide">
					<select id="chat_noauth_view" name="chat[noauth_view]">
						<?php
						foreach ( $noauth_view_options as $value => $label ) {
							?>
							<option value="<?php echo $value; ?>" <?php print ( $noauth_view == $value ) ? 'selected="selected"' : ''; ?>><?php echo $label; ?></option>
						<?php
						}
						?>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'noauth_view', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column-wide">
					<label for="chat_noauth_login_prompt"><?php _e( 'Im Hauptchatbereich angezeigte Nachricht zur Aufforderung zur Benutzeranmeldung',
							$psource_chat->translation_domain ); ?></label><br/>
					<input name="chat[noauth_login_prompt]" id="chat_noauth_login_prompt" style="width: 100%"
						value="<?php echo $noauth_login_prompt ?>"/>

					<p class="info"><?php echo htmlentities( 'Zulässiges HTML <br />, <strong>, <em>, <i>, <b>' ); ?></p>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'noauth_login_prompt', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column-wide">
					<label for="chat_noauth_login_message"><?php _e( 'Anmeldeformular Nachricht wird über den Anmeldefeldern und der Facebook-Schaltfläche angezeigt', $psource_chat->translation_domain ); ?></label><br/>
					<input name="chat[noauth_login_message]" id="chat_noauth_login_message" style="width: 100%"
						value="<?php echo $noauth_login_message ?>"/>

					<p class="info"><?php echo htmlentities( 'Zulässiges HTML <br />, <strong>, <em>, <i>, <b>' ); ?></p>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'noauth_login_message', 'tip' ); ?></td>
			</tr>

		</table>
	</fieldset>
<?php
}


function psource_chat_form_section_twitter( $form_section = 'global' ) {
	global $psource_chat;
	?>
	<fieldset>
		<legend><?php _e( 'Twitter', $psource_chat->translation_domain ); ?></legend>

		<p class="info"><?php _e( sprintf( 'Gib Deinen Besuchern die Möglichkeit sich per Twitter zu Deinen Chats anzumelden.' ), $psource_chat->translation_domain ); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td style="vertical-align:top; width: 35%">
					<label for="chat_twitter_api_key"><?php _e( 'Consumer Key', $psource_chat->translation_domain ); ?></label><br/>
					<input type="text" id="chat_twitter_api_key" name="chat[twitter_api_key]"
						value="<?php print $psource_chat->get_option( 'twitter_api_key', $form_section ); ?>" class="" style="width: 90%" size="30"/><br/><br/>

					<label for="chat_twitter_api_secret"><?php _e( 'Consumer Secret', $psource_chat->translation_domain ); ?></label><br/>
					<input type="password" id="chat_twitter_api_secret" name="chat[twitter_api_secret]"
						value="<?php print $psource_chat->get_option( 'twitter_api_secret', $form_section ); ?>" class="" style="width: 90%" size="30"/>
				</td>
				<td class="info" style="vertical-align:top; width: 65%">
					<ol>
						<li><?php _e( sprintf( 'Melde Dich bei Deinem Twitterkonto an und navigiere zur <a target="_blank" href="%s">Twitter Developer</a> Sektion.', "https://dev.twitter.com/apps" ), $psource_chat->translation_domain ); ?></li>

						<li><?php _e( sprintf( 'Wenn Du bereits eine Twitter-Anwendung für Deine Website registriert hast, kannst Du denselben Verbraucherschlüssel und dieselben geheimen Verbraucherschlüssel wiederverwenden.', "https://dev.twitter.com/apps" ), $psource_chat->translation_domain ); ?></li>


						<li><?php _e( sprintf( "Wenn Du eine neue Anwendung erstellen musst, kannst Du auf <strong> Neue Anwendung erstellen </strong> klicken und die restlichen Anweisungen unten befolgen.", "https://dev.twitter.com/apps" ), $psource_chat->translation_domain ); ?></li>
						<li><?php _e( 'Fill in the form with values for your website:', $psource_chat->translation_domain ); ?>
							<ul>
								<li><?php _e( '<strong>Name</strong>: Sollte der Name Deiner Webseite sein.', $psource_chat->translation_domain ); ?></li>
								<li><?php _e( '<strong>Description</strong>: Sollte eine kurze Beschreibung Deiner Webseite sein.', $psource_chat->translation_domain ); ?></li>
								<li><?php _e( '<strong>Website</strong>: Should be the home URL of your website. <strong>Not the page where Chat is displayed</strong>', $psource_chat->translation_domain ); ?></li>
								<li><?php _e( '<strong>Callback URL</strong>: Sollte die Home-URL Deiner Website sein', $psource_chat->translation_domain ); ?>
									<strong><?php print get_bloginfo( 'url' ); ?></strong></li>
							</ul>
						</li>
						<li><?php _e( 'Nachdem Du das App-Formular gesendet hast, werden auf der nächsten Seite die Details für Deine neue Twitter-App angezeigt. Kopiere die Werte für den <strong>Verbraucherschlüssel</strong> und das <strong>Verbrauchergeheimnis</strong> in die Formularfelder auf dieser Seite.', $psource_chat->translation_domain ); ?></li>
					</ol>
				</td>
			</tr>
		</table>
	</fieldset>
<?php
}


function psource_chat_form_section_polling_interval( $form_section = 'global' ) {
	global $psource_chat;
	?>
	<fieldset>
		<legend><?php _e( 'Abfrageintervall für Chat-Sitzungen', $psource_chat->translation_domain ); ?></legend>
		<p class="info"><?php _e( 'Jedes Chat-Feld (Seite, untere Ecke) ruft über AJAX zum Server zurück, um nach neuen Nachrichten und Statusänderungen zu suchen. Die folgenden Optionen steuern Optionen für diese AJAX-Abfrage', $psource_chat->translation_domain ); ?></p>

		<p class="info">
			<strong><?php _e( 'Vorschlag: Die Abfrage neuer Nachrichten ist die Hauptschleife. Die Abfragewerte für Invites und Meta sollten ein Vielfaches des Werts für neue Nachrichten sein. Zum Beispiel, wenn die Abfrage neuer Nachrichten 2 Sekunden beträgt. Peile einen Einladungswert von 4 Sekunden und Metawert von 6 Sekunden an.', $psource_chat->translation_domain ); ?></strong>
		</p>

		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column chat-label-column-top">
					<label for="chat_session_poll_interval_messages"><?php _e( 'Wie oft nach neuen Nachrichten gefragt werden soll',
							$psource_chat->translation_domain ); ?></label></td>
				<td class="chat-value-column">
					<input type="text" id="chat_session_poll_interval_messages" name="chat[session_poll_interval_messages]"
						value="<?php echo $psource_chat->get_option( 'session_poll_interval_messages', $form_section ); ?>"/>

					<p class="description"><?php _e( '<strong>Empfohlen 1 oder 2 Sekunden</strong>. Nachrichtenlisten sind Hauptelemente des Chats und sollten so oft wie möglich aktualisiert werden. Sekundenbruchteile sind z.B: 1.02, 0.5, 5.35.', $psource_chat->translation_domain ); ?></p>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'session_poll_interval_messages', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column chat-label-column-top">
					<label for="chat_session_poll_interval_invites"><?php _e( 'Wie oft nach Einladungen fragen',
							$psource_chat->translation_domain ); ?></label></td>
				<td class="chat-value-column">
					<input type="text" id="chat_session_poll_interval_invites" name="chat[session_poll_interval_invites]"
						value="<?php echo $psource_chat->get_option( 'session_poll_interval_invites', $form_section ); ?>"/>

					<p class="description"><?php _e( '<strong>Empfohlen 3 Sekunden</strong>. Chat-Einladungen sind Einladungen anderer Benutzer, an privaten Chats teilzunehmen. Sekundenbruchteile sind z.B: 1.02, 0.5, 5.35',
							$psource_chat->translation_domain ); ?></p>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'session_poll_interval_invites', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column chat-label-column-top">
					<label for="chat_session_poll_interval_meta"><?php _e( 'Wie oft müssen Metadaten aktualisiert werden?',
							$psource_chat->translation_domain ); ?></label></td>
				<td class="chat-value-column">
					<input type="text" id="chat_session_poll_interval_meta" name="chat[session_poll_interval_meta]"
						value="<?php echo $psource_chat->get_option( 'session_poll_interval_meta', $form_section ); ?>"/>

					<p class="description"><?php _e( '<strong>Empfohlen 5 Sekunden</strong>. Metalisten sind sekundäre Elemente des Chats, einschließlich der aktiven Benutzerlisten für alle offenen Chats, Chatstatus und blockierten Benutzer. Sekundenbruchteile sind z.B: 1.02, 0.5, 5.35', $psource_chat->translation_domain ); ?></p>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'session_poll_interval_meta', 'tip' ); ?></td>
			</tr>
		</table>
	</fieldset>
<?php
}


function psource_chat_form_section_polling_content( $form_section = 'global' ) {
	global $psource_chat;

	if ( psource_chat_validate_config_file( $psource_chat->_chat_plugin_settings['config_file'] ) ) {
		$_use_plugin_ajax = true;
	} else {
		$_use_plugin_ajax = false;
	}

	?>
	<fieldset>
		<legend><?php _e( 'Abfrageinhalt von Chat-Sitzungen', $psource_chat->translation_domain ); ?></legend>
		<p class="info"><?php _e( 'Standardmäßig rufen die Chat-Sitzungen eine spezielle AJAX-Datei ab, die sich im Plugin-Verzeichnis befindet (psource-chat-ajax.php). Manchmal ist dies aus Sicherheitsgründen auf dem Server nicht zulässig. In diesen Fällen setze den Abfragetyp auf WordPress.', $psource_chat->translation_domain ); ?></p>

		<p class="info"><?php _e( '<strong>Das WordPress AJAX ist viel langsamer und verwendet mehr Serverressourcen als das Plugin AJAX.</strong>', $psource_chat->translation_domain ); ?></p>
		<?php
		if ($_use_plugin_ajax !== true) {
		?>
		<p class="psource-chat-error"><?php _e( "Das Chat-Plugin-Verzeichnis muss während der Aktivierung beschreibbar sein, um die schnellere Option 'Plugin AJAX' verwenden zu können.", $psource_chat->translation_domain );
			}
			?>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column chat-label-column-top">
					<label for="chat_session_poll_type"><?php _e( 'Wählen für die Abfrage verwendete AJAX-Variante (WordPress AJAX empfohlen)',
							$psource_chat->translation_domain ); ?></label></td>
				<td class="chat-value-column"><?php
					?><select id="chat_session_poll_type" name="chat[session_poll_type]">
						<?php if ( $_use_plugin_ajax === true ) { ?>
							<option value="plugin" <?php print ( $psource_chat->get_option( 'session_poll_type', $form_section ) == 'plugin' ) ? 'selected="selected"' : ''; ?>><?php _e( 'Plugin AJAX', $psource_chat->translation_domain ); ?></option><?php
						} ?>
						<option value="wordpress" <?php print ( $psource_chat->get_option( 'session_poll_type', $form_section ) == 'wordpress' ) ? 'selected="selected"' : '';
						?>><?php _e( 'WordPress AJAX', $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'session_poll_type', 'tip' ); ?></td>
			</tr>
			<?php /* ?>
		<tr>
			<td class="chat-label-column"><label for="chat_session_static_file_path"><?php _e('Static Files Directory',
				$psource_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_session_static_file_path" name="chat[session_static_file_path]"
					value="<?php echo $psource_chat->get_option('session_static_file_path', $form_section); ?>" />
				<p class="description"><?php _e('This static file directory should be writable and accessible at all times from the chat plugin.',
				 	$psource_chat->translation_domain); ?></p>
			</td>
			<td class="chat-help-column"><?php echo psource_chat_get_help_item('session_static_file_path', 'tip'); ?></td>
		</tr>
<?php */
			?>
		</table>
	</fieldset>

<?php
}

function psource_chat_form_section_performance_content( $form_section = 'global' ) {
	global $psource_chat;

	?>
	<fieldset>
		<legend><?php _e( 'Chat Performance/Debug Information', $psource_chat->translation_domain ); ?></legend>
		<p class="info"><?php _e( 'Wird diese Option aktiviert, wird ein statischer Fußzeilenabschnitt angezeigt, in dem Metriken für die AJAX-Abfrage angezeigt werden. Zu den Metriken gehören die Anzahl der Abfragen, der verwendete Speicher und die Ausführungszeit.', $psource_chat->translation_domain ); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column chat-label-column-top">
					<label for="chat_session_performance"><?php _e( 'Aktiviere Performace/Debug Anzeige',
							$psource_chat->translation_domain ); ?></label></td>
				<td class="chat-value-column">
					<select id="chat_session_performance" name="chat[session_performance]">
						<option value="enabled" <?php selected( $psource_chat->get_option( 'session_performance', $form_section ), 'enabled' ) ?>><?php _e( "Aktiviert", $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php selected( $psource_chat->get_option( 'session_performance', $form_section ), 'disabled' ) ?>><?php _e( "Deaktiviert", $psource_chat->translation_domain ); ?></option>
					</select>

				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'session_performance', 'tip' ); ?></td>
			</tr>
			<?php /* ?>
		<tr>
			<td class="chat-label-column"><label for="chat_session_static_file_path"><?php _e('Static Files Directory',
				$psource_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_session_static_file_path" name="chat[session_static_file_path]"
					value="<?php echo $psource_chat->get_option('session_static_file_path', $form_section); ?>" />
				<p class="description"><?php _e('This static file directory should be writable and accessible at all times from the chat plugin.',
				 	$psource_chat->translation_domain); ?></p>
			</td>
			<td class="chat-help-column"><?php echo psource_chat_get_help_item('session_static_file_path', 'tip'); ?></td>
		</tr>
<?php */
			?>
		</table>
	</fieldset>

<?php
}

function psource_chat_form_section_facebook( $form_section = 'global' ) {
	global $psource_chat;
	?>
	<fieldset>
		<legend><?php _e( 'Facebook', $psource_chat->translation_domain ); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td style="vertical-align:top; width: 35%">
					<label for="chat_facebook_application_id"><?php _e( 'App ID', $psource_chat->translation_domain ); ?></label><br/>
					<input type="text" id="chat_facebook_application_id" name="chat[facebook_application_id]" value="<?php
					print $psource_chat->get_option( 'facebook_application_id', $form_section ); ?>" class="" style="width: 90%" size="40"/><br/><br/>

					<label for="chat_facebook_application_secret"><?php _e( 'App Geheimnis', $psource_chat->translation_domain ); ?></label><br/>
					<input type="password" id="chat_facebook_application_secret" name="chat[facebook_application_secret]"
						value="<?php print $psource_chat->get_option( 'facebook_application_secret', $form_section ); ?>" class="" style="width: 90%" size="40"/><br/><br/>


					<label for="chat_facebook_active_in_site"><?php _e( 'Lade Facebook JavaScript ( all.js ) Bibliothek?', $psource_chat->translation_domain ); ?></label><br/>
					<label><input type="radio" id="chat_facebook_active_in_site" name="chat[facebook_active_in_site]" value="yes" <?php
						print ( $psource_chat->get_option( 'facebook_active_in_site', $form_section ) == 'yes' ) ? 'checked="checked"' : ''; ?> class=""
							/> <?php _e( 'JA', $psource_chat->translation_domain ); ?></label>
					<label><input type="radio" id="chat_facebook_active_in_site" name="chat[facebook_active_in_site]" value="no" <?php
						print ( $psource_chat->get_option( 'facebook_active_in_site', $form_section ) == 'no' ) ? 'checked="checked"' : ''; ?> class=""
							/> <?php _e( 'NEIN', $psource_chat->translation_domain ); ?></label><br/>

					<p class="description"><?php _e( 'Wähle NEIN, wenn Du bereits andere Facebook-Plugins ausführst.', $psource_chat->translation_domain ); ?></p>

				</td>
				<td class="info" style="vertical-align:top; width: 65%">

					<ol>
						<li><?php print sprintf( __( 'Registriere diese Seite als Anwendung auf Facebook <a target="_blank" href="%s">App-Registrierungsseite</a>', $psource_chat->translation_domain ), 'http://www.facebook.com/developers/createapp.php' ); ?></li>
						<li><?php _e( 'Wenn Du nicht angemeldet bist, kannst Du Deinen Facebook-Benutzernamen und Passwort verwenden', $psource_chat->translation_domain ); ?></li>
						<li><?php _e( 'Die Seiten-URL sollte sein', $psource_chat->translation_domain ); ?>
							<b><?php print get_bloginfo( 'url' ); ?></b></li>
						<li><?php _e( 'Sobald Du Deine Website als Anwendung registriert hast, erhältst Du eine App-ID und ein App-Geheimnis.', $psource_chat->translation_domain ); ?></li>
						<li><?php _e( 'Kopiere sie und füge sie in die Felder links ein', $psource_chat->translation_domain ); ?></li>
					</ol>
				</td>
			</tr>
		</table>
	</fieldset>
<?php
}

/*function psource_chat_form_section_google_plus( $form_section = 'global' ) {
	global $psource_chat;
	?>
	<fieldset>
		<legend><?php _e( 'Google', $psource_chat->translation_domain ); ?></legend>
		<p><?php _e( 'Um eine Client-ID und ein Client-Geheimnis zu erstellen, erstelle ein Google Developers Console-Projekt, aktiviere die Google API, erstelle eine OAuth 2.0-Client-ID und registriere Deine JavaScript-Ursprünge', $psource_chat->translation_domain ); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td style="vertical-align:top; width: 35%">
					<label for="chat_google_plus_application_id"><?php _e( 'Client ID', $psource_chat->translation_domain ); ?></label><br/>
					<input type="text" id="chat_google_plus_application_id" name="chat[google_plus_application_id]"
						value="<?php print $psource_chat->get_option( 'google_plus_application_id', $form_section ); ?>" class="" style="width: 90%" size="30"/>
				</td>
				<td class="info" style="vertical-align:top; width: 65%">
					<ol>
						<li><?php _e( 'Melde Dich zuerst in Deinem Google Konto an', $psource_chat->translation_domain ); ?></li>
						<li><?php echo sprintf( __( 'In der <a target="_blank" href="%s">Google Developers Console</a>, klicke auf <strong>Create Project</strong>, und gib einen Projektnamen ein (wie "Seitenname Chat").', $psource_chat->translation_domain ), "https://console.developers.google.com/project" ); ?></li>

						<li><?php echo sprintf( __( 'Nach der Projekterstellung wirst Du zum Projekt-Dashboard weitergeleitet. Gehe zu <a target="_blank" href="%s">Enable and Manage APIs</a>, aktiviere <strong>Google API</strong>.', $psource_chat->translation_domain ), "https://console.developers.google.com/apis/library" ); ?></li>

						<li><?php echo sprintf( __( 'Gehe zu <a target="_blank" href="%s">Credentials</a> Tab zur linken, klicke auf New Credentials und wähle <strong>OAuth client ID</strong>. Du wirst aufgefordert, zuerst den Einwilligungsbildschirm zu konfigurieren.', $psource_chat->translation_domain ), "https://console.developers.google.com/apis/credentials" ); ?>
							<ol style="list-style-type:lower-alpha;">
								<li><?php _e( 'In das <strong>Produkt Name</strong> Feld, gibst Du einen Namen für Deine Anwendung ein (z. B. "Wordpress Chat"). Alle anderen Felder sind optional. Klicke auf <strong>Speichern</strong>.', $psource_chat->translation_domain ); ?></li>
								<li><?php _e( 'Gehe im Abschnitt Client-ID-Einstellungen wie folgt vor:	', $psource_chat->translation_domain ); ?>
									<ol style="list-style-type:circle">
										<li><?php _e( 'Wähle <em>Webanwendung</em> als <strong>Anwendungstyp</strong> aus.', $psource_chat->translation_domain ); ?></li>
										<li><?php _e( 'Gib einen Namen für die Anmeldeinformationen an und unterscheide ihn vom Projektnamen.', $psource_chat->translation_domain ); ?></li>
										<li><?php _e( 'Gib die vollständige Domain Deiner Seite in die <strong>autorisierten JavaScript-Ursprünge</strong> und <strong>autorisierte Weiterleitungs-URIs</strong> ein.', $psource_chat->translation_domain ); ?>
											<strong><?php print get_bloginfo( 'url' ); ?></strong></li>
										<li><?php _e( 'Klicke abschließend auf die Schaltfläche <strong>Erstellen</strong>.', $psource_chat->translation_domain ); ?></li>
									</ol>
								</li>
							</ol>
						</li>
						<li><?php _e( 'Auf der nächsten Seite findest Du den Abschnitt <strong>Client-ID für Webanwendungen</strong>.', $psource_chat->translation_domain ); ?>
							<ol style="list-style-type:lower-alpha;">
								<li><?php _e( 'Vergewissere Dich, dass die <em>JavaScript-Ursprünge</em> mit der URL Deiner Website übereinstimmen.', $psource_chat->translation_domain ); ?></li>
								<li><?php _e( 'Kopiere den Wert <strong>Client ID</strong> in das Feld links.', $psource_chat->translation_domain ); ?></li>
							</ol>
						</li>
					</ol>
				</td>
			</tr>
		</table>
	</fieldset>
<?php
}*/


function psource_chat_form_section_blocked_words_global( $form_section = 'banned' ) {
	global $psource_chat;

	?>
	<fieldset>
		<legend><?php _e( 'Filterung verbotener Wörter', $psource_chat->translation_domain ); ?></legend>
		<p class="info"><?php _e( 'Steuert den Filter für gesperrte Wörter, der für ALLE Chat-Sitzungen verwendet wird. Nach der Aktivierung kannst Du einzelne Chat-Sitzungen über die Registerkarte Erweitert steuern.', $psource_chat->translation_domain ); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column chat-label-column-top">
					<label for="chat_blocked_words_replace"><?php _e( 'Ersetze blockierte Wörter durch', $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" id="chat_blocked_words_replace" name="chat[<?php echo $form_section; ?>][blocked_words_replace]"
						value="<?php print $psource_chat->get_option( 'blocked_words_replace', $form_section ); ?>"/><br/>
					<span class="description"><?php _e( 'Zum Entfernen leer lassen', $psource_chat->translation_domain ); ?></span>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'blocked_words_replace', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column chat-label-column-top">
					<label for="chat_blocked_words"><?php _e( 'Blockierte Wörter',$psource_chat->translation_domain ); ?></label>

					<p class="description"><?php _e( 'Ein Wort pro Zeile. Teilwortübereinstimmungen werden eingeschlossen.', $psource_chat->translation_domain ); ?></p>
				</td>
				<td class="chat-value-column">
					<textarea id="chat_blocked_words" name="chat[<?php echo $form_section; ?>][blocked_words]" cols="40" rows="30"><?php
						$blocked_words = $psource_chat->get_option( 'blocked_words', $form_section );
						if ( ( isset( $blocked_words ) ) && ( is_array( $blocked_words ) ) && ( count( $blocked_words ) ) ) {
							foreach ( $blocked_words as $bad_word ) {
								echo trim( $bad_word ) . "\n";
							}
						}
						?></textarea>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'blocked_words', 'tip' ); ?></td>
			</tr>
		</table>
	</fieldset>
<?php
}

function psource_chat_form_section_blocked_words( $form_section = 'page' ) {
	global $psource_chat;

	?>
	<fieldset>
		<legend><?php _e( 'Blockierte Wörter in dieser Sitzung filtern', $psource_chat->translation_domain ); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column">
					<label for="chat_blocked_words_active"><?php _e( 'Aktiv für diese Chat-Sitzung?', $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">

					<select id="chat_blocked_words_active" name="chat[blocked_words_active]">
						<option value="enabled" <?php print ( $psource_chat->get_option( 'blocked_words_active', $form_section ) == 'enabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Aktiviert', $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php print ( $psource_chat->get_option( 'blocked_words_active', $form_section ) == 'disabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Deaktiviert', $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'blocked_words_active', 'tip' ); ?></td>
			</tr>
		</table>
	</fieldset>
<?php
}

function psource_chat_form_section_block_users_global( $form_section = 'global' ) {
	global $psource_chat;

	?>
	<fieldset>
		<legend><?php _e( 'Blockierte Benutzer nach E-Mail-Adresse', $psource_chat->translation_domain ); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column chat-label-column-top">
					<label for="chat_blocked_users"><?php _e( 'Benutzer (nur E-Mail-Adresse)', $psource_chat->translation_domain ); ?></label>

					<p class="description"><?php _e( 'Eine Benutzer-E-Mail pro Zeile. Muster oder Bereiche werden nicht unterstützt. Wirkt sich nur auf öffentliche Benutzer aus. Unterstützt nicht das Blockieren von Facebook- oder Twitter Nutzern.', $psource_chat->translation_domain ); ?></p>
				</td>
				<td class="chat-value-column">

					<textarea id="chat_blocked_users" name="chat[blocked_users]" cols="40" rows="8"><?php
						$blocked_users = $psource_chat->get_option( 'blocked_users', $form_section );
						if ( ( isset( $blocked_users ) ) && ( is_array( $blocked_users ) ) && ( count( $blocked_users ) ) ) {
							foreach ( $blocked_users as $blocked_user ) {
								echo trim( $blocked_user ) . "\n";
							}
						}
						?></textarea>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'blocked_users', 'tip' ); ?></td>
			</tr>
		</table>
	</fieldset>
<?php
}

function psource_chat_form_section_block_urls_site( $form_section = 'site' ) {
	global $psource_chat;
	//$blocked_urls = $psource_chat->get_option('blocked_urls', $form_section);
	$blocked_urls_str   = '';
	$blocked_urls_array = $psource_chat->get_option( 'blocked_urls', $form_section );
	if ( ( isset( $blocked_urls_array ) ) && ( is_array( $blocked_urls_array ) ) && ( count( $blocked_urls_array ) ) ) {
		foreach ( $blocked_urls_array as $blocked_url ) {
			$blocked_urls_str .= trim( $blocked_url ) . "\n";
		}
	}

	?>
	<fieldset>
		<legend><?php _e( 'Den Seitenrand Chat auf URLs ausblenden', $psource_chat->translation_domain ); ?></legend>

		<p class="info"><?php _e( 'Diese Einstellung steuert, wo der Chat in der unteren Ecke auf Deiner Site angezeigt wird. Diese Einstellungen wirken sich nicht auf das Chat-Menü der WP-Symbolleiste, den Seiten-Chat, private Chats oder Widget-Chats aus.', $psource_chat->translation_domain ) ?></p>

		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column">
					<label for="chat_blocked_on_shortcode"><?php _e( "Blockiere URLs mit Shortcode", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select id="chat_blocked_on_shortcode" name="chat[blocked_on_shortcode]">
						<option value="enabled" <?php print ( $psource_chat->get_option( 'blocked_on_shortcode', $form_section ) == 'enabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Aktiviert', $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php print ( $psource_chat->get_option( 'blocked_on_shortcode', $form_section ) == 'disabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Deaktiviert', $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'blocked_on_shortcode', 'tip' ); ?></td>
			</tr>
		</table>

		<p class="info"><?php _e( 'Darüber hinaus kannst Du den Seitenrand-Chat für bestimmte URLs mithilfe der folgenden Optionen ausschließen/einschließen. Die URLs können Front- oder WPAdmin-URLs sein.',
				$psource_chat->translation_domain )?></p>

		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column">
					<label for="chat_blocked_urls_action"><?php _e( "Aktion auswählen", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select id="chat_blocked_urls_action" name="chat[blocked_urls_action]">
						<option value="include" <?php print ( $psource_chat->get_option( 'blocked_urls_action', $form_section ) == 'include' ) ? 'selected="selected"' : ''; ?>><?php _e( "NUR auf URLs anzeigen", $psource_chat->translation_domain ); ?></option>
						<option value="exclude" <?php print ( $psource_chat->get_option( 'blocked_urls_action', $form_section ) == 'exclude' ) ? 'selected="selected"' : ''; ?>><?php _e( "Blende auf URLs aus", $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'blocked_urls_action', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column chat-label-column-top">
					<label for="chat_blocked_urls"><?php _e( 'URLs', $psource_chat->translation_domain ); ?></label>

					<p class="description"><?php _e( 'Eine URL pro Zeile. <br /> URL kann relativ oder absolut sein und Parameter enthalten', $psource_chat->translation_domain ); ?></p>
				</td>
				<td class="chat-value-column">

					<textarea id="chat_blocked_urls" name="chat[blocked_urls]" cols="40" rows="5"><?php echo $blocked_urls_str; ?></textarea>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'blocked_urls', 'tip' ); ?></td>
			</tr>
		</table>
	</fieldset>
<?php
}

function psource_chat_form_section_block_urls_widget( $form_section = 'widget' ) {
	global $psource_chat;

	$blocked_urls_str   = '';
	$blocked_urls_array = $psource_chat->get_option( 'blocked_urls', $form_section );
	if ( ( isset( $blocked_urls_array ) ) && ( is_array( $blocked_urls_array ) ) && ( count( $blocked_urls_array ) ) ) {
		foreach ( $blocked_urls_array as $blocked_url ) {
			$blocked_urls_str .= trim( $blocked_url ) . "\n";
		}
	}

	?>
	<fieldset>
		<legend><?php _e( 'Widget-Chat auf URLs ausblenden', $psource_chat->translation_domain ); ?></legend>

		<p class="info"><?php _e( 'Diese Einstellung steuert, wo Widget-Chats auf Deiner Site angezeigt werden. Diese Einstellungen wirken sich nicht auf das Chat-Menü der WP-Symbolleiste, den Seiten-Chat, die privaten Chats oder den Chat in der unteren Ecke aus.', $psource_chat->translation_domain ) ?></p>

		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column">
					<label for="chat_blocked_on_shortcode"><?php _e( "Blockiere URLs mit Shortcode", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select id="chat_blocked_on_shortcode" name="chat[blocked_on_shortcode]">
						<option value="enabled" <?php print ( $psource_chat->get_option( 'blocked_on_shortcode', $form_section ) == 'enabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Aktiviert', $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php print ( $psource_chat->get_option( 'blocked_on_shortcode', $form_section ) == 'disabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Deaktiviert', $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'blocked_on_shortcode', 'tip' ); ?></td>
			</tr>
		</table>

		<p class="info"><?php _e( 'Darüber hinaus kannst Du Widget-Chats für bestimmte URLs mithilfe der folgenden Optionen ausschließen/einschließen',
				$psource_chat->translation_domain )?></p>

		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column">
					<label for="chat_blocked_urls_action"><?php _e( "Aktion auswählen", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select id="chat_blocked_urls_action" name="chat[blocked_urls_action]">
						<option value="include" <?php print ( $psource_chat->get_option( 'blocked_urls_action', $form_section ) == 'include' ) ? 'selected="selected"' : ''; ?>><?php _e( "NUR auf URLs anzeigen", $psource_chat->translation_domain ); ?></option>
						<option value="exclude" <?php print ( $psource_chat->get_option( 'blocked_urls_action', $form_section ) == 'exclude' ) ? 'selected="selected"' : ''; ?>><?php _e( "Auf URLs ausblenden", $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'blocked_urls_action', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column chat-label-column-top">
					<label for="chat_blocked_urls"><?php _e( 'URLs', $psource_chat->translation_domain ); ?></label>

					<p class="description"><?php _e( 'Eine URL pro Zeile. <br /> URL kann relativ oder absolut sein und Parameter enthalten', $psource_chat->translation_domain ); ?></p>
				</td>
				<td class="chat-value-column">

					<textarea id="chat_blocked_urls" name="chat[blocked_urls]" cols="40" rows="5"><?php echo $blocked_urls_str; ?></textarea>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'blocked_urls', 'tip' ); ?></td>
			</tr>
		</table>
	</fieldset>
<?php
}

function psource_chat_form_section_blocked_ip_addresses_global( $form_section = 'global' ) {
	global $psource_chat;

	?>
	<fieldset>
		<legend><?php _e( 'Blockierte IP Addressen', $psource_chat->translation_domain ); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column">
					<label for="chat_blocked_ip_addresses_active"><?php _e( 'Aktiv für ALLE Chat-Sitzungen?', $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select id="chat_blocked_ip_addresses_active" name="chat[blocked_ip_addresses_active]">
						<option value="enabled" <?php print ( $psource_chat->get_option( 'blocked_ip_addresses_active', $form_section ) == 'enabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Aktiviert', $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php print ( $psource_chat->get_option( 'blocked_ip_addresses_active', $form_section ) == 'disabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Deaktiviert', $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'blocked_ip_addresses_active', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column chat-label-column-top">
					<label for="chat_blocked_ip_message"><?php _e( 'Dem Benutzer angezeigte Nachricht',
							$psource_chat->translation_domain ); ?></label>

					<p class="description"><?php _e( 'Wenn der Benutzer gesperrt ist, wird diese Nachricht anstelle aller anderen Chat-Nachrichten und Informationen angezeigt', $psource_chat->translation_domain ); ?></p>
				</td>
				<td class="chat-value-column">
					<textarea id="chat_blocked_ip_message" name="chat[blocked_ip_message]" cols="40" rows="8"><?php
						echo $psource_chat->get_option( 'blocked_ip_message', $form_section );
						?></textarea>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'blocked_ip_message', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column chat-label-column-top">
					<label for="chat_blocked_ip_addresses"><?php _e( 'Blockierte IP Addressen',
							$psource_chat->translation_domain ); ?></label>

					<p class="description"><?php _e( 'Eine IP-Adresse pro Zeile. Muster oder Bereiche werden nicht unterstützt', $psource_chat->translation_domain ); ?></p>
				</td>
				<td class="chat-value-column">
					<textarea id="chat_blocked_ip_addresses" name="chat[blocked_ip_addresses]" cols="40" rows="8"><?php
						$blocked_ip_addresses = $psource_chat->get_option( 'blocked_ip_addresses', $form_section );
						if ( ( isset( $blocked_ip_addresses ) ) && ( is_array( $blocked_ip_addresses ) ) && ( count( $blocked_ip_addresses ) ) ) {
							foreach ( $blocked_ip_addresses as $blocked_ip_address ) {
								echo trim( $blocked_ip_address ) . "\n";
							}
						}
						?></textarea>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'blocked_ip_addresses', 'tip' ); ?></td>
			</tr>
		</table>
	</fieldset>
<?php
}

function psource_chat_form_section_blocked_ip_addresses( $form_section = 'page' ) {
	global $psource_chat;

	?>
		<fieldset>
			<legend><?php _e( 'Blockierte IP Addressen', $psource_chat->translation_domain ); ?></legend>
				<table border="0" cellpadding="4" cellspacing="0">
					<tr>
					<td class="chat-label-column chat-label-column-top">
					<label for="chat_blocked_words_active"><?php _e( 'Aktiv für diese Chat-Sitzung?', $psource_chat->translation_domain ); ?></label>
					</td>
					<td class="chat-value-column">
						<select id="chat_blocked_ip_addresses_active" name="chat[blocked_ip_addresses_active]">
						<option value="enabled" <?php print ( $psource_chat->get_option( 'blocked_ip_addresses_active', $form_section ) == 'enabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Aktiviert', $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php print ( $psource_chat->get_option( 'blocked_ip_addresses_active', $form_section ) == 'disabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Deaktiviert', $psource_chat->translation_domain ); ?></option>
						</select>
					</td>
					<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'blocked_ip_addresses_active', 'tip' ); ?></td>
					</tr>
				</table>
		</fieldset>

	<?php
}


function psource_chat_form_section_bottom_corner( $form_section = 'site' ) {
	global $psource_chat;

	?>
	<fieldset>
		<legend><?php _e( 'Seitenkanten Chat', $psource_chat->translation_domain ); ?></legend>

		<p class="info"><?php _e( 'Der Seitenkanten Chat ist ein Gruppenchat, der auf allen Seiten der Website angezeigt wird. Mit den folgenden Einstellungen kannst Du die Anzeige und Funktionalität fein einstellen.', $psource_chat->translation_domain ); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
			<?php
			if ( ( $psource_chat->_chat_plugin_settings['network_active'] == true ) && ( ! is_network_admin() ) ) {
				if ( $psource_chat->get_option( 'bottom_corner', 'network-site' ) == 'enabled' ) {
					?>
					<div class="error">
						<p class="info"><?php _e( 'Der Seitenkanten Chat wurde vom Super Admin netzwerkaktiviert. Dies bedeutet, dass die Sichtbarkeit des Seitenkanten Chats Deiner lokalen Seite ausgeschlossen ist.', $psource_chat->translation_domain ); ?></p>
					</div>
				<?php
				}
				if ( $psource_chat->get_option( 'bottom_corner_wpadmin', 'network-site' ) == 'enabled' ) {
					?>
					<div class="error">
						<p class="info"><?php _e( 'Der WPAdmin-Chat in der unteren Ecke wurde vom Superadministrator für das Netzwerk aktiviert. Dies bedeutet, dass die WPAdmin-Chat-Sichtbarkeit Deiner lokalen Seite ausgeschlossen wird.', $psource_chat->translation_domain ); ?></p>
					</div>
				<?php
				}
			}
			?>
			<tr>
				<td class="chat-label-column">
					<label for="chat_site_bottom_corner"><?php _e( 'Zeige Seitenkanten Chat im Frontend?', $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select id="chat_site_bottom_corner" name="chat[bottom_corner]">
						<option value="enabled" <?php print ( $psource_chat->get_option( 'bottom_corner', $form_section ) == 'enabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Aktiviert', $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php print ( $psource_chat->get_option( 'bottom_corner', $form_section ) == 'disabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Deaktiviert', $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'bottom_corner', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_site_bottom_corner_wpadmin"><?php _e( 'Seitenkanten Chat im Dashboard anzeigen?',
							$psource_chat->translation_domain ); ?></label></td>
				<td class="chat-value-column">
					<select id="chat_site_bottom_corner_wpadmin" name="chat[bottom_corner_wpadmin]">
						<option value="enabled" <?php print ( $psource_chat->get_option( 'bottom_corner_wpadmin', $form_section ) == 'enabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Aktiviert', $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php print ( $psource_chat->get_option( 'bottom_corner_wpadmin', $form_section ) == 'disabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Deaktiviert', $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'bottom_corner_wpadmin', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_box_title"><?php _e( "Titel", $psource_chat->translation_domain ); ?></label></td>
				<td class="chat-value-column">
					<input type="text" id="chat_box_title" name="chat[box_title]" value="<?php echo $psource_chat->get_option( 'box_title', $form_section ); ?>"
						size="5" placeholder="<?php echo psource_chat_get_help_item( 'box_title', 'placeholder' ); ?>"/>

					<?php /* ?><p class="info"><?php _e('Title will be displayed in chat bar above messages', $psource_chat->translation_domain); ?></p><?php */ ?>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_title', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_site_status_max_min"><?php _e( 'Seitenkantenchat Initialansicht',
							$psource_chat->translation_domain ); ?></label></td>
				<td class="chat-value-column">
					<select id="chat_site_status_max_min" name="chat[status_max_min]">
						<option value="max" <?php
						print ( $psource_chat->get_option( 'status_max_min', $form_section ) == 'max' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Maximiert', $psource_chat->translation_domain ); ?></option>
						<option value="min" <?php
						print ( $psource_chat->get_option( 'status_max_min', $form_section ) == 'min' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Minimiert', $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'status_max_min', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_site_poll_max_min"><?php _e( 'Wenn Minimiert benachrichtige für neue Nachrichten?',
							$psource_chat->translation_domain ); ?></label></td>
				<td class="chat-value-column">
					<select id="chat_site_poll_max_min" name="chat[poll_max_min]">
						<option value="enabled" <?php
						print ( $psource_chat->get_option( 'poll_max_min', $form_section ) == 'enabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Aktiviert', $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php
						print ( $psource_chat->get_option( 'poll_max_min', $form_section ) == 'disabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Deaktiviert', $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'poll_max_min', 'tip' ); ?></td>
			</tr>

		</table>
	</fieldset>

	<fieldset>
		<legend><?php _e( 'Privater Chat', $psource_chat->translation_domain ); ?></legend>

		<p class="info"><?php _e( 'Die privaten Chats funktionieren ähnlich wie die Chat-Sitzung in der unteren Ecke. Ein privater Chat ist eine Eins-zu-Eins-Chat-Sitzung zwischen zwei Benutzern. Mit den folgenden Einstellungen können Sie die Optionen für Private und deren Auswirkungen auf Benutzer steuern.', $psource_chat->translation_domain ); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column">
					<label for="chat_private_reopen_after_exit"><?php _e( 'Privates Chat-Popup nach dem Vorhandensein wieder öffnen lassen?',
							$psource_chat->translation_domain ); ?></label></td>
				<td class="chat-value-column">
					<select id="chat_site_poll_max_min" name="chat[private_reopen_after_exit]">
						<option value="enabled" <?php
						print ( $psource_chat->get_option( 'private_reopen_after_exit', $form_section ) == 'enabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Aktiviert', $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php
						print ( $psource_chat->get_option( 'private_reopen_after_exit', $form_section ) == 'disabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Deaktiviert', $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'private_reopen_after_exit', 'tip' ); ?></td>
			</tr>

		</table>
	</fieldset>
<?php
}


function psource_chat_form_section_tinymce_button_roles( $form_section = 'global' ) {
	global $psource_chat, $wp_roles;
	?>
	<fieldset>
		<legend><?php _e( 'WYSIWYG Chat-Schaltfläche Benutzerrollen', $psource_chat->translation_domain ); ?></legend>

		<p class="info"><?php _e( "Wähle mit der Schaltfläche Rollen aus welche die Chat WYSIWYG Schaltfläche, verwenden dürfen. Beachte, dass der Benutzer auch über Bearbeitungsfunktionen für den Beitragstyp verfügen muss.", $psource_chat->translation_domain ); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column-wide">
					<?php
					foreach ( $wp_roles->role_names as $role => $name ) {
						?>
						<input type="checkbox" id="chat_tinymce_roles_<?php print $role; ?>" name="chat[tinymce_roles][]" class="chat_tinymce_roles" value="<?php print $role; ?>" <?php print ( in_array( $role, $psource_chat->get_option( 'tinymce_roles', $form_section ) ) > 0 ) ? 'checked="checked"' : ''; ?> />
						<label><?php _e( $name, $psource_chat->translation_domain ); ?></label><br/>
					<?php
					}
					?>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'tinymce_roles', 'tip' ); ?></td>
			</tr>
		</table>
	</fieldset>
<?php
}

function psource_chat_form_section_tinymce_button_post_types( $form_section = 'page' ) {
	global $psource_chat;
	?>
	<fieldset>
		<legend><?php _e( 'WYSIWYG Chat Button Beitragstypen', $psource_chat->translation_domain ); ?></legend>
		<p class="info"><?php _e( "Wähle aus, für welche Beitragstypen die Schaltfläche Chat WYSIWYG verfügbar sein soll.", $psource_chat->translation_domain ); ?></p>

		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column-wide">
					<?php
					foreach ( (array) get_post_types( array( 'show_ui' => true ), 'name' ) as $post_type => $details ) {
						if ( $post_type == "attachment" ) {
							continue;
						}

						?><input type="checkbox" id="chat_tinymce_post_types_<?php print $post_type; ?>"
						name="chat[tinymce_post_types][]" class="chat_tinymce_roles"
						value="<?php print $post_type; ?>" <?php
						print ( in_array( $post_type, $psource_chat->get_option( 'tinymce_post_types', $form_section ) ) > 0 ) ? 'checked="checked"' : ''; ?> />
						<label><?php echo $details->labels->name; ?></label><br/><?php
					}
					?>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'tinymce_post_types', 'tip' ); ?></td>
			</tr>
		</table>
	</fieldset>
<?php
}

function psource_chat_users_list( $form_section = 'page' ) {
	global $psource_chat;

	?>
	<fieldset>
	<legend><?php _e( 'Liste der Chat-Benutzer anzeigen', $psource_chat->translation_domain ); ?></legend>
	<p class="info"><?php _e( "Mit dieser Option kannst Du eine Liste der Benutzer anzeigen, die an der Chat-Sitzung teilnehmen. Du kannst die Benutzerliste auf beiden Seiten des Chat-Fensters positionieren. Du kannst auch den Benutzer-Avatar oder -Namen anzeigen.", $psource_chat->translation_domain ); ?></p>

	<p class="info"><?php _e( "Für Positionen von links nach rechts funktioniert die Avatar-Option am besten. Für Positionen über oder unter funktioniert das Namensformat am besten.", $psource_chat->translation_domain ); ?></p>

	<table border="0" cellpadding="4" cellspacing="0">
	<tr>
		<td class="chat-label-column">
			<label for="chat_users_list_position"><?php _e( "Position der Benutzerliste anzeigen", $psource_chat->translation_domain );
				?></label></td>
		<td class="chat-value-column">
			<select id="chat_users_list_position" name="chat[users_list_position]">
				<option value="none" <?php print ( $psource_chat->get_option( 'users_list_position', $form_section ) == 'none' ) ? 'selected="selected"' : '';
				?>><?php _e( "Benutzerliste nicht anzeigen", $psource_chat->translation_domain ); ?></option>
				<option value="right" <?php print ( $psource_chat->get_option( 'users_list_position', $form_section ) == 'right' ) ? 'selected="selected"' : '';
				?>><?php _e( "Rechts von der Nachrichtenliste", $psource_chat->translation_domain ); ?></option>
				<option value="left" <?php print ( $psource_chat->get_option( 'users_list_position', $form_section ) == 'left' ) ? 'selected="selected"' : '';
				?>><?php _e( "Links von der Nachrichtenliste", $psource_chat->translation_domain ); ?></option>
				<option value="above" <?php print ( $psource_chat->get_option( 'users_list_position', $form_section ) == 'above' ) ? 'selected="selected"' : '';
				?>><?php _e( "Oben in der Nachrichtenliste", $psource_chat->translation_domain ); ?></option>
				<option value="below" <?php print ( $psource_chat->get_option( 'users_list_position', $form_section ) == 'below' ) ? 'selected="selected"' : '';
				?>><?php _e( "Unterhalb der Nachrichtenliste", $psource_chat->translation_domain ); ?></option>
			</select>
		</td>
		<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'users_list_position', 'tip' ); ?></td>
	</tr>
	<tr>
		<td class="chat-label-column">
			<label for="chat_users_list_style"><?php _e( "Moderatoren und Benutzer anzeigen", $psource_chat->translation_domain );
				?></label></td>
		<td class="chat-value-column">
			<select id="chat_users_list_style" name="chat[users_list_style]">
				<option value="split" <?php print ( $psource_chat->get_option( 'users_list_style', $form_section ) == 'split' ) ? 'selected="selected"' : '';
				?>><?php _e( "Teilen - Moderator und Benutzer als separate Listen anzeigen. ", $psource_chat->translation_domain ); ?></option>
				<option value="combined" <?php print ( $psource_chat->get_option( 'users_list_style', $form_section ) == 'combined' ) ? 'selected="selected"' : '';
				?>><?php _e( "Kombiniert - Zeigt zuerst Moderatoren und dann Benutzer an.", $psource_chat->translation_domain ); ?></option>
			</select>
		</td>
		<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'users_list_style', 'tip' ); ?></td>
	</tr>
	<tr id="chat_users_list_width_tr">
		<td class="chat-label-column">
			<label for="chat_users_list_width"><?php _e( 'Listenbreite/-höhe', $psource_chat->translation_domain ); ?></label>
		</td>
		<td class="chat-value-column">
			<input type="text" id="chat_users_list_width" name="chat[users_list_width]" class=""
				value="<?php print $psource_chat->get_option( 'users_list_width', $form_section ); ?>"
				placeholder="<?php echo psource_chat_get_help_item( 'users_list_width', 'placeholder' ); ?>"/>
		</td>
		<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'users_list_width', 'tip' ); ?></td>
	</tr>
	<tr id="chat_users_list_background_color_tr">
		<td class="chat-label-column">
			<label for="chat_users_list_background_color"><?php _e( 'Hintergrundfarbe', $psource_chat->translation_domain ); ?></label>
		</td>
		<td class="chat-value-column">
			<input type="text" id="chat_users_list_background_color" name="chat[users_list_background_color]" class="pickcolor_input"
				value="<?php echo $psource_chat->get_option( 'users_list_background_color', $form_section ); ?>"
				data-default-color="<?php echo $psource_chat->get_option( 'users_list_background_color', $form_section ); ?>"/>
		</td>
		<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'users_list_background_color', 'tip' ); ?></td>
	</tr>
	<tr id="chat_users_list_width_tr">
		<td class="chat-label-column">
			<label for="chat_users_list_threshold_delete"><?php _e( 'Inaktiven Benutzer entfernen nach (Sekunden)',
					$psource_chat->translation_domain ); ?></label></td>
		<td class="chat-value-column">
			<input type="text" id="chat_users_list_threshold_delete" name="chat[users_list_threshold_delete]" class=""
				value="<?php print $psource_chat->get_option( 'users_list_threshold_delete', $form_section ); ?>"
				placeholder="<?php echo psource_chat_get_help_item( 'users_list_threshold_delete', 'placeholder' ); ?>"/>
		</td>
		<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'users_list_threshold_delete', 'tip' ); ?></td>
	</tr>

	<tr>
		<td class="chat-label-column">
			<label for="chat_users_list_header"><?php _e( "Kopfzeile über Listen", $psource_chat->translation_domain ); ?></label>
		</td>
		<td class="chat-value-column">
			<input type="text" name="chat[users_list_header]" id="chat_users_list_header"
				value="<?php echo $psource_chat->get_option( 'users_list_header', $form_section ) ?>"
				placeholder="<?php echo psource_chat_get_help_item( 'users_list_header', 'placeholder' ); ?>"/>
		</td>
		<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'users_list_header', 'tip' ); ?></td>
	</tr>
	<tr>
		<td class="chat-label-column">
			<label for="chat_users_list_header_font_family"><?php _e( "Kopfzeile-Schriftart", $psource_chat->translation_domain ); ?></label>
		</td>
		<td class="chat-value-column">
			<select id="chat_users_list_header_font_family" name="chat[users_list_header_font_family]">
				<option value=""><?php _e( "-- vererbt vom Theme --", $psource_chat->translation_domain ); ?></option>
				<?php foreach ( $psource_chat->_chat_options_defaults['fonts_list'] as $font_name => $font ) { ?>
					<option value="<?php print $font_name; ?>" <?php print ( $psource_chat->get_option( 'users_list_header_font_family', $form_section ) == $font_name ) ? 'selected="selected"' : ''; ?> ><?php print $font_name; ?></option>
				<?php } ?>
			</select>

		</td>
		<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'users_list_header_font_family', 'tip' ); ?></td>
	</tr>
	<tr>
		<td class="chat-label-column">
			<label for="chat_users_list_header_font_size"><?php _e( "Schriftgröße der Kopfzeile", $psource_chat->translation_domain ); ?></label>
		</td>
		<td class="chat-value-column">
			<?php
			$users_list_header_font_size = trim( $psource_chat->get_option( 'users_list_header_font_size', $form_section ) );
			?>
			<input type="text" name="chat[users_list_header_font_size]" id="chat_users_list_header_font_size"
				value="<?php echo ( ! empty( $users_list_header_font_size ) ) ? psource_chat_check_size_qualifier( $users_list_header_font_size ) : ''; ?>"
				placeholder="<?php echo psource_chat_get_help_item( 'users_list_header_font_size', 'placeholder' ); ?>"/>
		</td>
		<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'users_list_header_font_size', 'tip' ); ?></td>
	</tr>
	<tr>
		<td class="chat-label-column">
			<label for="chat_users_list_header_color"><?php _e( 'Kopfzeilentextfarbe', $psource_chat->translation_domain ); ?></label>
		</td>
		<td class="chat-value-column">
			<input type="text" id="chat_users_list_header_color" name="chat[users_list_header_color]" class="pickcolor_input"
				value="<?php echo $psource_chat->get_option( 'users_list_header_color', $form_section ); ?>"
				data-default-color="<?php echo $psource_chat->get_option( 'users_list_header_color', $form_section ); ?>"/>
		</td>
		<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'users_list_header_color', 'tip' ); ?></td>
	</tr>

	<tr id="chat_users_list_show_tr">
		<?php $users_list_show = $psource_chat->get_option( 'users_list_show', $form_section ); ?>

		<td class="chat-label-column">
			<label for="chat_users_list_show"><?php _e( "Benutzerliste anzeigen", $psource_chat->translation_domain ); ?></label>
		</td>
		<td class="chat-value-column">
			<select id="chat_users_list_show" name="chat[users_list_show]">
				<option value="avatar" <?php print ( $users_list_show == 'avatar' ) ? 'selected="selected"' : ''; ?>><?php
					_e( "Avatare", $psource_chat->translation_domain ); ?></option>
				<option value="name" <?php print ( $users_list_show == 'name' ) ? 'selected="selected"' : ''; ?>><?php
					_e( "Namen", $psource_chat->translation_domain ); ?></option>
			</select>
		</td>
		<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'users_list_show', 'tip' ); ?></td>
	</tr>
	<tr id="chat_users_list_avatar_width_tr" <?php if ( $users_list_show != "avatar" ) {
		echo ' style="display:none" ';
	} ?>>
		<td class="chat-label-column">
			<label for="chat_users_list_avatar_width"><?php _e( 'Benutzer-Avatar-Breite', $psource_chat->translation_domain ); ?></label>
		</td>
		<td class="chat-value-column">
			<input type="text" id="chat_users_list_avatar_width" name="chat[users_list_avatar_width]" class=""
				value="<?php echo psource_chat_check_size_qualifier( $psource_chat->get_option( 'users_list_avatar_width', $form_section ), array( 'px' ) ); ?>"
				placeholder="<?php echo psource_chat_get_help_item( 'users_list_avatar_width', 'placeholder' ); ?>"/>
		</td>
		<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'users_list_avatar_width', 'tip' ); ?></td>
	</tr>
	<tr id="chat_users_list_avatar_border_width_tr" <?php if ( $users_list_show != "avatar" ) {
		echo ' style="display:none" ';
	} ?>>
		<td class="chat-label-column">
			<label for="chat_users_list_avatar_border_width"><?php _e( 'Benutzer-Avatar-Rahmenbreite', $psource_chat->translation_domain ); ?></label>
		</td>
		<td class="chat-value-column">
			<input type="text" id="chat_users_list_avatar_border_width" name="chat[users_list_avatar_border_width]" class=""
				value="<?php echo psource_chat_check_size_qualifier( $psource_chat->get_option( 'users_list_avatar_border_width', $form_section ), array( 'px' ) ); ?>"
				placeholder="<?php echo psource_chat_get_help_item( 'users_list_avatar_border_width', 'placeholder' ); ?>"/>
		</td>
		<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'users_list_avatar_border_width', 'tip' ); ?></td>
	</tr>
	<tr id="chat_users_list_moderator_avatar_border_color_tr"  <?php if ( $users_list_show != "avatar" ) {
		echo ' style="display:none" ';
	} ?>>
		<td class="chat-label-column"><label for="chat_users_list_moderator_avatar_border_color"><?php
				_e( 'Moderator-Avatar-Rahmenfarbe', $psource_chat->translation_domain ); ?></label></td>
		<td class="chat-value-column">
			<input type="text" id="chat_users_list_moderator_avatar_border_color" name="chat[users_list_moderator_avatar_border_color]" class="pickcolor_input"
				value="<?php echo $psource_chat->get_option( 'users_list_moderator_avatar_border_color', $form_section ); ?>"
				data-default-color="<?php echo $psource_chat->get_option( 'users_list_moderator_avatar_border_color', $form_section ); ?>"/>
		</td>
		<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'users_list_moderator_avatar_border_color', 'tip' ); ?></td>
	</tr>
	<tr id="chat_users_list_user_avatar_border_color_tr"  <?php if ( $users_list_show != "avatar" ) {
		echo ' style="display:none" ';
	} ?>>
		<td class="chat-label-column"><label for="chat_users_list_user_avatar_border_color"><?php
				_e( 'Benutzer-Avatar-Rahmenfarbe', $psource_chat->translation_domain ); ?></label></td>
		<td class="chat-value-column">
			<input type="text" id="chat_users_list_user_avatar_border_color" name="chat[users_list_user_avatar_border_color]" class="pickcolor_input"
				value="<?php echo $psource_chat->get_option( 'users_list_user_avatar_border_color', $form_section ); ?>"
				data-default-color="<?php echo $psource_chat->get_option( 'users_list_user_avatar_border_color', $form_section ); ?>"/>
		</td>
		<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'users_list_user_avatar_border_color', 'tip' ); ?></td>
	</tr>


	<tr id="chat_users_list_moderator_color_tr"  <?php if ( $users_list_show != "name" ) {
		echo ' style="display:none" ';
	} ?>>
		<td class="chat-label-column">
			<label for="chat_users_list_moderator_color"><?php _e( 'Moderator Name Farbe', $psource_chat->translation_domain ); ?></label>
		</td>
		<td class="chat-value-column">
			<input type="text" id="chat_users_list_moderator_color" name="chat[users_list_moderator_color]" class="pickcolor_input"
				value="<?php echo $psource_chat->get_option( 'users_list_moderator_color', $form_section ); ?>"
				data-default-color="<?php echo $psource_chat->get_option( 'users_list_moderator_color', $form_section ); ?>"/>
		</td>
		<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'users_list_moderator_color', 'tip' ); ?></td>
	</tr>
	<tr id="chat_users_list_name_color_tr"  <?php if ( $users_list_show != "name" ) {
		echo ' style="display:none" ';
	} ?>>
		<td class="chat-label-column">
			<label for="chat_users_list_name_color"><?php _e( 'Benutzername Farbe', $psource_chat->translation_domain ); ?></label>
		</td>
		<td class="chat-value-column">
			<input type="text" id="chat_users_list_name_color" name="chat[users_list_name_color]" class="pickcolor_input"
				value="<?php echo $psource_chat->get_option( 'users_list_name_color', $form_section ); ?>"
				data-default-color="<?php echo $psource_chat->get_option( 'users_list_name_color', $form_section ); ?>"/>
		</td>
		<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'users_list_name_color', 'tip' ); ?></td>
	</tr>
	<tr id="chat_users_list_font_family_tr"  <?php if ( $users_list_show != "name" ) {
		echo ' style="display:none" ';
	} ?>>
		<td class="chat-label-column">
			<label for="chat_users_list_font_family"><?php _e( "Schriftart", $psource_chat->translation_domain ); ?></label>
		</td>
		<td class="chat-value-column">
			<select id="chat_users_list_font_family" name="chat[users_list_font_family]">
				<option value=""><?php _e( "-- vererbt vom Theme --", $psource_chat->translation_domain ); ?></option>
				<?php foreach ( $psource_chat->_chat_options_defaults['fonts_list'] as $font_name => $font ) { ?>
					<option value="<?php print $font_name; ?>" <?php print ( $psource_chat->get_option( 'users_list_font_family', $form_section ) == $font_name ) ? 'selected="selected"' : ''; ?> ><?php print $font_name; ?></option>
				<?php } ?>
			</select>
		</td>
		<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'users_list_font_family', 'tip' ); ?></td>
	</tr>
	<tr id="chat_users_list_font_size_tr" <?php if ( $users_list_show != "name" ) {
		echo ' style="display:none" ';
	} ?>>
		<td class="chat-label-column">
			<label for="chat_users_list_font_size"><?php _e( "Schriftgröße", $psource_chat->translation_domain ); ?></label>
		</td>
		<td class="chat-value-column">
			<?php $users_list_font_size = trim( $psource_chat->get_option( 'users_list_font_size', $form_section ) ); ?>
			<input type="text" name="chat[users_list_font_size]" id="chat_users_list_font_size"
				value="<?php echo ( ! empty( $users_list_font_size ) ) ? psource_chat_check_size_qualifier( $users_list_font_size ) : ''; ?>"
				placeholder="<?php echo psource_chat_get_help_item( 'users_list_font_size', 'placeholder' ); ?>"/>
		</td>
		<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'users_list_font_size', 'tip' ); ?></td>
	</tr>

	</table>
	</fieldset>
<?php
}

function psource_chat_form_section_blocked_urls_admin( $form_section = 'global' ) {
	global $psource_chat;

	$blocked_admin_urls_str   = '';
	$blocked_admin_urls_array = $psource_chat->get_option( 'blocked_admin_urls', $form_section );
	if ( ( isset( $blocked_admin_urls_array ) ) && ( is_array( $blocked_admin_urls_array ) ) && ( count( $blocked_admin_urls_array ) ) ) {
		foreach ( $blocked_admin_urls_array as $blocked_admin_url ) {
			$blocked_admin_urls_str .= trim( $blocked_admin_url ) . "\n";
		}
	}

	?>
	<fieldset>
		<legend><?php _e( 'Chat auf WP Admin-URLs ausblenden', $psource_chat->translation_domain ); ?></legend>
		<p class="info"><?php _e( 'Dieser Abschnitt steuert, wie der Chat im WordPress-Administrationsbereich funktioniert. Dies sind globale Einstellungen, die sich auf alle Benutzer auswirken', $psource_chat->translation_domain ); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column">
					<label for="chat_blocked_admin_urls_action"><?php _e( "Aktion auswählen", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select id="chat_blocked_admin_urls_action" name="chat[blocked_admin_urls_action]">
						<option value="include" <?php print ( $psource_chat->get_option( 'blocked_admin_urls_action', $form_section ) == 'include' ) ? 'selected="selected"' : ''; ?>><?php _e( "NUR auf Admin-URLs anzeigen", $psource_chat->translation_domain ); ?></option>
						<option value="exclude" <?php print ( $psource_chat->get_option( 'blocked_admin_urls_action', $form_section ) == 'exclude' ) ? 'selected="selected"' : ''; ?>><?php _e( "Auf Admin-URLs ausblenden", $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'blocked_admin_urls_action', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column chat-label-column-top">
					<label for="chat_blocked_admin_urls"><?php _e( 'WP Admin URLs', $psource_chat->translation_domain ); ?></label>

					<p class="description"><?php _e( 'Eine URL pro Zeile. <br /> URL kann relativ oder absolut sein und Parameter enthalten', $psource_chat->translation_domain ); ?></p>
				</td>
				<td class="chat-value-column">

					<textarea id="chat_blocked_admin_urls" name="chat[blocked_admin_urls]" cols="40" rows="5"><?php echo $blocked_admin_urls_str; ?></textarea>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'blocked_admin_urls', 'tip' ); ?></td>
			</tr>
		</table>
	</fieldset>
<?php
}

function psource_chat_form_section_blocked_urls_front( $form_section = 'global' ) {
	global $psource_chat;

	$blocked_front_urls_str   = '';
	$blocked_front_urls_array = $psource_chat->get_option( 'blocked_front_urls', $form_section );
	if ( ( isset( $blocked_front_urls_array ) ) && ( is_array( $blocked_front_urls_array ) ) && ( count( $blocked_front_urls_array ) ) ) {
		foreach ( $blocked_front_urls_array as $blocked_front_url ) {
			$blocked_front_urls_str .= trim( $blocked_front_url ) . "\n";
		}
	}

	$chat_load_jscss_all = $psource_chat->get_option( 'load_jscss_all', $form_section );
	?>
	<fieldset>
		<p class="info"><?php _e( 'Standardmäßig lädt PS-Chat das erforderliche JS/CSS auf alle Front-URLs. Dies dient dazu, private Chat-Einladungen sowie Interaktionen mit dem Chat-Bereich der WP-Symbolleiste zu erleichtern. Wenn deaktiviert, werden die JS/CSS-Dateien nicht geladen. Auch das Chat-Menü der WP-Symbolleiste sowie private Chats werden nicht angezeigt.', $psource_chat->translation_domain ); ?></p>

		<p class="info"><?php printf( __( "Beachte dass dies NUR URLs betrifft, bei denen der Seiten-Chat (Shortcode), der Widget-Chat oder der Bottom Corner-Chat noch nicht angezeigt werden. Du kannst den Chat %s und %s im Abschnitt Einstellungen deaktivieren.", $psource_chat->translation_domain ),
				'<a href="admin.php?page=chat_settings_panel_site#chat_advanced_panel">' . __( 'Seitenkanten-Chat', $psource_chat->translation_domain ) . '</a>',
				'<a href="admin.php?page=chat_settings_panel_widget#chat_advanced_panel">' . __( 'Widget', $psource_chat->translation_domain ) . '</a>' ); ?></p>


		<legend><?php _e( 'Chat auf WP Front-URLs ausblenden', $psource_chat->translation_domain ); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column">
					<label for="chat_load_jscss_all"><?php _e( "Lade JS/CSS auf ALLEN URLs", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select id="chat_load_jscss_all" name="chat[load_jscss_all]">
						<option value="enabled" <?php print ( $chat_load_jscss_all == 'enabled' ) ? 'selected="selected"' : ''; ?>><?php _e( "Ja, alle URLs", $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php print ( $chat_load_jscss_all == 'disabled' ) ? 'selected="selected"' : ''; ?>><?php _e( "Nein, nur URLs, die für Shortcode, Widgets usw. benötigt werden.", $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'load_jscss_all', 'tip' ); ?></td>
			</tr>
			<tr id="chat_front_urls_actions_tr" <?php if ( $chat_load_jscss_all == "disabled" ) {
				echo ' style="display:none;" ';
			} ?>>
				<td class="chat-label-column">
					<label for="chat_blocked_front_urls_action"><?php _e( "Aktion auswählen", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select id="chat_blocked_front_urls_action" name="chat[blocked_front_urls_action]">
						<option value="include" <?php print ( $psource_chat->get_option( 'blocked_front_urls_action', $form_section ) == 'include' ) ? 'selected="selected"' : ''; ?>><?php _e( "NUR auf Front-URLs anzeigen", $psource_chat->translation_domain ); ?></option>
						<option value="exclude" <?php print ( $psource_chat->get_option( 'blocked_front_urls_action', $form_section ) == 'exclude' ) ? 'selected="selected"' : ''; ?>><?php _e( "Auf Front-URLs ausblenden", $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'blocked_front_urls_action', 'tip' ); ?></td>
			</tr>
			<tr id="chat_front_urls_list_tr" <?php if ( $chat_load_jscss_all == "disabled" ) {
				echo ' style="display:none;" ';
			} ?>>
				<td class="chat-label-column chat-label-column-top">
					<label for="chat_blocked_front_urls"><?php _e( 'WP Front URLs', $psource_chat->translation_domain ); ?></label>

					<p class="description"><?php _e( 'Eine URL pro Zeile. <br /> URL kann relativ oder absolut sein und Parameter enthalten', $psource_chat->translation_domain ); ?></p>
				</td>
				<td class="chat-value-column">

					<textarea id="chat_blocked_front_urls" name="chat[blocked_front_urls]" cols="40" rows="5"><?php echo $blocked_front_urls_str; ?></textarea>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'blocked_front_urls', 'tip' ); ?></td>
			</tr>
		</table>
	</fieldset>
<?php
}

function psource_chat_form_section_buddypress_group_information( $form_section = 'global' ) {
	global $psource_chat;

	?>
	<fieldset>
		<legend><?php _e( 'Gruppen Information', $psource_chat->translation_domain ); ?></legend>
		<p class="info"><?php _e( 'Steuert die Gruppenmenübezeichnung und den URL-Slug auf den BuddyPress Gruppen-Seiten.', $psource_chat->translation_domain ); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column">
					<label for="chat_bp_menu_label"><?php _e( "Menü Label", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" id="chat_bp_menu_label" name="chat[bp_menu_label]" value="<?php echo $psource_chat->get_option( 'bp_menu_label', $form_section ); ?>"
						size="5" placeholder="<?php echo psource_chat_get_help_item( 'bp_menu_label', 'placeholder' ); ?>"/>

				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'bp_menu_label', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_bp_menu_slug"><?php _e( "Seitenslug", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" id="chat_bp_menu_slug" name="chat[bp_menu_slug]" value="<?php echo $psource_chat->get_option( 'bp_menu_slug', $form_section ); ?>"
						size="5" placeholder="<?php echo psource_chat_get_help_item( 'bp_menu_slug', 'placeholder' ); ?>"/>

					<?php /* ?><p class="info"><?php _e('Title will be displayed in chat bar above messages', $psource_chat->translation_domain); ?></p><?php */ ?>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'bp_menu_slug', 'tip' ); ?></td>
			</tr>
		</table>
	</fieldset>
<?php
}

function psource_chat_form_section_buddypress_group_hide_site( $form_section = 'global' ) {
	global $psource_chat;
	?>
	<fieldset>
		<legend><?php _e( 'Verberge Seitenkanten Chats', $psource_chat->translation_domain ); ?></legend>
		<p class="info"><?php _e( 'Steuert die Anzeige des Chats in der unteren Ecke auf den Seiten der BuddyPress-Gruppe. Diese Einstellung überschreibt die blockierten URLs, die auf der Registerkarte "Einstellungssite" festgelegt wurden.', $psource_chat->translation_domain ); ?></p>

		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column">
					<label for="chat_bp_group_show_site"><?php _e( "Auf Gruppenseiten anzeigen", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select id="chat_bp_group_show_site" name="chat[bp_group_show_site]">
						<option value="enabled" <?php print ( $psource_chat->get_option( 'bp_group_show_site', $form_section ) == 'enabled' ) ? 'selected="selected"' : ''; ?>><?php
							_e( "Aktiviert", $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php print ( $psource_chat->get_option( 'bp_group_show_site', $form_section ) == 'disabled' ) ? 'selected="selected"' : ''; ?>><?php
							_e( "Deaktiviert", $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'bp_group_show_site', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_bp_group_admin_show_site"><?php _e( "Auf Gruppen-Administrationsseiten anzeigen", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select id="chat_bp_group_admin_show_site" name="chat[bp_group_admin_show_site]">
						<option value="enabled" <?php print ( $psource_chat->get_option( 'bp_group_admin_show_site', $form_section ) == 'enabled' ) ? 'selected="selected"' : ''; ?>><?php
							_e( "Aktiviert", $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php print ( $psource_chat->get_option( 'bp_group_admin_show_site', $form_section ) == 'disabled' ) ? 'selected="selected"' : ''; ?>><?php
							_e( "Deaktiviert", $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'bp_group_admin_show_site', 'tip' ); ?></td>
			</tr>
		</table>
	</fieldset>
<?php
}

function psource_chat_form_section_buddypress_group_hide_widget( $form_section = 'global' ) {
	global $psource_chat;
	?>
	<fieldset>
		<legend><?php _e( 'Verberge Widget Chats', $psource_chat->translation_domain ); ?></legend>
		<p class="info"><?php _e( 'Steuert die Anzeige des Widget-Chats auf den Seiten der BuddyPress-Gruppe. Diese Einstellung überschreibt die blockierten URLs, die auf der Registerkarte "Widget" festgelegt wurden.', $psource_chat->translation_domain ); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column">
					<label for="chat_bp_group_show_widget"><?php _e( "Auf Gruppenseiten anzeigen", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select id="chat_bp_group_show_widget" name="chat[bp_group_show_widget]">
						<option value="enabled" <?php print ( $psource_chat->get_option( 'bp_group_show_widget', $form_section ) == 'enabled' ) ? 'selected="selected"' : ''; ?>><?php
							_e( "Aktiviert", $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php print ( $psource_chat->get_option( 'bp_group_show_widget', $form_section ) == 'disabled' ) ? 'selected="selected"' : ''; ?>><?php
							_e( "Deaktiviert", $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'bp_group_show_widget', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_bp_group_admin_show_widget"><?php _e( "Auf Gruppen-Administrationsseiten anzeigen", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select id="chat_bp_group_admin_show_widget" name="chat[bp_group_admin_show_widget]">
						<option value="enabled" <?php print ( $psource_chat->get_option( 'bp_group_admin_show_widget', $form_section ) == 'enabled' ) ? 'selected="selected"' : ''; ?>><?php
							_e( "Aktiviert", $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php print ( $psource_chat->get_option( 'bp_group_admin_show_widget', $form_section ) == 'disabled' ) ? 'selected="selected"' : ''; ?>><?php
							_e( "Deaktiviert", $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'bp_group_admin_show_widget', 'tip' ); ?></td>
			</tr>
		</table>
	</fieldset>
<?php
}

function psource_chat_form_section_buddypress_group_admin_colors( $form_section = 'global' ) {
	global $psource_chat;
	?>
	<fieldset>
		<legend><?php _e( 'Farben für das BuddyPress Gruppen Admin Chat-Formular', $psource_chat->translation_domain ); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column">
					<label for="chat_bp_form_background_color"><?php _e( 'Hintergrundfarbe', $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" id="chat_bp_form_background_color" name="chat[bp_form_background_color]" class="pickcolor_input"
						value="<?php echo $psource_chat->get_option( 'bp_form_background_color', $form_section ); ?>"
						data-default-color="<?php echo $psource_chat->get_option( 'bp_form_background_color', $form_section ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'bp_form_background_color', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_bp_form_label_color"><?php _e( 'Etikettenfarbe', $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" id="chat_bp_form_label_color" name="chat[bp_form_label_color]" class="pickcolor_input"
						value="<?php echo $psource_chat->get_option( 'bp_form_label_color', $form_section ); ?>"
						data-default-color="<?php echo $psource_chat->get_option( 'bp_form_label_color', $form_section ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'bp_form_label_color', 'tip' ); ?></td>
			</tr>

		</table>
	</fieldset>
<?php
}

function psource_chat_form_section_wpadmin( $form_section = 'global' ) {
	global $psource_chat, $current_user;

	//echo "user_meta defaults<pre>"; print_r($psource_chat->_chat_options_defaults['user_meta']); echo "</pre>";

	?>
	<fieldset>
		<legend><?php _e( 'Die Standardeinstellungen für das Benutzerprofil', $psource_chat->translation_domain ); ?></legend>
		<p class="info"><?php _e( 'Mit den folgenden Optionen kannst Du Standardeinstellungen für Benutzer auf Deiner Seite definieren. Diese Einstellungen sind nur Standardeinstellungen, wenn der Benutzer noch keine eigenen Werte über sein Profil gespeichert hat.', $psource_chat->translation_domain ); ?></p>

		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column">
					<label for="psource_chat_status"><?php _e( 'Benutzer-Chat-Status', $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select name="chat_user_meta[chat_user_status]" id="psource_chat_status">
						<?php
						foreach ( $psource_chat->_chat_options['user-statuses'] as $status_key => $status_label ) {
							if ( $status_key == $psource_chat->_chat_options_defaults['user_meta']['chat_user_status'] ) {
								$selected = ' selected="selected" ';
							} else {
								$selected = '';
							}

							?>
							<option value="<?php echo $status_key; ?>" <?php echo $selected; ?>><?php echo $status_label; ?></option><?php
						}
						?>
					</select>
				</td>
				<td class="chat-help-column"><?php //echo psource_chat_get_help_item('', 'tip'); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="psource_chat_name_display"><?php _e( 'In Chat Sessions Show Name as', $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select name="chat_user_meta[chat_name_display]" id="psource_chat_name_display">
						<option value="display_name" <?php if ( $psource_chat->_chat_options_defaults['user_meta']['chat_name_display'] == 'display_name' ) {
							echo ' selected="selected" ';
						} ?>><?php echo __( 'Anzeigename', $psource_chat->translation_domain ) ?></option>
						<option value="user_login" <?php if ( $psource_chat->_chat_options_defaults['user_meta']['chat_name_display'] == 'user_login' ) {
							echo ' selected="selected" ';
						} ?>><?php echo __( 'Benutzername', $psource_chat->translation_domain ) ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php //echo psource_chat_get_help_item('bp_form_background_color', 'tip'); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column chat-label-column-top">
					<label for="psource_chat_wp_admin"><?php _e( 'Chats in WP Admin anzeigen', $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select name="chat_user_meta[chat_wp_admin]" id="psource_chat_wp_admin">
						<option value="enabled"<?php if ( $psource_chat->_chat_options_defaults['user_meta']['chat_wp_admin'] == 'enabled' ) {
							echo ' selected="selected" ';
						} ?>><?php
							_e( 'Aktiviert', $psource_chat->translation_domain ); ?></option>
						<option value="disabled"<?php if ( $psource_chat->_chat_options_defaults['user_meta']['chat_wp_admin'] == 'disabled' ) {
							echo ' selected="selected" ';
						} ?>><?php
							_e( 'Deaktivert', $psource_chat->translation_domain ); ?></option>
					</select>

					<p class="description"><?php _e( 'Dadurch werden alle Chat-Funktionen einschließlich des WordPress-Symbolleistenmenüs deaktiviert', $psource_chat->translation_domain ); ?></p>
				</td>
				<td class="chat-help-column"><?php //echo psource_chat_get_help_item('bp_form_background_color', 'tip'); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="psource_chat_wp_toolbar"><?php _e( 'Chat WordPress-Symbolleistenmenü anzeigen?', $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select name="chat_user_meta[chat_wp_toolbar]" id="psource_chat_wp_toolbar">
						<option value="enabled"<?php if ( $psource_chat->_chat_options_defaults['user_meta']['chat_wp_toolbar'] == 'enabled' ) {
							echo ' selected="selected" ';
						} ?>><?php
							_e( 'Aktiviert', $psource_chat->translation_domain ); ?></option>
						<option value="disabled"<?php if ( $psource_chat->_chat_options_defaults['user_meta']['chat_wp_toolbar'] == 'disabled' ) {
							echo ' selected="selected" ';
						} ?>><?php
							_e( 'Deaktivert', $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php //echo psource_chat_get_help_item('bp_form_background_color', 'tip'); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column chat-label-column-top">
					<label for="psource_chat_users_listing"><?php _e( 'Chat-Statusspalte in der Liste Benutzer> Alle Benutzer anzeigen?', $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select name="chat_user_meta[chat_users_listing]" id="psource_chat_users_listing">
						<option value="enabled"<?php if ( $psource_chat->_chat_options_defaults['user_meta']['chat_users_listing'] == 'enabled' ) {
							echo ' selected="selected" ';
						} ?>><?php
							_e( 'Aktiviert', $psource_chat->translation_domain ); ?></option>
						<option value="disabled"<?php if ( $psource_chat->_chat_options_defaults['user_meta']['chat_users_listing'] == 'disabled' ) {
							echo ' selected="selected" ';
						} ?>><?php
							_e( 'Deaktiviert', $psource_chat->translation_domain ); ?></option>
					</select>

					<p class="description"><?php _e( 'Der Benutzer muss über die Rollenfunktion <strong>list_users</strong> verfügen', $psource_chat->translation_domain ); ?></p>
				</td>
				<td class="chat-help-column"><?php //echo psource_chat_get_help_item('bp_form_background_color', 'tip'); ?></td>
			</tr>

			<tr>
				<td class="chat-label-column">
					<label for="psource_chat_dashboard"><?php _e( 'Chat-Widget im Dashboard anzeigen', $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select name="chat_user_meta[chat_dashboard]" id="psource_chat_dashboard">
						<option value="enabled"<?php if ( $psource_chat->_chat_options_defaults['user_meta']['chat_dashboard'] == 'enabled' ) {
							echo ' selected="selected" ';
						} ?>><?php
							_e( 'Aktiviert', $psource_chat->translation_domain ); ?></option>
						<option value="disabled"<?php if ( $psource_chat->_chat_options_defaults['user_meta']['chat_dashboard'] == 'disabled' ) {
							echo ' selected="selected" ';
						} ?>><?php
							_e( 'Deaktiviert', $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php //echo psource_chat_get_help_item('bp_form_background_color', 'tip'); ?></td>
			</tr>

			<tr>
				<td class="chat-label-column">
					<label for="psource_chat_dashboard_status"><?php _e( 'Chat-Status-Widget im Dashboard anzeigen', $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select name="chat_user_meta[chat_dashboard_status_widget]" id="psource_chat_dashboard_status_widget">
						<option value="enabled"<?php if ( $psource_chat->_chat_options_defaults['user_meta']['chat_dashboard_status_widget'] == 'enabled' ) {
							echo ' selected="selected" ';
						} ?>><?php
							_e( 'Aktiviert', $psource_chat->translation_domain ); ?></option>
						<option value="disabled"<?php if ( $psource_chat->_chat_options_defaults['user_meta']['chat_dashboard_status_widget'] == 'disabled' ) {
							echo ' selected="selected" ';
						} ?>><?php
							_e( 'Deaktivert', $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php //echo psource_chat_get_help_item('bp_form_background_color', 'tip'); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="psource_chat_dashboard_friends"><?php _e( 'Chat-Freunde-Widget im Dashboard anzeigen', $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<select name="chat_user_meta[chat_dashboard_friends_widget]" id="psource_chat_dashboard_friends_widget">
						<option value="enabled"<?php if ( $psource_chat->_chat_options_defaults['user_meta']['chat_dashboard_friends_widget'] == 'enabled' ) {
							echo ' selected="selected" ';
						} ?>><?php
							_e( 'Aktiviert', $psource_chat->translation_domain ); ?></option>
						<option value="disabled"<?php if ( $psource_chat->_chat_options_defaults['user_meta']['chat_dashboard_friends_widget'] == 'disabled' ) {
							echo ' selected="selected" ';
						} ?>><?php
							_e( 'Deaktiviert', $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php //echo psource_chat_get_help_item('bp_form_background_color', 'tip'); ?></td>
			</tr>

		</table>
	</fieldset>
	<fieldset>
		<legend><?php _e( 'Benutzerverwaltung', $psource_chat->translation_domain ); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">

			<tr>
				<td class="chat-label-column chat-label-column-top">
					<label for="chat_delete_user_messages"><?php _e( 'Entferne Chat-Nachrichten, wenn der WordPress-Benutzer gelöscht wird',
							$psource_chat->translation_domain ); ?></label></td>
				<td class="chat-value-column">
					<select id="chat_session_performance" name="chat[delete_user_messages]">
						<option value="enabled" <?php selected( $psource_chat->get_option( 'delete_user_messages', 'global' ), 'enabled' ) ?>><?php _e( "Aktiviert", $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php selected( $psource_chat->get_option( 'delete_user_messages', 'global' ), 'disabled' ) ?>><?php _e( "Deaktiviert", $psource_chat->translation_domain ); ?></option>
					</select>

				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'delete_user_messages', 'tip' ); ?></td>
			</tr>
		</table>
	</fieldset>
<?php
}

function psource_chat_form_section_bottom_corner_network( $form_section = 'network-site' ) {
	global $psource_chat;

	//echo "chat<pre>"; print_r($psource_chat); echo "</pre>";

	?>
	<fieldset>
		<legend><?php _e( 'Seitenkanten Chat', $psource_chat->translation_domain ); ?></legend>

		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column">
					<label for="chat_site_bottom_corner_global"><?php _e( 'Aktiviere den globalen Chat in der unteren Ecke?', $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<p class="description"><?php _e( 'Wenn Du den globalen Chat in der unteren Ecke aktivierst, werden Nachrichten, die von einer Seite gesendet wurden, allen Sites in Deinem Multisite-System angezeigt. Lokale Seiten-Moderatoren können weiterhin Nachrichten usw. löschen/ moderieren. Auch lokale Administratoren können weiterhin verhindern, dass der Chat in der unteren Ecke auf ihrer Seite angezeigt wird.', $psource_chat->translation_domain ); ?></p>
					<select id="chat_site_bottom_corner_global" name="chat[bottom_corner_global]">
						<option value="enabled" <?php print ( $psource_chat->get_option( 'bottom_corner_global', $form_section ) == 'enabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Aktiviert', $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php print ( $psource_chat->get_option( 'bottom_corner_global', $form_section ) == 'disabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Deaktiviert', $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'bottom_corner_global', 'tip' ); ?></td>
			</tr>
		</table>
	</fieldset>
<?php
}

function psource_chat_show_size_selector( $field_name, $form_section = "page" ) {
	global $psource_chat;

	$size_values = array(
		'pc' => __( 'pc', $psource_chat->translation_domain ),
		'pt' => __( 'pt', $psource_chat->translation_domain ),
		'px' => __( 'px', $psource_chat->translation_domain ),
		'em' => __( 'em', $psource_chat->translation_domain ),
		'%'  => __( '%', $psource_chat->translation_domain )
	);

	$field_value = $psource_chat->get_option( $field_name, $form_section );

	?>
	<select id="chat_<?php echo $field_name; ?>" name="chat[<?php echo $field_name; ?>]" class="size_qualifier_field"><?php

	foreach ( $size_values as $size_key => $size_val ) {
		?>
		<option value="<?php echo $size_key; ?>" <?php print ( $field_value == $size_key ) ? 'selected="selected"' : ''; ?>><?php echo $size_val; ?></option><?php
	}
	?></select><?php
}

function psource_chat_form_section_dashboard( $form_section = 'widget' ) {
	global $psource_chat;

//	echo "form_section[". $form_section ."]<br />";
//	echo "psource_chat<pre>"; print_r($psource_chat->_chat_options[$form_section]); echo "</pre>";
//	echo "psource_chat<pre>"; print_r($psource_chat->_chat_options); echo "</pre>";
	?>
	<fieldset>
		<legend><?php _e( 'Chat Widget im Dashboard', $psource_chat->translation_domain ); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column chat-label-column-top"><label for="chat_dashboard_widget"><?php
						if ( is_network_admin() ) {
							_e( 'Aktiviere das Chat-Widget im Netzwerk-Dashboard?', $psource_chat->translation_domain );
						} else {
							_e( 'Aktiviere das Chat-Widget im Dashboard?', $psource_chat->translation_domain );
						}
						?></label></td>
				<td class="chat-value-column">
					<select id="chat_dashboard_widget" name="chat[dashboard_widget]">
						<option value="enabled" <?php print ( $psource_chat->get_option( 'dashboard_widget', $form_section ) == 'enabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Aktiviert', $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php print ( $psource_chat->get_option( 'dashboard_widget', $form_section ) == 'disabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Deaktiviert', $psource_chat->translation_domain ); ?></option>
					</select>

					<p class="description"><?php _e( 'Wenn diese Option aktiviert ist, können Benutzer die Sichtbarkeit über ihr Profil steuern.', $psource_chat->translation_domain ); ?></p>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'dashboard_widget', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="dashboard_widget_title"><?php _e( "Titel", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" id="chat_dashboard_widget_title" name="chat[dashboard_widget_title]" value="<?php echo $psource_chat->get_option( 'dashboard_widget_title', $form_section ); ?>" size="5" placeholder="<?php echo psource_chat_get_help_item( 'box_title', 'placeholder' ); ?>"/>

				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_title', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_dashboard_widget_height"><?php _e( "Höhe", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" id="chat_dashboard_widget_height" name="chat[dashboard_widget_height]" class="size" size="5"
						value="<?php echo psource_chat_check_size_qualifier( $psource_chat->get_option( 'dashboard_widget_height', $form_section ) ); ?>"
						placeholder="<?php echo psource_chat_get_help_item( 'box_height', 'placeholder' ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_height', 'tip' ); ?></td>
			</tr>
		</table>
	</fieldset>

	<fieldset>
		<legend><?php _e( 'Chat-Status-Widget im Dashboard', $psource_chat->translation_domain ); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column chat-label-column-top"><label for="chat_dashboard_status_widget"><?php
						if ( is_network_admin() ) {
							_e( 'Chat-Status-Widget im Netzwerk-Dashboard aktivieren?', $psource_chat->translation_domain );
						} else {
							_e( 'Chat-Status-Widget im Dashboard aktivieren?', $psource_chat->translation_domain );
						}
						?></label></td>
				<td class="chat-value-column">
					<select id="chat_dashboard_status_widget" name="chat[dashboard_status_widget]">
						<option value="enabled" <?php print ( $psource_chat->get_option( 'dashboard_status_widget', $form_section ) == 'enabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Aktiviert', $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php print ( $psource_chat->get_option( 'dashboard_status_widget', $form_section ) == 'disabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Deaktiviert', $psource_chat->translation_domain ); ?></option>
					</select>

					<p class="description"><?php _e( 'Wenn diese Option aktiviert ist, können Benutzer die Sichtbarkeit über ihr Profil steuern.', $psource_chat->translation_domain ); ?></p>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'dashboard_status_widget', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="dashboard_status_widget_title"><?php _e( "Titel", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" id="chat_dashboard_status_widget_title" name="chat[dashboard_status_widget_title]" value="<?php echo $psource_chat->get_option( 'dashboard_status_widget_title', $form_section ); ?>" size="5" placeholder="<?php echo psource_chat_get_help_item( 'box_title', 'placeholder' ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_title', 'tip' ); ?></td>
			</tr>
		</table>
	</fieldset>

	<fieldset>
		<legend><?php _e( 'Chat Freunde Widget im Dashboard', $psource_chat->translation_domain ); ?></legend>
		<p><?php _e( 'Erfordert entweder das PS-Buddy-Plugins oder BuddyPress mit aktivierter Freunde-Option', $psource_chat->translation_domain ); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column chat-label-column-top"><label for="chat_dashboard_friends_widget"><?php
						if ( ( is_multisite() ) && ( is_network_admin() ) ) {
							_e( 'Widget für Chat-Freunde im Netzwerk-Dashboard aktivieren?', $psource_chat->translation_domain );
						} else {
							_e( 'Chat-Freunde-Widget im Dashboard aktivieren?', $psource_chat->translation_domain );
						}
						?></label></td>
				<td class="chat-value-column">
					<select id="chat_dashboard_friends_widget" name="chat[dashboard_friends_widget]">
						<option value="enabled" <?php print ( $psource_chat->get_option( 'dashboard_friends_widget', $form_section ) == 'enabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Aktiviert', $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php print ( $psource_chat->get_option( 'dashboard_friends_widget', $form_section ) == 'disabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Deaktiviert', $psource_chat->translation_domain ); ?></option>
					</select>

					<p class="description"><?php _e( 'Wenn diese Option aktiviert ist, können Benutzer die Sichtbarkeit über ihr Profil steuern.', $psource_chat->translation_domain ); ?></p>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'dashboard_friends_widget', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="dashboard_friends_widget_title"><?php _e( "Titel", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" id="chat_dashboard_friends_widget_title" name="chat[dashboard_friends_widget_title]" value="<?php echo $psource_chat->get_option( 'dashboard_friends_widget_title', $form_section ); ?>" size="5" placeholder="<?php echo psource_chat_get_help_item( 'box_title', 'placeholder' ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'box_title', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column">
					<label for="chat_dashboard_friends_widget_height"><?php _e( "Höhe", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" id="chat_dashboard_friends_widget_height" name="chat[dashboard_friends_widget_height]" class="size" size="5"
						value="<?php echo psource_chat_check_size_qualifier( $psource_chat->get_option( 'dashboard_friends_widget_height', $form_section ) ); ?>"
						placeholder="<?php echo psource_chat_get_help_item( 'dashboard_friends_widget_height', 'placeholder' ); ?>"/>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'dashboard_friends_widget_height', 'tip' ); ?></td>
			</tr>
		</table>
	</fieldset>

<?php
}

function psource_chat_form_section_user_enter_exit_messages( $form_section ) {
	global $psource_chat;

	?>
	<fieldset>
		<legend><?php _e( 'Benutzer hat Chat betreten/verlassen Hinweis', $psource_chat->translation_domain ); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column chat-label-column-top"><label for="chat_users_enter_exit_status"><?php
						_e( 'Zeige betreten/verlassen Hinweis?', $psource_chat->translation_domain );
						?></label></td>
				<td class="chat-value-column">
					<select id="chat_users_enter_exit_status" name="chat[users_enter_exit_status]">
						<option value="enabled" <?php print ( $psource_chat->get_option( 'users_enter_exit_status', $form_section ) == 'enabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Aktiviert', $psource_chat->translation_domain ); ?></option>
						<option value="disabled" <?php print ( $psource_chat->get_option( 'users_enter_exit_status', $form_section ) == 'disabled' ) ? 'selected="selected"' : '';
						?>><?php _e( 'Deaktiviert', $psource_chat->translation_domain ); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'users_enter_exit_status', 'tip' ); ?></td>
			</tr>
			<tr>
				<td class="chat-label-column chat-label-column-top">
					<label for="chat_users_enter_exit_delay"><?php _e( "Benachrichtigungszeit anzeigen <br /> <em>(Sekunden)</em>", $psource_chat->translation_domain ); ?></label>
				</td>
				<td class="chat-value-column">
					<input type="text" id="chat_users_enter_exit_delay" name="chat[users_enter_exit_delay]" class="size" size="5"
						value="<?php echo $psource_chat->get_option( 'users_enter_exit_delay', $form_section ); ?>"
						placeholder="<?php echo psource_chat_get_help_item( 'users_enter_exit_delay', 'placeholder' ); ?>"/>

					<p class="description"><?php _e( 'Sekundenbruchteile sind z. 1,02, 0,5, 5,35.', $psource_chat->translation_domain ) ?></p>
				</td>
				<td class="chat-help-column"><?php echo psource_chat_get_help_item( 'users_enter_exit_delay', 'tip' ); ?></td>
			</tr>

		</table>
	</fieldset>
<?php

}