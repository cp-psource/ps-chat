<?php
if ( ( isset( $_GET['psource-chat-key'] ) ) && ( ! empty( $_GET['psource-chat-key'] ) ) ) {

	$psource_chat_key = base64_decode( $_GET['psource-chat-key'] );
	if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
		$chat_session = get_option( $psource_chat_key );
	} else {
		$chat_session = get_transient( $psource_chat_key );
	}
	if ( ( ! empty( $chat_session ) ) && ( is_array( $chat_session ) ) ) {
		global $psource_chat;
		?>
		<!DOCTYPE html>
		<!--[if IE 6]>
		<html id="ie6" lang="en-US">
		<![endif]-->
		<!--[if IE 7]>
		<html id="ie7" lang="en-US">
		<![endif]-->
		<!--[if IE 8]>
		<html id="ie8" lang="en-US">
		<![endif]-->
		<!--[if !(IE 6) | !(IE 7) | !(IE 8)  ]><!-->
		<html lang="en-US">
	<!--<![endif]-->
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>"/>
		<title><?php
			if ( ! empty( $chat_session['box_title'] ) ) {
				echo sanitize_text_field( $chat_session['box_title'] ) . " &ndash; ";
			} ?></title>
		<?php $psource_chat->wp_enqueue_scripts(); ?>
		<?php $psource_chat->wp_head(); ?>
		<style type="text/css">
			body.psource-chat-pop-out {
				margin: auto;
				padding: 0;
			}

			body.psource-chat-pop-out div#psource-chat-box-<?php echo $chat_session['id'] ?> {
				width: 99%;
				height: 99%;
				position: fixed !important;
				position: absolute;
				top: 0;
				right: 0;
				bottom: 0;
				left: 0;
				font-size: 100%;
				box-shadow: none;
				margin: 0;
				/* padding: 5px; */
			}

			body.psource-chat-pop-out div#psource-chat-box-<?php echo $chat_session['id'] ?> div.psource-chat-module-messages-list div.psource-chat-row p.psource-chat-message {
				/* font-size: 100%; */
			}

			body.psource-chat-pop-out div#psource-chat-box-<?php echo $chat_session['id'] ?> div.psource-chat-module-message-area textarea.psource-chat-send {
				height: 20%;
			}

			div.psource-chat-box div.psource-chat-module-messages-list div.psource-chat-row ul.psource-chat-row-footer {
				font-size: 90%;
			}
		</style>
	</head>
	<body class="psource-chat-pop-out">
	<?php echo $psource_chat->process_chat_shortcode( $chat_session ); ?>
	<?php
	$psource_chat->wp_footer();
	?>
	</body>
		</html><?php
	}
}