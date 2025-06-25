<?php
/*
Plugin Name: PS Chat
Plugin URI: https://cp-psource.github.io/ps-chat/
Description: Bietet Dir einen voll ausgestatteten Chat-Bereich entweder in einem Beitrag, einer Seite, einem Widget oder in der unteren Ecke Ihrer Website. Unterstützt BuddyPress Group-Chats und private Chats zwischen angemeldeten Benutzern. KEINE EXTERNEN SERVER/DIENSTE! NEU: Media-Support für Link-Previews, Bilder und YouTube-Videos.
Author: PSOURCE
Version: 2.5.1
Author URI: https://github.com/cp-psource
Text Domain: psource-chat
Domain Path: /languages
*/
require 'psource/psource-plugin-update/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
 
$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/cp-psource/ps-chat',
	__FILE__,
	'ps-chat'
);
 
//Set the branch that contains the stable release.
$myUpdateChecker->setBranch('master');


// Needs to be set BEFORE loading psource_chat_utilities.php!
//define('CHAT_DEBUG_LOG', 1);

include_once( dirname( __FILE__ ) . '/lib/psource_chat_utilities.php' );
include_once( dirname( __FILE__ ) . '/lib/psource_chat_wpadminbar.php' );

if ( ( ! defined( 'PSOURCE_CHAT_SHORTINIT' ) ) || ( PSOURCE_CHAT_SHORTINIT != true ) ) {
	include_once( dirname( __FILE__ ) . '/lib/psource_chat_widget.php' );
	include_once( dirname( __FILE__ ) . '/lib/psource_chat_buddypress.php' );
}

// Hauptklasse laden
include_once( dirname( __FILE__ ) . '/includes/class-psource-chat-avatar.php' );
include_once( dirname( __FILE__ ) . '/includes/class-psource-chat-emoji.php' );
include_once( dirname( __FILE__ ) . '/includes/class-psource-chat-media.php' );
include_once( dirname( __FILE__ ) . '/includes/class-psource-chat-upload.php' );
include_once( dirname( __FILE__ ) . '/includes/class-psource-chat-ajax.php' );
include_once( dirname( __FILE__ ) . '/includes/class-psource-chat.php' );



// Lets get things started
$psource_chat = new PSOURCE_Chat();
