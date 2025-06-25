<?php
function psource_chat_panel_help() {

	global $psource_chat, $wp_version;

	$screen = get_current_screen();

	$screen_help_text = array();

	if ( ( isset( $_GET['page'] ) ) && ( $_GET['page'] === "chat_settings_panel" ) ) {
		$screen_help_text['psource-chat-help-page-overview'] = '<p>' . __( 'Dies ist die Seitenübersicht', 'psource-chat' ) . '</p>';
		$screen->add_help_tab( array(
				'id'      => 'psource-chat-help-page-overview',
				'title'   => __( 'Übersicht über die Seiteneinstellungen', 'psource-chat' ),
				'content' => $screen_help_text['psource-chat-help-page-overview']
			)
		);
	} else if ( ( isset( $_GET['page'] ) ) && ( $_GET['page'] === "chat_settings_panel_site" ) ) {
		$screen_help_text['psource-chat-help-site-overview'] = '<p>' . __( 'Dies ist die Seite-Übersicht', 'psource-chat' ) . '</p>';
		$screen->add_help_tab( array(
				'id'      => 'psource-chat-help-site-overview',
				'title'   => __( 'Übersicht über die Seite-Einstellungen', 'psource-chat' ),
				'content' => $screen_help_text['psource-chat-help-site-overview']
			)
		);
	} else if ( ( isset( $_GET['page'] ) ) && ( $_GET['page'] === "chat_settings_panel_widget" ) ) {
		$screen_help_text['psource-chat-help-widget-overview'] = '<p>' . __( 'Dies ist die Widget-Übersicht', 'psource-chat' ) . '</p>';
		$screen->add_help_tab( array(
				'id'      => 'psource-chat-help-widget-overview',
				'title'   => __( 'Übersicht über die Seite-Einstellungen', 'psource-chat' ),
				'content' => $screen_help_text['psource-chat-help-widget-overview']
			)
		);
	} else if ( ( isset( $_GET['page'] ) ) && ( $_GET['page'] === "chat_settings_panel_global" ) ) {
		$screen_help_text['psource-chat-help-global-overview'] = '<p>' . __( 'Dies ist die globale Übersicht', 'psource-chat' ) . '</p>';
		$screen->add_help_tab( array(
				'id'      => 'psource-chat-help-global-overview',
				'title'   => __( 'Übersicht über die Seite-Einstellungen', 'psource-chat' ),
				'content' => $screen_help_text['psource-chat-help-global-overview']
			)
		);
	} else if ( ( isset( $_GET['page'] ) ) && ( $_GET['page'] === "chat_session_logs" ) ) {
		$screen_help_text['psource-chat-help-session-logs-overview'] = '<p>' . __( 'Dies ist die Übersicht über die Sitzungsprotokolle', 'psource-chat' ) . '</p>';
		$screen->add_help_tab( array(
				'id'      => 'psource-chat-help-session-logs-overview',
				'title'   => __( 'Übersicht über die Seite-Einstellungen', 'psource-chat' ),
				'content' => $screen_help_text['psource-chat-help-session-logs-overview']
			)
		);
	}
}

function psource_chat_get_help_item( $key, $type = 'full', $form_section = "page" ) {

	global $psource_chat;

	$psource_chat_help_items = array();

	$psource_chat_help_items['log_creation'] = array(
		'full' => __( 'Wenn diese Option aktiviert ist, können Chat-Nachrichten am Ende der Chat-Sitzung archiviert werden.', 'psource-chat' ),
	);

	$psource_chat_help_items['log_display'] = array(
		'full' => __( 'Wenn diese Option aktiviert ist, wird unter dem Chatfeld eine Liste der Protokolle für vergangene Chat-Sitzungen angezeigt.', 'psource-chat' ),
	);

	$psource_chat_help_items['log_display_label']        = array(
		'full' => __( 'Beschriftungstext wird über der Liste des Chat-Archivs angezeigt.', 'psource-chat' ),
	);
	$psource_chat_help_items['log_display_label']        = array(
		'full'        => __( 'Maximale Anzahl von Archivsitzungen, die in der Liste angezeigt werden sollen.', 'psource-chat' ),
		'placeholder' => __( 'z.B: 1, , 25, 100, leer für alle.', 'psource-chat' ),
	);
	$psource_chat_help_items['log_display_hide_session'] = array(
		'full' => __( 'Ein-/Ausblenden der Haupt-Chat-Sitzung beim Anzeigen der Chat-Archivliste.', 'psource-chat' ),
	);

	$psource_chat_help_items['log_display_role_level'] = array(
		'full' => __( "Wähle die Benutzerrollen der untersten Ebene aus, die zum Anzeigen der Chat-Protokollarchive zulässig sind. Bei Einstellung auf öffentlich werden Protokolle für alle Benutzer angezeigt. <br /> <br /> Vergleicht die Rollenstufen der WordPress-Benutzer (level_10, level_7, level_0 usw.).", 'psource-chat' ),
	);

	$psource_chat_help_items['log_display_bp_level'] = array(
		'full' => __( "Wähle die niedrigsten BuddyPress-Gruppenrollen aus, die zum Anzeigen der Chat-Protokollarchive zulässig sind.", 'psource-chat' ),
	);

	$psource_chat_help_items['log_limit'] = array(
		'full' => __( 'Wenn ein Benutzer zum ersten Mal in den Chat eintritt, wird nur die letzte Anzahl von Nachrichten angezeigt. Wenn neue Nachrichten hinzugefügt werden, werden ältere Nachrichten aus der Nachrichtenliste entfernt. Diese Option löscht die Datenbank nicht.', 'psource-chat' )
	);

	$psource_chat_help_items['log_purge'] = array(
		'full' => __( 'Wenn Du einen Chat über mehrere Stunden hostest, hilft diese Option, die ältere Nachrichten zu löschen, um Serverlast zu vermeiden', 'psource-chat' ),
	);

	$psource_chat_help_items['session_status_message'] = array(
		'full' => __( 'Dies ist eine Nachricht, die Benutzern angezeigt wird, wenn die Chat-Sitzung vom Moderator geschlossen wurde.', 'psource-chat' ),
	);

	$psource_chat_help_items['session_cleared_message'] = array(
		'full' => __( 'Diese Nachricht wird kurz angezeigt, wenn der Moderator die aktuellen Sitzungsnachrichten archiviert oder löscht.', 'psource-chat' ),
	);

//	$psource_chat_help_items['session_status_auto_close'] = array(
//		'full'	=>	__('', 'psource-chat'),
//	);

	$psource_chat_help_items['box_position_h'] = array(
		'full' => __( 'Horizontale Position des Seiten und des privaten Chats', 'psource-chat' ),
	);

	$psource_chat_help_items['box_position_v'] = array(
		'full' => '',
		'tip'  => __( 'Vertikale Position des Seiten und des privaten Chats.', 'psource-chat' )
	);


	$psource_chat_help_items['box_position_adjust_mobile'] = array(
		'full' => '',
		'tip'  => __( 'Diese Einstellung steuert die feste Position der Chatbox in der unteren Ecke. <br /> Die untere Ecke ist normalerweise eine feste Position. Bei der Anzeige unter dem Handy wird die Position in relativ geändert.', 'psource-chat' )
	);


	$psource_chat_help_items['box_offset_h'] = array(
		'full'        => '',
		'tip'         => __( 'Vertikaler Pixelversatz vom linken/rechten Rand des Browsers', 'psource-chat' ),
		'placeholder' => __( 'zB. 3px, 10px, etc.', 'psource-chat' ),
	);

	$psource_chat_help_items['box_offset_v'] = array(
		'full'        => '',
		'tip'         => __( 'Vertikaler Pixelversatz vom oberen/unteren Rand des Browsers.', 'psource-chat' ),
		'placeholder' => __( 'zB. 3px, 10px, etc.', 'psource-chat' ),
	);

	$psource_chat_help_items['box_spacing_h'] = array(
		'full'        => __( 'Pixelabstand zwischen Chatboxen.', 'psource-chat' ),
		'tip'         => __( 'Pixelabstand zwischen Chatboxen.', 'psource-chat' ),
		'placeholder' => __( 'Pixelabstand zwischen Chatboxen. zB. 3px, 10px, etc. ', 'psource-chat' ),
	);

	$psource_chat_help_items['box_resizable'] = array(
		'full'	=>	__('Dieses experimentielle Feature erlaubt Deinen Benutzern die Größenänderung der Chatboxen im Frontend. Bitte deaktiviere diese Option falls es Probleme mit Deinem Theme gibt.', 'psource-chat'),
		'tip'	=>	__('Dieses experimentielle Feature erlaubt Deinen Benutzern die Größenänderung der Chatboxen im Frontend. Bitte deaktiviere diese Option falls es Probleme mit Deinem Theme gibt', 'psource-chat')
	);

	$psource_chat_help_items['box_shadow_show'] = array(
		'full' => __( 'Aktiviere Schattenwurf in den unteren und privaten Chatboxen', 'psource-chat' ),
		'tip'  => ''
	);

	$psource_chat_help_items['box_shadow_v'] = array(
		'full'        => __( 'Die Position des vertikalen Schattens. Negative Werte sind zulässig', 'psource-chat' ),
		'tip'         => '',
		'placeholder' => __( 'zB. 3px, 10px, etc.', 'psource-chat' )
	);

	$psource_chat_help_items['box_shadow_h'] = array(
		'full'        => __( 'Die Position des horizontalen Schattens. Negative Werte sind zulässig', 'psource-chat' ),
		'tip'         => '',
		'placeholder' => __( 'zB. 3px, 10px, etc.', 'psource-chat' )
	);

	$psource_chat_help_items['box_shadow_blur'] = array(
		'full'        => __( 'Steuert, wie scharf/weich die Schattenkante ist. Die Unschärfedistanz', 'psource-chat' ),
		'tip'         => '',
		'placeholder' => __( 'zB. 3px, 10px, etc.', 'psource-chat' )
	);

	$psource_chat_help_items['box_shadow_spread'] = array(
		'full'        => __( 'Die Größe des Schattens', 'psource-chat' ),
		'tip'         => '',
		'placeholder' => __( 'zB. 3px, 10px, etc.', 'psource-chat' )
	);

	$psource_chat_help_items['box_shadow_color'] = array(
		'full' => __( 'Startfarbe des Schattenwurfs', 'psource-chat' ),
		'tip'  => '',

	);

	$psource_chat_help_items['box_title'] = array(
		'full'        => __( 'Der Titel der Chat-Sitzung. Wird in der Kopfzeile angezeigt. Dies unterscheidet sich vom Beitrags-/Seitentitel.', 'psource-chat' ),
		'tip'         => '',
		'placeholder' => __( 'Der Titel der Chat-Sitzung', 'psource-chat' )
	);

	$psource_chat_help_items['box_width'] = array(
		'full'        => __( 'Hiermit wird die Breite der Chatbox festgelegt. Stelle dies für den Seiten-/Post-Chat auf 100% ein, um die gesamte Breite des Inhaltsbereichs zu nutzen. Stelle diesen Wert für Seiten-Chats auf einen bestimmten Wert wie 300 Pixel ein. <br /> Die Dropdown-Liste Für Mobil anpassen steuert, wie die Chatbreite behandelt wird, wenn der Bildschirm schmaler als das Chatfeld ist.', 'psource-chat' ),
		'tip'         => '',
		'placeholder' => __( 'Breite der Chatbox', 'psource-chat' )
	);

	$psource_chat_help_items['box_height'] = array(
		'full'        => __( 'Hiermit wird die Höhe der Chatbox festgelegt. Dies sollte ein bestimmter Wert wie 300px sein. <br /> Die Dropdown-Liste Für Mobil anpassen steuert, wie die Chat-Höhe behandelt wird, wenn der Bildschirm kürzer als das Chat-Feld ist.', 'psource-chat' ),
		'placeholder' => __( 'Höhe der Chatbox', 'psource-chat' )
	);

	$psource_chat_help_items['box_font_family'] = array(
		'full' => __( "Schriftfamilie für die Nachrichteneingabe. Wähle Erben, um die Standardschriftart Deines Themes zu verwenden", 'psource-chat' ),
	);

	$psource_chat_help_items['box_font_size'] = array(
		'full'        => __( 'Schriftgröße für die Nachrichteneingabe.', 'psource-chat' ),
		'placeholder' => __( "12px, 0.9em oder leer lassen, um vom Theme zu erben", 'psource-chat' )
	);

	$psource_chat_help_items['box_text_color'] = array(
		'full' => __( 'Textfarbe im Chatfeld. Dies entspricht nicht der Textfarbe der Nachrichtenzeile', 'psource-chat' )
	);

	$psource_chat_help_items['box_background_color'] = array(
		'full' => __( 'Hintergrundfarbe des Chatbox-Bereichs. Dies ist nicht die Farbe des Nachrichtenbereichs.', 'psource-chat' )
	);

	$psource_chat_help_items['box_border_color'] = array(
		'full' => __( 'Rahmenfarbe für Chat-Elemente wie Nachrichtenbereich, Benutzerliste usw.. ', 'psource-chat' ),
	);

	$psource_chat_help_items['box_border_width'] = array(
		'full'        => __( 'Rahmenbreite für äußere Chatbox.', 'psource-chat' ),
		'placeholder' => __( 'Rahmenbreite 1px, 3px usw..', 'psource-chat' )
	);

	$psource_chat_help_items['box_padding'] = array(
		'full'        => __( 'Der Abstand zwischen dem äußeren Containerrand und den Chat-Elementen wie Nachrichtenliste, Nachrichtenbereich usw.', 'psource-chat' ),
		'placeholder' => __( 'Padding 1px, 3px, etc.', 'psource-chat' )
	);

	$psource_chat_help_items['box_sound'] = array(
		'full' => __( 'Ermöglicht die Wiedergabe von Ton, wenn eine neue Nachricht empfangen wird. Benutzer können dies ein- und ausschalten.', 'psource-chat' ),
	);

	$psource_chat_help_items['box_popout']           = array(
		'full' => __( 'Ermöglicht Benutzern das Popout der Chat-Sitzung in einem neuen Fenster.', 'psource-chat' ),
	);
	$psource_chat_help_items['box_moderator_footer'] = array(
		'full' => __( 'Aktiviert die Sichtbarkeit der Fußzeilenoptionen für Moderatoren, die in jeder Chatnachricht angezeigt werden.', 'psource-chat' ),
	);


	$psource_chat_help_items['box_emoticons'] = array(
		'full' => __( 'Zeige Emoticons in der Chat-Sitzung an.', 'psource-chat' ),
	);

	$psource_chat_help_items['box_new_message_color'] = array(
		'full' => __( 'Textfarbe für Elemente der äußeren Chatbox.', 'psource-chat' ),
	);

	$psource_chat_help_items['buttonbar'] = array(
		'full' => __( 'Schaltflächenleiste über dem Nachrichteneintrag anzeigen', 'psource-chat' ),
	);

	$psource_chat_help_items['row_area_background_color'] = array(
		'full' => __( 'Hintergrundfarbe des Nachrichtenbereichs', 'psource-chat' ),
	);

	$psource_chat_help_items['row_background_color'] = array(
		'full' => __( 'Hintergrundfarbe der Nachrichtenzeilenelemente', 'psource-chat' ),
	);

	$psource_chat_help_items['row_spacing'] = array(
		'full' => __( 'Abstand zwischen Zeilenelementen', 'psource-chat' ),
	);

	$psource_chat_help_items['row_border_color'] = array(
		'full' => __( 'Rahmenfarbe der Nachrichtenzeilenelemente. Nur obere und untere Ränder.', 'psource-chat' ),
	);

	$psource_chat_help_items['row_border_width'] = array(
		'full' => __( 'Rahmenbreite der Nachrichtenzeilenelemente', 'psource-chat' ),
	);

	$psource_chat_help_items['background_highlighted_color'] = array(
		'full' => __( 'Hintergrundfarbe der Chatbox, wenn eine neue Nachricht vorliegt', 'psource-chat' ),
	);


	$psource_chat_help_items['row_font_family'] = array(
		'full' => __( "Schriftfamilie für die Nachrichteneingabe. Wähle Erben, um die Standardseitenfront Deines Themas zu verwenden", 'psource-chat' ),
	);

	$psource_chat_help_items['row_font_size'] = array(
		'full'        => __( 'Schriftgröße für die Nachrichteneingabe.', 'psource-chat' ),
		'placeholder' => __( "12px, 0.9em oder leer lassen, um vom Theme zu erben", 'psource-chat' )
	);

	$psource_chat_help_items['row_text_color'] = array(
		'full' => __( 'Textfarbe der Chat-Nachrichtenzeile', 'psource-chat' ),
	);

	$psource_chat_help_items['row_name_avatar'] = array(
		'full' => __( "Zeigen Sie den Avatar des Benutzers mit der Nachricht an", 'psource-chat' ),
	);

	$psource_chat_help_items['row_name_color'] = array(
		'full' => __( 'Hintergrundfarbe des Benutzernamens', 'psource-chat' ),
	);

	$psource_chat_help_items['row_moderator_name_color'] = array(
		'full' => __( 'Hintergrundfarbe des Moderatornamens', 'psource-chat' ),
	);

	$psource_chat_help_items['row_avatar_width'] = array(
		'full'        => __( 'Der Avatar ist eine quadratische Grafik und repräsentiert den Benutzer. Gib einen Wert für die maximale Breite/Höhe der Grafik ein', 'psource-chat' ),
		'placeholder' => 'Gib einen Wert wie 25px, 50px, etc. an'
	);

	$psource_chat_help_items['row_date'] = array(
		'full' => __( 'Zeige das Datum an, an dem die Nachricht gesendet wurde', 'psource-chat' ),
	);

	$psource_chat_help_items['row_time'] = array(
		'full' => __( 'Zeige das Datum an, an dem die Nachricht gesendet wurde', 'psource-chat' ),
	);

	$psource_chat_help_items['row_date_text_color'] = array(
		'full' => __( 'Datums-/Uhrzeitfarbe', 'psource-chat' ),
	);

	$psource_chat_help_items['row_date_color'] = array(
		'full' => __( 'Datum/Uhrzeit Hintergrundfarbe', 'psource-chat' ),
	);

	$psource_chat_help_items['row_code_color'] = array(
		'full' => __( 'Textfarbe für Quellcode', 'psource-chat' ),
	);

	$psource_chat_help_items['box_input_position'] = array(
		'full' => __( "Steuert die Position der Chat-Nachrichteneingabe. Wenn der Chat-Nachrichtenverlauf oben eingestellt ist, wird er oben am neuesten sortiert. Bei der Einstellung Unten wird der Verlauf der Chat-Nachricht unten als neueste Reihenfolge sortiert", 'psource-chat' ),
	);

	$psource_chat_help_items['row_message_input_font_family'] = array(
		'full' => __( "Schriftfamilie für die Nachrichteneingabe. Wähle Erben, um die Standardseitenfront Deines Themes zu verwenden", 'psource-chat' ),
	);

	$psource_chat_help_items['row_message_input_font_size'] = array(
		'full'        => __( 'Schriftgröße für die Nachrichteneingabe.', 'psource-chat' ),
		'placeholder' => __( "12px, 0.9em oder leer lassen, um vom Theme zu erben", 'psource-chat' )
	);

	$psource_chat_help_items['row_message_input_height'] = array(
		'full'        => __( 'Höhe des Nachrichteneingabebereichs', 'psource-chat' ),
		'placeholder' => ''
	);

	$psource_chat_help_items['row_message_input_lock'] = array(
		'full'        => __( 'Steuere die Höhe der Textbereichseingabe. Ermögliche Benutzern, die Größe der Eingabe zu ändern oder sie zu sperren, um eine Größenänderung zu verhindern. Beachte dass der Textbereich immer 100% der Breite der Chatbox beträgt. <br /> <br /> Diese Option wird nicht von allen modernen Browsern unterstützt.', 'psource-chat' ),
		'placeholder' => ''
	);

	$psource_chat_help_items['row_message_input_length'] = array(
		'full'        => __( 'Maximale Anzahl von Zeichen, die ein Benutzer eingeben kann. Lasse das Feld für unbegrenzt leer.', 'psource-chat' ),
		'placeholder' => 'blank or zero for no limit'
	);

	$psource_chat_help_items['row_message_input_text_color'] = array(
		'full' => __( 'Textfarbe für die Nachrichteneingabe', 'psource-chat' ),
	);

	$psource_chat_help_items['row_message_input_background_color'] = array(
		'full' => __( 'Hintergrundfarbe für den Nachrichteneingabebereich', 'psource-chat' ),
	);


	$psource_chat_help_items['send_button_enable']       = array(
		'full' => __( 'Zeige eine Senden-Schaltfläche zum Senden von Chat-Nachrichten an.', 'psource-chat' ),
	);
	$psource_chat_help_items['box_send_button_position'] = array(
		'full' => __( 'Steuert die Anzeigeposition der Sendetaste.', 'psource-chat' ),
	);


	$psource_chat_help_items['session_poll_interval_messages'] = array(
		'full'        => __( 'Steuert, wie oft (Sekunden) nach neuen Nachrichten von anderen Chat-Teilnehmern gesucht werden soll. Der Wert kann Teilsekunden wie 1,5, 2,75 sein. etc.', 'psource-chat' ),
		'placeholder' => __( '1, 2, 1.5, etc. ', 'psource-chat' ),
	);

	$psource_chat_help_items['session_poll_interval_meta'] = array(
		'full' => __( 'Steuert, wie oft (Sekunden) die Benutzerlisten für die Chat-Sitzung aktualisiert werden sollen', 'psource-chat' ),
	);

	$psource_chat_help_items['session_poll_type'] = array(
		'full' => __( 'Wähle das AJAX-System für Chat-Anfragen. PS Chat AJAX nutzt moderne REST APIs und Caching für beste Performance. CMS AJAX verwendet das Standard WordPress admin-ajax.php System.', 'psource-chat' ),
	);

	$psource_chat_help_items['session_static_file_path'] = array(
		'full' => __( 'Speicherort für statische Abfragedateien.', 'psource-chat' ),
	);

	$psource_chat_help_items['blocked_words_active'] = array(
		'full' => __( 'Aktiviere die Filterung blockierter Wörter.', 'psource-chat' ),
	);

	$psource_chat_help_items['blocked_words_replace'] = array(
		'full' => __( 'Ersetzt blockiertes Wort durch etwas anderes, zB: PIIIIEPS.', 'psource-chat' ),
	);

	$psource_chat_help_items['blocked_words'] = array(
		'full' => __( 'Blockierte Wörter sind in Chat-Sitzungen nicht zulässig. Dies gilt global für alle Sitzungen.', 'psource-chat' ),
	);

	$psource_chat_help_items['blocked_users'] = array(
		'full' => __( 'Blockliste wird von allen Chat-Sitzungen verwendet. Diese Option verbirgt den gesamten Chat vor bestimmten Benutzer-E-Mail-Adressen.', 'psource-chat' ),
	);

	$psource_chat_help_items['blocked_urls_action'] = array(
		'full' => '',
		'tip'  => ''
	);

	$psource_chat_help_items['blocked_urls'] = array(
		'full' => __( 'Diese Option blendet den Chat in der unteren Ecke basierend auf den angegebenen URLs aus.', 'psource-chat' ),
	);

	$psource_chat_help_items['blocked_ip_addresses_active'] = array(
		'full' => ''
	);

	$psource_chat_help_items['blocked_ip_message'] = array(
		'full' => __( 'Nachricht, die dem Benutzer angezeigt wird, wenn seine IP-Adresse gesperrt wurde.', 'psource-chat' ),
	);

	$psource_chat_help_items['blocked_ip_addresses'] = array(
		'full' => __( 'Liste der blockierten IP-Adressen. Jede IP-Adresse sollte als gepunktetes Dezimalformat eingegeben werden. Beispiel: 123.123.123.123, 10.0.1.168', 'psource-chat' ),
	);

	$psource_chat_help_items['blocked_ip_addresses_active'] = array(
		'full' => ''
	);

	$psource_chat_help_items['bottom_corner']         = array(
		'full' => __( 'Der Seitenkanten-Chat ist ein globales Gruppen-Chat-Feld, das auf ALLEN Seiten Ihrer Website angezeigt wird.', 'psource-chat' ),
	);
	$psource_chat_help_items['status_max_min']        = array(
		'full' => __( 'Steuert den Anfangsstatus des Chats in der unteren Ecke für neue Benutzer.', 'psource-chat' ),
	);
	$psource_chat_help_items['poll_max_min']          = array(
		'full' => __( 'Wenn der Seitenkanten-Chat minimiert ist, wird gesteuert, ob der Server noch nach neuen Nachrichten abgefragt wird.', 'psource-chat' ),
	);
	$psource_chat_help_items['bottom_corner_wpadmin'] = array(
		'full' => __( 'Zeige den Chat in der unteren Ecke in WP Admin an.', 'psource-chat' ),
	);

	$psource_chat_help_items['private_reopen_after_exit'] = array(
		'full' => __( 'Wenn ein Benutzer einen privaten Chat beendet, können andere Benutzer, die noch verbunden sind, eine Nachricht posten, um die Chat-Konversation fortzusetzen. Diese Option steuert, ob dem Benutzer, der die Konversation verlassen hat, das Chat-Popup erneut angezeigt wird.', 'psource-chat' ),
	);


	$psource_chat_help_items['users_list_position'] = array(
		'full' => __( 'Steuert die Position der teilnehmenden Benutzer in der Chat-Sitzung', 'psource-chat' ),
	);

	$psource_chat_help_items['users_list_show'] = array(
		'full' => __( 'Liste der Benutzer anzeigen, die an der Chat-Sitzung teilnehmen.', 'psource-chat' ),
	);

	$psource_chat_help_items['users_list_style'] = array(
		'full' => __( 'Listenmoderatoren und Benutzer in separaten Listen anzeigen oder kombinieren.<br /><br />Bei Verwendung von Split und Oben/Unten befinden sich die Moderatoren links und die Benutzer rechts. Bei Verwendung von Split und Links/Rechts werden Moderatoren zuerst und dann Benutzer in einer neuen Zeile angezeigt.', 'psource-chat' ),
	);


	$psource_chat_help_items['users_list_width'] = array(
		'full'        => __( 'Breite/Höhe des Benutzerlistenbereichs. Das kann eine feste Größe 250px oder ein Prozentsatz von 25% sein. Der Benutzerlistenbereich wird bei Bedarf auf automatisches Scrollen (CSS: Überlauf) eingestellt, um alle Benutzer anzuzeigen.', 'psource-chat' ),
		'placeholder' => __( "Die Breite des Nachrichtenbereichs wird automatisch angepasst", 'psource-chat' )
	);

	$psource_chat_help_items['users_list_avatar_width'] = array(
		'full'        => __( 'Größe der im Benutzerlistenbereich angezeigten Benutzeravatare.', 'psource-chat' ),
		'placeholder' => __( "30px, 40px usw. Muss eine feste Größe haben. ", 'psource-chat' )
	);

	$psource_chat_help_items['users_list_threshold_delete'] = array(
		'full'        => __( 'Wenn ein Benutzer den Chat verlässt, indem er zu einer anderen Stelle der Seite navigiert oder den Browser schließt, wird er nach diesem Schwellenwert von Sekunden entfernt. Mindestens 20 Sekunden.', 'psource-chat' ),
		'placeholder' => __( "Sekunden Verzögerung, wenn der Benutzer aus der Liste entfernt wird. Mindestens 20 Sekunden.",
			'psource-chat' )
	);

	$psource_chat_help_items['users_list_background_color'] = array(
		'full'        => __( 'Hintergrundfarbe des Benutzerlistenbereichs.', 'psource-chat' ),
		'placeholder' => ""
	);

	/*
		$psource_chat_help_items['users_list_name_color'] = array(
			'full'			=>	__('Color of the user names. Not used is displaying avatars.', 'psource-chat'),
			'placeholder'	=>	__("", 'psource-chat')
		);
	*/
	$psource_chat_help_items['users_list_header_text_color'] = array(
		'full'        => __( 'Farbe des Benutzerlisten-Headers. Nicht verwendet wird die Anzeige von Avataren.', 'psource-chat' ),
		'placeholder' => ""
	);

	$psource_chat_help_items['users_list_header_font_family'] = array(
		'full'        => __( 'Schriftfamilie für die Elemente der Benutzernamenliste.', 'psource-chat' ),
		'placeholder' => ""
	);

	$psource_chat_help_items['users_list_header_font_size'] = array(
		'full'        => __( 'Schriftgröße für die Elemente der Benutzernamenliste.', 'psource-chat' ),
		'placeholder' => __( "12px, 0.9em oder leer lassen, um vom Theme zu erben", 'psource-chat' )
	);

	$psource_chat_help_items['users_list_header_color'] = array(
		'full' => __( 'Farbe für den oben gezeigten Kopfzeilentext Avatare/Namen.', 'psource-chat' ),
	);

	$psource_chat_help_items['users_list_header'] = array(
		'full' => __( 'Header werden über den Benutzerlisten angezeigt. Lasse das Feld leer, um die Kopfzeile nicht anzuzeigen. ', 'psource-chat' )
	);

	$psource_chat_help_items['users_list_moderator_color'] = array(
		'full' => __( 'Farbe für Moderatornamen in der Benutzerliste.', 'psource-chat' )
	);
	$psource_chat_help_items['users_list_name_color']      = array(
		'full' => __( 'Farbe für Benutzernamen in der Benutzerliste.', 'psource-chat' )
	);
	$psource_chat_help_items['users_list_font_family']     = array(
		'full' => __( 'Schriftfamilie für Moderator-/Benutzernamen in der Benutzerliste.', 'psource-chat' )
	);
	$psource_chat_help_items['users_list_font_size']       = array(
		'full'        => __( 'Schriftgröße für Moderator-/Benutzernamen in der Benutzerliste.', 'psource-chat' ),
		'placeholder' => __( "12px, 0.9em oder leer lassen, um vom Theme zu erben", 'psource-chat' )
	);

	$psource_chat_help_items['users_list_avatar_border_width']           = array(
		'full' => __( 'Rahmenbreite für Moderator-/Benutzeravatare in der Benutzerliste.', 'psource-chat' )
	);
	$psource_chat_help_items['users_list_moderator_avatar_border_color'] = array(
		'full' => __( 'Rahmenfarbe für Moderator-Avatare in der Benutzerliste.', 'psource-chat' )
	);

	$psource_chat_help_items['users_list_user_avatar_border_color'] = array(
		'full' => __( 'Rahmenfarbe für Benutzeravatare in der Benutzerliste.', 'psource-chat' )
	);


	$psource_chat_help_items['users_enter_exit_status'] = array(
		'full' => __( 'Zeigt eine kurze Nachricht an, wenn ein Benutzer die Chat-Sitzung betritt/verlässt', 'psource-chat' )
	);

	$psource_chat_help_items['users_enter_exit_delay'] = array(
		'full' => __( 'Steuert, wie lange der Hinweis auf dem Bildschirm angezeigt wird, wenn ein Benutzer den Chat betritt/verlässt.', 'psource-chat' )
	);


	$psource_chat_help_items['login_options'] = array(
		'full' => __( 'Welche Benutzeranmeldeoptionen sind für die Chat-Sitzungen zulässig?', 'psource-chat' ),
	);

	$psource_chat_help_items['noauth_view'] = array(
		'full' => __( 'Steuert, was der Benutzer sehen darf, wenn er sich nicht im Chat angemeldet hat.', 'psource-chat' ),
	);

	$psource_chat_help_items['noauth_login_prompt'] = array(
		'full' => __( 'Dies ist die Eingabeaufforderung, die dem Benutzer mitteilt, dass er sich anmelden muss, bevor er Chat-Nachrichten veröffentlicht.', 'psource-chat' ),
	);

	$psource_chat_help_items['noauth_login_message'] = array(
		'full' => __( 'Die Anmeldemeldung wird über dem Anmeldeformular angezeigt. ', 'psource-chat' ),
	);

	$psource_chat_help_items['moderator_roles'] = array(
		'full' => __( 'Steuert, welche Benutzer Nachrichten und andere Benutzer während der Chat-Sitzung moderieren können.', 'psource-chat' ),
	);

	$psource_chat_help_items['tinymce_roles'] = array(
		'full' => __( 'Steuert, welche WordPress-Benutzerrollen die Symbolleistenschaltfläche Chat WYSIWYG sehen.', 'psource-chat' ),
	);

	$psource_chat_help_items['tinymce_post_types'] = array(
		'full' => __( 'Steuert, für welche Beitragstypen die Symbolleistenschaltfläche Chat WYSIWYG aktiviert ist', 'psource-chat' ),
	);

	$psource_chat_help_items['blocked_admin_urls'] = array(
		'full' => __( 'Ermöglicht das Blockieren des Ladens von Chat auf bestimmten WordPress-Admin-Seiten.', 'psource-chat' ),
	);

	$psource_chat_help_items['blocked_on_shortcode'] = array(
		'full' => __( 'Ausblenden/Anzeigen des Widgets auf Beiträgen/Seiten, die den Chat-Shortcode enthalten.', 'psource-chat' ),
	);

	$psource_chat_help_items['bp_menu_label'] = array(
		'full' => __( 'Der Titel der Registerkarte im BuddyPress-Gruppenabschnitt für den Zugriff auf den Chat', 'psource-chat' ),
	);

	$psource_chat_help_items['bp_menu_slug'] = array(
		'full' => __( 'Der Titel der Registerkarte im BuddyPress-Gruppenabschnitt für den Zugriff auf den Chat.', 'psource-chat' ),
	);

	$psource_chat_help_items['bp_group_show_site'] = array(
		'full' => __( 'Der Titel der Kontakte im BuddyPress-Gruppenabschnitt für den Zugriff auf den Chat', 'psource-chat' ),
	);

	$psource_chat_help_items['bp_group_admin_show_site'] = array(
		'full' => __( 'Steuert die Anzeige von Chats in der unteren Ecke im BuddyPress Group Admin-Bereich', 'psource-chat' ),
	);

	$psource_chat_help_items['bp_group_show_widget'] = array(
		'full' => __( 'Steuert die Anzeige von Widget-Chats im Bereich BuddyPress Gruppen', 'psource-chat' ),
	);

	$psource_chat_help_items['bp_group_admin_show_widget'] = array(
		'full' => __( 'Steuert die Anzeige des unteren Widgets im Bereich BuddyPress Gruppen Admin', 'psource-chat' ),
	);

	$psource_chat_help_items['bp_form_background_color'] = array(
		'full' => __( 'Steuert die Hintergrundfarbe im BuddyPress Gruppen Admin Chat-Formular.', 'psource-chat' ),
	);

	$psource_chat_help_items['bp_form_label_color'] = array(
		'full' => __( 'Steuert die Textfarbe für Beschriftungen im BuddyPress Gruppen Admin Chat-Formular.', 'psource-chat' ),
	);


	$psource_chat_help_items['bottom_corner_global'] = array(
		'full' => __( 'Der globale Chat in der unteren Ecke bedeutet, dass die Chat-Nachricht von allen Sites innerhalb des Multisite-Systems stammt. Beim Wechseln zwischen Standorten bleiben die Nachrichten erhalten.', 'psource-chat' ),
	);

	$psource_chat_help_items['chat_user_status'] = array(
		'full' => __( 'Diese Benutzeroption steuert den öffentlichen Chat-Status für andere Benutzer in Deinem Netzwerk. Auf diese Weise kannst Du steuern, wann andere Personen private Chat-Sitzungen initiieren können. Beachte dass dies private Chats während bestehender Chat-Sitzungen, an denen man teilnehmen, nicht verhindern.', 'psource-chat' ),
	);

	$psource_chat_help_items['chat_name_display'] = array(
		'full' => ''
	);
	$psource_chat_help_items['chat_wp_admin']     = array(
		'full' => __( 'Dadurch werden alle Chat-Funktionen einschließlich des WordPress-Symbolleistenmenüs deaktiviert', 'psource-chat' ),
	);
	$psource_chat_help_items['chat_wp_toolbar']   = array(
		'full' => ''
	);


	if ( $type == "tip" ) {
		if ( ( isset( $psource_chat_help_items[ $key ]['tip'] ) ) && ( strlen( $psource_chat_help_items[ $key ]['tip'] ) ) ) {
			return $psource_chat->tips->add_tip( $psource_chat_help_items[ $key ]['tip'] );
		} else if ( ( isset( $psource_chat_help_items[ $key ]['full'] ) ) && ( strlen( $psource_chat_help_items[ $key ]['full'] ) ) ) {
			return $psource_chat->tips->add_tip( $psource_chat_help_items[ $key ]['full'] );
		}
	} else if ( ( isset( $psource_chat_help_items[ $key ][ $type ] ) ) && ( strlen( $psource_chat_help_items[ $key ][ $type ] ) ) ) {
		return $psource_chat_help_items[ $key ][ $type ];
	}
}