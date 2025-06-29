/**
 * PSOURCE   WordPress Chat plugin javascript
 *
 * @author    Paul Menard <paul@incsub.com>
 * @since    2.0.0
 */
//"use strict";
var psource_chat = jQuery.extend(psource_chat || {}, {
    settings: {},
    pids: {},
    errors: {},
    timers: {},
    send_data: {},
//	init_users: true,
    popouts: {},
    bound: false,
    isPlaceholderSupported: true,
    Sounds: [],
    init: function () {

        if ((psource_chat_localized['settings']['session_poll_interval_messages'] == undefined) || (psource_chat_localized['settings']['session_poll_interval_messages'] < 1)) {
            psource_chat_localized['settings']['session_poll_interval_messages'] = 1;
        }
        if ((psource_chat_localized['settings']['session_poll_interval_invites'] == undefined) || (psource_chat_localized['settings']['session_poll_interval_invites'] < 1)) {
            psource_chat_localized['settings']['session_poll_interval_invites'] = 3;
        }
        if ((psource_chat_localized['settings']['session_poll_interval_meta'] == undefined) || (psource_chat_localized['settings']['session_poll_interval_meta'] < 1)) {
            psource_chat_localized['settings']['session_poll_interval_meta'] = 5;
        }
        if ((psource_chat_localized['settings']['session_poll_interval_users'] == undefined) || (psource_chat_localized['settings']['session_poll_interval_users'] < 1)) {
            psource_chat_localized['settings']['session_poll_interval_user'] = 5;
        }

        jQuery(window).on( 'resize', function () {
            psource_chat.chat_session_size_box();
        });
        //console.log('wp_is_mobile['+psource_chat_localized['settings']['wp_is_mobile']+']');

        psource_chat_localized['settings']['screen_width'] = jQuery(window).width();
        psource_chat_localized['settings']['screen_height'] = jQuery(window).height();
        //console.log('screen_width['+psource_chat_localized['settings']['screen_width']+'] screen_height['+psource_chat_localized['settings']['screen_height']+']');

        psource_chat.isPlaceholderSupported = 'placeholder' in document.createElement('input');

        psource_chat.settings['sessions'] = {};
        if (psource_chat_localized['sessions'] != undefined) {
            for (var chat_id in psource_chat_localized['sessions']) {
                if (!psource_chat_localized['sessions'].hasOwnProperty(chat_id)) continue;

                psource_chat.settings['sessions'][chat_id] = psource_chat_localized['sessions'][chat_id];

                // Set this flag on initial page load. Prevents the ping sound.
                psource_chat.settings['sessions'][chat_id]['has_send_message'] = true;
            }
        }

        psource_chat.settings['user'] = {};
        if (psource_chat_localized['user'] != undefined) {
            //psource_chat.settings['user'] = psource_chat_localized['user'];
            for (var chat_id in psource_chat_localized['user']) {
                if (!psource_chat_localized['user'].hasOwnProperty(chat_id)) continue;

                psource_chat.settings['user'][chat_id] = psource_chat_localized['user'][chat_id];
            }
        }
        psource_chat.cookie('psource-chat-user', JSON.stringify(psource_chat.settings['user']), {
            path: psource_chat_localized['settings']['cookiepath'],
            domain: psource_chat_localized['settings']['cookie_domain']
        });

        psource_chat.settings['auth'] = {};
        if ((psource_chat_localized['auth'] != 'undefined' ) /*&& (!jQuery.isEmptyObject(psource_chat_localized['auth'])) */) {
            if (psource_chat_localized['auth']['type'] != 'invalid') {
                psource_chat.settings['auth'] = psource_chat_localized['auth'];
            }
        } else {
            var auth_cookie = psource_chat.cookie('psource-chat-auth');
            if ((auth_cookie != undefined) && (!jQuery.isEmptyObject(auth_cookie))) {
                psource_chat.settings['auth'] = JSON.parse(auth_cookie);
            }
        }
        psource_chat.cookie('psource-chat-auth', JSON.stringify(psource_chat.settings['auth']), {
            path: psource_chat_localized['settings']['cookiepath'],
            domain: psource_chat_localized['settings']['cookie_domain']
        });

        //var auth_cookie = psource_chat.cookie('psource-chat-auth');
        //var auth_cookie_parsed = JSON.parse(auth_cookie);

        psource_chat.timers['messages'] = 0;
        psource_chat.timers['invites'] = 0;
        psource_chat.timers['meta'] = 0;
        psource_chat.timers['users'] = 0;

        psource_chat.pids['chat_session_message_update'] = '';
        psource_chat.pids['chat_sessions_init'] = '';
        psource_chat.pids['chat_session_messages_send'] = '';

        psource_chat.errors['chat_session_message_update'] = 0;
        psource_chat.errors['chat_sessions_init'] = 0;
        psource_chat.errors['chat_session_messages_send'] = 0;

        psource_chat.chat_privite_invite_click();

        // Initialize avatar fallback system
        psource_chat.init_avatar_fallbacks();

        psource_chat.chat_sessions_init();
        //psource_chat.chat_session_message_update();
    },
    chat_sessions_init: function () {

        var sessions_data = {};

        if ((psource_chat.settings['sessions'] != undefined) && (Object.keys(psource_chat.settings['sessions']).length > 0)) {
            for (var chat_id in psource_chat.settings['sessions']) {
                //sessions_data[chat_id] = psource_chat.settings['sessions'][chat_id];
                var chat_session = psource_chat.settings['sessions'][chat_id];
                sessions_data[chat_id] = {};
                sessions_data[chat_id]['id'] = chat_session['id'];
                sessions_data[chat_id]['blog_id'] = chat_session['blog_id'];
                sessions_data[chat_id]['session_type'] = chat_session['session_type'];
            }
        }
        if (Object.keys(sessions_data).length > 0) {

            if ((psource_chat.pids['chat_sessions_init'] == '') && (psource_chat.errors['chat_sessions_init'] < 10)) {
                //console.log('chat_sessions_init: consecutive errors['+psource_chat.errors['chat_sessions_init']+']');

                psource_chat.pids['chat_sessions_init'] = jQuery.ajax({

                    type: "POST",
                    url: psource_chat_localized['settings']["ajax_url"],
                    dataType: "json",
                    cache: false,
                    data: {
                        'function': 'chat_init',
                        'action': 'chatProcess',
                        'psource-chat-sessions': sessions_data,
                        'psource-chat-settings-request-uri': psource_chat_localized['settings']['REQUEST_URI']
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        psource_chat.pids['chat_sessions_init'] = '';
                        psource_chat.errors['chat_sessions_init'] = parseInt(psource_chat.errors['chat_sessions_init']) + 1;
                        console.log('init: error HTTP Status[' + jqXHR.status + '] ' + errorThrown);

                        var poll_interval = 1;
                        setTimeout(function () {
                            psource_chat.chat_sessions_init();
                        }, poll_interval * 1000);
                    },
                    success: function (reply_data) {

                        psource_chat.pids['chat_sessions_init'] = '';
                        if (reply_data != undefined) {

                            if (reply_data['performance'] != undefined) {
                                console.log('performance: chat_init: %o', reply_data['performance']);
                            }

                            psource_chat.errors['chat_sessions_init'] = 0;
                            if (reply_data['sessions'] != undefined) {
                                for (var chat_id in reply_data['sessions']) {
                                    var chat_reply_data = reply_data['sessions'][chat_id];
                                    if (chat_reply_data['html'] != undefined) {
                                        jQuery('div#psource-chat-box-' + chat_id).html(chat_reply_data['html']);
                                    }
                                    if (chat_reply_data['css'] != undefined) {
                                        jQuery('div#psource-chat-box-' + chat_id).after(chat_reply_data['css']);
                                    }

                                    psource_chat.chat_session_box_actions(chat_id);

                                    // The login form labels are hidden y default via CSS. But on browsers which do not support
                                    // placeholders (IE!) we want to show them.
                                    if (psource_chat.isPlaceholderSupported == false) {
                                        jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-login div.chat-login-wrap label.psource-chat-login-label').show();
                                    }
                                    // Need to add this to the text area at some point. Maybe when we add the send button option
                                    //var isTextareaSupported = 'placeholder' in document.createElement('textarea');

                                    jQuery('div#psource-chat-box-' + chat_id).show();
                                }
                            }
                            psource_chat.chat_session_set_auth_view();
                            psource_chat.chat_session_sound_setup(0);
                            psource_chat.chat_session_size_box();
                            psource_chat.chat_session_size_message_list();
                            psource_chat.chat_session_message_update();
                        } else {
                            psource_chat.errors['chat_sessions_init'] = parseInt(psource_chat.errors['chat_sessions_init']) + 1;
                            var poll_interval = 1;
                            setTimeout(function () {
                                psource_chat.chat_sessions_init();
                            }, poll_interval * 1000);
                        }
                    }
                });
            }
        } else {
            psource_chat.chat_session_message_update();
        }
    },
    chat_session_message_update: function () {

        var sessions_data = {};

        // First loop through each session. Get the last row ID to seed the 'since' variable sent to the server. Controls the last message timestamp.
        if (psource_chat.settings['sessions'] != undefined) {
            for (var chat_id in psource_chat.settings['sessions']) {
                var chat_session = psource_chat.settings['sessions'][chat_id];
                //If chat box exists for a chat session
                if (jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list').length) {

                    //Skip loop if polling is disabled for minimized chats
                    if ((chat_session['poll_max_min'] != undefined) && (chat_session['poll_max_min'] == "disabled") && (jQuery('div#psource-chat-box-' + chat_session['id']).hasClass('psource-chat-box-min'))) {
                        continue;
                    }

                    // IF this is a Dashboard widget we check of the parent wrapper is closed.
                    if (jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box').hasClass('psource-chat-dashboard-widget')) {
                        var postbox = jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box').parents('div.postbox');
                        if (postbox != undefined) {
                            if ((jQuery(postbox).hasClass('closed')) || ((!jQuery(postbox).is(":visible"))))
                                continue;
                        }
                    }

                    //Skip popout chats
                    if (jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box').hasClass('psource-chat-box-pop-out')) {
                        continue;
                    }

                    sessions_data[chat_id] = {};
                    sessions_data[chat_id]['id'] = chat_session['id'];
                    sessions_data[chat_id]['blog_id'] = chat_session['blog_id'];
                    sessions_data[chat_id]['session_type'] = chat_session['session_type'];

                    //if there are any messages in the chat box
                    if (jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list div.psource-chat-row').length) {
                        if (chat_session['box_input_position'] == "top") {
                            var last_row_timestamp_id = jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list div.psource-chat-row').first().attr('id').replace('psource-chat-row-', '');
                        } else {
                            var last_row_timestamp_id = jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list div.psource-chat-row').last().attr('id').replace('psource-chat-row-', '');
                        }
                        if (last_row_timestamp_id != undefined) {
                            var last_row_data = last_row_timestamp_id.split('-');
                            if (last_row_data[1] != undefined)
                                sessions_data[chat_id]['last_row_id'] = last_row_data[1];
                        }
                    } else {
                        sessions_data[chat_id]['last_row_id'] = '__EMPTY__';
                    }

                    if (jQuery('body').hasClass('psource-chat-pop-out'))
                        sessions_data[chat_id]['template'] = "psource-chat-pop-out";
                }
            }
            /* End of for loop */
        }

        var timers = {};
        timers['messages'] = 0;
        timers['users'] = 0;
        timers['invites'] = 0;
        timers['meta'] = 0;

        var current_ts = Math.round((new Date()).getTime() / 1000);

        if (Object.keys(sessions_data).length) {
            timers['messages'] = 1;
        }

        if ((current_ts - psource_chat_localized['settings']['session_poll_interval_invites']) > psource_chat.timers['invites']) {
            timers['invites'] = 1;
            psource_chat.timers['invites'] = current_ts;
        }

        if ((current_ts - psource_chat_localized['settings']['session_poll_interval_meta']) > psource_chat.timers['meta']) {
            timers['meta'] = 1;
            psource_chat.timers['meta'] = current_ts;
        }

        if ((current_ts - psource_chat_localized['settings']['session_poll_interval_users']) > psource_chat.timers['users']) {
            timers['users'] = 1;
            psource_chat.timers['users'] = current_ts;
        }

        if ((psource_chat.pids['chat_session_message_update'] == '') && (psource_chat.errors['chat_session_message_update'] < 10)) {
            // If our timers are all unset then wait for the next interval.
            if ((timers['users'] == 0) && (timers['meta'] == 0) && (timers['invites'] == 0) && (timers['messages'] == 0)) {
                if (psource_chat.settings['auth']['type'] != undefined) {
                    var poll_interval = psource_chat_localized['settings']['session_poll_interval_messages'];
                } else {
                    var poll_interval = psource_chat_localized['settings']['session_poll_interval_messages'] * 3;
                }
                poll_interval = 5;
                setTimeout(function () {
                    psource_chat.chat_session_message_update();
                }, poll_interval * 1000);
            } else {

                psource_chat.pids['chat_session_message_update'] = jQuery.ajax({
                    type: "POST",
                    url: psource_chat_localized['settings']['ajax_url'],
                    dataType: "json",
                    cache: false,
                    data: {
                        'function': 'chat_messages_update',
                        'action': 'chatProcess',
                        'timers': timers,
                        'psource-chat-sessions': sessions_data,
                        'psource-chat-settings-request-uri': psource_chat_localized['settings']['REQUEST_URI']
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        psource_chat.errors['chat_session_message_update'] = parseInt(psource_chat.errors['chat_session_message_update']) + 1;
                        console.log('chat_session_message_update: error HTTP Status[' + jqXHR.status + '] ' + errorThrown);
                    },
                    success: function (reply_data) {
                        var play_new_messages_sound = {};

                        if (reply_data != undefined) {
                            psource_chat.errors['chat_session_message_update'] = 0;

                            if (reply_data['performance'] != undefined) {
                                console.log('performance: chat_messages_update: %o', reply_data['performance']);
                            }

                            //Check for new invites
                            if (reply_data['invites'] != undefined) {

                                for (var chat_id in reply_data['invites']) {
                                    //Skip if chat id does not exists
                                    if (!reply_data['invites'].hasOwnProperty(chat_id)) {
                                        continue;
                                    }

                                    //If chat box already exists
                                    if (psource_chat.settings['sessions'][chat_id] != undefined) {
                                        console.log('chat_session [' + chat_id + '] already exists');
                                        continue;
                                    } else {
                                        //Otherwise add a new invite window to user screen
                                        psource_chat.chat_session_add_item(chat_id, reply_data['invites'][chat_id]);

                                        // Clue in the box actions for processing.
                                        psource_chat.chat_session_box_actions(chat_id);
                                    }
                                }
                                psource_chat.chat_session_size_box();
                            }

                            if (reply_data['sessions'] != undefined) {
                                for (var chat_id in reply_data['sessions']) {
                                    var chat_session = psource_chat.chat_session_get_session_by_id(chat_id);
                                    if (chat_session == undefined)
                                        continue;

                                    //If there is a error for the chat session
                                    if (reply_data['sessions'][chat_id]['errorStatus']) {
                                        jQuery('#psource-chat-box-' + chat_id + ' .psource-chat-session-user-status-message p').html(reply_data['sessions'][chat_id]['errorText']);
                                        jQuery('#psource-chat-box-' + chat_id + ' .psource-chat-session-user-status-message').show();
                                    }

                                    var chat_reply_data = reply_data['sessions'][chat_id];

                                    if (chat_reply_data['last_row_id'] != undefined) {
                                        psource_chat.settings['sessions'][chat_id]['last_row_id'] = chat_reply_data['last_row_id'];
                                    }

                                    if (chat_reply_data['rows'] != undefined) {

                                        if (chat_reply_data['rows'] == "__EMPTY__") {
                                            //if (psource_chat.settings['sessions'][chat_id]['session_type'] != 'private') {
                                            if (psource_chat.settings['sessions'][chat_id]['last_row_id'] != "__EMPTY__") {
                                                psource_chat.settings['sessions'][chat_id]['last_row_id'] = chat_reply_data['rows'];
                                                jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list').empty();
                                                jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-session-generic-message p').html(chat_session['session_cleared_message']);
                                                jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-session-generic-message').show().delay(5000).fadeOut(psource_chat.chat_session_size_message_list);
                                            }
                                            //}
                                        } else if (Object.keys(chat_reply_data['rows']).length) {
                                            //console.log('rows['+Object.keys(chat_reply_data['rows']).length+']');
                                            var has_new_messages = psource_chat.chat_session_process_rows(chat_session, chat_reply_data['rows']);

                                            if (has_new_messages == true) {
                                                if ((psource_chat.settings['sessions'][chat_id]['box_sound'] == "enabled") && (psource_chat.settings['user'][chat_id]['sound_on_off'] == "on")) {
                                                    if (!jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box').hasClass('psource-chat-box-pop-out')) {
                                                        if (chat_session['has_send_message'] == true) {
                                                            psource_chat.settings['sessions'][chat_id]['has_send_message'] = false;
                                                        } else {
                                                            play_new_messages_sound[chat_id] = true;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        psource_chat.chat_session_click_avatar_row(chat_id);
                                        psource_chat.chat_session_admin_row_actions(chat_id);

                                        // If not moderator we want to remove the admin UL item within the rows
                                        //if ((chat_session['moderator'] == "no") || (chat_session['session_type'] == "private")) {
                                        if (chat_session['moderator'] == "no") {
                                            jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list div.psource-chat-row ul.psource-chat-row-footer').remove();
                                        }
                                    }

                                    if ((chat_reply_data['meta'] != undefined) && (Object.keys(chat_reply_data['meta']).length)) {

                                        // Update our session status
                                        if (chat_reply_data['meta']['session-status'] != undefined) {

                                            // If the session type is private it does not follow the convention of open/closed like a group chat. The session-status value
                                            // returned fom the server is the users archived status for the private chat. This allows us to rmeove the chat_session if the
                                            // user left the chat_session via another brower session.
                                            if (chat_session['session_type'] == "private") {
                                                //console.log('chat_id['+chat_id+'] session-status['+chat_reply_data['meta']['session-status']+']');
                                                if (chat_reply_data['meta']['session-status'] == 'yes') {
                                                    psource_chat.chat_session_remove_item(chat_id);
                                                    continue;
                                                }
                                            } else {
                                                psource_chat.chat_session_process_status_change(chat_id, chat_reply_data['meta']['session-status']);
                                            }
                                        }

                                        // Update the users list (optional)
                                        if (chat_reply_data['meta']['users-active'] != undefined) {
                                            psource_chat.chat_session_process_users_list(chat_id, chat_reply_data['meta']['users-active']);
                                        }

                                        // Mark Deleted/Undeleted rows
                                        if (chat_reply_data['meta']['deleted-rows'] != undefined) {
                                            psource_chat.chat_session_admin_process_row_delete_actions(chat_id, chat_reply_data['meta']['deleted-rows']);
                                        }
                                    }

                                    // Mark the rows with Blocked IP Addresses
                                    if (chat_reply_data['global'] != undefined) {

                                        if (chat_reply_data['global']['blocked-ip-addresses'] != undefined) {
                                            psource_chat.chat_session_admin_process_blocked_ip_addresses(chat_id, chat_reply_data['global']['blocked-ip-addresses']);
                                        }

                                        // Mark the rows with Blocked Users
                                        if (chat_reply_data['global']['blocked-users'] != undefined) {
                                            psource_chat.chat_session_admin_process_blocked_users(chat_id, chat_reply_data['global']['blocked-users']);
                                        }
                                    }
                                }
                                if (Object.keys(play_new_messages_sound).length > 0) {
                                    psource_chat.chat_session_sound_play();
                                }
                            }

                            // We update out wp toolbar on each meta cycle!
                            psource_chat.wp_admin_bar_setup();

                            psource_chat.chat_session_set_auth_view();
                        } else {
                            psource_chat.errors['chat_session_message_update'] = parseInt(psource_chat.errors['chat_session_message_update']) + 1;
                        }

                        // Just in case thee are messages to be sent
                        psource_chat.chat_session_messages_send();
                    },
                    complete: function (e, xhr, settings) {

                        psource_chat.pids['chat_session_message_update'] = '';
                        if (Object.keys(psource_chat.settings['sessions']).length > 0) {

                            //if (psource_chat.settings['auth']['type'] != undefined) {
                            var poll_interval = psource_chat_localized['settings']['session_poll_interval_messages'];
                            //} else {
                            //	var poll_interval = psource_chat_localized['settings']['session_poll_interval_messages']*3;
                            //}
                        } else {
                            if (psource_chat_localized['settings']['session_poll_interval_messages'] < 5)
                                poll_interval = 5;
                            else
                                poll_interval = psource_chat_localized['settings']['session_poll_interval_messages'];
                        }
                        setTimeout(function () {
                            psource_chat.chat_session_message_update();
                        }, poll_interval * 1000);
                    }
                });
            }
        }
    }, /* End of function chat_session_message_update */
    // Called to dynamically add new private chats to the user's screen
    chat_session_add_item: function (chat_id, chat_item) {

        // Double check we don't already have this session in our array/object
        if (psource_chat.chat_session_get_session_by_id(chat_id) != undefined)
            return;

        // Add the new chat session to our sessions list
        psource_chat.settings['sessions'][chat_id] = chat_item['session'];

        // Add the new chat session to our users settings list...and update the cookie
        psource_chat.settings['user'][chat_id] = chat_item['user'];

        //If invitation is not declined add Chat window
        if (psource_chat.settings['user'][chat_id]['invite-status']['invite-status'] !== 'declined') {
            psource_chat.cookie('psource-chat-user', JSON.stringify(psource_chat.settings['user']), {
                path: psource_chat_localized['settings']['cookiepath'],
                domain: psource_chat_localized['settings']['cookie_domain']
            });

            var chat_session = chat_item['session'];

            // Add the new session to our site container
            var items_cnt = 0;
            var item_offset_h = 0;

            jQuery('.psource-chat-box-site').each(function () {
                items_cnt += 1;
                item_offset_h += jQuery(this).outerWidth(true)
            });

            if ((chat_item['html'] != undefined) && ((chat_item['html'] != undefined) != '')) {
                jQuery("body").append(chat_item['html']);
            }

            if ((chat_item['css'] != undefined) && ((chat_item['css'] != undefined) != '')) {
                // Yes, we add the CSS after the div in the body. Not into the <head></head>
                jQuery('div#psource-chat-box-' + chat_id).after(chat_item['css']);
            }

            // We don't position the first element. Because it will be handled by CSS
            if (items_cnt > 0) {

                item_offset_h += parseInt(chat_session['box_offset_h']) + ( items_cnt ) * 10;

                if (chat_session['box_position_h'] == "left") {
                    jQuery('#psource-chat-box-' + chat_session['id']).css('left', item_offset_h + 'px');
                }
                else {
                    jQuery('#psource-chat-box-' + chat_session['id']).css('right', item_offset_h + 'px');
                }
            }
            jQuery('#psource-chat-box-' + chat_session['id']).show();
        }
    },
    // Called when the user leaves a private chat via menu option
    chat_session_remove_item: function (chat_id) {
        var sessions_data = {};

        var chat_session = psource_chat.settings['sessions'][chat_id];
        sessions_data[chat_id] = {};
        sessions_data[chat_id]['id'] = chat_session['id'];
        sessions_data[chat_id]['blog_id'] = chat_session['blog_id'];
        sessions_data[chat_id]['session_type'] = chat_session['session_type'];

        jQuery.ajax({
            type: "POST",
            url: psource_chat_localized['settings']["ajax_url"],
            dataType: "json",
            cache: false,
            data: {
                'function': 'chat_meta_leave_private_session',
                'action': 'chatProcess',
                'psource-chat-sessions': sessions_data,
                //'psource-chat-auth': psource_chat.settings['auth'],
                //'psource-chat-settings': psource_chat_localized['settings']
                //'psource-chat-settings-abspath': psource_chat_localized['settings']['ABSPATH'],
                'psource-chat-settings-request-uri': psource_chat_localized['settings']['REQUEST_URI']
            },
            success: function (reply_data) {
                if (reply_data != undefined) {
                    if (reply_data['errorStatus'] != undefined) {
                        if (reply_data['errorStatus'] == true) {
                            if (reply_data['errorText'] != undefined) {
                                //console.log("Chat: chat_meta_delete_session: reply [%s]", reply_data['errorText']);
                            }
                        } else if (reply_data['errorStatus'] == false) {

                            if (reply_data['sessions'] != undefined) {
                                for (var chat_id in reply_data['sessions']) {
                                    //console.log('post AJAX processing chat_id['+chat_id+']');

                                    // IF no error then we remove the box!

                                    // Remove the item from the DOM
                                    var chat_box = jQuery('div#psource-chat-box-' + chat_id);
                                    if (!jQuery.isEmptyObject(chat_box)) {
                                        jQuery(chat_box).remove();
                                    }

                                    var chat_box_css = jQuery('style#psource-chat-box-' + chat_id + '-css');
                                    if (!jQuery.isEmptyObject(chat_box_css)) {
                                        jQuery(chat_box_css).remove();
                                    }


                                    // Remove the item from our internal lists.
                                    //var chat_id = jQuery(chat_box).attr('id').replace('psource-chat-box-', '');
                                    delete psource_chat.settings['sessions'][chat_id];

                                    delete psource_chat.settings['user'][chat_id];
                                    psource_chat.cookie('psource-chat-user', JSON.stringify(psource_chat.settings['user']), {
                                        path: psource_chat_localized['settings']['cookiepath'],
                                        domain: psource_chat_localized['settings']['cookie_domain']
                                    });
                                }
                            }

                        }
                    }
                }
            }
        });
    },
    chat_session_update_user_invite_status: function (chat_id, invite_status) {
        jQuery.ajax({
            type: "POST",
            url: psource_chat_localized['settings']["ajax_url"],
            dataType: "json",
            cache: false,
            data: {
                'function': 'chat_invite_update_user_status',
                'action': 'chatProcess',
                'chat-id': chat_id,
                'invite-status': invite_status,
                //'psource-chat-settings': psource_chat_localized['settings']
                //'psource-chat-settings-abspath': psource_chat_localized['settings']['ABSPATH'],
                'psource-chat-settings-request-uri': psource_chat_localized['settings']['REQUEST_URI']
            },
            success: function (reply_data) {
                if (reply_data != undefined) {
                    if (reply_data['errorStatus'] != undefined) {
                        if (reply_data['errorStatus'] == true) {
                            if (reply_data['errorText'] != undefined) {
                                //console.log("Chat: chat_invite_update_user_status: reply [%s]", reply_data['errorText']);
                            }
                        }
                    }
                }
            }
        });
    },
    chat_session_size_box: function () {
        //if (psource_chat_localized['settings']['wp_is_mobile'] == false) return;

        // Ignore resize for popout chats.
        if (jQuery('body').hasClass('psource-chat-pop-out')) {
            //console.log('window is popout');
            return;
        }

        psource_chat_localized['settings']['screen_width'] = jQuery(window).outerWidth(true); //width();
        psource_chat_localized['settings']['screen_height'] = jQuery(window).outerHeight(true); //height();

        for (var chat_id in psource_chat.settings['sessions']) {
            var chat_session = psource_chat.settings['sessions'][chat_id];

            var chat_session_box_width = parseInt(chat_session['box_width']);
            if (chat_session['box_shadow_show'] == 'enabled') {
                chat_session_box_width = chat_session_box_width + parseInt(chat_session['box_shadow_h']);
            }

            var chat_session_box_height = parseInt(chat_session['box_height']);
            if (chat_session['box_shadow_show'] == 'enabled') {
                chat_session_box_height = chat_session_box_height + parseInt(chat_session['box_shadow_v']);
            }
            // If the current width is larger then the screen then do the user a favor and set the width to the screen width. D'oh
            if (chat_session['box_width_mobile_adjust'] == 'window') {
                if (chat_session_box_width > psource_chat_localized['settings']['screen_width']) {
                    jQuery('#psource-chat-box-' + chat_id).css('width', parseInt(psource_chat_localized['settings']['screen_width']) - 5);

                    if ((jQuery('div#psource-chat-box-' + chat_session['id']).hasClass('psource-chat-box-site')) && (chat_session['box_position_adjust_mobile'] == 'enabled')) {
                        jQuery('#psource-chat-box-' + chat_id).css({'position': 'relative', 'margin': '0'});

                        if (chat_session['box_position_h'] == 'right') {
                            jQuery('#psource-chat-box-' + chat_id).css('right', '0');
                        } else if (chat_session['box_position_h'] == 'left') {
                            jQuery('#psource-chat-box-' + chat_id).css('left', '0');
                        }

                        if (chat_session['box_shadow_show'] == 'enabled') {
                            jQuery('#psource-chat-box-' + chat_id).css('box-shadow', 'none');
                        }
                    }

                } else {
                    //Styling for Site wide chats
                    jQuery('#psource-chat-box-' + chat_id).css('width', chat_session['box_width']);

                    var chat_id_selector = jQuery('div#psource-chat-box-' + chat_session['id']);
                    if (chat_id_selector.hasClass('psource-chat-box-site') && chat_session['box_position_adjust_mobile'] == 'enabled') {
                        //If not private chat, make it fixed
                        if (!chat_id_selector.hasClass('psource-chat-box-private')) {
                            jQuery('#psource-chat-box-' + chat_id).css('position', 'fixed');
                            if (chat_session['box_position_h'] == 'right') {
                                jQuery('#psource-chat-box-' + chat_id).css('margin', '0 0 0 ' + chat_session['box_offset_h']);
                                jQuery('#psource-chat-box-' + chat_id).css('right', chat_session['box_offset_h']);
                                //
                            } else if (chat_session['box_position_h'] == 'left') {
                                jQuery('#psource-chat-box-' + chat_id).css('margin', '0 ' + chat_session['box_offset_h'] + ' 0 0');
                                jQuery('#psource-chat-box-' + chat_id).css('left', chat_session['box_offset_h']);
                            }
                        } else {
                            if (chat_session['box_position_h'] == 'right') {
                                jQuery('#psource-chat-box-' + chat_id).css({
                                    'margin': '0 0 0 ' + chat_session['box_offset_h'],
                                    float: 'right'
                                });
                                //jQuery('#psource-chat-box-' + chat_id).css('right', parseInt( chat_session['box_offset_h'] ) + 10);
                            } else if (chat_session['box_position_h'] == 'left') {
                                jQuery('#psource-chat-box-' + chat_id).css({
                                    'margin': '0 ' + chat_session['box_offset_h'] + ' 0 0',
                                    float: 'left'
                                });
                                //jQuery('#psource-chat-box-' + chat_id).css('left', parseInt( chat_session['box_offset_h'] ) + 10);
                            }
                        }
                        if (chat_session['box_shadow_show'] == 'enabled') {
                            jQuery('#psource-chat-box-' + chat_id).css('box-shadow', chat_session['box_shadow_v'] + ' ' + chat_session['box_shadow_h'] + ' ' + chat_session['box_shadow_blur'] + ' ' + chat_session['box_shadow_spread'] + ' ' + chat_session['box_shadow_color']);
                        }
                    }
                }
            }

            if (chat_session['box_height_mobile_adjust'] == 'window') {
                if (!jQuery('div#psource-chat-box-' + chat_session['id']).hasClass('psource-chat-box-min')) {
                    if (chat_session_box_height > psource_chat_localized['settings']['screen_height']) {
                        jQuery('#psource-chat-box-' + chat_id).css('height', psource_chat_localized['settings']['screen_height']);
                        if (chat_session['box_shadow_show'] == 'enabled') {
                            jQuery('#psource-chat-box-' + chat_id).css('box-shadow', 'none');
                        }
                        if (chat_session['box_position_v'] == 'bottom') {
                            //jQuery('#psource-chat-box-' + chat_id).css('bottom', '0');
                        } else if (chat_session['box_position_v'] == 'top') {
                            jQuery('#psource-chat-box-' + chat_id).css('top', '0');
                        }
                    } else {
                        jQuery('#psource-chat-box-' + chat_id).css('height', chat_session['box_height']);
                        if (chat_session['box_shadow_show'] == 'enabled') {
                            jQuery('#psource-chat-box-' + chat_id).css('box-shadow', chat_session['box_shadow_v'] + ' ' + chat_session['box_shadow_h'] + ' ' + chat_session['box_shadow_blur'] + ' ' + chat_session['box_shadow_spread'] + ' ' + chat_session['box_shadow_color']);
                        }
                        if (chat_session['box_position_v'] == 'bottom') {
                            jQuery('#psource-chat-box-' + chat_id).css('margin', '0 0 ' + chat_session['box_offset_v'] + ' 0');
                            //jQuery('#psource-chat-box-' + chat_id).css('bottom', chat_session['box_offset_v']);

                        } else if (chat_session['box_position_v'] == 'top') {
                            jQuery('#psource-chat-box-' + chat_id).css('margin', chat_session['box_offset_v'] + ' 0 0 0');
                            jQuery('#psource-chat-box-' + chat_id).css('top', chat_session['box_offset_v']);
                        }
                    }
                }
            }
        }
        psource_chat.chat_session_size_message_list();
    },
    chat_session_size_message_list: function () {

        for (var chat_id in psource_chat.settings['sessions']) {
            var chat_session = psource_chat.settings['sessions'][chat_id];

            if (!jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list').is(":visible"))
                continue;

            var chat_session_current_height = jQuery('#psource-chat-box-' + chat_id).height();
            var chat_session_current_width = jQuery('#psource-chat-box-' + chat_id).width();
            //console.log('chat_session_size_message_list: chat_id['+chat_id+'] h['+chat_session_current_height+'] w['+chat_session_current_width+']');

//			if (chat_session_current_height > psource_chat_localized['settings']['screen_height']) {
//				console.log('chat_id: ['+chat_id+'] adjusting height  '+chat_session_current_height+' > '+psource_chat_localized['settings']['screen_height']);
//				jQuery('#psource-chat-box-'+chat_id).css('max-height', psource_chat_localized['settings']['screen_height']-5);
//			}
//			if (chat_session_current_width > psource_chat_localized['settings']['screen_width']) {
//				console.log('chat_id: ['+chat_id+'] adjusting width  '+chat_session_current_width+' > '+psource_chat_localized['settings']['screen_width']);
//				jQuery('#psource-chat-box-'+chat_id).css('max-width', psource_chat_localized['settings']['screen_width']-5);
//			}

            var chat_session_wrap_height = 0;
            jQuery('#psource-chat-box-' + chat_id + ' .psource-chat-module').each(function () {
                if ((!jQuery(this).hasClass('psource-chat-module-messages-list')) && (!jQuery(this).hasClass('psource-chat-module-users-list')) && (jQuery(this).is(":visible"))) {
                    chat_session_wrap_height += jQuery(this).outerHeight(true);
                }
            });
            //console.log('chat_session_size_message_list: chat_session_wrap_height['+chat_session_wrap_height+']');

            if (chat_session['users_list_position'] == "none") {
                if (chat_session_wrap_height < chat_session_current_height) {
                    var height_diff = chat_session_current_height - chat_session_wrap_height;
                    jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list').height(height_diff + 1);
                } else if (chat_session_wrap_height > chat_session_current_height) {
                    var height_diff = chat_session_current_height - chat_session_wrap_height;
                    jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list').height(height_diff + 1);
                }

            } else if ((chat_session['users_list_position'] == "left") || (chat_session['users_list_position'] == "right")) {

                if ((jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list')) && (jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list').is(":visible"))) {
                    var messages_list_height = jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list').outerHeight(true);
                } else {
                    var messages_list_height = 0;
                }

                if ((jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-users-list')) && (jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-users-list').is(":visible"))) {
                    var users_list_height = jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-users-list').outerHeight(true);
                } else {
                    var users_list_height = 0;
                }
                //console.log('chat_session_size_message_list: chat_id['+chat_id+'] messages_list_height['+messages_list_height+'] users_list_height['+users_list_height+']');

                if (messages_list_height == users_list_height) {
                    chat_session_wrap_height += messages_list_height;
                } else if (messages_list_height > users_list_height) {
                    chat_session_wrap_height += messages_list_height;
                    jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-users-list').height(messages_list_height);
                } else if (messages_list_height < users_list_height) {
                    chat_session_wrap_height += users_list_height;
                    jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list').height(users_list_height);
                }

                var message_list_height = jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list').outerHeight(true);
                if (chat_session_wrap_height < chat_session_current_height) {
                    var height_diff = chat_session_current_height - chat_session_wrap_height;
                    //console.log('chat_session_size_message_list: message_list_height['+message_list_height+'] height_diff['+height_diff+']');

                    jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list').height(message_list_height + height_diff);
                    jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-users-list').height(message_list_height + height_diff);
                } else if (chat_session_wrap_height > chat_session_current_height) {
                    var height_diff = chat_session_current_height - chat_session_wrap_height;
                    jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list').height(message_list_height + height_diff);
                    jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-users-list').height(message_list_height + height_diff);
                }

            } else if ((chat_session['users_list_position'] == "above") || (chat_session['users_list_position'] == "below")) {

                if ((jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-users-list')) && (jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-users-list').is(":visible"))) {
                    chat_session_wrap_height += jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-users-list').outerHeight(true);
                }
                if ((jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list')) && (jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list').is(":visible"))) {
                    var messages_list_height = jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list').outerHeight(true);
                } else {
                    var messages_list_height = 0;
                }
                chat_session_wrap_height += messages_list_height;

                if (chat_session_wrap_height < chat_session_current_height) {
                    var height_diff = chat_session_current_height - chat_session_wrap_height;
                    jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list').height(messages_list_height + height_diff + 1);
                } else if (chat_session_wrap_height > chat_session_current_height) {
                    var height_diff = chat_session_current_height - chat_session_wrap_height;
                    jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list').height(messages_list_height + height_diff + 1);
                }
            }
        }
    },
    // This function contains somewhat funky logic. Its role is to keep certain module visible based on the users auth ability.
    // This function also considers the site type sessions which might be minimized.
    chat_session_set_auth_view: function () {

        jQuery('div.psource-chat-box-site.psource-chat-box-min div.psource-chat-module-login').hide();

        jQuery('div.psource-chat-box.psource-chat-box-max.psource-chat-session-closed div.psource-chat-module-session-status').show();
        jQuery('div.psource-chat-box.psource-chat-box-max.psource-chat-session-open div.psource-chat-module-session-status').hide();
        jQuery('div.psource-chat-box.psource-chat-box-min.psource-chat-session-closed div.psource-chat-module-session-status').hide();

        if (psource_chat.settings['auth']['type'] != undefined) {

            // Hide the chat module for login because the user is already there.
            jQuery('div.psource-chat-box div.psource-chat-module-login').hide();
            jQuery('div.psource-chat-box div.psource-chat-module-login-prompt').hide();

            //jQuery('div.psource-chat-box div.psource-chat-module-header ul.psource-chat-actions-menu li.psource-chat-actions-settings-pop-out').show();
            if (jQuery('body').hasClass('psource-chat-pop-out'))
                jQuery('body.psource-chat-pop-out div.psource-chat-box div.psource-chat-module-header ul.psource-chat-actions-menu li.psource-chat-actions-settings-pop-out').hide();
            else
                jQuery('div.psource-chat-box div.psource-chat-module-header ul.psource-chat-actions-menu li.psource-chat-actions-settings-pop-out').show();

            // For WordPress type users we don't provide a login/logout functionality.
            if (psource_chat.settings['auth']['type'] == "wordpress") {
                jQuery('div.psource-chat-box ul.psource-chat-actions-menu li.psource-chat-action-menu-item-login').hide();
                jQuery('div.psource-chat-box ul.psource-chat-actions-menu li.psource-chat-action-menu-item-logout').hide();
            } else {
                jQuery('div.psource-chat-box ul.psource-chat-actions-menu li.psource-chat-action-menu-item-login').hide();

                jQuery('div.psource-chat-box ul.psource-chat-actions-menu li.psource-chat-action-menu-item-logout').show();
                jQuery('div.psource-chat-box-private ul.psource-chat-actions-menu li.psource-chat-action-menu-item-logout').hide();
            }

            for (var chat_id in psource_chat.settings['sessions']) {
                if (!jQuery('div#psource-chat-box-' + chat_id).length)
                    continue;

                var chat_session = psource_chat.settings['sessions'][chat_id];
                if (chat_session == undefined) continue;

                if (jQuery('div#psource-chat-box-' + chat_session['id']).hasClass('psource-chat-session-ip-blocked')) {
                    if (chat_session['moderator'] == "no") {

                        jQuery('div#psource-chat-box-' + chat_session['id'] + '.psource-chat-session-ip-blocked div.psource-chat-module-banned-status').show();
                        jQuery('div#psource-chat-box-' + chat_session['id'] + '.psource-chat-session-ip-blocked div.psource-chat-module-messages-list').hide();
                        jQuery('div#psource-chat-box-' + chat_session['id'] + '.psource-chat-session-ip-blocked div.psource-chat-module-users-list').hide();
                        jQuery('div#psource-chat-box-' + chat_session['id'] + '.psource-chat-session-ip-blocked div.psource-chat-module-message-area').hide();
                        jQuery('div#psource-chat-box-' + chat_session['id'] + '.psource-chat-session-ip-blocked div.psource-chat-module-login').hide();
                    }
                } else if (jQuery('div#psource-chat-box-' + chat_session['id']).hasClass('psource-chat-session-user-blocked')) {
                    if (chat_session['moderator'] == "no") {

                        jQuery('div#psource-chat-box-' + chat_session['id'] + '.psource-chat-session-user-blocked div.psource-chat-module-banned-status').show();
                        jQuery('div#psource-chat-box-' + chat_session['id'] + '.psource-chat-session-user-blocked div.psource-chat-module-messages-list').hide();
                        jQuery('div#psource-chat-box-' + chat_session['id'] + '.psource-chat-session-user-blocked div.psource-chat-module-users-list').hide();
                        jQuery('div#psource-chat-box-' + chat_session['id'] + '.psource-chat-session-user-blocked div.psource-chat-module-message-area').hide();
                        jQuery('div#psource-chat-box-' + chat_session['id'] + '.psource-chat-session-user-blocked div.psource-chat-module-login').hide();
                    }
                } else {

                    //jQuery('div.psource-chat-box-'+chat_session['id']+' ul.psource-chat-actions-menu li.psource-chat-actions-settings').show();

                    if (chat_session['moderator'] == "no") {
                        jQuery('div#psource-chat-box-' + chat_session['id'] + ' div.psource-chat-module-banned-status').hide();

                        //if (chat_session['session_status'] == 'closed') {
                        if (jQuery('div#psource-chat-box-' + chat_session['id']).hasClass('psource-chat-session-closed')) {
                            jQuery('div#psource-chat-box-' + chat_session['id'] + ' div.psource-chat-module-message-area').hide();
                        } else {
                            jQuery('div#psource-chat-box-' + chat_session['id'] + ' div.psource-chat-module-message-area').show();

                            /*
                             if (chat_session['box_input_moderator_hide'] == 'enabled') {
                             if ((chat_session['users'] == undefined) || (chat_session['users']['moderators'] == undefined) || (jQuery.isEmptyObject(chat_session['users']['moderators']))) {
                             jQuery('div#psource-chat-box-'+chat_session['id']+' div.psource-chat-module-message-area').hide();
                             if (chat_session['session_type'] != 'private') {
                             jQuery('div#psource-chat-box-'+chat_id+' div.psource-chat-session-user-status-message').empty();
                             jQuery('div#psource-chat-box-'+chat_id+' div.psource-chat-session-user-status-message').html('<p>'+chat_session['box_input_moderator_hide_label']+'</p>');
                             jQuery('div#psource-chat-box-'+chat_id+' div.psource-chat-session-user-status-message').show();
                             psource_chat.chat_session_size_message_list;
                             }
                             } else {
                             var active_moderator_count = 0;

                             if ((Object.keys(chat_session['users']['moderators']).length > 0)) {
                             for (var user_id in chat_session['users']['moderators']) {
                             if (!chat_session['users']['moderators'].hasOwnProperty(user_id)) continue;
                             var chat_user = chat_session['users']['moderators'][user_id];
                             if (chat_user['connect_status'] == 'accepted') {
                             active_moderator_count += 1;
                             }
                             }
                             }

                             if (active_moderator_count == 0) {
                             jQuery('div#psource-chat-box-'+chat_session['id']+' div.psource-chat-module-message-area').hide();

                             if (chat_session['session_type'] != 'private') {
                             jQuery('div#psource-chat-box-'+chat_id+' div.psource-chat-session-user-status-message').empty();
                             jQuery('div#psource-chat-box-'+chat_id+' div.psource-chat-session-user-status-message').html('<p>'+chat_session['box_input_moderator_hide_label']+'</p>');
                             jQuery('div#psource-chat-box-'+chat_id+' div.psource-chat-session-user-status-message').show();
                             psource_chat.chat_session_size_message_list;
                             }

                             } else {
                             jQuery('div#psource-chat-box-'+chat_session['id']+' div.psource-chat-module-message-area').show();

                             if (chat_session['session_type'] != 'private') {
                             jQuery('div#psource-chat-box-'+chat_id+' div.psource-chat-session-user-status-message').hide();
                             }
                             }
                             }
                             } else {
                             jQuery('div#psource-chat-box-'+chat_session['id']+' div.psource-chat-module-message-area').show();
                             }
                             */
                        }
                    }

                    //if ((chat_session['session_type'] == "site") || (chat_session['session_type'] == "private")) {
                    if ((jQuery('div#psource-chat-box-' + chat_session['id']).hasClass('psource-chat-box-site')) || (jQuery('div#psource-chat-box-' + chat_session['id']).hasClass('psource-chat-box-private'))) {
                        // If the site chat box is minimized then we don't show the modules
                        if (jQuery('div#psource-chat-box-' + chat_session['id']).hasClass('psource-chat-box-min')) {
                            jQuery('div#psource-chat-box-' + chat_session['id'] + ' div.psource-chat-module-messages-list').hide();
                            jQuery('div#psource-chat-box-' + chat_session['id'] + ' div.psource-chat-module-users-list').hide();
                            jQuery('div#psource-chat-box-' + chat_session['id'] + ' div.psource-chat-module-message-area').hide();

                            jQuery('div#psource-chat-box-' + chat_session['id'] + ' ul.psource-chat-actions-menu li.psource-chat-actions-settings').hide();
                            jQuery('div#psource-chat-box-' + chat_session['id'] + ' ul.psource-chat-actions-menu li.psource-chat-actions-settings-pop-out').hide();
                        } else {
                            jQuery('div#psource-chat-box-' + chat_session['id'] + ' div.psource-chat-module-users-list').show();
                            jQuery('div#psource-chat-box-' + chat_session['id'] + ' div.psource-chat-module-messages-list').show();
                            jQuery('div#psource-chat-box-' + chat_session['id'] + ' div.psource-chat-module-message-area').show();

                            if (chat_session['moderator'] == "no") {
                                if (jQuery('div#psource-chat-box-' + chat_session['id']).hasClass('psource-chat-session-closed')) {
                                    jQuery('div#psource-chat-box-' + chat_session['id'] + ' div.psource-chat-module-message-area').hide();
                                } else {
                                    jQuery('div#psource-chat-box-' + chat_session['id'] + ' div.psource-chat-module-message-area').show();
                                }
                            }
                            jQuery('div#psource-chat-box-' + chat_session['id'] + ' ul.psource-chat-actions-menu li.psource-chat-actions-settings').show();

                            if (jQuery('body').hasClass('psource-chat-pop-out'))
                                jQuery('div#psource-chat-box-' + chat_session['id'] + ' ul.psource-chat-actions-menu li.psource-chat-actions-settings-pop-out').hide();
                            else
                                jQuery('div#psource-chat-box-' + chat_session['id'] + ' ul.psource-chat-actions-menu li.psource-chat-actions-settings-pop-out').show();
                        }
                    } else {
                        jQuery('div#psource-chat-box-' + chat_session['id'] + ' div.psource-chat-module-header ul.psource-chat-actions-menu li.psource-chat-actions-settings').show();
                        jQuery('div#psource-chat-box-' + chat_session['id'] + ' div.psource-chat-module-users-list').show();
                        jQuery('div#psource-chat-box-' + chat_session['id'] + ' div.psource-chat-module-messages-list').show();
                    }
                }
            }

        } else {
            // If the user id not authenticated we hide the status about the session being closed. Too many prompts
            jQuery('div.psource-chat-box div.psource-chat-module-session-status').hide();

            // Show the login menu. Hide the logout
            jQuery('div.psource-chat-box ul.psource-chat-actions-menu li.psource-chat-action-menu-item-login').show();
            jQuery('div.psource-chat-box ul.psource-chat-actions-menu li.psource-chat-action-menu-item-logout').hide();

            jQuery('div.psource-chat-box div.psource-chat-module-header ul.psource-chat-actions-menu li.psource-chat-actions-settings-pop-out').hide();

            for (var chat_id in psource_chat.settings['sessions']) {
                if (!jQuery('div#psource-chat-box-' + chat_id).length)
                    continue;

                var chat_session = psource_chat.settings['sessions'][chat_id];
                if (chat_session == undefined) continue;

                if ((jQuery('div#psource-chat-box-' + chat_session['id']).hasClass('psource-chat-session-ip-blocked'))
                    && (chat_session['moderator'] == "no")) {

                    jQuery('div#psource-chat-box-' + chat_session['id'] + '.psource-chat-session-ip-blocked div.psource-chat-module-banned-status').show();
                    jQuery('div#psource-chat-box-' + chat_session['id'] + '.psource-chat-session-ip-blocked div.psource-chat-module-login-prompt').hide();
                    jQuery('div#psource-chat-box-' + chat_session['id'] + '.psource-chat-session-ip-blocked div.psource-chat-module-messages-list').hide();
                    jQuery('div#psource-chat-box-' + chat_session['id'] + '.psource-chat-session-ip-blocked div.psource-chat-module-users-list').hide();
                    jQuery('div#psource-chat-box-' + chat_session['id'] + '.psource-chat-session-ip-blocked div.psource-chat-module-message-area').hide();
                    jQuery('div#psource-chat-box-' + chat_session['id'] + '.psource-chat-session-ip-blocked div.psource-chat-module-login').hide();
                    jQuery('div#psource-chat-box-' + chat_session['id'] + '.psource-chat-session-ip-blocked ul.psource-chat-actions-menu').hide();

                } else {
                    //Commented out as it shows the settings menu back, as soon as ajax request is sent
                    //jQuery('div#psource-chat-box-' + chat_session['id'] + ' ul.psource-chat-actions-menu').show();

                    jQuery('div#psource-chat-box-' + chat_session['id'] + ' div.psource-chat-module-message-area').hide();

                    if (((chat_session['session_type'] == "site") || (chat_session['session_type'] == "private")) && (jQuery('div#psource-chat-box-' + chat_session['id']).hasClass('psource-chat-box-min'))) {
                        jQuery('div#psource-chat-box-' + chat_session['id'] + ' div.psource-chat-module-login-prompt').hide();
                    } else {
                        if (chat_session['noauth_view'] == "default") {
                            if (jQuery('div#psource-chat-box-' + chat_session['id'] + ' div.psource-chat-module-login').is(":visible")) {
                                jQuery('div#psource-chat-box-' + chat_session['id'] + ' div.psource-chat-module-login-prompt').hide();
                                jQuery('div#psource-chat-box-' + chat_session['id'] + ' div.psource-chat-module-message-list').hide();
                                jQuery('div#psource-chat-box-' + chat_session['id'] + ' div.psource-chat-module-users-list').hide();
                            } else {
                                jQuery('div#psource-chat-box-' + chat_session['id'] + ' div.psource-chat-module-messages-list').show();
                                jQuery('div#psource-chat-box-' + chat_session['id'] + ' div.psource-chat-module-users-list').show();
                                jQuery('div#psource-chat-box-' + chat_session['id'] + ' div.psource-chat-module-login-prompt').show();
                            }
                            jQuery('div#psource-chat-box-' + chat_session['id'] + ' ul.psource-chat-actions-menu li.psource-chat-actions-settings').show();
                        } else if (chat_session['noauth_view'] == "login-only") {
                            jQuery('div#psource-chat-box-' + chat_session['id'] + ' div.psource-chat-module-login').show();
                            jQuery('div#psource-chat-box-' + chat_session['id'] + ' div.psource-chat-module-login button.psource-chat-login-cancel').hide();
                            jQuery('div#psource-chat-box-' + chat_session['id'] + ' div.psource-chat-module-messages-list').hide();
                            jQuery('div#psource-chat-box-' + chat_session['id'] + ' div.psource-chat-module-users-list').hide();
                            jQuery('div#psource-chat-box-' + chat_session['id'] + ' ul.psource-chat-actions-menu li.psource-chat-actions-settings').hide();
                        } else if (chat_session['noauth_view'] == "no-login") {
                            jQuery('div#psource-chat-box-' + chat_session['id'] + ' div.psource-chat-module-login-prompt').hide();
                            jQuery('div#psource-chat-box-' + chat_session['id'] + ' div.psource-chat-module-login').hide();

                            jQuery('div.psource-chat-box ul.psource-chat-actions-menu li.psource-chat-action-menu-item-login').hide();
                            jQuery('div.psource-chat-box ul.psource-chat-actions-menu li.psource-chat-action-menu-item-logout').hide();

                        }
                    }
                }
            }
        }

        psource_chat.chat_session_size_message_list();

        for (var chat_id in psource_chat.settings['sessions']) {
            if (!jQuery('div#psource-chat-box-' + chat_id).length)
                continue;

            var chat_session = psource_chat.settings['sessions'][chat_id];
            if (chat_session == undefined) continue;

            var chat_box_width = jQuery('div#psource-chat-box-' + chat_id).width();
            var chat_box_menu_width = jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-header div.psource-chat-module-header-actions').width();
            jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-header div.psource-chat-module-header-title').width(chat_box_width - (chat_box_menu_width) - 5);
        }
    },

    chat_session_handle_keydown: function (event) {

        var chat_box_id = jQuery(this).parents('div.psource-chat-box').attr('id');
        var chat_id = chat_box_id.replace('psource-chat-box-', '');
        ;
        var chat_session = psource_chat.chat_session_get_session_by_id(chat_id);
        if (chat_session != undefined) {

            // http://stackoverflow.com/questions/23503254/not-working-jquery-enter-key-and-shift-enter-key
            // event.shiftKey check if shift+CR was used.

            var code = event.keyCode ? event.keyCode : event.which;

            if ((code.toString() != 13) || ((code.toString() == 13) && (event.shiftKey))) {
                // If the key is not CR or if the key is CR+shift
                var message_text = jQuery(this).val();
                if (chat_session['row_message_input_length'] > 0) {
                    if (message_text.length > chat_session['row_message_input_length']) {
                        var message_text_new = message_text.substr(0, chat_session['row_message_input_length']);
                        jQuery(this).val(message_text_new);
                        jQuery('#' + chat_box_id + ' div.psource-chat-module-message-area ul.psource-chat-send-meta li.psource-chat-send-input-length span.psource-chat-character-count').html(message_text_new.length);
                        //event.preventDefault();
                    } else {
                        jQuery('#' + chat_box_id + ' div.psource-chat-module-message-area ul.psource-chat-send-meta li.psource-chat-send-input-length span.psource-chat-character-count').html(message_text.length);
                    }
                } else {
                    jQuery('#' + chat_box_id + ' div.psource-chat-module-message-area ul.psource-chat-send-meta li.psource-chat-send-input-length span.psource-chat-character-count').html(message_text.length);
                }

            } else {
                // If the key is CR check if the chat session uses the send key.
                if (!jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box div.psource-chat-module-message-area button.psource-chat-send-button').length) {

                    event.preventDefault();

                    var $chatBox = jQuery('div#psource-chat-box-' + chat_id);
                    var message_text = jQuery(this).val().trim();
                    
                    console.log('Enter key - Message:', message_text);

                    // Prüfe ob Upload-System verfügbar ist und Queue-Uploads vorhanden sind
                    var hasUploads = false;
                    if (typeof PSChatUpload !== 'undefined') {
                        console.log('PSChatUpload available, checking queue...');
                        hasUploads = PSChatUpload.uploadQueue.some(function(item) {
                            return item.status === 'queued' && item.chatBox.is($chatBox);
                        });
                        console.log('Has uploads:', hasUploads);
                    }

                    // Senden nur wenn Text ODER Uploads vorhanden sind
                    if (message_text === '' && !hasUploads) {
                        console.log('Nothing to send via Enter');
                        return;
                    }

                    // IF we are NOT using the send button we want to remove the
                    //message_text = message_text.replace('\n', '');

                    if (typeof PSChatUpload !== 'undefined' && hasUploads) {
                        console.log('Processing uploads via Enter...');
                        
                        PSChatUpload.processQueueOnSend($chatBox, function(uploadReferences) {
                            console.log('Enter Upload callback - References:', uploadReferences);
                            // Text ohne Dateinamen (nur echten Text behalten)
                            var cleanMessage = PSChatUpload.cleanMessageText(message_text);
                            console.log('Enter Clean message:', cleanMessage);
                            
                            // Upload-Referenzen zur Nachricht hinzufügen
                            var finalMessage = cleanMessage;
                            if (uploadReferences && uploadReferences.length > 0) {
                                finalMessage = cleanMessage ? cleanMessage + ' ' + uploadReferences.join(' ') : uploadReferences.join(' ');
                            }
                            console.log('Enter Final message:', finalMessage);
                            
                            // Nachricht senden
                            if (finalMessage.trim() !== '') {
                                console.log('Sending final message via Enter:', finalMessage);
                                psource_chat.chat_session_enqueue_message(finalMessage, chat_session);
                                jQuery($chatBox.find('textarea.psource-chat-send')).val('');
                                jQuery('#' + chat_box_id + ' div.psource-chat-module-message-area ul.psource-chat-send-meta li.psource-chat-send-input-length span.psource-chat-character-count').html('0');
                            } else {
                                console.log('Final message is empty, not sending');
                            }
                        });
                    } else {
                        // Fallback: normales Verhalten
                        if (message_text != '') {
                            psource_chat.chat_session_enqueue_message(message_text, chat_session);
                            jQuery(this).val('');
                            jQuery('#' + chat_box_id + ' div.psource-chat-module-message-area ul.psource-chat-send-meta li.psource-chat-send-input-length span.psource-chat-character-count').html('0');
                        }
                    }
                } else {

                }
            }
        }
    },
    chat_session_handle_send_button: function (chat_id) {
        var chat_session = psource_chat.chat_session_get_session_by_id(chat_id);

        if (jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box div.psource-chat-module-message-area button.psource-chat-send-button').length) {
            jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box div.psource-chat-module-message-area button.psource-chat-send-button').on( "click", function (event) {
                event.preventDefault();

                var $chatBox = jQuery('div#psource-chat-box-' + chat_id);
                var chat_textarea = $chatBox.find('textarea.psource-chat-send');
                var message_text = chat_textarea.val().trim();
                
                // Prüfe ob Upload-System verfügbar ist und Queue-Uploads vorhanden sind
                var hasUploads = false;
                if (typeof PSChatUpload !== 'undefined') {
                    hasUploads = PSChatUpload.uploadQueue.some(function(item) {
                        return item.status === 'queued' && item.chatBox.is($chatBox);
                    });
                }
                
                // Senden nur wenn Text ODER Uploads vorhanden sind
                if (message_text === '' && !hasUploads) {
                    return; // Nichts zu senden
                }
                
                // Prüfe ob Upload-System verfügbar ist
                if (typeof PSChatUpload !== 'undefined' && hasUploads) {
                    // Button deaktivieren während Upload/Send
                    var $sendButton = jQuery(this);
                    $sendButton.prop('disabled', true).text('Wird gesendet...');
                    
                    PSChatUpload.processQueueOnSend($chatBox, function(uploadReferences) {
                        // Text ohne Dateinamen (nur echten Text behalten)
                        var cleanMessage = PSChatUpload.cleanMessageText(message_text);
                        
                        // Upload-Referenzen zur Nachricht hinzufügen
                        var finalMessage = cleanMessage;
                        if (uploadReferences && uploadReferences.length > 0) {
                            finalMessage = cleanMessage ? cleanMessage + ' ' + uploadReferences.join(' ') : uploadReferences.join(' ');
                        }
                        
                        // Nachricht senden
                        if (finalMessage.trim() !== '') {
                            psource_chat.chat_session_enqueue_message(finalMessage, chat_session);
                            chat_textarea.val('');
                            jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-message-area ul.psource-chat-send-meta li.psource-chat-send-input-length span.psource-chat-character-count').html('0');
                        }
                        
                        // Button wieder aktivieren
                        $sendButton.prop('disabled', false).text($sendButton.data('original-text') || 'Senden');
                    });
                } else {
                    // Fallback: normales Verhalten ohne Upload-System
                    if (message_text != '') {
                        psource_chat.chat_session_enqueue_message(message_text, chat_session);
                        jQuery(chat_textarea).val('');
                        jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-message-area ul.psource-chat-send-meta li.psource-chat-send-input-length span.psource-chat-character-count').html('0');
                    }
                }
            });
        } else {
            //jQuery('div#psource-chat-box-'+chat_id+'.psource-chat-box div.psource-chat-module-message-area textarea.psource-chat-send').on('keyup', psource_chat.chat_session_handle_keyup);
            jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box div.psource-chat-module-message-area textarea.psource-chat-send').on('keydown', psource_chat.chat_session_handle_keydown);
        }
    },
    /* Get a specific chat_session from out settings list by chat_id */
    chat_session_get_session_by_id: function (chat_id) {
        if (psource_chat.settings['sessions'][chat_id] != undefined)
            return psource_chat.settings['sessions'][chat_id];
    },
    /* Assign a chat_session to our settings list */
    chat_session_set_session_by_id: function (chat_id, chat_session) {
        psource_chat.settings['sessions'][chat_id] = chat_session;
    },
    chat_session_get_auth_type: function () {
        if (psource_chat.settings['auth']['type'] == undefined)
            return '';
        else
            return psource_chat.settings['auth']['type'];
    },
    chat_session_enqueue_message: function (message, chat_session) {

        var chat_id = chat_session['id'];

        if (psource_chat.send_data[chat_id] == undefined)
            psource_chat.send_data[chat_id] = {};

        if (message.length) {
            var microtime_timestamp = psource_chat.microtime(true);
            psource_chat.send_data[chat_id][microtime_timestamp] = message;
        }
        psource_chat.chat_session_messages_send();
    },
    chat_session_messages_send: function () {

        if (psource_chat.pids['chat_session_messages_send'] != '')
            return;

        var sessions_data = {};

        if ((psource_chat.settings['sessions'] != undefined) && (Object.keys(psource_chat.settings['sessions']).length > 0)) {
            for (var chat_id in psource_chat.settings['sessions']) {
                //sessions_data[chat_id] = psource_chat.settings['sessions'][chat_id];

                if ((psource_chat.send_data[chat_id] != undefined) && (Object.keys(psource_chat.send_data[chat_id]).length > 0)) {
                    var chat_session = psource_chat.settings['sessions'][chat_id];
                    sessions_data[chat_id] = {};
                    sessions_data[chat_id]['id'] = chat_session['id'];
                    sessions_data[chat_id]['blog_id'] = chat_session['blog_id'];
                    sessions_data[chat_id]['session_type'] = chat_session['session_type'];

                    // Set a flag for this session so we don't make a sound when we update the message rows.
                    //commented out, as it plays the sound twice
                    //psource_chat.settings['sessions'][chat_id]['has_send_message'] = true;
                }
            }
        }

        if ((Object.keys(sessions_data).length > 0) && (psource_chat.pids['chat_session_messages_send'] == '') && (psource_chat.errors['chat_session_messages_send'] < 10)) {

            psource_chat.pids['chat_session_messages_send'] = jQuery.ajax({
                type: "POST",
                url: psource_chat_localized['settings']["ajax_url"],
                dataType: "json",
                cache: false,
                data: {
                    'function': 'chat_message_send',
                    'action': 'chatProcess',
                    'psource-chat-sessions': sessions_data,
                    'chat_messages': psource_chat.send_data,
                    'psource-chat-settings-request-uri': psource_chat_localized['settings']['REQUEST_URI']
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    psource_chat.errors['chat_session_messages_send'] = parseInt(psource_chat.errors['chat_session_messages_send']) + 1;
                    console.log('chat_message_send: error HTTP Status[' + jqXHR.status + '] ' + errorThrown);
                },
                success: function (reply_data) {
                    psource_chat.pids['chat_session_messages_send'] = '';
                    if (reply_data != undefined) {
                        psource_chat.errors['chat_session_messages_send'] = 0;

                        if (reply_data['errorStatus'] != undefined) {
                            if (reply_data['errorStatus'] == true) {
                                if (reply_data['errorText'] != undefined) {
                                }
                            } else if (reply_data['chat_messages'] != undefined) {
                                for (var chat_id in reply_data['chat_messages']) {

                                    if (!reply_data['chat_messages'].hasOwnProperty(chat_id)) continue;

                                    var chat_messages = reply_data['chat_messages'][chat_id];
                                    for (var idx in chat_messages) {
                                        if (!chat_messages.hasOwnProperty(idx)) continue;

                                        if ((chat_messages[idx] == true) && (psource_chat.send_data[chat_id][idx] != undefined)) {
                                            var message = psource_chat.send_data[chat_id][idx];

                                            //psource_chat.send_data[chat_id].splice(psource_chat.send_data[chat_id].indexOf(idx), 1);
                                            delete psource_chat.send_data[chat_id][idx];
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        psource_chat.errors['chat_session_messages_send'] = parseInt(psource_chat.errors['chat_session_messages_send']) + 1;
                    }

                    var poll_interval = 1;
                    setTimeout(function () {
                        psource_chat.chat_session_messages_send();
                    }, poll_interval * 1000);
                },
                complete: function (e, xhr, settings) {
                    psource_chat.pids['chat_session_messages_send'] = '';
                    var poll_interval = 1;
                    setTimeout(function () {
                        psource_chat.chat_session_messages_send();
                    }, poll_interval * 1000);
                }
            });
        }
    },

    /* Appends rows from AJAX reply to chat -box */
    chat_session_process_rows: function (chat_session, rows) {
        var updateContent = '';
        var chat_id = chat_session['id'];

        var new_rows_count = 0;

        for (var i in rows) {
            if (rows.hasOwnProperty(i)) {
                if (!jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list div#psource-chat-row-' + i).length) {
                    new_rows_count += 1;
                    if (chat_session['box_input_position'] == "top") {
                        updateContent = rows[i] + updateContent;
                    } else {
                        updateContent = updateContent + rows[i];
                    }
                }
            }
        }

        if (updateContent !== '') {
            var force_scroll_bottom = true;

            var container = jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list');
            var c_height = container.height();
            var row = jQuery('div.psource-chat-row', container).last();

            if (jQuery(row).length) {
                var r_height = jQuery(row).height();
                var r_offset = row.offset();

                var c_offset = container.offset();

                var diff_offset = r_offset.top - c_offset.top;
                if (diff_offset < c_height)
                    force_scroll_bottom = true;
                else
                    force_scroll_bottom = false
            }
            if (chat_session['box_input_position'] == "top") {
                jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list').prepend(updateContent);
                // Refresh avatar fallbacks for new content
                psource_chat.refresh_avatar_fallbacks();

            } else if (chat_session['box_input_position'] == "bottom") {
                jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list').append(updateContent);
                // Refresh avatar fallbacks for new content
                psource_chat.refresh_avatar_fallbacks();

                //Check User preference
                $auto_scroll = jQuery('.manage-auto-scroll').attr('data-auto_scroll');
                $auto_scroll = typeof( $auto_scroll ) != 'undefined' && $auto_scroll == 'on' ? true : false;

                if (force_scroll_bottom == true && $auto_scroll) {
                    var row = jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list div.psource-chat-row').last();
                    if (row.length) {
                        var r_position = row.position();
                        var c_scrollTop = container.scrollTop() + r_position.top;

                        jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list').animate({scrollTop: c_scrollTop}, 1000);
                    }
                }
            }

            // This will limit the number of message show to the user on entry and page reload. Default 100 per settings.
            if (chat_session['log_limit'] != undefined) {
                while (jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list div.psource-chat-row').length > chat_session['log_limit']) {
                    if (chat_session['box_input_position'] == "bottom") {
                        jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list div.psource-chat-row').eq(0).remove();
                    } else if (chat_session['box_input_position'] == "top") {
                        jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list div.psource-chat-row').last().remove();
                    }
                }
            }

            if ((new_rows_count > 0) && (jQuery('#psource-chat-box-' + chat_id).hasClass('psource-chat-box-min'))) {
                var prev_rows_count = jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-header span.psource-chat-title-count').attr('last_row_count');
                if ((prev_rows_count == '') || (prev_rows_count == undefined)) {
                    prev_rows_count = 0;
                } else {
                    prev_rows_count = parseInt(prev_rows_count);
                }
                var rows_count = new_rows_count + prev_rows_count;
                jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-header span.psource-chat-title-count').html('(' + rows_count + ')');
                jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-header span.psource-chat-title-count').attr('last_row_count', rows_count);
            }

            return true;
        }
    },
    chat_session_admin_row_actions: function (chat_id) {

        if (psource_chat.settings['auth']['auth_hash'] != undefined) {
            jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box-moderator div.psource-chat-module-messages-list div.psource-chat-row ul.psource-chat-row-footer li.psource-chat-admin-actions-item-invite a[rel="' + psource_chat.settings['auth']['auth_hash'] + '"]').each(function () {
                jQuery(this).parents('li.psource-chat-admin-actions-item-invite').hide();
            });
        }


        var selector = 'div#psource-chat-box-' + chat_id + '.psource-chat-box-moderator div.psource-chat-module-messages-list div.psource-chat-row ul.psource-chat-row-footer li.psource-chat-admin-actions-item a';
        jQuery(selector).off('click');
        jQuery(selector).on( "click", function (event) {
            event.preventDefault();

            var row_id = jQuery(this).parents('.psource-chat-row').attr('id').replace('psource-chat-row-', '');
            var chat_session = psource_chat.chat_session_get_session_by_id(chat_id);
            if (chat_session == undefined) return false;

            if (jQuery(this).hasClass('psource-chat-admin-actions-item-delete')) {
                if (jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box-moderator div.psource-chat-module-messages-list #psource-chat-row-' + row_id).hasClass('psource-chat-row-deleted'))
                    var admin_action = "undelete";
                else
                    var admin_action = "delete";

                //console.log('chat_session_moderate_message: row_id['+row_id+']');
                jQuery.ajax({
                    type: "POST",
                    url: psource_chat_localized['settings']["ajax_url"],
                    cache: false,
                    dataType: "json",
                    data: {
                        'action': 'chatProcess',
                        'function': 'chat_session_moderate_message',
                        'chat_id': chat_id,
                        'chat_session': chat_session,
                        'row_id': row_id,
                        'moderate_action': admin_action,
                        //'psource-chat-auth': psource_chat.settings['auth'],
                        //'psource-chat-settings': psource_chat_localized['settings']
                        //'psource-chat-settings-abspath': psource_chat_localized['settings']['ABSPATH'],
                        'psource-chat-settings-request-uri': psource_chat_localized['settings']['REQUEST_URI']
                    },
                    success: function (reply_data) {
                        if (reply_data != undefined) {
                            if (reply_data['errorStatus'] != undefined) {
                                if (reply_data['errorStatus'] == true) {
                                    if (reply_data['errorText'] != undefined) {
                                        //console.log("Chat: chat_session_moderate_message: reply [%s]", reply_data['errorText']);
                                    }
                                }
                            }
                        }
                    }
                });

            } else if (jQuery(this).hasClass('psource-chat-admin-actions-item-block-ip')) {
                var row_ip_address = jQuery(this).attr('rel');

                if (jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box-moderator div.psource-chat-module-messages-list #psource-chat-row-' + row_id).hasClass('psource-chat-row-ip-blocked'))
                    var admin_action = "unblock-ip";
                else
                    var admin_action = "block-ip";

                jQuery.ajax({
                    type: "POST",
                    url: psource_chat_localized['settings']["ajax_url"],
                    cache: false,
                    dataType: "json",
                    data: {
                        'action': 'chatProcess',
                        'function': 'chat_session_moderate_ipaddress',
                        'chat_id': chat_id,
                        'chat_session': chat_session,
                        'ip_address': row_ip_address,
                        'moderate_action': admin_action,
                        //'psource-chat-auth': psource_chat.settings['auth'],
                        //'psource-chat-settings': psource_chat_localized['settings']
                        //'psource-chat-settings-abspath': psource_chat_localized['settings']['ABSPATH'],
                        'psource-chat-settings-request-uri': psource_chat_localized['settings']['REQUEST_URI']
                    },
                    success: function (reply_data) {
                        if (reply_data != undefined) {
                            if (reply_data['errorStatus'] != undefined) {
                                if (reply_data['errorStatus'] == true) {
                                    if (reply_data['errorText'] != undefined) {
                                        //console.log("Chat: chat_session_moderate_ipaddress: reply [%s]", reply_data['errorText']);
                                    }
                                }
                            }
                        }
                    }
                });
            } else if (jQuery(this).hasClass('psource-chat-admin-actions-item-block-user')) {
                var row_user = jQuery(this).attr('rel');

                if (jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box-moderator div.psource-chat-module-messages-list #psource-chat-row-' + row_id).hasClass('psource-chat-row-user-blocked'))
                    var moderate_action = "unblock-user";
                else
                    var moderate_action = "block-user";

                jQuery.ajax({
                    type: "POST",
                    url: psource_chat_localized['settings']["ajax_url"],
                    cache: false,
                    dataType: "json",
                    data: {
                        'action': 'chatProcess',
                        'function': 'chat_session_moderate_user',
                        'chat_id': chat_id,
                        'chat_session': chat_session,
                        'moderate_item': row_user,
                        'moderate_action': moderate_action,
                        //'psource-chat-auth': psource_chat.settings['auth'],
                        //'psource-chat-settings': psource_chat_localized['settings']
                        //'psource-chat-settings-abspath': psource_chat_localized['settings']['ABSPATH'],
                        'psource-chat-settings-request-uri': psource_chat_localized['settings']['REQUEST_URI']
                    },
                    success: function (reply_data) {
                        if (reply_data != undefined) {
                            if (reply_data['errorStatus'] != undefined) {
                                if (reply_data['errorStatus'] == true) {
                                    if (reply_data['errorText'] != undefined) {
                                        //console.log("Chat: chat_session_moderate_user: reply [%s]", reply_data['errorText']);
                                    }
                                }
                            }
                        }
                    }
                });
            }
            else if (jQuery(this).hasClass('psource-chat-user-invite')) {

                var user_hash = jQuery(this).attr('rel');
                if (user_hash != '') {
                    //console.log('chat_session_admin_row_actions user_hash=['+user_hash+']');
                    psource_chat.chat_process_private_invite(user_hash);
                }
            }
            return false;
        });
    },
    // Process Deleted Rows from AJAX meta information
    chat_session_admin_process_row_delete_actions: function (chat_id, deleted_rows) {
        var delete_row_class = 'psource-chat-row-deleted';

        jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list div.psource-chat-row').each(function () {
            var row_id_full = jQuery(this).attr('id');
            var row_id = row_id_full.replace('psource-chat-row-', '');
            var item_found = jQuery.inArray(row_id, deleted_rows);
            if (item_found == -1) {
                jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list #' + row_id_full).removeClass(delete_row_class);
                jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list #' + row_id_full + " ul.psource-chat-row-footer li.psource-chat-admin-actions-item a.psource-chat-admin-actions-item-delete span").text(psource_chat_localized['settings']["row_delete_text"]);
            } else {
                jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list #' + row_id_full).addClass(delete_row_class);
                jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list #' + row_id_full + " ul.psource-chat-row-footer li.psource-chat-admin-actions-item a.psource-chat-admin-actions-item-delete span").text(psource_chat_localized['settings']["row_undelete_text"]);
            }
        });
    },
    // Process Blocked IP Addresses from AJAX meta information
    chat_session_admin_process_blocked_ip_addresses: function (chat_id, blocked_ip_addresses) {
        var delete_row_class = 'psource-chat-row-ip-blocked';

        // First we undelete all rows not in the ip_addresses listing...
        jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list div.psource-chat-row.' + delete_row_class).each(function () {
            var row_id_full = jQuery(this).attr('id');
            //var row_id = row_id_full.replace('psource-chat-row-', '');

            if (!jQuery('#psource-chat-box-' + chat_id).hasClass('psource-chat-box-private')) {
                var row_ip_address = jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list #' + row_id_full + ' ul.psource-chat-row-footer li.psource-chat-admin-actions-item a.psource-chat-admin-actions-item-block-ip').attr('rel');
                if (row_ip_address != undefined) {
                    var item_found = jQuery.inArray(row_ip_address, blocked_ip_addresses);
                    if (item_found == -1) {
                        jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list #' + row_id_full).removeClass(delete_row_class);
                    } else {
                        jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list #' + row_id_full).addClass(delete_row_class);
                    }
                }
            }
        });

        //...then we hide all rows per the ip_addresses listing
        for (var ip_idx in blocked_ip_addresses) {
            if (blocked_ip_addresses.hasOwnProperty(ip_idx)) {
                var ip_address = blocked_ip_addresses[ip_idx];
                ip_address = ip_address.replace('.', '-');
                ip_address = ip_address.replace('.', '-');
                ip_address = ip_address.replace('.', '-');

                if (!jQuery('#psource-chat-box-' + chat_id).hasClass('psource-chat-box-private')) {
                    jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list div.psource-chat-row-ip-' + ip_address).each(function () {
                        var row_id_full = jQuery(this).attr('id');
                        jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list #' + row_id_full).addClass(delete_row_class);
                    });
                }
            }
        }

        // Last we check the current user's IP address against the ip_addresses listing. But also check if we are moderator
        if (!jQuery('#psource-chat-box-' + chat_id).hasClass('psource-chat-box-private')) {
            if ((psource_chat.settings['auth']['ip_address'] != undefined) && (psource_chat.settings['sessions'][chat_id]['moderator'] != 'yes')) {
                var session_ip_address = psource_chat.settings['auth']['ip_address'];
                var item_found = jQuery.inArray(session_ip_address, blocked_ip_addresses);
                if (item_found == -1) {
                    jQuery('#psource-chat-box-' + chat_id).removeClass('psource-chat-session-ip-blocked');
                } else {
                    jQuery('#psource-chat-box-' + chat_id).addClass('psource-chat-session-ip-blocked');
                }
            }
        }

        // now double check the sessions on this page. Loop then and check the 'ip_address' against the blocked ip_addresses
        for (var chat_id in psource_chat.settings['sessions']) {
            if (jQuery('div#psource-chat-box-' + chat_id).length) {

                if (!jQuery('#psource-chat-box-' + chat_id).hasClass('psource-chat-box-private')) {
                    var item_found = jQuery.inArray(psource_chat.settings['sessions'][chat_id]['ip_address'], blocked_ip_addresses);
                    if (item_found == -1) {
                        jQuery('#psource-chat-box-' + chat_id).removeClass('psource-chat-session-ip-blocked');
                    } else {
                        jQuery('#psource-chat-box-' + chat_id).addClass('psource-chat-session-ip-blocked');
                    }
                }
            }
        }

    },
    // Process Blocked IP Addresses from AJAX meta information
    chat_session_admin_process_blocked_users: function (chat_id, blocked_users) {
        var delete_row_class = 'psource-chat-row-user-blocked';

        // First we undelete all rows not in the blocked_users listing...
        jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list div.psource-chat-row.' + delete_row_class).each(function () {
            var row_id_full = jQuery(this).attr('id');

            var row_user = jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list #' + row_id_full + ' ul.psource-chat-row-footer li.psource-chat-admin-actions-item a.psource-chat-admin-actions-item-block-user').attr('rel');
            if (row_user != undefined) {
                var item_found = jQuery.inArray(row_user, blocked_users);
                if (item_found == -1) {
                    jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list #' + row_id_full).removeClass(delete_row_class);
                } else {
                    jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list #' + row_id_full).addClass(delete_row_class);
                }
            }
        });

        //...then we hide all rows per the blocked_users listing
        for (var user_idx in blocked_users) {
            if (blocked_users.hasOwnProperty(user_idx)) {
                var blocked_user = blocked_users[user_idx];
                blocked_user = blocked_user.replace('@', '-');
                blocked_user = blocked_user.replace('.', '-');

                jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list div.psource-chat-row-user-' + blocked_user).each(function () {
                    var row_id_full = jQuery(this).attr('id');
                    var something_else;
                    jQuery('#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list #' + row_id_full).addClass(delete_row_class);
                });
            }
        }

        // Last we check the current user's avatar against the ip_addresses listing. But also check if we are moderator
        if ((psource_chat.settings['auth']['email'] != undefined) && (psource_chat.settings['sessions'][chat_id]['moderator'] != 'yes')) {
            var session_email = psource_chat.settings['auth']['email'];
            var item_found = jQuery.inArray(session_email, blocked_users);
            if (item_found == -1) {
                jQuery('#psource-chat-box-' + chat_id).removeClass('psource-chat-session-user-blocked');
            } else {
                jQuery('#psource-chat-box-' + chat_id).addClass('psource-chat-session-user-blocked');
            }
        }

        // now double check the sessions on this page. Loop then and check the 'ip_address' against the blocked ip_addresses
//		for (var chat_id in psource_chat.settings['sessions']) {
//			if (jQuery('div#psource-chat-box-'+chat_id).length) {
//				var item_found = jQuery.inArray(psource_chat.settings['sessions'][chat_id]['ip_address'], ip_addresses);
//				if (item_found == -1) {				
//					jQuery('#psource-chat-box-'+chat_id).removeClass('psource-chat-session-ip-blocked');
//				} else {
//					jQuery('#psource-chat-box-'+chat_id).addClass('psource-chat-session-ip-blocked');
//				}
//			} 
//		}			

    },
    chat_session_process_status_change: function (chat_id, chat_session_status) {

        psource_chat.settings['sessions'][chat_id]['session_status'] = chat_session_status;
        if (chat_session_status == "open") {
            jQuery('div#psource-chat-box-' + chat_id).removeClass('psource-chat-session-closed');
            jQuery('div#psource-chat-box-' + chat_id).addClass('psource-chat-session-open');
        } else {
            jQuery('div#psource-chat-box-' + chat_id).removeClass('psource-chat-session-open');
            jQuery('div#psource-chat-box-' + chat_id).addClass('psource-chat-session-closed');
        }
    },
    chat_session_status_update: function (chat_id, chat_session_status) {

        // We are closing the chat session
        jQuery.ajax({
            type: "POST",
            url: psource_chat_localized['settings']["ajax_url"],
            cache: false,
            dataType: "json",
            data: {
                'action': 'chatProcess',
                'function': 'chat_session_moderate_status',
                'chat_session': psource_chat.chat_session_get_session_by_id(chat_id),
                'chat_session_status': chat_session_status,
                //'psource-chat-auth': psource_chat.settings['auth'],
                //'psource-chat-settings': psource_chat_localized['settings']
                //'psource-chat-settings-abspath': psource_chat_localized['settings']['ABSPATH'],
                'psource-chat-settings-request-uri': psource_chat_localized['settings']['REQUEST_URI']
            },
            success: function (reply_data) {
                if (reply_data != undefined) {
                    if (reply_data['errorStatus'] != undefined) {
                        if (reply_data['errorStatus'] == true) {
                            if (reply_data['errorText'] != undefined) {
                                //console.log("Chat: chat_session_moderate_user: reply [%s]", reply_data['errorText']);
                            }
                        }
                    }
                }
            }
        });
    },
    chat_session_click_avatar_row: function (chat_id) {
        // We unbind the click first to prevent previous events bindings.
        //jQuery('div#psource-chat-box-'+chat_id+' div.psource-chat-module-messages-list .psource-chat-row-avatar a.psource-chat-user-avatar').off('click'); // Works for jQuery 1.4.2

        // Then setup a new click binding.
        jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list div.psource-chat-row p.psource-chat-message a.psource-chat-user').off('click');
        jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list div.psource-chat-row p.psource-chat-message a.psource-chat-user').on( "click", function (event) {
            event.preventDefault();

            var name_title = jQuery(this).attr('title');
            if (name_title != '') {
                var textarea_el = jQuery('#psource-chat-box-' + chat_id + ' textarea.psource-chat-send');
                var existing_text = jQuery(textarea_el).val();
                if (psource_chat.isPlaceholderSupported == false) {
                    //console.log('isPlaceholderSupported is FALSE');

                    var placeholder_text = jQuery(textarea_el).attr('placeholder');
                    //console.log('placeholder_text['+placeholder_text+'] existing_text['+existing_text+']');
                    if (placeholder_text == existing_text) {
                        existing_text = '';
                    }
                }
                if (existing_text != '') existing_text = existing_text + ' ';
                existing_text = existing_text + name_title + ' ';
                //psource_chat.moveCaretToEnd(textarea_el);
                jQuery(textarea_el).val(existing_text).focus();
            }
            event.preventDefault();
            return false;
        });
    },
    chat_session_add_user_to_list: function (chat_id, user, user_type) {
        var chat_session = psource_chat.chat_session_get_session_by_id(chat_id);
        if (chat_session == undefined) return;

        if (chat_session['users_list_position'] == "none") return;

        // We don't show ourselves to ourselves
        if (user['auth_hash'] == psource_chat.settings['auth']['auth_hash']) return;

        var user_id = user.auth_hash;

        if (!jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-users-list ul.psource-chat-' + user_type).length) {
            jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-users-list').append('<ul class="psource-chat-' + user_type + '"></ul>');
        }

        if (!jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-users-list ul.psource-chat-' + user_type + ' li#psource-chat-user-' + user_id).length) {
            var user_html;
            if ((chat_session['users_list_show'] == "avatar") && (user['avatar'] != undefined)) {
                user_html = '<li id="psource-chat-user-' + user_id + '" class="psource-chat-user"><a title="@' + user['name'] + '" href="#">' + user['avatar'] + '</a></li>';
            } else {
                user_html = '<li id="psource-chat-user-' + user_id + '" class="psource-chat-user"><a title="@' + user['name'] + '" href="#">' + user['name'] + '</a></li>';
            }
            if (user_html != '') {
                jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-users-list ul.psource-chat-' + user_type).append(user_html);

                // Need to setup the click action...
                jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-users-list ul.psource-chat-' + user_type + ' li#psource-chat-user-' + user_id + ' a').on( "click", function (event) {
                    event.preventDefault();
                    var name_title = jQuery(this).attr('title');
                    if (name_title != '') {
                        //var existing_text = jQuery('#psource-chat-box-'+chat_id+' textarea.psource-chat-send').val();
                        //if (existing_text != '') existing_text = existing_text+' ';
                        //jQuery('#psource-chat-box-'+chat_id+' textarea.psource-chat-send').val(existing_text+jQuery(this).attr('title')).focus();
                        var textarea_el = jQuery('#psource-chat-box-' + chat_id + ' textarea.psource-chat-send');
                        var existing_text = jQuery(textarea_el).val();
                        if (psource_chat.isPlaceholderSupported == false) {
                            //console.log('isPlaceholderSupported is FALSE');

                            var placeholder_text = jQuery(textarea_el).attr('placeholder');
                            //console.log('placeholder_text['+placeholder_text+'] existing_text['+existing_text+']');
                            if (placeholder_text == existing_text) {
                                existing_text = '';
                            }
                        }
                        if (existing_text != '') existing_text = existing_text + ' ';
                        existing_text = existing_text + name_title + ' ';
                        //psource_chat.moveCaretToEnd(textarea_el);
                        jQuery(textarea_el).val(existing_text).focus();
                    }
                });
            }
        }
    },
    chat_session_remove_user_from_list: function (chat_id, user_id, user_type) {
        var chat_session = psource_chat.chat_session_get_session_by_id(chat_id);
        if (chat_session == undefined) return;

        if (chat_session['users_list_position'] == "none") return;

        jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-users-list ul.psource-chat-' + user_type + ' li#psource-chat-user-' + user_id).remove();
    },
    chat_session_private_update_title: function (chat_session) {

        // Update the private chat itle to show the users active in chat
        if (chat_session['session_type'] != "private") return;

        var user_text = '';
        var chat_id = chat_session['id'];

        // First from out internal session users list we check each type and user against the active_users received from the server. Remove items not found.
        if ((Object.keys(chat_session['users']).length > 0)) {
            for (var user_type in chat_session['users']) {
                if (!chat_session['users'].hasOwnProperty(user_type)) continue;

                if ((Object.keys(chat_session['users'][user_type]).length > 0)) {

                    for (var user_id in chat_session['users'][user_type]) {
                        if (!chat_session['users'][user_type].hasOwnProperty(user_id)) continue;

                        //var user = chat_session['users'][user_type][user_id];
                        if (chat_session['users'][user_type][user_id]['auth_hash'] != psource_chat.settings['auth']['auth_hash']) {
                            if (user_text != '') user_text = user_text + ',';
                            user_text = user_text + chat_session['users'][user_type][user_id]['name'];
                        }
                    }
                }
            }
        }
        if (user_text != '')
            jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-header span.psource-chat-private-attendees').html(user_text);
        else
            jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-header span.psource-chat-private-attendees').html('');

    },
    chat_session_process_users_list: function (chat_id, active_users) {
        var chat_session = psource_chat.chat_session_get_session_by_id(chat_id);
        if (chat_session == undefined) return;

        var chat_user_entered_messages = '';
        var chat_user_exited_messages = '';
        var chat_user_declined_messages = '';
        var chat_user_pending_messages = '';

        if (chat_session['users'] == undefined) {
            chat_session['users'] = {};
        }

        // First from out internal session users list we check each type and user against the active_users received from the server. Remove items not found.
        if ((Object.keys(chat_session['users']).length > 0)) {
            for (var user_type in chat_session['users']) {
                if (!chat_session['users'].hasOwnProperty(user_type)) continue;

                if ((Object.keys(chat_session['users'][user_type]).length > 0)) {

                    for (var user_id in chat_session['users'][user_type]) {
                        if (!chat_session['users'][user_type].hasOwnProperty(user_id)) continue;

                        if (active_users[user_type][user_id] == undefined) {
                            // Remove from our visible list of users/moderators
                            psource_chat.chat_session_remove_user_from_list(chat_id, user_id, user_type);

                            if (chat_session['users'][user_type][user_id] != undefined) {
                                // We don't show ourselves to ourselves
                                if (user_id != psource_chat.settings['auth']['auth_hash']) {
                                    if (chat_user_exited_messages != '') chat_user_exited_messages += ', ';
                                    chat_user_exited_messages += chat_session['users'][user_type][user_id]['name'];
                                }

                                delete chat_session['users'][user_type][user_id];
                            }
                        }
                    }
                }
            }
        }

        // Second loop over the active_users list and add any new users.
        for (var user_type in active_users) {
            if (!active_users.hasOwnProperty(user_type)) return;

            if (chat_session['users'][user_type] == undefined) {
                chat_session['users'][user_type] = {};
            }

            if ((Object.keys(active_users[user_type]).length > 0)) {

                //var active_users_list = active_users[user_type];
                for (var user_id in active_users[user_type]) {
                    if (!active_users[user_type].hasOwnProperty(user_id)) continue;

                    // Don't add the current user to our internal list
                    if (user_id != psource_chat.settings['auth']['auth_hash']) {
                        var connect_status = '';
                        if (chat_session['users'][user_type][user_id] == undefined) {
                            chat_session['users'][user_type][user_id] = active_users[user_type][user_id];
                            connect_status = active_users[user_type][user_id]['connect_status'];
                        } else {
                            if (chat_session['users'][user_type][user_id]['connect_status'] != active_users[user_type][user_id]['connect_status']) {
                                chat_session['users'][user_type][user_id] = active_users[user_type][user_id];
                                connect_status = active_users[user_type][user_id]['connect_status'];
                            } else if ((active_users[user_type][user_id]['connect_status'] == "pending") || (active_users[user_type][user_id]['connect_status'] == "declined")) {
                                connect_status = active_users[user_type][user_id]['connect_status'];
                            } else if (active_users[user_type][user_id]['connect_status'] == "exited") {
                                connect_status = active_users[user_type][user_id]['connect_status'];
                            }
                        }

                        //var user_inst = active_users[user_type][user_id];
                        if (connect_status == 'accepted') {
                            if (chat_user_entered_messages != '') chat_user_entered_messages += ', ';
                            chat_user_entered_messages += active_users[user_type][user_id]['name'];

                            //console.log('accepted: '+active_users[user_type][user_id]['name']);

                            // Add to our visible list of users/moderators
                            psource_chat.chat_session_add_user_to_list(chat_id, active_users[user_type][user_id], user_type);

                        } else if (connect_status == 'pending') {
                            if (chat_user_pending_messages != '') chat_user_pending_messages += ', ';
                            chat_user_pending_messages += active_users[user_type][user_id]['name'];
                            //console.log('pending: '+active_users[user_type][user_id]['name']);

                        } else if (connect_status == 'declined') {
                            if (chat_user_declined_messages != '') chat_user_declined_messages += ', ';
                            chat_user_declined_messages += active_users[user_type][user_id]['name'];

                            //console.log('declined:'+active_users[user_type][user_id]['name']);
                        } else if (connect_status == 'exited') {
                            if (chat_user_exited_messages != '') chat_user_exited_messages += ', ';
                            chat_user_exited_messages += active_users[user_type][user_id]['name'];

                            //console.log('existed:'+active_users[user_type][user_id]['name']);
                        }
                    }
                }
            } else {
                //if (chat_session['users'][user_type+'_active_count'] == undefined) {
                //	chat_session['users'][user_type+'_active_count'] = 0;
                //}
            }
        }

        if ((chat_session['users_enter_exit_status'] == 'enabled') && (psource_chat.settings['auth']['type'] != undefined)) {

            var users_enter_exit_delay = chat_session['users_enter_exit_delay'];
            users_enter_exit_delay = 1000 * users_enter_exit_delay;
            //console.log('users_enter_exit_delay['+users_enter_exit_delay+']');

            if (chat_user_entered_messages != '') {

                chat_user_entered_messages = psource_chat_localized['settings']['user_entered_chat'] + ': ' + chat_user_entered_messages;
                //console.log('chat_user_entered_messages['+chat_user_entered_messages+']');
                jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-session-user-status-message').empty();
                jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-session-user-status-message').html('<p>' + chat_user_entered_messages + '</p>');
                if (chat_session['session_type'] == 'private') {
                    //jQuery('div#psource-chat-box-'+chat_id+' div.psource-chat-session-user-status-message').show();
                    //psource_chat.chat_session_size_message_list();
                    jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-session-user-status-message').show().delay(users_enter_exit_delay).fadeOut(psource_chat.chat_session_size_message_list);

                } else {
                    jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-session-user-status-message').show().delay(users_enter_exit_delay).fadeOut(psource_chat.chat_session_size_message_list);
                }
            }

            if (chat_user_exited_messages != '') {
                chat_user_exited_messages = psource_chat_localized['settings']['user_exited_chat'] + ': ' + chat_user_exited_messages;

                //console.log('chat_user_exited_messages['+chat_user_exited_messages+']');
                jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-session-user-status-message').empty();
                jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-session-user-status-message').html('<p>' + chat_user_exited_messages + '</p>');
                //jQuery('div#psource-chat-box-'+chat_id+' div.psource-chat-session-user-status-message').show().delay(users_enter_exit_delay).fadeOut(psource_chat.chat_session_size_message_list);
                if (chat_session['session_type'] == 'private') {
                    jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-session-user-status-message').show();
                    psource_chat.chat_session_size_message_list();
                } else {
                    jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-session-user-status-message').show().delay(users_enter_exit_delay).fadeOut(psource_chat.chat_session_size_message_list);
                }

            }

            if (chat_session['session_type'] == 'private') {
                var chat_user_pending_declined_messages = '';
                if ((chat_user_pending_messages != '') || (chat_user_declined_messages != '')) {

                    if (chat_user_pending_messages != '') {
                        chat_user_pending_declined_messages += psource_chat_localized['settings']['user_pending_chat'] + ': ' + chat_user_pending_messages;
                    }

                    if (chat_user_declined_messages != '') {
                        chat_user_pending_declined_messages += psource_chat_localized['settings']['user_declined_chat'] + ': ' + chat_user_declined_messages;
                    }

                    if (chat_user_pending_declined_messages != '') {
                        //console.log('chat_user_pending_declined_messages['+chat_user_pending_declined_messages+']');
                        jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-session-user-status-message').empty();
                        jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-session-user-status-message').html('<p>' + chat_user_pending_declined_messages + '</p>');
                        //jQuery('div#psource-chat-box-'+chat_id+' div.psource-chat-session-user-status-message').show().delay(users_enter_exit_delay).fadeOut(psource_chat.chat_session_size_message_list);
                        jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-session-user-status-message').show();
                    }
                } else {
                    //jQuery('div#psource-chat-box-'+chat_id+' div.psource-chat-session-user-status-message').empty();
                    //jQuery('div#psource-chat-box-'+chat_id+' div.psource-chat-session-user-status-message').hide();
                }
            }
        }

        psource_chat.chat_session_set_session_by_id(chat_id, chat_session);
        psource_chat.chat_session_private_update_title(chat_session);
    },
    chat_process_private_invite: function (user_hash) {

        if (user_hash != '') {
            //console.log('user_hash=['+user_hash+']');

            jQuery.ajax({
                type: "POST",
                url: psource_chat_localized['settings']["ajax_url"],
                cache: false,
                dataType: "json",
                data: {
                    'action': 'chatProcess',
                    'function': 'chat_session_invite_private',
                    'psource-chat-to-user': user_hash,
                    //'psource-chat-settings': psource_chat_localized['settings']
                    //'psource-chat-settings-abspath': psource_chat_localized['settings']['ABSPATH'],
                    'psource-chat-settings-request-uri': psource_chat_localized['settings']['REQUEST_URI']
                },
                success: function (reply_data) {
                    if (reply_data != undefined) {
                        if (reply_data['errorStatus'] != undefined) {
                            if (reply_data['errorStatus'] == true) {
                                if (reply_data['errorText'] != undefined) {
                                    //console.log("Chat: chat_session_moderate_user: reply [%s]", reply_data['errorText']);
                                }
                            }
                        }
                    }
                }
            });
        }
    },

    chat_process_user_status_change: function (user_new_status) {
        if (user_new_status != '') {

            jQuery.ajax({
                type: "POST",
                url: psource_chat_localized['settings']["ajax_url"],
                cache: false,
                dataType: "json",
                data: {
                    'action': 'chatProcess',
                    'function': 'chat_update_user_status',
                    'psource-chat-user-status': user_new_status,
                    //'psource-chat-settings': psource_chat_localized['settings']
                    //'psource-chat-settings-abspath': psource_chat_localized['settings']['ABSPATH'],
                    'psource-chat-settings-request-uri': psource_chat_localized['settings']['REQUEST_URI']
                },
                success: function (reply_data) {
                    if (jQuery('#wp-toolbar li#wp-admin-bar-psource-chat-container div.ab-item span.psource-chat-user-status-current span.psource-chat-ab-icon').length) {
                        jQuery('#wp-toolbar li#wp-admin-bar-psource-chat-container div.ab-item span.psource-chat-user-status-current span.psource-chat-ab-icon').removeClass('psource-chat-ab-icon-' + psource_chat.settings['auth']['chat_status']);
                        jQuery('#wp-toolbar li#wp-admin-bar-psource-chat-container div.ab-item span.psource-chat-user-status-current span.psource-chat-ab-icon').addClass('psource-chat-ab-icon-' + user_new_status);

                        jQuery('#wp-toolbar li#wp-admin-bar-psource-chat-container div.ab-sub-wrapper li#wp-admin-bar-psource-chat-user-statuses div.ab-sub-wrapper li#wp-admin-bar-psource-chat-user-status-change-' + psource_chat.settings['auth']['chat_status']).removeClass('psource-chat-user-status-current');
                        jQuery('#wp-toolbar li#wp-admin-bar-psource-chat-container div.ab-sub-wrapper li#wp-admin-bar-psource-chat-user-statuses div.ab-sub-wrapper li#wp-admin-bar-psource-chat-user-status-change-' + user_new_status).addClass('psource-chat-user-status-current');

                        var current_label = jQuery('#wp-toolbar li#wp-admin-bar-psource-chat-container div.ab-sub-wrapper li#wp-admin-bar-psource-chat-user-statuses div.ab-sub-wrapper li.psource-chat-user-status-current span.psource-chat-ab-label').html();
                        if (current_label != '') {
                            jQuery('#wp-toolbar li#wp-admin-bar-psource-chat-container div.ab-sub-wrapper li#wp-admin-bar-psource-chat-user-statuses div.ab-item span.psource-chat-current-status-label').html(current_label);
                        }
                    }

                    if (jQuery('select.psource-chat-status-widget').length) {
                        jQuery('select.psource-chat-status-widget').val(user_new_status);

                        if (user_new_status == 'available') {
                            jQuery('select.psource-chat-status-widget').addClass('psource-chat-status-widget-available');
                        } else {
                            jQuery('select.psource-chat-status-widget').removeClass('psource-chat-status-widget-available');
                        }
                    }

                    // Update our internal settings...and update the cookie
                    psource_chat.settings['auth']['chat_status'] = user_new_status;
                    psource_chat.cookie('psource-chat-auth', JSON.stringify(psource_chat.settings['auth']), {
                        path: psource_chat_localized['settings']['cookiepath'],
                        domain: psource_chat_localized['settings']['cookie_domain']
                    });

                }
            });
        }

    },
    /*
     Play a sound when new messages are received. Note we don't care about which session has sound since that was determined in 'chat_session_setup_sound' function.
     The fact that pingSound object is not false tells os we have one session with sound enabled.
     */
    chat_session_sound_play: function () {

        //if (psource_chat.Sounds['ping']) {
        //    psource_chat.Sounds['ping'].play();
        //}
        if (psource_chat.Sounds['chime'] ) {
        	psource_chat.Sounds['chime'].play();
        }
    },

    /* We loop through the chat sessions. If we find just one that has sound enabled we setup the sound engine */
    chat_session_sound_setup: function (try_count) {

        //console.log('try_count=['+try_count+']');
        try {
            //psource_chat.Sounds['ping'] = new buzz.sound(psource_chat_localized['settings']['plugin_url'] + 'audio/ping', {
            //    formats: ["mp3", "wav", "ogg"]
            //});
            psource_chat.Sounds['chime'] = new buzz.sound(psource_chat_localized['settings']['plugin_url'] + 'audio/chime', {
                formats: [ "mp3","wav","ogg" ]
            });

        } catch (err) {
            if (psource_chat_localized['settings']['soundManager-js'] !== undefined) {
                jQuery.getScript(psource_chat_localized['settings']['soundManager-js'], function (data, textStatus, jqxhr) {
                    // We set this to prevent looping in the case where the sound lib could not be loaded. Some
                    // browsers (IE9) don't always play well with getScript()
                    if (try_count == 0) {
                        psource_chat.chat_session_sound_setup(try_count + 1);
                    }
                });
            }
        }
    },
    chat_session_box_actions: function (chat_id) {

        var chat_session = psource_chat.chat_session_get_session_by_id(chat_id);

//		var quicktags_settings = {
//	        id : 'psource-chat-send-'+chat_id,
//	        buttons: "strong,em,link,block"
//	    }
//	    QTags(quicktags_settings);
        //edToolbar('#'+chat_box_id+'.psource-chat-box div.psource-chat-module-message-area textarea.psource-chat-send').

        // Just in case we received wrong status from the server. Go through the Site floating windows and set open or closed.
        if (psource_chat.settings['user'][chat_id]['status_max_min'] == "max") {
            psource_chat.chat_session_site_max(chat_id);
        } else if (psource_chat.settings['user'][chat_id]['status_max_min'] == "min") {
            psource_chat.chat_session_site_min(chat_id);
        }

        //console.log('setup for settings gear icon events');
        // Handle the Settings 'gear' click events. We use clicks instead of hover.
        jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box div.psource-chat-module-header ul.psource-chat-actions-menu li.psource-chat-actions-settings ul.psource-chat-actions-settings-menu').css({display: "none"});
        jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box div.psource-chat-module-header ul.psource-chat-actions-menu li.psource-chat-actions-settings a.psource-chat-actions-settings-button').on( "click", function (event) {

            event.preventDefault();
            event.stopPropagation();

            //console.log('click event settings gear [%o]', event);
            var settingsMenu = jQuery(this).next();
            
            // Alle anderen offenen Menüs schließen
            jQuery('.psource-chat-actions-settings-menu').not(settingsMenu).slideUp(200);
            
            // Dieses Menü umschalten
            settingsMenu.slideToggle(400);
        });

        // Klick außerhalb des Menüs schließt es
        jQuery(document).on('click.chat-settings-' + chat_id, function(event) {
            var target = jQuery(event.target);
            var chatBox = jQuery('div#psource-chat-box-' + chat_id);
            
            // Wenn Klick nicht innerhalb des Settings-Menüs oder Settings-Buttons
            if (!target.closest('.psource-chat-actions-settings').length) {
                chatBox.find('.psource-chat-actions-settings-menu:visible').slideUp(200);
            }
        });

        // Verhindere dass Klicks innerhalb des Menüs es schließen (außer bei spezifischen Aktionen)
        jQuery('div#psource-chat-box-' + chat_id + ' .psource-chat-actions-settings-menu').on('click', function(event) {
            // Nur bei bestimmten Aktionen nicht stoppen
            if (!jQuery(event.target).closest('.psource-chat-action-sound, .psource-chat-action-logout, .psource-chat-action-login, .psource-chat-action-exit, .psource-chat-action-session-open, .psource-chat-action-session-closed').length) {
                event.stopPropagation();
            }
        });

        // Handle the Emoticons click/hover.
        jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box div.psource-chat-module-message-area ul.psource-chat-send-meta li.psource-chat-send-input-emoticons ul.psource-chat-emoticons-list').css({display: "none"});
        jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box div.psource-chat-module-message-area ul.psource-chat-send-meta li.psource-chat-send-input-emoticons').on( "click", function (event) {
            event.preventDefault();
            jQuery('ul.psource-chat-emoticons-list', this).slideToggle(400);
        });
        // Emoticons child item. When clicked will close the parent UL
        jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box div.psource-chat-module-message-area ul.psource-chat-send-meta li.psource-chat-send-input-emoticons ul.psource-chat-emoticons-list li').on( "click", function (event) {
            event.preventDefault();
            event.stopPropagation();
            jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box div.psource-chat-module-message-area ul.psource-chat-send-meta li.psource-chat-send-input-emoticons ul.psource-chat-emoticons-list').css({display: "none"});
        });

        jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box div.psource-chat-module-header ul.psource-chat-actions-menu li.psource-chat-actions-settings-pop-out a').on( "click", function (event) {
            event.preventDefault();
            var popup_href = jQuery(this).attr('href');

            var chat_session = psource_chat.chat_session_get_session_by_id(chat_id);

            var popup_width = 500;
            if (psource_chat_localized['settings']['screen_width'] < 500) {
                popup_width = psource_chat_localized['settings']['screen_width'];
            }

            var popup_height = 500;
            if (psource_chat_localized['settings']['screen_height'] < 500) {
                popup_height = psource_chat_localized['settings']['screen_height'];
            }


            var popup_chat = window.open(popup_href, chat_session['box_title'], "width=" + popup_width + ",height=" + popup_height + ",resizable=yes,scrollbars=yes");
            if ((popup_chat == null || typeof(popup_chat) == 'undefined')) {
                alert("Your browser has blocked a popup window\n\nWhen try to open the following url:\n" + popup_href);
                window.location.href = popup_href;

            } else {
                psource_chat.popouts[chat_id] = popup_chat;

                jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box').hide();
                jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box').addClass('psource-chat-box-pop-out');

                var pollTimer = window.setInterval(function () {
                    if (popup_chat.closed !== false) {
                        jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box').show();
                        jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box').removeClass('psource-chat-box-pop-out');
                        psource_chat.popouts[chat_id] = '';
                        window.clearInterval(pollTimer);
                    } else {
                        //console.log("Pop-up is open");
                    }
                }, 1000);
            }
        });

        if (jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box-private').hasClass('psource-chat-box-invite-pending')) {
            jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box-private.psource-chat-box-invite-pending div.psource-chat-module-invite-prompt button').on( "click", function (event) {
                event.preventDefault();
                if (jQuery(this).hasClass('psource-chat-invite-accept')) {
                    psource_chat.chat_session_update_user_invite_status(chat_id, 'accepted');
                    jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box-private').removeClass('psource-chat-box-invite-pending');
                    jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box-private').addClass('psource-chat-box-invite-accepted');
                } else if (jQuery(this).hasClass('psource-chat-invite-declined')) {
                    console.log('Declined');
                    psource_chat.chat_session_update_user_invite_status(chat_id, 'declined');
                    psource_chat.chat_session_remove_item(chat_id);
                }
            });
        } else {
            jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box-private div.psource-chat-module-invite-prompt').hide();
        }


        // Close the Pop-out window
        jQuery('body.psource-chat-pop-out div#psource-chat-box-' + chat_id + '.psource-chat-box div.psource-chat-module-header ul.psource-chat-actions-menu li.psource-chat-actions-settings-pop-in a').on( "click", function (event) {
            event.preventDefault();
            window.close();
        });

        jQuery(window).on( "resize", function() {
            if (!jQuery('body').hasClass('psource-chat-pop-out')) {
                return;
            }

            jQuery('body.psource-chat-pop-out div#psource-chat-box-'+chat_id+'.psource-chat-box').height(jQuery(window).height());
            jQuery('body.psource-chat-pop-out div#psource-chat-box-'+chat_id+'.psource-chat-box').width(jQuery(window).innerWidth());
        });
        
        psource_chat.chat_session_handle_send_button(chat_id);


        if (psource_chat.isPlaceholderSupported == false) {
            //console.log('placeholder text is NOT supported');
            var txtval = jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box div.psource-chat-module-message-area textarea.psource-chat-send').attr('placeholder');

            var currentValue = jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box div.psource-chat-module-message-area textarea.psource-chat-send').val();
            if (currentValue == '')
                jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box div.psource-chat-module-message-area textarea.psource-chat-send').val(txtval);

            jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box div.psource-chat-module-message-area textarea.psource-chat-send').focus(function () {
                if (jQuery(this).val() == txtval) {
                    jQuery(this).val('')
                }
            });
            jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box div.psource-chat-module-message-area textarea.psource-chat-send').blur(function () {
                if (jQuery(this).val() == "") {
                    jQuery(this).val(txtval);
                }
            });
        }

        // Handle Minimize/Maximize of the Site floating chat windows.
        //if ((chat_session['session_type'] == 'private') || (chat_session['session_type'] == 'site')) {
        if (jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box').hasClass('psource-chat-box-can-minmax')) {

            jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box .psource-chat-module-header ul.psource-chat-actions-menu li.psource-chat-min-max a').on( "click", function (event) {
                //console.log('click event min-max [%o]', event);
                event.preventDefault();
                psource_chat.chat_session_site_change_minmax(chat_id, event);
            });

            jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box .psource-chat-module-header div.psource-chat-module-header-title').on( "click", function (event) {
                //console.log('click event min-max [%o]', event);
                event.preventDefault();
                psource_chat.chat_session_site_change_minmax(chat_id, event);
            });
        }
        /*
         jQuery('div#psource-chat-box-'+chat_id+'.psource-chat-box .psource-chat-module-header ul.psource-chat-actions-menu li.psource-chat-min-max a').on( "click", function(event) {
         // FPM
         console.log('click event min-max [%o]', event);

         event.preventDefault();

         var chat_box_id 	= jQuery(this).parents('.psource-chat-box').attr('id');
         //var chat_id 		= chat_box_id.replace('psource-chat-box-', '');

         //var chat_box_id = 'div#psource-chat-box-'+chat_id+'..psource-chat-box';
         var chat_site_display_status = '';

         if (jQuery('#'+chat_box_id).hasClass('psource-chat-box-min')) {
         psource_chat.chat_session_site_max(chat_id);
         chat_site_display_status = "max";
         jQuery('#'+chat_box_id+' ul.psource-chat-actions-menu li.psource-chat-actions-settings').show();

         } else if (jQuery('#'+chat_box_id).hasClass('psource-chat-box-max')) {
         psource_chat.chat_session_site_min(chat_id);
         chat_site_display_status = "min";
         jQuery('#'+chat_box_id+' ul.psource-chat-actions-menu li.psource-chat-actions-settings').hide();
         }
         psource_chat.settings['user'][chat_id]['status_max_min'] = chat_site_display_status;
         psource_chat.cookie('psource-chat-user', JSON.stringify(psource_chat.settings['user']), { path: psource_chat_localized['settings']['cookiepath'], domain: psource_chat_localized['settings']['cookie_domain']});
         });
         */

        // Event handler when the login event is clicked (updated for new toggle system)
        jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box').on('click', 'ul.psource-chat-actions-settings-menu li.psource-chat-action-menu-item-auth-toggle a.psource-chat-action-login', function (event) {
            event.preventDefault();
            event.stopPropagation();

            //Hide settings menu
            jQuery(this).closest('.psource-chat-actions-settings-menu').slideUp(200);

            var chat_box_id = jQuery(this).parents('.psource-chat-box').attr('id');

            jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-messages-list').hide();
            jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-users-list').hide();
            jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-login').show();
            jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-login-prompt').hide();

            jQuery('div#psource-chat-box-' + chat_id + ' div.psource-chat-module-login input.psource-chat-login-name').focus();

            return false;
        });

        // Event handler when logout is clicked (updated for new toggle system)
        jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box').on('click', 'ul.psource-chat-actions-settings-menu li.psource-chat-action-menu-item-auth-toggle a.psource-chat-action-logout', function (event) {
            event.preventDefault();
            event.stopPropagation();

            if (psource_chat.settings['auth']['type'] == "wordpress") {
                // There is no logout for wordpress users.
                return;

            } else if (psource_chat.settings['auth']['type'] == "public_user") {
                psource_chat.settings['auth'] = {};

            }

            // Update our cookie
            psource_chat.cookie('psource-chat-auth', JSON.stringify(psource_chat.settings['auth']), {
                path: psource_chat_localized['settings']['cookiepath'],
                domain: psource_chat_localized['settings']['cookie_domain']
            });

            //Hide settings menu
            jQuery(this).closest('.psource-chat-actions-settings-menu').slideUp(200);

            // Update the auth toggle display immediately
            var authMenuItem = jQuery(this).closest('li.psource-chat-action-menu-item-auth-toggle');
            var currentIcon = jQuery(this).find('span[class*="psource-chat-icon-"]');
            var linkElement = jQuery(this);
            
            // Switch to login state
            authMenuItem.removeClass('logged-in').addClass('logged-out');
            currentIcon.removeClass('psource-chat-icon-logout').addClass('psource-chat-icon-login');
            linkElement.removeClass('psource-chat-action-logout').addClass('psource-chat-action-login');
            linkElement.attr('title', 'Im Chat anmelden');
            linkElement.contents().filter(function() {
                return this.nodeType === 3; // Text nodes
            }).first().replaceWith('Anmelden');

            // Reload the page or update the chat session
            psource_chat.chat_session_site_reload(chat_id);

            return false;
        });

        jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box ul.psource-chat-actions-settings-menu li.psource-chat-action-menu-item-exit a.psource-chat-action-exit').on( "click", function (event) {
            event.preventDefault();

            //var chat_id = jQuery(this).parents('.psource-chat-box').attr('id').replace('psource-chat-box-', '');
            psource_chat.chat_session_remove_item(chat_id);
        });

        // From the login form if the Cancel button is clicked. Cancel and return to default view.
        jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box button.psource-chat-login-cancel').on( "click", function () {
            var chat_box = jQuery(this).parents('.psource-chat-box');
            jQuery('div.psource-chat-module-login', chat_box).hide();
            psource_chat.chat_session_set_auth_view();
            return false;
        });

        // Event handler for Sound Off/On click
        jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box a.psource-chat-action-sound').on( "click", psource_chat.chat_session_site_change_sound);

        // From the login form if the Submit button is clicked. Validate the info and take the needed action.
        jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box button.psource-chat-login-submit').on( "click", function () {
            var chat_box_id = jQuery(this).parents('.psource-chat-box').attr('id');
            //var chat_id 		= chat_box_id.replace('psource-chat-box-', '');

            var form_name = jQuery('#' + chat_box_id + ' input.psource-chat-login-name').val();
            var form_email = jQuery('#' + chat_box_id + ' input.psource-chat-login-email').val();

            var user_info = {};
            user_info['type'] = 'public_user';
            user_info['id'] = '';
            user_info['name'] = form_name;
            user_info['profile_link'] = '';
            user_info['avatar'] = form_email;
            user_info['email'] = form_email;

//			jQuery('#'+chat_box_id+' .psource-chat-login-error').html('');
//			jQuery('#'+chat_box_id+' .psource-chat-login-error').hide();
            var replyText = psource_chat.chat_session_user_login(user_info, chat_box_id);
//			if ((replyText != '') && (replyText != undefined)) {
//				jQuery('#'+chat_box_id+' .psource-chat-login-error').html(replyText);
//				jQuery('#'+chat_box_id+' .psource-chat-login-error').show();
//			}
        });

        //jQuery('div.psource-chat-box div.psource-chat-module-message-area ul.psource-chat-send-meta ul.psource-chat-emoticons-list img').off('click');
        jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box div.psource-chat-module-message-area ul.psource-chat-send-meta ul.psource-chat-emoticons-list li').on( "click", function (event) {
            var chat_box_id = jQuery(this).parents('.psource-chat-box').attr('id');
            //var chat_id 		= chat_box_id.replace('psource-chat-box-', '');
            //jQuery('#'+chat_box_id+'.psource-chat-box div.psource-chat-module-message-area ul.psource-chat-send-meta ul.psource-chat-emoticons-list').css('display', 'none');
            var current_msg_box = jQuery('#' + chat_box_id + '.psource-chat-box div.psource-chat-module-message-area textarea.psource-chat-send');

            var current_msg_box_val = current_msg_box.val();

            //Check if undefined, it causes issue in safari
            current_msg_box_val = 'undefined' != typeof current_msg_box_val ? current_msg_box_val : '';

            //Update the value in input field
            var emoji = jQuery(this).find('img').attr('alt');

            //Fix for Safari
            if( 'undefined' == typeof emoji ) {
                emoji = jQuery(this).html();
            }
            current_msg_box.val(current_msg_box_val + ' ' + emoji );

            jQuery('#' + chat_box_id + '.psource-chat-box div.psource-chat-module-message-area textarea.psource-chat-send').focus();
            return false;

        });

        // ADMIN: Session Status Toggle (neue Version)
        jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box').on('click', 'ul.psource-chat-actions-settings-menu li.psource-chat-action-menu-item-session-toggle a.psource-chat-action-session-open', function (event) {
            event.preventDefault();
            event.stopPropagation();
            
            var chat_session_status = 'open';
            psource_chat.chat_session_status_update(chat_id, chat_session_status);
            
            // Menü-Inhalt sofort aktualisieren
            var sessionMenuItem = jQuery(this).closest('li.psource-chat-action-menu-item-session-toggle');
            var currentIcon = jQuery(this).find('span[class*="psource-chat-icon-lock"]');
            var linkElement = jQuery(this);
            
            // Zu "schließen" Option wechseln
            sessionMenuItem.removeClass('session-closed').addClass('session-open');
            currentIcon.removeClass('psource-chat-icon-lock-closed').addClass('psource-chat-icon-lock-open');
            linkElement.removeClass('psource-chat-action-session-open').addClass('psource-chat-action-session-closed');
            linkElement.attr('title', 'Chat für andere Benutzer schließen');
            linkElement.contents().filter(function() {
                return this.nodeType === 3; // Text nodes
            }).first().replaceWith('Chat schließen');
            
            // Chat-Box Klassen aktualisieren
            jQuery('#psource-chat-box-' + chat_id).removeClass('psource-chat-session-closed').addClass('psource-chat-session-open');

            return false;
        });

        jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box').on('click', 'ul.psource-chat-actions-settings-menu li.psource-chat-action-menu-item-session-toggle a.psource-chat-action-session-closed', function (event) {
            event.preventDefault();
            event.stopPropagation();
            
            var chat_session_status = 'closed';
            psource_chat.chat_session_status_update(chat_id, chat_session_status);
            
            // Menü-Inhalt sofort aktualisieren
            var sessionMenuItem = jQuery(this).closest('li.psource-chat-action-menu-item-session-toggle');
            var currentIcon = jQuery(this).find('span[class*="psource-chat-icon-lock"]');
            var linkElement = jQuery(this);
            
            // Zu "öffnen" Option wechseln
            sessionMenuItem.removeClass('session-open').addClass('session-closed');
            currentIcon.removeClass('psource-chat-icon-lock-open').addClass('psource-chat-icon-lock-closed');
            linkElement.removeClass('psource-chat-action-session-closed').addClass('psource-chat-action-session-open');
            linkElement.attr('title', 'Chat für andere Benutzer öffnen');
            linkElement.contents().filter(function() {
                return this.nodeType === 3; // Text nodes
            }).first().replaceWith('Chat öffnen');
            
            // Chat-Box Klassen aktualisieren
            jQuery('#psource-chat-box-' + chat_id).removeClass('psource-chat-session-open').addClass('psource-chat-session-closed');

            return false;
        });

        // ADMIN: Clear menu options
        jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box div.psource-chat-module-header ul.psource-chat-actions-settings-menu li.psource-chat-action-menu-item-session-clear a.psource-chat-action-session-clear').on( "click", function () {
            var chat_box_id = jQuery(this).parents('.psource-chat-box').attr('id');

            var chat_session = psource_chat.chat_session_get_session_by_id(chat_id);
            if (chat_session != undefined) {

                var sessions_data = {};
                //sessions_data[chat_id] = chat_session;
                sessions_data[chat_id] = {};
                sessions_data[chat_id]['id'] = chat_session['id'];
                sessions_data[chat_id]['blog_id'] = chat_session['blog_id'];
                sessions_data[chat_id]['session_type'] = chat_session['session_type'];

                jQuery.ajax({
                    type: "POST",
                    url: psource_chat_localized['settings']["ajax_url"],
                    cache: false,
                    dataType: "json",
                    data: {
                        'action': 'chatProcess',
                        'function': 'chat_messages_clear',
                        'psource-chat-sessions': sessions_data,
                        //'psource-chat-auth': psource_chat.settings['auth'],
                        //'psource-chat-settings': psource_chat_localized['settings']
                        //'psource-chat-settings-abspath': psource_chat_localized['settings']['ABSPATH'],
                        'psource-chat-settings-request-uri': psource_chat_localized['settings']['REQUEST_URI']
                    },
                    success: function (reply_data) {
                        if (reply_data != undefined) {
                            if (reply_data['errorStatus'] != undefined) {
                                if (reply_data['errorStatus'] == true) {
                                    if (reply_data['errorText'] != undefined) {
                                        //console.log("Chat: chat_session_moderate_user: reply [%s]", reply_data['errorText']);
                                    }
                                }
                            }
                        }
                    }
                });
            }
            return false;
        });


        // ADMIN: Archive menu option
        //jQuery('div.psource-chat-box div.psource-chat-module-header ul.psource-chat-actions-settings-menu li.psource-chat-action-menu-item-session-archive a.psource-chat-action-session-archive').off('click');
        jQuery('div#psource-chat-box-' + chat_id + '.psource-chat-box div.psource-chat-module-header ul.psource-chat-actions-settings-menu li.psource-chat-action-menu-item-session-archive a.psource-chat-action-session-archive').on( "click", function () {
            var chat_box_id = jQuery(this).parents('.psource-chat-box').attr('id');
            //var chat_id 		= chat_box_id.replace('psource-chat-box-', '');

            var chat_session = psource_chat.chat_session_get_session_by_id(chat_id);
            if (chat_session != undefined) {

                var sessions_data = {};
                //sessions_data[chat_id] = chat_session;
                sessions_data[chat_id] = {};
                sessions_data[chat_id]['id'] = chat_session['id'];
                sessions_data[chat_id]['blog_id'] = chat_session['blog_id'];
                sessions_data[chat_id]['session_type'] = chat_session['session_type'];

                jQuery.ajax({
                    type: "POST",
                    url: psource_chat_localized['settings']["ajax_url"],
                    cache: false,
                    dataType: "json",
                    data: {
                        'action': 'chatProcess',
                        'function': 'chat_messages_archive',
                        'psource-chat-sessions': sessions_data,
                        //'psource-chat-auth': psource_chat.settings['auth'],
                        //'psource-chat-settings': psource_chat_localized['settings']
                        //'psource-chat-settings-abspath': psource_chat_localized['settings']['ABSPATH'],
                        'psource-chat-settings-request-uri': psource_chat_localized['settings']['REQUEST_URI']
                    },
                    success: function (reply_data) {
                        if (reply_data != undefined) {
                            if (reply_data['errorStatus'] != undefined) {
                                if (reply_data['errorStatus'] == true) {
                                    if (reply_data['errorText'] != undefined) {
                                        //console.log("Chat: chat_session_moderate_user: reply [%s]", reply_data['errorText']);
                                    }
                                }
                            }
                        }
                    }
                });
 }
            return false;
        });
    },
    chat_session_site_change_minmax: function (chat_id, event) {

        var chat_site_display_status = '';

        if (jQuery('div#psource-chat-box-' + chat_id).hasClass('psource-chat-box-min')) {
            psource_chat.chat_session_site_max(chat_id);
            chat_site_display_status = "max";
            jQuery('div#psource-chat-box-' + chat_id + ' ul.psource-chat-actions-menu li.psource-chat-actions-settings').show();

        } else if (jQuery('div#psource-chat-box-' + chat_id).hasClass('psource-chat-box-max')) {
            psource_chat.chat_session_site_min(chat_id);
            chat_site_display_status = "min";
            jQuery('div#psource-chat-box-' + chat_id + ' ul.psource-chat-actions-menu li.psource-chat-actions-settings').hide();
        }
        psource_chat.settings['user'][chat_id]['status_max_min'] = chat_site_display_status;
        psource_chat.cookie('psource-chat-user', JSON.stringify(psource_chat.settings['user']), {
            path: psource_chat_localized['settings']['cookiepath'],
            domain: psource_chat_localized['settings']['cookie_domain']
        });

    },
    chat_session_site_change_sound: function (event) {
        event.preventDefault();
        event.stopPropagation(); // Verhindert Event Bubbling, das das Menü schließt

        var chat_box = jQuery(this).parents('.psource-chat-box');
        var chat_id = jQuery(chat_box).attr('id').replace('psource-chat-box-', '');

        if (jQuery(chat_box).hasClass('psource-chat-box-sound-on')) {
            jQuery(chat_box).removeClass('psource-chat-box-sound-on');
            jQuery(chat_box).addClass('psource-chat-box-sound-off');

            psource_chat.settings['user'][chat_id]['sound_on_off'] = "off";

        } else if (jQuery(chat_box).hasClass('psource-chat-box-sound-off')) {
            jQuery(chat_box).removeClass('psource-chat-box-sound-off');
            jQuery(chat_box).addClass('psource-chat-box-sound-on');

            psource_chat.settings['user'][chat_id]['sound_on_off'] = "on";
        }
        psource_chat.cookie('psource-chat-user', JSON.stringify(psource_chat.settings['user']), {
            path: psource_chat_localized['settings']['cookiepath'],
            domain: psource_chat_localized['settings']['cookie_domain']
        });

        // Menü-Inhalt sofort aktualisieren
        var soundMenuItem = jQuery(this).closest('li.psource-chat-action-menu-item-sound-toggle');
        var currentIcon = jQuery(this).find('span[class*="psource-chat-icon-sound"]');
        var linkText = jQuery(this);
        
        if (jQuery(chat_box).hasClass('psource-chat-box-sound-on')) {
            // Sound ist jetzt AN - zeige "ausschalten" Option
            soundMenuItem.removeClass('sound-inactive').addClass('sound-active');
            currentIcon.removeClass('psource-chat-icon-sound-off').addClass('psource-chat-icon-sound-on');
            linkText.attr('title', psource_chat_localized['sound_off_title'] || 'Chat-Sound ausschalten');
            linkText.contents().filter(function() {
                return this.nodeType === 3; // Text nodes
            }).first().replaceWith('Sound ausschalten');
        } else {
            // Sound ist jetzt AUS - zeige "einschalten" Option  
            soundMenuItem.removeClass('sound-active').addClass('sound-inactive');
            currentIcon.removeClass('psource-chat-icon-sound-on').addClass('psource-chat-icon-sound-off');
            linkText.attr('title', psource_chat_localized['sound_on_title'] || 'Chat-Sound einschalten');
            linkText.contents().filter(function() {
                return this.nodeType === 3; // Text nodes
            }).first().replaceWith('Sound einschalten');
        }

        // Menü offen halten - Return false verhindert weitere Event-Handler
        return false;

    },
    chat_session_site_max: function (chat_id) {
        if (jQuery('body').hasClass('psource-chat-pop-out'))
            return;

        var chat_session = psource_chat.chat_session_get_session_by_id(chat_id);

        if (chat_session == undefined) return;

        var chat_box = jQuery('#psource-chat-box-' + chat_id);
        jQuery(chat_box).removeClass('psource-chat-box-min');
        jQuery(chat_box).addClass('psource-chat-box-max');

        if (chat_session['box_height'] != undefined) {
            jQuery(chat_box).height(chat_session['box_height']);
        }

        jQuery('.psource-chat-module', chat_box).each(function () {
            if (jQuery(this).hasClass('psource-chat-module-min-hidden')) {
                jQuery(this).removeClass('psource-chat-module-min-hidden');
                jQuery(this).show();
            }
        });

        // Swap our corner images - Maximiert: zeige Minimize-Button (rotes -), verstecke Maximize-Button
        jQuery('.psource-chat-module-header span.psource-chat-min', chat_box).show();
        jQuery('.psource-chat-module-header span.psource-chat-max', chat_box).hide();

        jQuery('.psource-chat-module-header span.psource-chat-title-count', chat_box).html('');
        jQuery('.psource-chat-module-header span.psource-chat-title-count', chat_box).attr('last_row_count', '0');
        jQuery('.psource-chat-module-header span.psource-chat-title-count', chat_box).hide();
        //jQuery('.psource-chat-module-header span.psource-chat-title-count', chat_box).attr('last_row_index_viewed', '');
        //jQuery('.psource-chat-module-header span.psource-chat-title-count', chat_box).attr('last_row_id_viewed', '');

        // Let the chat_session_set_auth function figure out what modules to show.
        psource_chat.chat_session_set_auth_view();
    },
    chat_session_site_min: function (chat_id) {

        var chat_session = psource_chat.chat_session_get_session_by_id(chat_id);
        if (chat_session == undefined) return;

        var chat_box = jQuery('#psource-chat-box-' + chat_id);
        jQuery(chat_box).removeClass('psource-chat-box-max');
        jQuery(chat_box).addClass('psource-chat-box-min');

        var chat_box_height_old = jQuery(chat_box).outerHeight();
        var chat_box_height_new = 0;

        jQuery('.psource-chat-module', chat_box).each(function () {
            if (jQuery(this).hasClass('psource-chat-module-header')) {
                chat_box_height_new += jQuery(this).outerHeight(true);
            } else {
                if (jQuery(this).is(':visible')) {
                    jQuery(this).addClass('psource-chat-module-min-hidden');
                    jQuery(this).hide();
                }
            }
        });

        if (chat_box_height_new > 0) {
            jQuery(chat_box).height(chat_box_height_new);

            if (chat_session['box_position_v'] == "bottom") {
                var border_width = chat_session['box_border_width'] ? chat_session['box_border_width'] : 0;
                border_width = parseInt(border_width);
                border_width = border_width ? border_width : 0;
//				jQuery(chat_box).css('bottom', chat_box_height_new-(chat_box_height_old-border_width-border_width));
            }
        }

        // Swap our corner images - Minimiert: verstecke Minimize-Button, zeige Maximize-Button (grünes +)
        jQuery('.psource-chat-module-header span.psource-chat-min', chat_box).hide();
        jQuery('.psource-chat-module-header span.psource-chat-max', chat_box).show();
        jQuery('.psource-chat-module-header span.psource-chat-title-count', chat_box).show();

//		var last_row_id_viewed 		= '';
//		var last_row_index_viewed 	= '';
//		if (jQuery('div.psource-chat-module-messages-list div.psource-chat-row', chat_box).length) {
//			if (chat_session['box_input_position'] == "top") {
//				var last_row = jQuery('div.psource-chat-module-messages-list div.psource-chat-row', chat_box).first();
//			} else {
//				var last_row = jQuery('div.psource-chat-module-messages-list div.psource-chat-row', chat_box).last();
//			}
//			last_row_index_viewed = last_row.index();
//			last_row_id_viewed = jQuery(last_row).attr('id').replace('psource-chat-row-', '');
//		} 
//		jQuery('.psource-chat-module-header span.psource-chat-title-count', chat_box).attr('last_row_index_viewed', last_row_index_viewed);
//		jQuery('.psource-chat-module-header span.psource-chat-title-count', chat_box).attr('last_row_id_viewed', last_row_id_viewed);

        return false;
    },

    chat_session_user_login: function (user_info, chat_box_id) {

        //console.log('chat_session_user_login user_info %o', user_info);

        jQuery.ajax({
            type: "POST",
            url: psource_chat_localized['settings']["ajax_url"],
            dataType: "json",
            cache: false,
            data: {
                'function': 'chat_user_login',
                'action': 'chatProcess',
                'user_info': user_info,
                //'psource-chat-settings': psource_chat_localized['settings']
                //'psource-chat-settings-abspath': psource_chat_localized['settings']['ABSPATH'],
                'psource-chat-settings-request-uri': psource_chat_localized['settings']['REQUEST_URI']
            },
            success: function (reply_data) {
                if (reply_data != undefined) {
                    if (reply_data['errorStatus'] != undefined) {
                        if (reply_data['errorStatus'] == false) {
                            if (reply_data['user_info'] != undefined) {
                                psource_chat.settings['auth'] = reply_data['user_info'];

                                psource_chat.cookie('psource-chat-auth', JSON.stringify(psource_chat.settings['auth']), {
                                    path: psource_chat_localized['settings']['cookiepath'],
                                    domain: psource_chat_localized['settings']['cookie_domain']
                                });
                                //var tmp_cookie_json = psource_chat.cookie('psource-chat-auth');
                                //var tmp_cookie_obj	= JSON.parse(tmp_cookie_json);

                                if (chat_box_id != '') {
                                    jQuery('#' + chat_box_id + ' .psource-chat-login-error').html('');
                                    jQuery('#' + chat_box_id + ' .psource-chat-login-error').hide();
                                }
                                psource_chat.chat_session_set_auth_view();

                                return false;
                            }

                        } else if (reply_data['errorStatus'] == true) {
                            if ((reply_data['errorText'] != undefined) && (reply_data['errorText'] != '')) {
                                //return reply_data['errorText'];
                                if (chat_box_id != '') {
                                    jQuery('#' + chat_box_id + ' .psource-chat-login-error').html(reply_data['errorText']);
                                    jQuery('#' + chat_box_id + ' .psource-chat-login-error').show();
                                }

                            }
                        }
                    }
                }
            }
        });
    },

    wp_admin_bar_setup: function () {

        if (jQuery('#wpadminbar #wp-toolbar li#wp-admin-bar-psource-chat-container').length) {

            // Hide the current status
            if (psource_chat.settings['auth']['chat_status'] != undefined) {
                jQuery('#wp-toolbar li#wp-admin-bar-psource-chat-container li#wp-admin-bar-psource-chat-user-status-change-' + psource_chat.settings['auth']['chat_status']).addClass('psource-chat-user-status-current');
            }

            if (psource_chat.bound != true) {
                psource_chat.bound = true;

                jQuery('#wp-toolbar li#wp-admin-bar-psource-chat-container li#wp-admin-bar-psource-chat-user-statuses ul#wp-admin-bar-psource-chat-user-statuses-default li a.ab-item').on( "click", function (event) {
                    event.preventDefault();
                    var user_new_status = jQuery(this).parents('li').attr('id').replace('wp-admin-bar-psource-chat-user-status-change-', '');
                    //var user_new_status = jQuery(this).attr('rel');

                    if (psource_chat.settings['auth']['chat_status'] != user_new_status) {
                        psource_chat.chat_process_user_status_change(user_new_status);
                    }
                    return false;
                });

                jQuery('#wp-toolbar li#wp-admin-bar-psource-chat-container li#wp-admin-bar-psource-chat-user-friends ul.ab-submenu li a.psource-chat-user-invite').on( "click", function (event) {
                    event.preventDefault();
                    var user_hash = jQuery(this).attr('rel');
                    user_hash = user_hash ? user_hash : '';
                    if (user_hash != '') {
                        psource_chat.chat_process_private_invite(user_hash);
                    }
                    return false;
                });
            } else {
                //console.log('already click events');
            }
        }
    },

    chat_privite_invite_click: function () {
        // Check for WPMU DEV Friends list page
        if (jQuery('div.friends-wrap').length) {
            jQuery('div.friends-wrap a.psource-chat-user-invite').on( "click", function (event) {
                event.preventDefault();
                var user_hash = jQuery(this).attr('rel');
                user_hash = user_hash ? user_hash : '';
                if (user_hash != '') {
                    psource_chat.chat_process_private_invite(user_hash);
                }
            });
        }

        // Check for WP User List
        if (jQuery('body.users-php table.wp-list-table td.column-psource-chat-status').length) {
            jQuery('body.users-php table.wp-list-table td.column-psource-chat-status a.psource-chat-user-invite').on( "click", function (event) {
                event.preventDefault();
                var user_hash = jQuery(this).attr('rel');
                user_hash = user_hash ? user_hash : '';
                if (user_hash != '') {
                    psource_chat.chat_process_private_invite(user_hash);
                }
            });
        }

        // Check for BP User List
        jQuery(document).on("click", "body.buddypress ul#members-list div.psource-chat-now-button a.psource-chat-user-invite", function (event) {
            event.preventDefault();
            var user_hash = jQuery(this).attr('rel');
            user_hash = user_hash ? user_hash : '';
            if (user_hash != '') {
                psource_chat.chat_process_private_invite(user_hash);
            }
        });

        jQuery(document).on("click", "body.buddypress.bp-user div.psource-chat-now-button a.psource-chat-user-invite", function (event) {
            event.preventDefault();
            var user_hash = jQuery(this).attr('rel');
            user_hash = user_hash ? user_hash : '';
            if (user_hash != '') {
                psource_chat.chat_process_private_invite(user_hash);
            }
        });

        if (jQuery('.psource-chat-friends-widget').length) {
            jQuery('.psource-chat-friends-widget a.psource-chat-user-invite').on( "click", function (event) {
                event.preventDefault();
                var user_hash = jQuery(this).attr('rel');
                user_hash = user_hash ? user_hash : '';
                if (user_hash != '') {
                    psource_chat.chat_process_private_invite(user_hash);
                }
            });
        }
        $public_chat_invite = jQuery('.psource-chat-admin-actions-item.psource-chat-user-invite');
        $single_member_page_invite = jQuery('.psource-chat-button.psource-chat-user-invite');

        //Single members page, If user clicks on private chat button
        if ($public_chat_invite.length) {
            psource_chat.handle_private_chat_click($public_chat_invite);
        }
        if ($single_member_page_invite.length) {
            psource_chat.handle_private_chat_click($public_chat_invite);
        }

        if (jQuery('select.psource-chat-status-widget').length) {
            jQuery('select.psource-chat-status-widget').on('change', function (event) {
                event.preventDefault();

                var user_new_status = jQuery(this).val();

                if (psource_chat.settings['auth']['chat_status'] != user_new_status) {

                    psource_chat.chat_process_user_status_change(user_new_status);
                }
            });
        }
    },
    microtime: function (get_as_float) {
        var unixtime_ms = new Date().getTime();
        var sec = parseInt(unixtime_ms / 1000);
        return get_as_float ? (unixtime_ms / 1000) : (unixtime_ms - (sec * 1000)) / 1000 + ' ' + sec;
    },
    cookie: function (name, value, options) {
        if (typeof value != 'undefined') { // name and value given, set cookie
            options = options || {};
            if (value === null) {
                value = '';
                options = $.extend({}, options); // clone object since it's unexpected behavior if the expired property were changed
                options.expires = -1;
            }
            var expires = '';
            if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
                var date;
                if (typeof options.expires == 'number') {
                    date = new Date();
                    date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
                } else {
                    date = options.expires;
                }
                expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
            }
            // NOTE Needed to parenthesize options.path and options.domain
            // in the following expressions, otherwise they evaluate to undefined
            // in the packed version for some reason...
            var path = options.path ? '; path=' + (options.path) : '';
            var domain = options.domain ? '; domain=' + (options.domain) : '';
            var secure = options.secure ? '; secure' : '';
            document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
        } else { // only name given, get cookie
            var cookieValue = null;
            if (document.cookie && document.cookie != '') {
                var cookies = document.cookie.split(';');
                for (var i = 0; i < cookies.length; i++) {
                    var cookie = cookies[i].trim();
                    // Does this cookie string begin with the name we want?
                    if (cookie.substring(0, name.length + 1) == (name + '=')) {
                        cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                        if( cookieValue.length > 0 ) {
                                break;
                        }
                    }
                }
            }
            return cookieValue;
        }
    },
    moveCaretToEnd: function (el) {
        if (typeof el.selectionStart == "number") {
            el.selectionStart = el.selectionEnd = el.value.length;
        } else if (typeof el.createTextRange != "undefined") {
            el.focus();
            var range = el.createTextRange();
            range.collapse(false);
            range.trigger("select");
        }
    },
    handle_private_chat_click: function ($selector) {
        jQuery($selector).on( "click", function (event) {
            event.preventDefault();
            var user_hash = jQuery(this).attr('rel');
            user_hash = user_hash ? user_hash : '';
            if (user_hash != '') {
                psource_chat.chat_process_private_invite(user_hash);
            }
        });
    },
    
    /**
     * Initialize avatar fallback system
     * Handle broken avatar images by replacing them with placeholder
     */
    init_avatar_fallbacks: function() {
        var placeholder_url = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMzIiIGN5PSIzMiIgcj0iMzIiIGZpbGw9IiNlMGUwZTAiLz4KPGNpcmNsZSBjeD0iMzIiIGN5PSIyNCIgcj0iMTAiIGZpbGw9IiM5OTk5OTkiLz4KPHBhdGggZD0iTTMyIDQwQzI0IDQwIDE4IDQ2IDE4IDU0VjYwSDQ2VjU0QzQ2IDQ2IDQwIDQwIDMyIDQwWiIgZmlsbD0iIzk5OTk5OSIvPgo8L3N2Zz4K';
        
        // Function to replace broken avatar with placeholder
        function replaceAvatarWithPlaceholder(img) {
            if (img.src !== placeholder_url) {
                console.log('Avatar 404 detected, replacing with placeholder:', img.src);
                img.src = placeholder_url;
                img.classList.add('avatar-placeholder');
                img.onerror = null; // Prevent infinite loop
            }
        }
        
        // Handle existing avatars on page
        jQuery('.avatar, img[class*="avatar"]').each(function() {
            var img = this;
            
            // If image is already loaded and broken
            if (img.complete && img.naturalWidth === 0) {
                replaceAvatarWithPlaceholder(img);
            } else {
                // Set error handler for future loads
                img.onerror = function() {
                    replaceAvatarWithPlaceholder(this);
                };
            }
        });
        
        // Handle dynamically added avatars (via chat updates)
        jQuery(document).on('load error', '.avatar, img[class*="avatar"]', function(e) {
            if (e.type === 'error') {
                replaceAvatarWithPlaceholder(this);
            }
        });
    },

    /**
     * Refresh avatar fallbacks after content updates
     * Call this after adding new chat messages with avatars
     */
    refresh_avatar_fallbacks: function() {
        psource_chat.init_avatar_fallbacks();
    }
});
jQuery(document).ready(function () {
    psource_chat.init();

    // Lightbox für Chat-Bilder
    jQuery(document).on('click', '.psource-chat-image-preview img', function(e) {
        e.preventDefault();
        var src = jQuery(this).attr('src');
        if (!src) return;
        // Lightbox-Overlay erzeugen
        var overlay = jQuery('<div class="psource-chat-lightbox-overlay"></div>');
        var img = jQuery('<img class="psource-chat-lightbox-img" src="'+src+'" alt="Bild" />');
        overlay.append(img);
        jQuery('body').append(overlay);
        overlay.on('click', function() { overlay.remove(); });
    });

    // Handle the Settings 'gear' children menu items clicks. Once a user click a child menu option we close the parent.
    jQuery('body').on('click', 'ul.psource-chat-actions-settings-menu li a', function (event) {
        jQuery(this).parents().eq(1).css({display: "none"});
    });

    //Handle Disable-Enable auto scroll
    jQuery('body').on('click', '.manage-auto-scroll a', function (e) {
        e.preventDefault();
        $manage_scroll = jQuery(this).parent();
        //Check Current status
        $auto_scroll = $manage_scroll.attr('data-auto_scroll');

        //Already Enabled, Disable it
        if ($auto_scroll == 'on') {
            $manage_scroll.attr('data-auto_scroll', 'off');
            jQuery(this).html(psource_chat_localized.auto_scroll.enable);
        } else {
            //Enable it
            $manage_scroll.attr('data-auto_scroll', 'on');
            jQuery(this).html(psource_chat_localized.auto_scroll.disable);
        }
    });

    // ============================================================================
    // MODERN EMOJI PICKER FUNCTIONALITY
    // ============================================================================
    
    // Emoji picker functionality
    function initEmojiPicker() {
        // Toggle emoji picker
        jQuery(document).off('click.emoji', '.psource-chat-emoticons-menu').on('click.emoji', '.psource-chat-emoticons-menu', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var picker = jQuery(this).siblings('.psource-chat-emoji-picker');
            var wasVisible = picker.hasClass('active');
            
            // Close all other emoji pickers
            jQuery('.psource-chat-emoji-picker').removeClass('active');
            
            // Toggle current picker
            if (!wasVisible) {
                picker.addClass('active');
            }
        });
        
        // Category tab switching
        jQuery(document).off('click.emoji', '.psource-chat-emoji-category-tab').on('click.emoji', '.psource-chat-emoji-category-tab', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var category = jQuery(this).data('category');
            var picker = jQuery(this).closest('.psource-chat-emoji-picker');
            
            // Update active tab
            picker.find('.psource-chat-emoji-category-tab').removeClass('active');
            jQuery(this).addClass('active');
            
            // Update active grid
            picker.find('.psource-chat-emoji-grid').removeClass('active');
            picker.find('.psource-chat-emoji-grid[data-category="' + category + '"]').addClass('active');
        });
        
        // Emoji selection
        jQuery(document).off('click.emoji', '.psource-chat-emoji-item').on('click.emoji', '.psource-chat-emoji-item', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var emoji = jQuery(this).data('emoji');
            console.log('Emoji clicked:', emoji); // Debug
            
            // Find the textarea within the same chat module
            var chatModule = jQuery(this).closest('.psource-chat-module-message-area');
            var textarea = chatModule.find('textarea.psource-chat-send');
            
            console.log('Chat module found:', chatModule.length); // Debug
            console.log('Textarea found:', textarea.length); // Debug
            
            if (textarea.length) {
                // Insert emoji at cursor position
                var currentText = textarea.val();
                var cursorPos = textarea[0].selectionStart || currentText.length;
                var newText = currentText.slice(0, cursorPos) + emoji + currentText.slice(cursorPos);
                
                textarea.val(newText);
                
                // Set cursor position after emoji
                var newCursorPos = cursorPos + emoji.length;
                if (textarea[0].setSelectionRange) {
                    textarea[0].setSelectionRange(newCursorPos, newCursorPos);
                }
                
                // Focus textarea
                textarea.focus();
                
                console.log('Emoji inserted successfully'); // Debug
            } else {
                console.log('Textarea not found - trying global fallback'); // Debug
                // Global fallback - find any visible chat textarea
                var fallbackTextarea = jQuery('textarea.psource-chat-send:visible').first();
                
                if (fallbackTextarea.length) {
                    var currentText = fallbackTextarea.val();
                    var cursorPos = fallbackTextarea[0].selectionStart || currentText.length;
                    var newText = currentText.slice(0, cursorPos) + emoji + currentText.slice(cursorPos);
                    
                    fallbackTextarea.val(newText);
                    
                    var newCursorPos = cursorPos + emoji.length;
                    if (fallbackTextarea[0].setSelectionRange) {
                        fallbackTextarea[0].setSelectionRange(newCursorPos, newCursorPos);
                    }
                    
                    fallbackTextarea.focus();
                    console.log('Emoji inserted via global fallback'); // Debug
                } else {
                    console.log('No textarea found at all'); // Debug
                }
            }
            
            // Close emoji picker
            jQuery(this).closest('.psource-chat-emoji-picker').removeClass('active');
        });
        
        // Close emoji picker when clicking outside
        jQuery(document).off('click.emoji-outside').on('click.emoji-outside', function(e) {
            if (!jQuery(e.target).closest('.psource-chat-send-input-emoticons').length) {
                jQuery('.psource-chat-emoji-picker').removeClass('active');
            }
        });
        
        // Prevent emoji picker from closing when clicking inside
        jQuery(document).off('click.emoji', '.psource-chat-emoji-picker').on('click.emoji', '.psource-chat-emoji-picker', function(e) {
            e.stopPropagation();
        });
    }
    
    // Initialize emoji picker
    initEmojiPicker();
    
    // Re-initialize on content updates
    jQuery(document).on('psource_chat_content_updated', function() {
        initEmojiPicker();
    });
});