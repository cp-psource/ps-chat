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
		<html id="ie6" lang="de-DE">
		<![endif]-->
		<!--[if IE 7]>
		<html id="ie7" lang="de-DE">
		<![endif]-->
		<!--[if IE 8]>
		<html id="ie8" lang="de-DE">
		<![endif]-->
		<!--[if !(IE 6) | !(IE 7) | !(IE 8)  ]><!-->
		<html lang="de-DE">
	<!--<![endif]-->
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>"/>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
		<meta name="mobile-web-app-capable" content="yes">
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
		<title><?php
			if ( ! empty( $chat_session['box_title'] ) ) {
				echo sanitize_text_field( $chat_session['box_title'] ) . " &ndash; ";
			} ?></title>
		<?php $psource_chat->wp_enqueue_scripts(); ?>
		<?php $psource_chat->wp_head(); ?>
		<style type="text/css">
			/* Reset and base styles */
			* {
				box-sizing: border-box;
			}
			
			body.psource-chat-pop-out {
				margin: 0;
				padding: 0;
				font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, sans-serif;
				background: #f5f5f5;
				overflow: hidden;
			}

			/* Desktop styles */
			body.psource-chat-pop-out div#psource-chat-box-<?php echo $chat_session['id'] ?> {
				width: 100%;
				height: 100vh;
				position: fixed !important;
				top: 0;
				right: 0;
				bottom: 0;
				left: 0;
				font-size: 14px;
				box-shadow: none;
				margin: 0;
				border: none;
				border-radius: 0;
			}

			/* Message list optimizations */
			body.psource-chat-pop-out div#psource-chat-box-<?php echo $chat_session['id'] ?> div.psource-chat-module-messages-list {
				height: calc(100vh - 120px);
				overflow-y: auto;
				-webkit-overflow-scrolling: touch;
				padding: 10px;
			}

			body.psource-chat-pop-out div#psource-chat-box-<?php echo $chat_session['id'] ?> div.psource-chat-module-messages-list div.psource-chat-row p.psource-chat-message {
				font-size: 14px;
				line-height: 1.4;
				word-wrap: break-word;
				padding: 8px 12px;
			}

			/* Text area optimization */
			body.psource-chat-pop-out div#psource-chat-box-<?php echo $chat_session['id'] ?> div.psource-chat-module-message-area textarea.psource-chat-send {
				min-height: 40px;
				max-height: 120px;
				font-size: 16px; /* Prevents zoom on iOS */
				padding: 10px;
				border: 1px solid #ddd;
				border-radius: 8px;
				resize: none;
			}

			/* Header optimizations */
			body.psource-chat-pop-out div#psource-chat-box-<?php echo $chat_session['id'] ?> div.psource-chat-module-header {
				padding: 10px;
				background: #fff;
				border-bottom: 1px solid #e1e1e1;
				font-size: 16px;
				font-weight: 600;
			}

			/* Footer/meta optimizations */
			div.psource-chat-box div.psource-chat-module-messages-list div.psource-chat-row ul.psource-chat-row-footer {
				font-size: 12px;
				margin-top: 5px;
			}

			/* Button optimizations */
			body.psource-chat-pop-out .psource-chat-send-button,
			body.psource-chat-pop-out button {
				min-height: 44px; /* iOS touch target size */
				font-size: 16px;
				padding: 10px 16px;
				border-radius: 8px;
				cursor: pointer;
			}

			/* Upload button optimizations */
			body.psource-chat-pop-out .psource-chat-upload-button {
				min-height: 44px;
				min-width: 44px;
				padding: 10px;
			}

			/* Action menu optimizations */
			body.psource-chat-pop-out .psource-chat-actions-menu {
				font-size: 14px;
			}

			body.psource-chat-pop-out .psource-chat-actions-menu li {
				margin: 5px 0;
			}

			/* Mobile specific styles */
			@media screen and (max-width: 768px) {
				body.psource-chat-pop-out div#psource-chat-box-<?php echo $chat_session['id'] ?> {
					font-size: 16px; /* Larger base font for mobile */
				}

				/* Larger touch targets for mobile */
				body.psource-chat-pop-out div#psource-chat-box-<?php echo $chat_session['id'] ?> div.psource-chat-module-message-area textarea.psource-chat-send {
					font-size: 18px; /* Prevent zoom on mobile */
					padding: 15px;
					min-height: 50px;
				}

				/* Mobile message styling */
				body.psource-chat-pop-out div#psource-chat-box-<?php echo $chat_session['id'] ?> div.psource-chat-module-messages-list div.psource-chat-row p.psource-chat-message {
					font-size: 16px;
					line-height: 1.5;
					padding: 12px 15px;
					margin: 8px 0;
				}

				/* Mobile header */
				body.psource-chat-pop-out div#psource-chat-box-<?php echo $chat_session['id'] ?> div.psource-chat-module-header {
					padding: 15px;
					font-size: 18px;
				}

				/* Mobile message list */
				body.psource-chat-pop-out div#psource-chat-box-<?php echo $chat_session['id'] ?> div.psource-chat-module-messages-list {
					height: calc(100vh - 140px);
					padding: 15px;
				}

				/* Mobile buttons */
				body.psource-chat-pop-out .psource-chat-send-button,
				body.psource-chat-pop-out button {
					min-height: 50px;
					font-size: 18px;
					padding: 15px 20px;
					border-radius: 12px;
				}

				/* Mobile upload button */
				body.psource-chat-pop-out .psource-chat-upload-button {
					min-height: 50px;
					min-width: 50px;
					padding: 15px;
				}

				/* Mobile action icons */
				body.psource-chat-pop-out .psource-chat-icon-minimize,
				body.psource-chat-pop-out .psource-chat-icon-maximize,
				body.psource-chat-pop-out .psource-chat-icon-settings {
					font-size: 20px;
					padding: 10px;
				}

				/* Hide minimize/maximize on mobile (doesn't make sense in pop-out) */
				body.psource-chat-pop-out .psource-chat-min-max {
					display: none;
				}
			}

			/* Small mobile devices */
			@media screen and (max-width: 480px) {
				body.psource-chat-pop-out div#psource-chat-box-<?php echo $chat_session['id'] ?> div.psource-chat-module-message-area textarea.psource-chat-send {
					font-size: 18px;
					padding: 20px;
					min-height: 60px;
				}

				body.psource-chat-pop-out div#psource-chat-box-<?php echo $chat_session['id'] ?> div.psource-chat-module-messages-list div.psource-chat-row p.psource-chat-message {
					font-size: 17px;
					padding: 15px 18px;
				}

				body.psource-chat-pop-out div#psource-chat-box-<?php echo $chat_session['id'] ?> div.psource-chat-module-messages-list {
					height: calc(100vh - 160px);
					padding: 20px 15px;
				}
			}

			/* Landscape orientation on mobile */
			@media screen and (max-width: 768px) and (orientation: landscape) {
				body.psource-chat-pop-out div#psource-chat-box-<?php echo $chat_session['id'] ?> div.psource-chat-module-messages-list {
					height: calc(100vh - 120px);
				}

				body.psource-chat-pop-out div#psource-chat-box-<?php echo $chat_session['id'] ?> div.psource-chat-module-message-area textarea.psource-chat-send {
					min-height: 40px;
				}
			}

			/* Dark mode support */
			@media (prefers-color-scheme: dark) {
				body.psource-chat-pop-out {
					background: #1a1a1a;
					color: #ffffff;
				}

				body.psource-chat-pop-out div#psource-chat-box-<?php echo $chat_session['id'] ?> div.psource-chat-module-header {
					background: #2a2a2a;
					border-bottom-color: #444;
					color: #ffffff;
				}

				body.psource-chat-pop-out div#psource-chat-box-<?php echo $chat_session['id'] ?> div.psource-chat-module-message-area textarea.psource-chat-send {
					background: #2a2a2a;
					color: #ffffff;
					border-color: #555;
				}
			}

			/* Pop-out: Minimize/Maximize-Button immer ausblenden */
			body.psource-chat-pop-out .psource-chat-min-max {
				display: none !important;
			}

			/* Pop-in Icon im Pop-out größer und touchfreundlich */
			body.psource-chat-pop-out .psource-chat-icon-popin {
				font-size: 24px !important;
				padding: 10px !important;
				line-height: 1;
				display: inline-block;
				vertical-align: middle;
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