<?php
/**
 * PSource Chat File Upload Handler
 * 
 * Handles secure file uploads for chat messages
 * 
 * @package PSource_Chat
 * @subpackage Upload
 * @since 2.5.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PSource_Chat_Upload {

	/**
	 * Upload-Verzeichnis
	 * @var string
	 */
	private static $upload_dir = null;

	/**
	 * Erlaubte Dateitypen (Standard)
	 * @var array
	 */
	private static $default_allowed_types = array(
		// Bilder
		'jpg' => 'image/jpeg',
		'jpeg' => 'image/jpeg', 
		'png' => 'image/png',
		'gif' => 'image/gif',
		'webp' => 'image/webp',
		// Videos
		'mp4' => 'video/mp4',
		'webm' => 'video/webm',
		'ogg' => 'video/ogg',
		// Audio
		'mp3' => 'audio/mpeg',
		'wav' => 'audio/wav',
		'ogg' => 'audio/ogg',
		// Dokumente
		'pdf' => 'application/pdf',
		'doc' => 'application/msword',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'xls' => 'application/vnd.ms-excel',
		'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'ppt' => 'application/vnd.ms-powerpoint',
		'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'txt' => 'text/plain',
		// Archive
		'zip' => 'application/zip',
		'rar' => 'application/x-rar-compressed'
	);

	/**
	 * Initialisiert Upload-Handler
	 */
	public static function init() {
		// Upload-Verzeichnis erstellen
		self::create_upload_directory();
		
		// AJAX-Handler registrieren
		add_action( 'wp_ajax_psource_chat_upload_file', array( __CLASS__, 'ajax_upload_file' ) );
		add_action( 'wp_ajax_nopriv_psource_chat_upload_file', array( __CLASS__, 'ajax_upload_file' ) );
		
		add_action( 'wp_ajax_psource_chat_download_file', array( __CLASS__, 'ajax_download_file' ) );
		add_action( 'wp_ajax_nopriv_psource_chat_download_file', array( __CLASS__, 'ajax_download_file' ) );
		
		// AJAX send_message vorübergehend deaktiviert
		// add_action( 'wp_ajax_psource_chat_send_message', array( __CLASS__, 'ajax_send_message' ) );
		// add_action( 'wp_ajax_nopriv_psource_chat_send_message', array( __CLASS__, 'ajax_send_message' ) );
		
		// Hook für Chat-Session-Löschung
		add_action( 'psource_chat_delete_session', array( __CLASS__, 'cleanup_session_files' ) );
		
		// Tägliches Cleanup für verwaiste Dateien
		add_action( 'psource_chat_cleanup_uploads', array( __CLASS__, 'cleanup_orphaned_files' ) );
		self::schedule_cleanup();
		
		// Message-Filter deaktiviert
		self::init_message_filters();
		
		// Hook für Message-Processing vorübergehend deaktiviert
		// add_filter( 'psource_chat_before_save_message', array( __CLASS__, 'process_uploaded_files' ), 15, 2 );
		// add_filter( 'psource_chat_display_message', array( __CLASS__, 'render_file_attachments' ), 15, 2 );
	}

	/**
	 * Erstellt sicheres Upload-Verzeichnis
	 */
	private static function create_upload_directory() {
		$upload_base = wp_upload_dir();
		$chat_upload_dir = $upload_base['basedir'] . '/psource-chat-files';
		
		if ( ! file_exists( $chat_upload_dir ) ) {
			wp_mkdir_p( $chat_upload_dir );
			
			// .htaccess für Sicherheit erstellen
			$htaccess_content = "# PSource Chat Upload Security\n";
			$htaccess_content .= "Options -Indexes\n";
			$htaccess_content .= "Options -ExecCGI\n";
			$htaccess_content .= "<Files *.php>\n";
			$htaccess_content .= "    Deny from all\n";
			$htaccess_content .= "</Files>\n";
			$htaccess_content .= "<Files *.html>\n";
			$htaccess_content .= "    Deny from all\n";
			$htaccess_content .= "</Files>\n";
			
			file_put_contents( $chat_upload_dir . '/.htaccess', $htaccess_content );
			
			// Index.php als zusätzliche Sicherheit
			file_put_contents( $chat_upload_dir . '/index.php', '<?php // Silence is golden' );
		}
		
		self::$upload_dir = $chat_upload_dir;
	}

	/**
	 * Holt Upload-Verzeichnis
	 */
	public static function get_upload_dir() {
		if ( self::$upload_dir === null ) {
			self::create_upload_directory();
		}
		return self::$upload_dir;
	}

	/**
	 * Prüft ob Uploads global erlaubt sind
	 */
	public static function are_uploads_enabled() {
		global $psource_chat;
		return $psource_chat->get_option( 'file_uploads_enabled', 'global' ) === 'enabled';
	}

	/**
	 * Prüft ob Uploads für Chat-Session erlaubt sind
	 */
	public static function are_uploads_enabled_for_session( $chat_session ) {
		if ( ! self::are_uploads_enabled() ) {
			return false;
		}
		
		return isset( $chat_session['file_uploads_enabled'] ) && 
			   $chat_session['file_uploads_enabled'] === 'enabled';
	}

	/**
	 * Holt erlaubte Dateitypen
	 */
	public static function get_allowed_file_types() {
		global $psource_chat;
		$allowed_types = $psource_chat->get_option( 'file_uploads_allowed_types', 'global' );
		
		if ( empty( $allowed_types ) ) {
			return self::$default_allowed_types;
		}
		
		$types = array();
		$allowed_extensions = explode( ',', $allowed_types );
		
		foreach ( $allowed_extensions as $ext ) {
			$ext = trim( strtolower( $ext ) );
			if ( isset( self::$default_allowed_types[ $ext ] ) ) {
				$types[ $ext ] = self::$default_allowed_types[ $ext ];
			}
		}
		
		return $types;
	}

	/**
	 * Holt maximale Dateigröße in Bytes
	 */
	public static function get_max_file_size() {
		global $psource_chat;
		$max_size = $psource_chat->get_option( 'file_uploads_max_size', 'global' );
		
		if ( empty( $max_size ) ) {
			$max_size = 5; // 5MB Standard
		}
		
		return intval( $max_size ) * 1024 * 1024; // In Bytes konvertieren
	}

	/**
	 * AJAX-Handler für direktes Senden von Nachrichten
	 */
	public static function ajax_send_message() {
		// Nonce-Prüfung
		if ( ! wp_verify_nonce( $_POST['nonce'], 'psource_chat_nonce' ) ) {
			wp_send_json_error( 'Sicherheitsfehler' );
		}

		$session_id = sanitize_text_field( $_POST['session_id'] );
		$message = wp_kses_post( $_POST['message'] ); // Erlaubt sicheres HTML
		
		if ( empty( $session_id ) || empty( $message ) ) {
			wp_send_json_error( 'Session-ID oder Nachricht fehlt' );
		}

		global $psource_chat;
		if ( ! $psource_chat ) {
			wp_send_json_error( 'Chat-System nicht verfügbar' );
		}

		// Chat-Session-Konfiguration holen
		$chat_session = self::get_chat_session( $session_id );
		if ( ! $chat_session ) {
			wp_send_json_error( 'Chat-Session nicht gefunden' );
		}

		// Nachricht über das Chat-System senden
		$result = $psource_chat->chat_session_send_message( $message, $chat_session );
		
		if ( $result ) {
			wp_send_json_success( 'Nachricht gesendet' );
		} else {
			wp_send_json_error( 'Fehler beim Senden der Nachricht' );
		}
	}

	/**
	 * AJAX-Handler für Datei-Upload
	 */
	public static function ajax_upload_file() {
		// Nonce prüfen
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'psource_chat_nonce' ) ) {
			wp_send_json_error( 'Security check failed' );
		}

		// Session prüfen
		$session_id = sanitize_text_field( $_REQUEST['session_id'] ?? '' );
		if ( empty( $session_id ) ) {
			wp_send_json_error( 'Keine Session-ID angegeben' );
		}

		// Upload-Berechtigung prüfen
		global $psource_chat;
		$chat_session = self::get_chat_session( $session_id );
		
		if ( ! $chat_session || ! self::are_uploads_enabled_for_session( $chat_session ) ) {
			wp_send_json_error( 'Datei-Uploads sind für diese Chat-Session nicht erlaubt' );
		}

		// Datei prüfen
		if ( ! isset( $_FILES['file'] ) || $_FILES['file']['error'] !== UPLOAD_ERR_OK ) {
			wp_send_json_error( 'Fehler beim Datei-Upload' );
		}

		$file = $_FILES['file'];
		$result = self::process_upload( $file, $session_id );
		
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}
		
		wp_send_json_success( $result );
	}

	/**
	 * Verarbeitet eine hochgeladene Datei
	 */
	private static function process_upload( $file, $session_id ) {
		// Dateivalidierung
		$validation = self::validate_file( $file );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Eindeutigen Dateinamen generieren
		$file_extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		$file_name = sanitize_file_name( pathinfo( $file['name'], PATHINFO_FILENAME ) );
		$unique_name = wp_generate_uuid4() . '_' . $file_name . '.' . $file_extension;
		
		// Session-Unterordner erstellen
		$session_dir = self::get_upload_dir() . '/' . $session_id;
		if ( ! file_exists( $session_dir ) ) {
			wp_mkdir_p( $session_dir );
		}
		
		$target_path = $session_dir . '/' . $unique_name;
		
		// Datei verschieben
		if ( ! move_uploaded_file( $file['tmp_name'], $target_path ) ) {
			return new WP_Error( 'upload_failed', 'Fehler beim Speichern der Datei' );
		}
		
		// Dateimetadaten speichern
		$file_data = array(
			'id' => wp_generate_uuid4(),
			'original_name' => $file['name'],
			'stored_name' => $unique_name,
			'file_path' => $target_path,
			'session_id' => $session_id,
			'mime_type' => $file['type'],
			'file_size' => $file['size'],
			'upload_time' => current_time( 'mysql' ),
			'uploader_ip' => self::get_client_ip()
		);
		
		// In Datenbank speichern
		self::save_file_metadata( $file_data );
		
		return $file_data;
	}

	/**
	 * Validiert eine Datei
	 */
	private static function validate_file( $file ) {
		// Dateigröße prüfen
		$max_size = self::get_max_file_size();
		if ( $file['size'] > $max_size ) {
			return new WP_Error( 'file_too_large', sprintf( 
				'Datei ist zu groß. Maximum: %s', 
				size_format( $max_size ) 
			) );
		}
		
		// Dateityp prüfen
		$allowed_types = self::get_allowed_file_types();
		$file_extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		
		if ( ! isset( $allowed_types[ $file_extension ] ) ) {
			return new WP_Error( 'invalid_file_type', sprintf(
				'Dateityp nicht erlaubt. Erlaubte Typen: %s',
				implode( ', ', array_keys( $allowed_types ) )
			) );
		}
		
		// MIME-Type prüfen
		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		$detected_mime = finfo_file( $finfo, $file['tmp_name'] );
		finfo_close( $finfo );
		
		$expected_mime = $allowed_types[ $file_extension ];
		if ( $detected_mime !== $expected_mime ) {
			return new WP_Error( 'mime_type_mismatch', 'Dateityp stimmt nicht mit Dateiendung überein' );
		}
		
		// Dateiname validieren
		if ( ! self::is_safe_filename( $file['name'] ) ) {
			return new WP_Error( 'unsafe_filename', 'Unsicherer Dateiname' );
		}
		
		return true;
	}

	/**
	 * Prüft ob Dateiname sicher ist
	 */
	private static function is_safe_filename( $filename ) {
		// Gefährliche Zeichen und Sequenzen prüfen
		$dangerous = array( '..', '/', '\\', ':', '*', '?', '"', '<', '>', '|', ';', '&' );
		
		foreach ( $dangerous as $char ) {
			if ( strpos( $filename, $char ) !== false ) {
				return false;
			}
		}
		
		// Keine ausführbaren Dateien
		$executable_extensions = array( 'php', 'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js' );
		$extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
		
		return ! in_array( $extension, $executable_extensions );
	}

	/**
	 * Speichert Dateimetadaten in der Datenbank
	 */
	private static function save_file_metadata( $file_data ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'psource_chat_files';
		
		// Tabelle erstellen falls nicht vorhanden
		self::create_files_table();
		
		$wpdb->insert(
			$table_name,
			array(
				'id' => $file_data['id'],
				'session_id' => $file_data['session_id'],
				'original_name' => $file_data['original_name'],
				'stored_name' => $file_data['stored_name'],
				'mime_type' => $file_data['mime_type'],
				'file_size' => $file_data['file_size'],
				'upload_time' => $file_data['upload_time'],
				'uploader_ip' => $file_data['uploader_ip']
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);
	}

	/**
	 * Erstellt Dateien-Tabelle
	 */
	private static function create_files_table() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'psource_chat_files';
		
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id varchar(255) NOT NULL,
			session_id varchar(255) NOT NULL,
			original_name varchar(500) NOT NULL,
			stored_name varchar(500) NOT NULL,
			mime_type varchar(100) NOT NULL,
			file_size bigint NOT NULL,
			upload_time datetime NOT NULL,
			uploader_ip varchar(45) NOT NULL,
			PRIMARY KEY (id),
			KEY session_id (session_id),
			KEY upload_time (upload_time)
		) $charset_collate;";
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Holt Chat-Session
	 */
	private static function get_chat_session( $session_id ) {
		global $psource_chat;
		
		// Chat-Sessions sind keine DB-Einträge, sondern Konfigurationen
		// Verwende die Chat-Instanz um Session-Konfiguration zu holen
		if ( ! $psource_chat ) {
			return false;
		}
		
		// Session-Typen: 'page', 'site', 'widget', 'dashboard', 'bp-group'
		$session_type = 'site'; // Default für bottom_corner
		
		if ( $session_id === 'bottom_corner' ) {
			$session_type = 'site';
		} elseif ( is_numeric( $session_id ) ) {
			$session_type = 'page';
		}
		
		// Hole VOLLSTÄNDIGE Session-Konfiguration aus dem Chat-System
		$full_session_config = array();
		
		// Alle Optionen für den Session-Typ holen
		foreach ( $psource_chat->_chat_options[ $session_type ] as $key => $value ) {
			$full_session_config[ $key ] = $value;
		}
		
		// Wichtige Felder sicherstellen
		$full_session_config['id'] = $session_id;
		$full_session_config['session_type'] = $session_type;
		
		// Blog-ID sicherstellen
		if ( ! isset( $full_session_config['blog_id'] ) ) {
			$full_session_config['blog_id'] = get_current_blog_id();
		}
		
		// Moderator-Status sicherstellen
		if ( ! isset( $full_session_config['moderator'] ) ) {
			$full_session_config['moderator'] = '';
		}
		
		return $full_session_config;
	}

	/**
	 * Holt Client-IP
	 */
	private static function get_client_ip() {
		$ip_keys = array( 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' );
		
		foreach ( $ip_keys as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
					$ip = trim( $ip );
					if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
						return $ip;
					}
				}
			}
		}
		
		return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
	}

	/**
	 * Verarbeitet Upload-Referenzen in Nachrichten
	 */
	public static function process_uploaded_files( $message, $chat_session ) {
		// Nach Upload-Referenzen suchen: [upload:file_id]
		if ( preg_match_all( '/\[upload:([a-f0-9\-]{36})\]/', $message, $matches ) ) {
			$file_attachments = array();
			
			foreach ( $matches[1] as $file_id ) {
				$file_data = self::get_file_metadata( $file_id );
				if ( $file_data ) {
					$file_attachments[] = $file_data;
				}
			}
			
			if ( ! empty( $file_attachments ) ) {
				$message .= '<!--PSOURCE_CHAT_FILES:' . base64_encode( json_encode( $file_attachments ) ) . '-->';
			}
		}
		
		return $message;
	}

	/**
	 * Rendert Datei-Anhänge in Nachrichten
	 */
	public static function render_file_attachments( $message, $message_data ) {
		// Datei-Daten aus verstecktem HTML-Kommentar extrahieren
		if ( preg_match( '/<!--PSOURCE_CHAT_FILES:(.*?)-->/', $message, $matches ) ) {
			$file_attachments = json_decode( base64_decode( $matches[1] ), true );
			
			// Datei-Kommentar aus der sichtbaren Nachricht entfernen
			$message = preg_replace( '/<!--PSOURCE_CHAT_FILES:.*?-->/', '', $message );
			
			if ( ! empty( $file_attachments ) ) {
				$message .= self::render_file_attachments_html( $file_attachments );
			}
		}
		
		return $message;
	}

	/**
	 * Rendert HTML für Datei-Anhänge
	 */
	private static function render_file_attachments_html( $attachments ) {
		$html = '<div class="psource-chat-file-attachments">';
		
		foreach ( $attachments as $file ) {
			$html .= self::render_single_file_attachment( $file );
		}
		
		$html .= '</div>';
		
		return $html;
	}

	/**
	 * Rendert einzelnen Datei-Anhang
	 */
	private static function render_single_file_attachment( $file ) {
		$file_type = self::get_file_type_category( $file['mime_type'] );
		$file_size = size_format( $file['file_size'] );
		$download_url = self::get_download_url( $file['id'] );
		
		$html = '<div class="psource-chat-file-attachment" data-file-type="' . esc_attr( $file_type ) . '">';
		
		// Icon basierend auf Dateityp
		$icon = self::get_file_type_icon( $file_type );
		$html .= '<div class="psource-chat-file-icon">' . $icon . '</div>';
		
		// Dateiinfos
		$html .= '<div class="psource-chat-file-info">';
		$html .= '<div class="psource-chat-file-name">' . esc_html( $file['original_name'] ) . '</div>';
		$html .= '<div class="psource-chat-file-size">' . $file_size . '</div>';
		$html .= '</div>';
		
		// Download-Button
		$html .= '<div class="psource-chat-file-actions">';
		$html .= '<a href="' . esc_url( $download_url ) . '" class="psource-chat-file-download" target="_blank">';
		$html .= '<span class="dashicons dashicons-download"></span> Download';
		$html .= '</a>';
		$html .= '</div>';
		
		$html .= '</div>';
		
		return $html;
	}

	/**
	 * Holt Dateityp-Kategorie
	 */
	private static function get_file_type_category( $mime_type ) {
		if ( strpos( $mime_type, 'image/' ) === 0 ) return 'image';
		if ( strpos( $mime_type, 'video/' ) === 0 ) return 'video';
		if ( strpos( $mime_type, 'audio/' ) === 0 ) return 'audio';
		if ( strpos( $mime_type, 'application/pdf' ) === 0 ) return 'pdf';
		if ( strpos( $mime_type, 'application/zip' ) === 0 ) return 'archive';
		if ( strpos( $mime_type, 'text/' ) === 0 ) return 'text';
		
		return 'document';
	}

	/**
	 * Holt Icon für Dateityp
	 */
	private static function get_file_type_icon( $file_type ) {
		$icons = array(
			'image' => '<span class="dashicons dashicons-format-image"></span>',
			'video' => '<span class="dashicons dashicons-format-video"></span>',
			'audio' => '<span class="dashicons dashicons-format-audio"></span>',
			'pdf' => '<span class="dashicons dashicons-pdf"></span>',
			'archive' => '<span class="dashicons dashicons-archive"></span>',
			'text' => '<span class="dashicons dashicons-text"></span>',
			'document' => '<span class="dashicons dashicons-media-document"></span>'
		);
		
		return isset( $icons[ $file_type ] ) ? $icons[ $file_type ] : $icons['document'];
	}

	/**
	 * Generiert Download-URL
	 */
	private static function get_download_url( $file_id ) {
		return admin_url( 'admin-ajax.php?action=psource_chat_download_file&file_id=' . urlencode( $file_id ) . '&nonce=' . wp_create_nonce( 'psource_chat_download_' . $file_id ) );
	}

	/**
	 * AJAX-Handler für Datei-Download
	 */
	public static function ajax_download_file() {
		$file_id = sanitize_text_field( $_GET['file_id'] ?? '' );
		$nonce = sanitize_text_field( $_GET['nonce'] ?? '' );
		
		if ( empty( $file_id ) || ! wp_verify_nonce( $nonce, 'psource_chat_download_' . $file_id ) ) {
			wp_die( 'Ungültiger Download-Link' );
		}
		
		$file_data = self::get_file_metadata( $file_id );
		if ( ! $file_data ) {
			wp_die( 'Datei nicht gefunden' );
		}
		
		$file_path = self::get_upload_dir() . '/' . $file_data['session_id'] . '/' . $file_data['stored_name'];
		
		if ( ! file_exists( $file_path ) ) {
			wp_die( 'Datei existiert nicht mehr' );
		}
		
		// Prüfen ob es ein Bild ist
		$file_extension = strtolower( pathinfo( $file_data['original_name'], PATHINFO_EXTENSION ) );
		$is_image = in_array( $file_extension, array( 'jpg', 'jpeg', 'png', 'gif', 'webp' ) );
		
		// Headers setzen
		header( 'Content-Type: ' . $file_data['mime_type'] );
		
		// Für Bilder inline anzeigen, für andere Dateien als Download
		if ( $is_image ) {
			header( 'Content-Disposition: inline; filename="' . $file_data['original_name'] . '"' );
		} else {
			header( 'Content-Disposition: attachment; filename="' . $file_data['original_name'] . '"' );
		}
		
		header( 'Content-Length: ' . filesize( $file_path ) );
		header( 'Cache-Control: no-cache, must-revalidate' );
		
		readfile( $file_path );
		exit;
	}

	/**
	 * Holt Dateimetadaten
	 */
	private static function get_file_metadata( $file_id ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'psource_chat_files';
		
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE id = %s",
			$file_id
		), ARRAY_A );
	}

	/**
	 * Bereinigt Dateien einer Chat-Session
	 */
	public static function cleanup_session_files( $session_id ) {
		global $wpdb;
		
		// Dateien aus Dateisystem löschen
		$session_dir = self::get_upload_dir() . '/' . $session_id;
		if ( file_exists( $session_dir ) ) {
			self::recursive_rmdir( $session_dir );
		}
		
		// Metadaten aus Datenbank löschen
		$table_name = $wpdb->prefix . 'psource_chat_files';
		$wpdb->delete( $table_name, array( 'session_id' => $session_id ), array( '%s' ) );
	}

	/**
	 * Bereinigt verwaiste Upload-Dateien (ältere als 24h ohne Chat-Referenz)
	 */
	public static function cleanup_orphaned_files() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'psource_chat_files';
		$message_table = PSOURCE_Chat::tablename( 'message' );
		
		// Finde Dateien die älter als 24 Stunden sind
		$old_files = $wpdb->get_results( $wpdb->prepare(
			"SELECT f.* FROM {$table_name} f 
			 WHERE f.upload_time < %s 
			 AND NOT EXISTS (
				 SELECT 1 FROM {$message_table} m 
				 WHERE m.message LIKE CONCAT('%%[upload:', f.id, ']%%')
			 )",
			date( 'Y-m-d H:i:s', strtotime( '-24 hours' ) )
		), ARRAY_A );
		
		$deleted_count = 0;
		foreach ( $old_files as $file ) {
			// Physische Datei löschen
			$file_path = self::get_upload_dir() . '/' . $file['session_id'] . '/' . $file['stored_name'];
			if ( file_exists( $file_path ) ) {
				unlink( $file_path );
			}
			
			// DB-Eintrag löschen
			$wpdb->delete( $table_name, array( 'id' => $file['id'] ), array( '%s' ) );
			$deleted_count++;
		}
		
		// Log für Admin
		if ( $deleted_count > 0 ) {
			error_log( "PS Chat Upload Cleanup: {$deleted_count} verwaiste Dateien gelöscht" );
		}
		
		return $deleted_count;
	}
	
	/**
	 * Aktiviert tägliche Cleanup-Aufgabe
	 */
	public static function schedule_cleanup() {
		if ( ! wp_next_scheduled( 'psource_chat_cleanup_uploads' ) ) {
			wp_schedule_event( time(), 'daily', 'psource_chat_cleanup_uploads' );
		}
	}
	
	/**
	 * Deaktiviert Cleanup-Aufgabe
	 */
	public static function unschedule_cleanup() {
		wp_clear_scheduled_hook( 'psource_chat_cleanup_uploads' );
	}

	/**
	 * Rekursive Verzeichnis-Löschung
	 */
	private static function recursive_rmdir( $dir ) {
		if ( is_dir( $dir ) ) {
			$objects = scandir( $dir );
			foreach ( $objects as $object ) {
				if ( $object != "." && $object != ".." ) {
					if ( is_dir( $dir . "/" . $object ) ) {
						self::recursive_rmdir( $dir . "/" . $object );
					} else {
						unlink( $dir . "/" . $object );
					}
				}
			}
			rmdir( $dir );
		}
	}

	/**
	 * Erstellt HTML für Datei-Anzeige in Chat-Nachrichten
	 */
	public static function get_file_display_html( $file_data, $message_text = '' ) {
		$file_extension = strtolower( pathinfo( $file_data['original_name'], PATHINFO_EXTENSION ) );
		$is_image = in_array( $file_extension, array( 'jpg', 'jpeg', 'png', 'gif', 'webp' ) );
		$is_video = in_array( $file_extension, array( 'mp4', 'webm', 'ogg' ) );
		
		$download_url = admin_url( 'admin-ajax.php' ) . '?action=psource_chat_download_file&file_id=' . urlencode( $file_data['stored_name'] );
		$file_size_formatted = size_format( $file_data['file_size'] );
		
		$html = '<div class="psource-chat-file-attachment">';
		
		if ( $is_image ) {
			// Bild-Vorschau mit kleinem Download-Link
			$html .= '<div class="psource-chat-image-preview">';
			$html .= '<img src="' . esc_url( $download_url ) . '" alt="' . esc_attr( $file_data['original_name'] ) . '" style="max-width: 100%; height: auto; border-radius: 8px; cursor: pointer;">';
			$html .= '<div class="psource-chat-image-download">';
			$html .= '<a href="' . esc_url( $download_url ) . '" download="' . esc_attr( $file_data['original_name'] ) . '" class="psource-chat-image-download-link" title="' . esc_attr( $file_data['original_name'] ) . ' herunterladen">';
			$html .= '<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M5,20H19V18H5M19,9H15V3H9V9H5L12,16L19,9Z"/></svg>';
			$html .= '</a>';
			$html .= '</div>';
			$html .= '</div>';
			
		} elseif ( $is_video ) {
			// Video-Player mit Download-Link
			$html .= '<div class="psource-chat-video-preview">';
			$html .= '<video controls style="max-width: 300px; max-height: 200px; border-radius: 8px;">';
			$html .= '<source src="' . esc_url( $download_url ) . '" type="' . esc_attr( $file_data['mime_type'] ) . '">';
			$html .= 'Dein Browser unterstützt das Video-Element nicht.';
			$html .= '</video>';
			$html .= '<div class="psource-chat-file-info">';
			$html .= '<span class="psource-chat-file-name">' . esc_html( $file_data['original_name'] ) . '</span>';
			$html .= '<span class="psource-chat-file-size">(' . $file_size_formatted . ')</span>';
			$html .= '<a href="' . esc_url( $download_url ) . '" download="' . esc_attr( $file_data['original_name'] ) . '" class="psource-chat-download-link">';
			$html .= '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M5,20H19V18H5M19,9H15V3H9V9H5L12,16L19,9Z"/></svg>';
			$html .= ' Download</a>';
			$html .= '</div>';
			$html .= '</div>';
			
		} else {
			// Standard-Datei mit Icon
			$html .= '<div class="psource-chat-file-download">';
			$html .= '<div class="psource-chat-file-icon">';
			
			// Datei-Icon basierend auf Typ
			if ( in_array( $file_extension, array( 'pdf' ) ) ) {
				$html .= '<svg width="24" height="24" viewBox="0 0 24 24" fill="#d32f2f"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/></svg>';
			} elseif ( in_array( $file_extension, array( 'doc', 'docx' ) ) ) {
				$html .= '<svg width="24" height="24" viewBox="0 0 24 24" fill="#1976d2"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/></svg>';
			} elseif ( in_array( $file_extension, array( 'zip', 'rar', '7z' ) ) ) {
				$html .= '<svg width="24" height="24" viewBox="0 0 24 24" fill="#ff9800"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/></svg>';
			} else {
				$html .= '<svg width="24" height="24" viewBox="0 0 24 24" fill="#666"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/></svg>';
			}
			
			$html .= '</div>';
			$html .= '<div class="psource-chat-file-details">';
			$html .= '<span class="psource-chat-file-name">' . esc_html( $file_data['original_name'] ) . '</span>';
			$html .= '<span class="psource-chat-file-size">' . $file_size_formatted . '</span>';
			$html .= '<a href="' . esc_url( $download_url ) . '" download="' . esc_attr( $file_data['original_name'] ) . '" class="psource-chat-download-link">';
			$html .= '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M5,20H19V18H5M19,9H15V3H9V9H5L12,16L19,9Z"/></svg>';
			$html .= ' Download</a>';
			$html .= '</div>';
			$html .= '</div>';
		}
		
		$html .= '</div>';
		
		// Wenn Text dabei ist, füge ihn hinzu
		if ( ! empty( $message_text ) ) {
			$html = '<div class="psource-chat-message-text">' . esc_html( $message_text ) . '</div>' . $html;
		}
		
		return $html;
	}

	/**
	 * Initialisiert Message-Filter für Upload-Referenzen
	 */
	public static function init_message_filters() {
		// Nur der Filter, keine anderen Hooks
		add_filter( 'psource_chat_display_message', array( __CLASS__, 'process_upload_references' ), 5, 2 );
	}

	/**
	 * Verarbeitet Upload-Referenzen in Chat-Nachrichten
	 */
	public static function process_upload_references( $message_content, $message_data = null ) {
		// Sicherheitscheck
		if ( empty( $message_content ) || ! is_string( $message_content ) ) {
			return $message_content;
		}
		
		// Suche nach [upload:ID] Referenzen mit UUID-Pattern
		$pattern = '/\[upload:([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})\]/';
		
		return preg_replace_callback( $pattern, function( $matches ) {
			$file_id = $matches[1];
			
			try {
				// Tabelle sicherstellen
				self::create_files_table();
				
				$file_data = self::get_file_by_id( $file_id );
				
				if ( ! $file_data ) {
					// Debug-Info für nicht gefundene Dateien
					error_log( 'PS Chat Upload: Datei mit ID ' . $file_id . ' nicht in DB gefunden' );
					return '<span style="color: #e74c3c; font-style: italic;">[Datei nicht gefunden]</span>';
				}
				
				// Vollständigen Pfad konstruieren
				$session_id = $file_data['session_id'];
				$file_path = self::get_upload_dir() . '/' . $session_id . '/' . $file_data['stored_name'];
				
				if ( ! file_exists( $file_path ) ) {
					error_log( 'PS Chat Upload: Datei physisch nicht vorhanden: ' . $file_path );
					return '<span style="color: #e74c3c; font-style: italic;">[Datei physisch nicht gefunden]</span>';
				}
				
				// Download-URL mit Nonce generieren
				$nonce = wp_create_nonce( 'psource_chat_download_' . $file_id );
				$download_url = admin_url( 'admin-ajax.php' ) . '?action=psource_chat_download_file&file_id=' . urlencode( $file_id ) . '&nonce=' . $nonce;
				$file_extension = strtolower( pathinfo( $file_data['original_name'], PATHINFO_EXTENSION ) );
				
				// Bilder mit Vorschau anzeigen
				if ( in_array( $file_extension, array( 'jpg', 'jpeg', 'png', 'gif', 'webp' ) ) ) {
					return '<div class="psource-chat-image-preview">' .
						   '<img src="' . esc_url( $download_url ) . '" alt="' . esc_attr( $file_data['original_name'] ) . '" ' .
						   'style="max-width: 300px; max-height: 200px; height: auto; border-radius: 8px; margin: 5px 0; cursor: pointer;" ' .
						   'title="' . esc_attr( $file_data['original_name'] ) . '" />' .
						   '</div>';
				}
				
				// Videos anzeigen
				if ( in_array( $file_extension, array( 'mp4', 'webm', 'ogg' ) ) ) {
					return '<div class="psource-chat-video-preview">' .
						   '<video controls style="max-width: 300px; max-height: 200px; border-radius: 8px; margin: 5px 0;">' .
						   '<source src="' . esc_url( $download_url ) . '" type="' . esc_attr( $file_data['mime_type'] ) . '">' .
						   'Ihr Browser unterstützt das Video-Element nicht.' .
						   '</video>' .
						   '</div>';
				}
				
				// Andere Dateien als Link mit Icon
				$file_size = size_format( $file_data['file_size'] );
				return '<div class="psource-chat-file-link">' .
					   '<a href="' . esc_url( $download_url ) . '" target="_blank" ' .
					   'style="display: inline-flex; align-items: center; padding: 8px 12px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; text-decoration: none; color: #495057; margin: 5px 0;">' .
					   '<span style="margin-right: 8px;">📎</span>' .
					   '<span><strong>' . esc_html( $file_data['original_name'] ) . '</strong><br>' .
					   '<small style="color: #6c757d;">' . esc_html( $file_size ) . '</small></span>' .
					   '</a>' .
					   '</div>';
				
			} catch ( Exception $e ) {
				// Fehler loggen und ursprüngliche Referenz zurückgeben
				error_log( 'PS Chat Upload Filter Error: ' . $e->getMessage() );
				return '<span style="color: #e74c3c; font-style: italic;">[Upload-Fehler: ' . esc_html( $e->getMessage() ) . ']</span>';
			}
		}, $message_content );
	}

	/**
	 * Holt Datei-Daten basierend auf ID
	 */
	private static function get_file_by_id( $file_id ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'psource_chat_files';
		
		$sql_str = $wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE id = %s",
			$file_id
		);
		
		return $wpdb->get_row( $sql_str, ARRAY_A );
	}
}

// Upload-Handler initialisieren
PSource_Chat_Upload::init();
