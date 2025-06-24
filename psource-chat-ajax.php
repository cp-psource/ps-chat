<?php
define( 'DONOTCACHEPAGE', true );

$configFile = dirname( __FILE__ ) . '/psource-chat-config.php';
if (!file_exists($configFile)) {
    die();
}

$configs = file_get_contents($configFile);
$configsArray = unserialize($configs);

if (!empty($configsArray) && isset($configsArray['ABSPATH'])) {
    $abspath = base64_decode($configsArray['ABSPATH']);
    $wpLoadFile = $abspath . "/wp-load.php";

    if (!file_exists($wpLoadFile)) {
        die();
    }
} else {
    die();
}

$isShortinit = ($_POST['function'] === "chat_messages_update");

define('SHORTINIT', $isShortinit);
define('WP_USE_THEMES', false);
define('WP_DEBUG', false);
define('PSOURCE_CHAT_SHORTINIT', $isShortinit);

require($wpLoadFile);

if ($isShortinit) {
    require_once( ABSPATH . WPINC . '/l10n.php' );
    require( ABSPATH . WPINC . '/formatting.php' );
    require( ABSPATH . WPINC . '/capabilities.php' );
    require( ABSPATH . WPINC . '/user.php' );

    if (file_exists(ABSPATH . WPINC . '/class-wp-user.php')) {
        require( ABSPATH . WPINC . '/class-wp-user.php' );
    }
    if (file_exists(ABSPATH . WPINC . '/class-wp-role.php')) {
        require( ABSPATH . WPINC . '/class-wp-role.php' );
    }
    if (file_exists(ABSPATH . WPINC . '/class-wp-roles.php')) {
        require( ABSPATH . WPINC . '/class-wp-roles.php' );
    }

    require( ABSPATH . WPINC . '/meta.php' );
    require( ABSPATH . WPINC . '/link-template.php' );
    require( ABSPATH . WPINC . '/post.php' );
    require( ABSPATH . WPINC . '/kses.php' );

    wp_cookie_constants();
    require( ABSPATH . WPINC . '/vars.php' );
}

// Now load out plugin code. Using as a library here.
include_once( dirname( __FILE__ ) . '/psource-chat.php' );
global $psource_chat;
$psource_chat->init();
$psource_chat->process_chat_actions();

die();