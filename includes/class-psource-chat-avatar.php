<?php
/**
 * PSource Chat Avatar Handler
 * 
 * Modern, robust avatar handling with CP Community support, fallbacks, and local placeholders.
 * 
 * Features:
 * - CP Community avatar support (primary)
 * - WordPress/Gravatar fallback
 * - Local placeholder for missing avatars
 * - URL-only or HTML output options
 * - Caching for performance
 * 
 * @package PSource_Chat
 * @subpackage Avatar
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PSource_Chat_Avatar {

	/**
	 * Cache for avatar URLs to prevent repeated processing
	 * @var array
	 */
	private static $avatar_cache = array();

	/**
	 * Default placeholder avatar (data URI for a simple user icon)
	 * @var string
	 */
	private static $default_placeholder = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMzIiIGN5PSIzMiIgcj0iMzIiIGZpbGw9IiNlMGUwZTAiLz4KPGNpcmNsZSBjeD0iMzIiIGN5PSIyNCIgcj0iMTAiIGZpbGw9IiM5OTk5OTkiLz4KPHBhdGggZD0iTTMyIDQwQzI0IDQwIDE4IDQ2IDE4IDU0VjYwSDQ2VjU0QzQ2IDQ2IDQwIDQwIDMyIDQwWiIgZmlsbD0iIzk5OTk5OSIvPgo8L3N2Zz4K';

	/**
	 * Get avatar for a user with intelligent fallback system
	 * 
	 * Priority order:
	 * 1. CP Community avatar (if available)
	 * 2. WordPress/Gravatar avatar
	 * 3. Local placeholder
	 * 
	 * @param int $user_id User ID
	 * @param int $size Avatar size (default: 96)
	 * @param bool $html_output Return HTML img tag (true) or URL only (false)
	 * @param array $args Additional arguments
	 * @return string Avatar HTML or URL
	 */
	public static function get_avatar( $user_id, $size = 96, $html_output = false, $args = array() ) {
		// Validate input
		$user_id = intval( $user_id );
		$size = intval( $size );
		if ( $user_id <= 0 || $size <= 0 ) {
			return self::get_placeholder_avatar( $size, $html_output );
		}

		// Check cache first
		$cache_key = "user_{$user_id}_size_{$size}_html_" . ( $html_output ? '1' : '0' );
		if ( isset( self::$avatar_cache[ $cache_key ] ) ) {
			return self::$avatar_cache[ $cache_key ];
		}

		$avatar_url = '';
		$avatar_html = '';

		// Try CP Community avatar first
		$cp_avatar = self::get_cp_community_avatar( $user_id, $size );
		if ( $cp_avatar ) {
			$avatar_url = $cp_avatar;
		}

		// Fallback to WordPress/Gravatar
		if ( empty( $avatar_url ) ) {
			$wp_avatar = self::get_wordpress_avatar( $user_id, $size );
			if ( $wp_avatar ) {
				$avatar_url = $wp_avatar;
			}
		}

		// Final fallback to placeholder
		if ( empty( $avatar_url ) ) {
			$avatar_url = self::$default_placeholder;
		}

		// Build HTML if requested
		if ( $html_output ) {
			$user = get_user_by( 'id', $user_id );
			$alt_text = $user ? esc_attr( $user->display_name ) : __( 'User Avatar', 'psource-chat' );
			
			$classes = array( 'avatar', 'avatar-' . $size, 'photo' );
			if ( isset( $args['class'] ) ) {
				$classes[] = $args['class'];
			}
			
			$avatar_html = sprintf(
				'<img src="%s" alt="%s" class="%s" width="%d" height="%d" style="width: %dpx !important; height: %dpx !important;" />',
				esc_url( $avatar_url ),
				$alt_text,
				esc_attr( implode( ' ', $classes ) ),
				$size,
				$size,
				$size,
				$size
			);
			$result = $avatar_html;
		} else {
			$result = $avatar_url;
		}

		// Cache the result
		self::$avatar_cache[ $cache_key ] = $result;

		return $result;
	}

	/**
	 * Get CP Community avatar URL if available
	 * 
	 * @param int $user_id User ID
	 * @param int $size Avatar size
	 * @return string|false Avatar URL or false if not available
	 */
	private static function get_cp_community_avatar( $user_id, $size ) {
		// Check if CP Community avatar functions are available
		if ( ! function_exists( 'user_avatar_fetch_avatar' ) || ! function_exists( 'user_avatar_avatar_exists' ) ) {
			return false;
		}

		try {
			// Get avatar without HTML wrapper (URL only)
			$avatar_url = user_avatar_fetch_avatar( array(
				'item_id' => $user_id,
				'width'   => $size,
				'height'  => $size,
				'type'    => $size > 100 ? 'full' : 'thumb',
				'html'    => false // Return URL only
			) );

			return ! empty( $avatar_url ) ? $avatar_url : false;
		} catch ( Exception $e ) {
			// Log error but don't break functionality
			error_log( 'PSource Chat Avatar: CP Community avatar error - ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Get WordPress/Gravatar avatar URL
	 * 
	 * @param int $user_id User ID
	 * @param int $size Avatar size
	 * @return string|false Avatar URL or false if not available
	 */
	private static function get_wordpress_avatar( $user_id, $size ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return false;
		}

		// Get avatar HTML from WordPress
		$avatar_html = get_avatar( $user->user_email, $size, '', $user->display_name );
		if ( empty( $avatar_html ) ) {
			return false;
		}

		// Extract URL from HTML
		$avatar_url = self::extract_avatar_url( $avatar_html );
		
		// Validate the URL (make sure it's not a default/mystery-man avatar if we want to fallback)
		if ( $avatar_url && ! self::is_default_gravatar( $avatar_url ) ) {
			return $avatar_url;
		}

		return false;
	}

	/**
	 * Extract avatar URL from HTML img tag
	 * 
	 * @param string $avatar_html HTML containing img tag
	 * @return string|false Avatar URL or false if not found
	 */
	private static function extract_avatar_url( $avatar_html ) {
		$avatar_parts = array();
		
		// Try double quotes first
		if ( stristr( $avatar_html, ' src="' ) !== false ) {
			preg_match( '/src="([^"]*)"/i', $avatar_html, $avatar_parts );
		} 
		// Try single quotes
		elseif ( stristr( $avatar_html, " src='" ) !== false ) {
			preg_match( "/src='([^']*)'/i", $avatar_html, $avatar_parts );
		}

		return isset( $avatar_parts[1] ) && ! empty( $avatar_parts[1] ) ? $avatar_parts[1] : false;
	}

	/**
	 * Check if a Gravatar URL is a default/fallback image
	 * 
	 * @param string $url Avatar URL
	 * @return bool True if it's a default avatar
	 */
	private static function is_default_gravatar( $url ) {
		// Check for common default avatar indicators
		$default_indicators = array(
			'mystery-man',
			'mm.png',
			'default=',
			'd=mm',
			'd=blank',
			'd=identicon',
			'd=wavatar',
			'd=monsterid',
			'd=retro'
		);

		foreach ( $default_indicators as $indicator ) {
			if ( strpos( $url, $indicator ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get placeholder avatar
	 * 
	 * @param int $size Avatar size
	 * @param bool $html_output Return HTML img tag (true) or URL only (false)
	 * @return string Placeholder avatar HTML or URL
	 */
	public static function get_placeholder_avatar( $size = 96, $html_output = false ) {
		$size = intval( $size );
		
		if ( $html_output ) {
			return sprintf(
				'<img src="%s" alt="%s" class="avatar avatar-%d photo avatar-placeholder" width="%d" height="%d" style="width: %dpx !important; height: %dpx !important;" />',
				esc_url( self::$default_placeholder ),
				esc_attr__( 'Default Avatar', 'psource-chat' ),
				$size,
				$size,
				$size,
				$size,
				$size
			);
		}

		return self::$default_placeholder;
	}

	/**
	 * Clear avatar cache (useful after avatar updates)
	 * 
	 * @param int|null $user_id Specific user ID to clear, or null for all
	 */
	public static function clear_cache( $user_id = null ) {
		if ( $user_id !== null ) {
			$user_id = intval( $user_id );
			foreach ( self::$avatar_cache as $key => $value ) {
				if ( strpos( $key, "user_{$user_id}_" ) === 0 ) {
					unset( self::$avatar_cache[ $key ] );
				}
			}
		} else {
			self::$avatar_cache = array();
		}
	}

	/**
	 * Set custom placeholder avatar
	 * 
	 * @param string $placeholder_url URL or data URI for placeholder
	 */
	public static function set_placeholder( $placeholder_url ) {
		if ( ! empty( $placeholder_url ) ) {
			self::$default_placeholder = $placeholder_url;
		}
	}

	/**
	 * Get avatar for chat authentication array (legacy compatibility)
	 * 
	 * @param int $user_id User ID
	 * @param string $user_email User email (fallback)
	 * @param string $user_name User name (for alt text)
	 * @return string Avatar URL
	 */
	public static function get_chat_avatar( $user_id, $user_email = '', $user_name = '' ) {
		// Try modern method first
		$avatar_url = self::get_avatar( $user_id, 96, false );
		
		// If we got the placeholder, try the legacy method as backup
		if ( $avatar_url === self::$default_placeholder && ! empty( $user_email ) ) {
			$legacy_avatar = get_avatar( $user_email, 96, '', $user_name );
			if ( $legacy_avatar ) {
				$extracted_url = self::extract_avatar_url( $legacy_avatar );
				if ( $extracted_url && ! self::is_default_gravatar( $extracted_url ) ) {
					$avatar_url = $extracted_url;
				}
			}
		}

		return $avatar_url;
	}
}
