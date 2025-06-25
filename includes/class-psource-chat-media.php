<?php
/**
 * PSource Chat Media Handler
 * 
 * Handles link previews, image displays, video embeds, and other rich media content
 * 
 * @package PSource_Chat
 * @subpackage Media
 * @since 2.5.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PSource_Chat_Media {

	/**
	 * Cache für Media-Metadaten
	 * @var array
	 */
	private static $cache = array();

	/**
	 * Unterstützte Bild-Formate
	 * @var array
	 */
	private static $image_extensions = array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp' );

	/**
	 * Unterstützte Video-Formate
	 * @var array
	 */
	private static $video_extensions = array( 'mp4', 'webm', 'ogg', 'avi', 'mov' );

	/**
	 * Media-Handler initialisieren
	 */
	public static function init() {
		// Hook für Nachrichtenverarbeitung
		add_filter( 'psource_chat_before_save_message', array( __CLASS__, 'process_message_media' ), 10, 2 );
		add_filter( 'psource_chat_display_message', array( __CLASS__, 'render_message_media' ), 10, 2 );
		
		// AJAX-Handler für Media-Previews
		add_action( 'wp_ajax_psource_chat_get_link_preview', array( __CLASS__, 'ajax_get_link_preview' ) );
		add_action( 'wp_ajax_nopriv_psource_chat_get_link_preview', array( __CLASS__, 'ajax_get_link_preview' ) );
	}

	/**
	 * Verarbeitet Nachrichten und extrahiert Media-URLs
	 *
	 * @param string $message Die Nachricht
	 * @param array $chat_session Chat-Session-Daten
	 * @return string Verarbeitete Nachricht
	 */
	public static function process_message_media( $message, $chat_session ) {
		// URLs in der Nachricht finden
		$urls = self::extract_urls( $message );
		
		if ( empty( $urls ) ) {
			return $message;
		}

		$media_data = array();
		
		foreach ( $urls as $url ) {
			$media_info = self::analyze_url( $url );
			if ( $media_info ) {
				$media_data[] = $media_info;
			}
		}

		// Media-Daten als JSON in der Nachricht speichern (versteckt)
		if ( ! empty( $media_data ) ) {
			$message .= '<!--PSOURCE_CHAT_MEDIA:' . base64_encode( json_encode( $media_data ) ) . '-->';
		}

		return $message;
	}

	/**
	 * Rendert Media-Inhalte in der Nachricht
	 *
	 * @param string $message Die Nachricht
	 * @param array $message_data Nachrichtendaten aus der DB
	 * @return string Gerenderte Nachricht mit Media
	 */
	public static function render_message_media( $message, $message_data ) {
		// Media-Daten aus verstecktem HTML-Kommentar extrahieren
		if ( preg_match( '/<!--PSOURCE_CHAT_MEDIA:(.*?)-->/', $message, $matches ) ) {
			$media_data = json_decode( base64_decode( $matches[1] ), true );
			
			// Media-Kommentar aus der sichtbaren Nachricht entfernen
			$message = preg_replace( '/<!--PSOURCE_CHAT_MEDIA:.*?-->/', '', $message );
			
			if ( ! empty( $media_data ) ) {
				$message .= self::render_media_content( $media_data );
			}
		}

		return $message;
	}

	/**
	 * Extrahiert alle URLs aus einer Nachricht
	 *
	 * @param string $message
	 * @return array Array von URLs
	 */
	private static function extract_urls( $message ) {
		$pattern = '/(https?:\/\/[^\s<>"{}|\\^`\[\]]+)/i';
		preg_match_all( $pattern, $message, $matches );
		return isset( $matches[1] ) ? array_unique( $matches[1] ) : array();
	}

	/**
	 * Analysiert eine URL und bestimmt den Media-Typ
	 *
	 * @param string $url
	 * @return array|false Media-Informationen oder false
	 */
	private static function analyze_url( $url ) {
		// Cache prüfen
		$cache_key = md5( $url );
		if ( isset( self::$cache[ $cache_key ] ) ) {
			return self::$cache[ $cache_key ];
		}

		$parsed_url = parse_url( $url );
		if ( ! $parsed_url ) {
			return false;
		}

		$media_info = array(
			'url' => $url,
			'type' => 'link',
			'title' => '',
			'description' => '',
			'image' => '',
			'site_name' => $parsed_url['host']
		);

		// YouTube-Video erkennen
		if ( self::is_youtube_url( $url ) ) {
			$video_id = self::extract_youtube_id( $url );
			if ( $video_id ) {
				$media_info['type'] = 'youtube';
				$media_info['video_id'] = $video_id;
				$media_info['thumbnail'] = "https://img.youtube.com/vi/{$video_id}/maxresdefault.jpg";
				$media_info['title'] = self::get_youtube_title( $video_id );
			}
		}
		// Direktes Bild erkennen
		elseif ( self::is_image_url( $url ) ) {
			$media_info['type'] = 'image';
			$media_info['image'] = $url;
		}
		// Direktes Video erkennen
		elseif ( self::is_video_url( $url ) ) {
			$media_info['type'] = 'video';
			$media_info['video_url'] = $url;
		}
		// Link-Preview für normale Webseiten
		else {
			$preview_data = self::get_link_preview( $url );
			if ( $preview_data ) {
				$media_info = array_merge( $media_info, $preview_data );
			}
		}

		// In Cache speichern
		self::$cache[ $cache_key ] = $media_info;
		
		return $media_info;
	}

	/**
	 * Prüft ob URL ein YouTube-Video ist
	 *
	 * @param string $url
	 * @return bool
	 */
	private static function is_youtube_url( $url ) {
		return preg_match( '/(?:youtube\.com|youtu\.be)/', $url );
	}

	/**
	 * Extrahiert YouTube-Video-ID
	 *
	 * @param string $url
	 * @return string|false
	 */
	private static function extract_youtube_id( $url ) {
		$patterns = array(
			'/youtube\.com\/watch\?v=([^&\n?#]+)/',
			'/youtube\.com\/embed\/([^&\n?#]+)/',
			'/youtu\.be\/([^&\n?#]+)/'
		);

		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $url, $matches ) ) {
				return $matches[1];
			}
		}

		return false;
	}

	/**
	 * Holt YouTube-Video-Titel über oEmbed API
	 *
	 * @param string $video_id
	 * @return string
	 */
	private static function get_youtube_title( $video_id ) {
		$oembed_url = "https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v={$video_id}&format=json";
		
		$response = wp_remote_get( $oembed_url, array( 'timeout' => 5 ) );
		
		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			return isset( $data['title'] ) ? $data['title'] : '';
		}

		return '';
	}

	/**
	 * Prüft ob URL ein Bild ist
	 *
	 * @param string $url
	 * @return bool
	 */
	private static function is_image_url( $url ) {
		$extension = strtolower( pathinfo( parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
		return in_array( $extension, self::$image_extensions );
	}

	/**
	 * Prüft ob URL ein Video ist
	 *
	 * @param string $url
	 * @return bool
	 */
	private static function is_video_url( $url ) {
		$extension = strtolower( pathinfo( parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
		return in_array( $extension, self::$video_extensions );
	}

	/**
	 * Holt Link-Preview-Daten von einer Website
	 *
	 * @param string $url
	 * @return array|false
	 */
	private static function get_link_preview( $url ) {
		// Transient für Caching prüfen
		$cache_key = 'psource_chat_preview_' . md5( $url );
		$cached = get_transient( $cache_key );
		if ( $cached !== false ) {
			return $cached;
		}

		$response = wp_remote_get( $url, array(
			'timeout' => 10,
			'user-agent' => 'Mozilla/5.0 (compatible; PSChat-LinkPreview/1.0)'
		) );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return false;
		}

		$html = wp_remote_retrieve_body( $response );
		$preview_data = array();

		// Open Graph Meta-Tags parsen
		$preview_data = array_merge( $preview_data, self::parse_open_graph( $html ) );
		
		// Twitter Card Meta-Tags als Fallback
		if ( empty( $preview_data['title'] ) || empty( $preview_data['description'] ) ) {
			$twitter_data = self::parse_twitter_cards( $html );
			$preview_data = array_merge( $twitter_data, $preview_data );
		}

		// Standard HTML Meta-Tags als weiterer Fallback
		if ( empty( $preview_data['title'] ) || empty( $preview_data['description'] ) ) {
			$html_data = self::parse_html_meta( $html );
			$preview_data = array_merge( $html_data, $preview_data );
		}

		// Ergebnis für 1 Stunde cachen
		set_transient( $cache_key, $preview_data, HOUR_IN_SECONDS );

		return $preview_data;
	}

	/**
	 * Parst Open Graph Meta-Tags
	 *
	 * @param string $html
	 * @return array
	 */
	private static function parse_open_graph( $html ) {
		$data = array();
		
		// Open Graph Meta-Tags extrahieren
		if ( preg_match( '/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $html, $matches ) ) {
			$data['title'] = html_entity_decode( $matches[1], ENT_QUOTES, 'UTF-8' );
		}
		
		if ( preg_match( '/<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $html, $matches ) ) {
			$data['description'] = html_entity_decode( $matches[1], ENT_QUOTES, 'UTF-8' );
		}
		
		if ( preg_match( '/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $html, $matches ) ) {
			$data['image'] = $matches[1];
		}
		
		if ( preg_match( '/<meta[^>]+property=["\']og:site_name["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $html, $matches ) ) {
			$data['site_name'] = html_entity_decode( $matches[1], ENT_QUOTES, 'UTF-8' );
		}

		return $data;
	}

	/**
	 * Parst Twitter Card Meta-Tags
	 *
	 * @param string $html
	 * @return array
	 */
	private static function parse_twitter_cards( $html ) {
		$data = array();
		
		if ( preg_match( '/<meta[^>]+name=["\']twitter:title["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $html, $matches ) ) {
			$data['title'] = html_entity_decode( $matches[1], ENT_QUOTES, 'UTF-8' );
		}
		
		if ( preg_match( '/<meta[^>]+name=["\']twitter:description["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $html, $matches ) ) {
			$data['description'] = html_entity_decode( $matches[1], ENT_QUOTES, 'UTF-8' );
		}
		
		if ( preg_match( '/<meta[^>]+name=["\']twitter:image["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $html, $matches ) ) {
			$data['image'] = $matches[1];
		}

		return $data;
	}

	/**
	 * Parst Standard HTML Meta-Tags
	 *
	 * @param string $html
	 * @return array
	 */
	private static function parse_html_meta( $html ) {
		$data = array();
		
		// Title-Tag
		if ( preg_match( '/<title[^>]*>([^<]+)<\/title>/i', $html, $matches ) ) {
			$data['title'] = html_entity_decode( trim( $matches[1] ), ENT_QUOTES, 'UTF-8' );
		}
		
		// Meta Description
		if ( preg_match( '/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $html, $matches ) ) {
			$data['description'] = html_entity_decode( $matches[1], ENT_QUOTES, 'UTF-8' );
		}

		return $data;
	}

	/**
	 * Rendert Media-Content als HTML
	 *
	 * @param array $media_data
	 * @return string
	 */
	private static function render_media_content( $media_data ) {
		$html = '';
		
		foreach ( $media_data as $media ) {
			$html .= '<div class="psource-chat-media-item" data-type="' . esc_attr( $media['type'] ) . '">';
			
			switch ( $media['type'] ) {
				case 'youtube':
					$html .= self::render_youtube_embed( $media );
					break;
					
				case 'image':
					$html .= self::render_image( $media );
					break;
					
				case 'video':
					$html .= self::render_video( $media );
					break;
					
				case 'link':
				default:
					$html .= self::render_link_preview( $media );
					break;
			}
			
			$html .= '</div>';
		}
		
		return $html;
	}

	/**
	 * Rendert YouTube-Embed
	 *
	 * @param array $media
	 * @return string
	 */
	private static function render_youtube_embed( $media ) {
		$video_id = esc_attr( $media['video_id'] );
		$title = ! empty( $media['title'] ) ? esc_html( $media['title'] ) : 'YouTube Video';
		
		return '
		<div class="psource-chat-youtube">
			<div class="psource-chat-youtube-thumbnail" data-video-id="' . $video_id . '">
				<img src="' . esc_url( $media['thumbnail'] ) . '" alt="' . $title . '">
				<div class="psource-chat-youtube-play">
					<svg width="68" height="48" viewBox="0 0 68 48">
						<path d="M66.52,7.74c-0.78-2.93-2.49-5.41-5.42-6.19C55.79,.13,34,0,34,0S12.21,.13,6.9,1.55 C3.97,2.33,2.27,4.81,1.48,7.74C0.06,13.05,0,24,0,24s0.06,10.95,1.48,16.26c0.78,2.93,2.49,5.41,5.42,6.19 C12.21,47.87,34,48,34,48s21.79-0.13,27.1-1.55c2.93-0.78,4.64-3.26,5.42-6.19C67.94,34.95,68,24,68,24S67.94,13.05,66.52,7.74z" fill="#f00"></path>
						<path d="M 45,24 27,14 27,34" fill="#fff"></path>
					</svg>
				</div>
			</div>
			<div class="psource-chat-youtube-info">
				<div class="psource-chat-youtube-title">' . $title . '</div>
				<div class="psource-chat-youtube-channel">YouTube</div>
			</div>
		</div>';
	}

	/**
	 * Rendert Bild
	 *
	 * @param array $media
	 * @return string
	 */
	private static function render_image( $media ) {
		return '
		<div class="psource-chat-image">
			<img src="' . esc_url( $media['image'] ) . '" alt="Geteiltes Bild" loading="lazy">
		</div>';
	}

	/**
	 * Rendert Video
	 *
	 * @param array $media
	 * @return string
	 */
	private static function render_video( $media ) {
		return '
		<div class="psource-chat-video">
			<video controls preload="metadata">
				<source src="' . esc_url( $media['video_url'] ) . '">
				Dein Browser unterstützt das Video-Element nicht.
			</video>
		</div>';
	}

	/**
	 * Rendert Link-Preview
	 *
	 * @param array $media
	 * @return string
	 */
	private static function render_link_preview( $media ) {
		$title = ! empty( $media['title'] ) ? esc_html( $media['title'] ) : esc_html( $media['url'] );
		$description = ! empty( $media['description'] ) ? esc_html( wp_trim_words( $media['description'], 20 ) ) : '';
		$site_name = ! empty( $media['site_name'] ) ? esc_html( $media['site_name'] ) : '';
		$image = ! empty( $media['image'] ) ? $media['image'] : '';
		
		$html = '<div class="psource-chat-link-preview">';
		$html .= '<a href="' . esc_url( $media['url'] ) . '" target="_blank" rel="noopener">';
		
		if ( $image ) {
			$html .= '<div class="psource-chat-link-image">';
			$html .= '<img src="' . esc_url( $image ) . '" alt="' . $title . '" loading="lazy">';
			$html .= '</div>';
		}
		
		$html .= '<div class="psource-chat-link-content">';
		$html .= '<div class="psource-chat-link-title">' . $title . '</div>';
		
		if ( $description ) {
			$html .= '<div class="psource-chat-link-description">' . $description . '</div>';
		}
		
		if ( $site_name ) {
			$html .= '<div class="psource-chat-link-site">' . $site_name . '</div>';
		}
		
		$html .= '</div>';
		$html .= '</a>';
		$html .= '</div>';
		
		return $html;
	}

	/**
	 * AJAX-Handler für Link-Previews
	 */
	public static function ajax_get_link_preview() {
		// Nonce prüfen
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'psource_chat_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		$url = sanitize_url( $_REQUEST['url'] ?? '' );
		
		if ( empty( $url ) ) {
			wp_send_json_error( 'Keine URL angegeben' );
		}

		$media_info = self::analyze_url( $url );
		
		if ( $media_info ) {
			wp_send_json_success( $media_info );
		} else {
			wp_send_json_error( 'Konnte keine Preview-Daten laden' );
		}
	}
}

// Media-Handler initialisieren
PSource_Chat_Media::init();
