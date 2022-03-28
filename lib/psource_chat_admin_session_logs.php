<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if ( ! class_exists( 'PSOURCEChat_Session_Logs_Table' ) ) {
	class PSOURCEChat_Session_Logs_Table extends WP_List_Table {

		var $filters = array();
		var $item;

		function __construct() {
			global $status, $page;

			//Set parent defaults
			parent::__construct( array(
					'singular' => 'Log',     //Singularname der aufgelisteten Datensätze
					'plural'   => 'Logs',    //Plural Name der aufgelisteten Datensätze
					'ajax'     => true        //Unterstützt diese Tabelle Ajax?
				)
			);

			$this->check_table_filters();
		}

		function get_table_classes() {
			return array( 'widefat', 'fixed', 'psource-chat-session-logs-table' );
		}

		function get_bulk_actions() {
			global $psource_chat;

			$actions = array(
				'hide'   => __( 'Ausblenden', 'psource-chat' ),
				'unhide' => __( 'Einblenden', 'psource-chat' ),
				'delete' => __( 'Löschen', 'psource-chat' )
			);

			return $actions;
		}

		function check_table_filters() {
			global $blog_id;

			if ( ( isset( $_GET['status'] ) ) && ( ! empty( $_GET['status'] ) ) ) {
				$this->filters['status'] = strtolower( esc_attr( $_GET['status'] ) );
				if ( $this->filters['status'] == "open" ) {
					$this->filters['status'] = 'no';
				} else if ( $this->filters['status'] == "archived" ) {
					$this->filters['status'] = 'yes';
				} else if ( $this->filters['status'] == "hidden" ) {
					$this->filters['status'] = 'hidden';
				} else {
					$this->filters['status'] = '';
				}

			} else {
				$this->filters['status'] = '';
			}

			if ( ( isset( $_GET['chat_id'] ) ) && ( ! empty( $_GET['chat_id'] ) ) ) {
				$this->filters['chat_id'] = esc_attr( $_GET['chat_id'] );
			} else {
				$this->filters['chat_id'] = '';
			}

			if ( ( isset( $_GET['session_type'] ) ) && ( ! empty( $_GET['session_type'] ) ) ) {
				$this->filters['session_type'] = esc_attr( $_GET['session_type'] );
			} else {
				$this->filters['session_type'] = '';
			}

			if ( is_multisite() ) {
				if ( strncasecmp( $this->filters['session_type'], 'private', strlen( 'private' ) ) === 0 ) {
					$this->filters['blog_id'] = 0;
				} else if ( is_network_admin() ) {
					$this->filters['blog_id'] = 0;
				} else {
					$this->filters['blog_id'] = $blog_id;
				}
			} else {
				$this->filters['blog_id'] = $blog_id;
			}

			if ( ( isset( $_GET['start'] ) ) && ( ! empty( $_GET['start'] ) ) ) {
				$this->filters['start'] = esc_attr( $_GET['start'] );
			} else {
				$this->filters['start'] = '';
			}

			if ( ( isset( $_GET['end'] ) ) && ( ! empty( $_GET['end'] ) ) ) {
				$this->filters['end'] = esc_attr( $_GET['end'] );
			} else {
				$this->filters['end'] = '';
			}

			// Check to ensure the start date if BEFORE the end date.
			if ( ( ! empty( $this->filters['start'] ) ) && ( ! empty( $this->filters['end'] ) ) ) {
				if ( $this->filters['start'] > $this->filters['end'] ) {
					$_time                  = $this->filters['end'];
					$this->filters['end']   = $this->filters['start'];
					$this->filters['start'] = $_time;
				}
			}

			if ( ( isset( $_GET['s'] ) ) && ( ! empty( $_GET['s'] ) ) ) {
				$this->filters['search'] = esc_attr( $_GET['s'] );
			} else {
				$this->filters['search'] = '';
			}

			return $this->filters;
		}

		function extra_tablenav( $which ) {

			if ( $which == "top" ) {
				$HAS_FILTERS = false;

				?>
				<div class="alignleft actions"><?php

				$this->show_filters_chat_status();
				$this->show_filters_chat_id();
				$this->show_filters_session_type();
				$this->show_filters_dates();

				?></div><?php
				?>
				<input id="post-query-submit" class="button-secondary" type="submit" value="Filter" name="chat-filter"><?php
			}
		}

		function show_filters_chat_status() {
			global $psource_chat;
			?>
			<select name="status" id="status">
				<option value=""><?php _e( 'Alle anzeigen', 'psource-chat' ); ?></option>
				<option <?php if ( 'no' == $this->filters['status'] ) {
					echo ' selected="selected" ';
				} ?>
					value="open"><?php _e( 'Offen', 'psource-chat' ); ?></option>
				<option <?php if ( 'yes' == $this->filters['status'] ) {
					echo ' selected="selected" ';
				} ?>
					value="archived"><?php _e( 'Archiviert', 'psource-chat' ); ?></option>
				<option <?php if ( 'hidden' == $this->filters['status'] ) {
					echo ' selected="selected" ';
				} ?>
					value="hidden"><?php _e( 'Versteckt', 'psource-chat' ); ?></option>
			</select>
		<?php
		}

		function show_filters_chat_id() {

			global $wpdb, $psource_chat;

			$sql_str = "SELECT chat_id, box_title FROM " . PSOURCE_Chat::tablename( 'log' ) . " WHERE 1=1 AND `blog_id`= " . $this->filters['blog_id'] . " AND `session_type` != 'private' GROUP BY `chat_id` ORDER BY `chat_id`";

			$results = $wpdb->get_results( $sql_str );

			if ( ( $results ) && ( count( $results ) ) ) {
				$chats = array();
				foreach ( $results as $result ) {
					if ( ! empty( $result->box_title ) ) {
						$chats[ $result->chat_id ] = $result->box_title;
					} else {
						$chats[ $result->chat_id ] = $result->chat_id;
					}
				}
			}
			?>
			<select
				name="chat_id" id="chat_id">
				<option value=""><?php _e( 'Alle Chats anzeigen', 'psource-chat' ); ?></option>
				<?php
				if ( ( $results ) && ( count( $results ) ) ) {
					foreach ( $results as $result ) {
						?>
						<option <?php if ( $result->chat_id == $this->filters['chat_id'] ) {
							echo ' selected="selected" ';
						} ?>
						value="<?php echo $result->chat_id ?>"><?php echo $result->chat_id; ?></option><?php
					}
				}
				?>
			</select>
		<?php
		}

		function show_filters_session_type() {
			global $wpdb, $psource_chat, $blog_id;

			if ( ( is_multisite() ) && ( is_network_admin() ) ) {
				$_blog_id = 0;
			} else {
				$_blog_id = $blog_id;
			}

			$sql_str = "SELECT session_type FROM " . PSOURCE_Chat::tablename( 'log' ) . " WHERE 1=1 AND (blog_id=" . $_blog_id . " OR blog_id=0) GROUP BY session_type ORDER BY session_type";

			$results = $wpdb->get_results( $sql_str );

			?>
			<select
				name="session_type" id="session_type">
				<option value=""><?php _e( 'Alle Typen anzeigen', 'psource-chat' ); ?></option>
				<?php
				if ( ( $results ) && ( count( $results ) ) ) {
					foreach ( $results as $result ) {
						if ( ! empty( $result->session_type ) ) {
							?>
							<option <?php if ( $result->session_type == $this->filters['session_type'] ) {
								echo ' selected="selected" ';
							} ?>
							value="<?php echo $result->session_type ?>"><?php echo $result->session_type; ?></option><?php
						}
					}
				}
				?>
			</select>
		<?php
		}

		function show_filters_dates() {
			global $psource_chat;

			?>
			<input type="text" placeholder="<?php _e( 'Anfangsdatum', 'psource-chat' ); ?>" name="start" size="15"
				class="chat-start" value="<?php echo $this->filters['start']; ?>" id="start"/>
			<input type="text" placeholder="<?php _e( 'Enddatum', 'psource-chat' ); ?>" name="end" size="15" class="chat-end" value="<?php echo $this->filters['end']; ?>" id="end"/>

			<script>
				jQuery(document).ready(function () {
					jQuery('input#start').datepicker({dateFormat: 'yy-mm-dd'});
					jQuery('input#end').datepicker({dateFormat: 'yy-mm-dd'});
				});
			</script>
		<?php
		}


		function single_row_columns( $item ) {
			list( $columns, $hidden ) = $this->get_column_info();

			if ( $item->deleted == "yes" ) {
				$chat_log_class = " chat-log-deleted";
			} else {
				$chat_log_class = "";
			}

			foreach ( $columns as $column_name => $column_display_name ) {
				$class = "class='$column_name column-$column_name $chat_log_class'";

				$style = '';
				if ( in_array( $column_name, $hidden ) ) {
					$style = ' style="display:none;"';
				}

				$attributes = $class . $style;

				if ( 'cb' == $column_name ) {
					echo '<th scope="row" class="check-column ' . $chat_log_class . '">';
					echo $this->column_cb( $item );
					echo '</th>';
				} elseif ( method_exists( $this, 'column_' . $column_name ) ) {
					echo "<td $attributes>";
					echo call_user_func( array( &$this, 'column_' . $column_name ), $item );
					echo "</td>";
				} else {
					echo "<td $attributes>";
					echo $this->column_default( $item, $column_name );
					echo "</td>";
				}
			}
		}

		function column_default( $item, $column_name ) {
			echo "&nbsp;";
		}

		function column_cb( $item ) {
			//$chat_details_value = strtotime($item->start) .'-'. $item->chat_id;
			?><input type="checkbox" name="chat-logs-bulk[]" value="<?php echo $item->id; ?>" /><?php
		}

		function get_columns() {
			global $psource_chat;

			$columns = array();

			$columns['cb'] = '<input type="checkbox" />';

			$columns['time'] = __( 'Zeit', 'psource-chat' );

//			if (is_multisite())
//				$columns['blog']		=	__('Blog', 			'psource-chat');

			$columns['title']          = __( 'Sitzung', 'psource-chat' );
			$columns['status']         = __( 'Status', 'psource-chat' );
			$columns['type']           = __( 'Typ', 'psource-chat' );
			$columns['moderators']     = __( 'Moderatoren', 'psource-chat' );
			$columns['users']          = __( 'Benutzer', 'psource-chat' );
			$columns['messages_count'] = __( 'Nachrichten', 'psource-chat' );

			return $columns;
		}

		function column_title( $item ) {
			global $psource_chat;

			if ( $item->session_type == "private" ) {
				echo __( 'Privat', 'psource-chat' );
			} else if ( ( isset( $item->box_title ) ) && ( ! empty( $item->box_title ) ) ) {
				echo strip_tags( $item->box_title ) . ' (' . $item->chat_id . ')';
			} else {
				echo $item->chat_id;
			}
		}

		function column_status( $item ) {
			global $psource_chat;

			//status
			if ( $item->archived == "yes" ) {
				if ( $item->deleted == "yes" ) {
					_e( 'Versteckt', 'psource-chat' );
				} else {
					_e( 'Archiviert', 'psource-chat' );
				}
			} else if ( $item->archived == "no" ) {
				_e( 'Offen', 'psource-chat' );
			}
		}

		function column_type( $item ) {
			global $psource_chat;
			if ( ! empty( $item->session_type ) ) {
				echo $item->session_type;
			} else {
				_e( 'Chat', 'psource-chat' );
			}
		}

		function column_time( $item ) {
			global $psource_chat;

//			if (isset($psource_chat->_chat_options_defaults[$item->session_type]['row_date_format'])) {
//				$row_date_format = $psource_chat->_chat_options_defaults[$item->session_type]['row_date_format'];
//			} else {
			$row_date_format = get_option( 'date_format' );
//			}

//			if (isset($psource_chat->_chat_options_defaults[$item->session_type]['row_time_format'])) {
//				$row_time_format = $psource_chat->_chat_options_defaults[$item->session_type]['row_time_format'];
//			} else {
			$row_time_format = get_option( 'time_format' );
//			}

			$date_str = '';
			if ( isset( $item->start ) ) {

				$date_str .= date_i18n( $row_date_format, strtotime( $item->start ) + get_option( 'gmt_offset' ) * 3600, false );
				$date_str .= " ";
				$date_str .= date_i18n( $row_time_format, strtotime( $item->start ) + get_option( 'gmt_offset' ) * 3600, false );
			}

			if ( $item->archived == 'yes' ) {
				if ( ! empty( $date_str ) ) {
					$date_str .= '<br />';
				}
				$date_str .= date_i18n( $row_date_format, strtotime( $item->end ) + get_option( 'gmt_offset' ) * 3600, false );
				$date_str .= " ";
				$date_str .= date_i18n( $row_time_format, strtotime( $item->end ) + get_option( 'gmt_offset' ) * 3600, false );
			}

			$chat_href = esc_url_raw( remove_query_arg( array( '_wpnonce', 'maction', 'message' ) ) );
			$chat_href = add_query_arg( 'chat_id', $item->chat_id, $chat_href );
			$chat_href = add_query_arg( 'lid', $item->id, $chat_href );

			$chat_details_href = add_query_arg( 'laction', 'details', $chat_href );
			//$chat_details_href 	= remove_query_arg(array('_wpnonce'), $chat_details_href);

			$details_array            = array();
			$details_array['details'] = array(
				'label' => __( 'Details', 'psource-chat' ),
				'title' => __( 'Details zu dieser Chat-Sitzung anzeigen', 'psource-chat' ),
				'href'  => $chat_details_href
			);

			$chat_href_tmp = remove_query_arg( array( 's' ), $chat_href );
			$chat_href_tmp = add_query_arg( '_wpnonce', wp_create_nonce( 'chat-log-item' ), $chat_href_tmp );

			//echo "archives[". $item->archived ."]<br />";
			if ( $item->archived == 'yes' ) {

				if ( $item->session_type != "private" ) {
					if ( $item->deleted == 'no' ) {
						$chat_hide_href        = add_query_arg( 'laction', 'hide', $chat_href_tmp );
						$details_array['hide'] = array(
							'label' => __( 'ausblenden', 'psource-chat' ),
							'title' => __( 'Blende die gesamte Chat-Sitzung aus, um die öffentliche Ansicht zu blockieren', 'psource-chat' ),
							'href'  => $chat_hide_href
						);
					} else if ( $item->deleted == 'yes' ) {
						$chat_unhide_href        = add_query_arg( 'laction', 'unhide', $chat_href_tmp );
						$details_array['unhide'] = array(
							'label' => __( 'einblenden', 'psource-chat' ),
							'title' => __( 'Blende die gesamte Chat-Sitzung ein, um die öffentliche Ansicht zu ermöglichen', 'psource-chat' ),
							'href'  => $chat_unhide_href
						);
					}
				}
				$chat_delete_href        = add_query_arg( 'laction', 'delete', $chat_href_tmp );
				$details_array['delete'] = array(
					'label' => __( 'löschen', 'psource-chat' ),
					'title' => __( 'Lösche die gesamte Chat-Sitzung dauerhaft', 'psource-chat' ),
					'href'  => $chat_delete_href
				);
			} else {
				$chat_href_tmp         = remove_query_arg( array(
					'_wpnonce',
					'paged',
					'status',
					'start',
					'end'
				), $chat_href_tmp );
				$chat_show_href        = add_query_arg( 'laction', 'show', $chat_href_tmp );
				$chat_show_href        = add_query_arg( 'session_type', $item->session_type, $chat_show_href );
				$details_array['show'] = array(
					'label' => __( 'Chat anzeigen', 'psource-chat' ),
					'title' => __( 'Live-Chat-Sitzung anzeigen', 'psource-chat' ),
					'href'  => $chat_show_href
				);

			}
			?>
			<a href="<?php echo $chat_details_href; ?>"><?php echo $date_str; ?></a>
			<div class="row-actions" style="margin:0; padding:0;">
				<?php
				$details_link_str = '';
				foreach ( $details_array as $key => $link ) {
					if ( ! empty( $details_link_str ) ) {
						$details_link_str .= ' | ';
					}

					$details_link_str .= '<span class="' . $key . '"><a href="' . $link['href'] . '" title="' . $link['title'] . '">' . $link['label'] . '</a></span>';
				}
				echo $details_link_str;
				?>
			</div>
		<?php
		}

		function column_users( $item ) {
			global $psource_chat, $wpdb;

			$names_str = '';

			if ( $item->archived == "no" ) {
				$chat_session = array(
					'id'                          => $item->chat_id,
					'blog_id'                     => $item->blog_id,
					'session_type'                => $item->session_type,
					'users_list_threshold_delete' => $psource_chat->_chat_options_defaults['page']['users_list_threshold_delete']
				);
				$active_users = $psource_chat->chat_session_get_active_users( $chat_session );
				if ( ( isset( $active_users['users'] ) ) && ( is_array( ( $active_users['users'] ) ) ) && ( count( ( $active_users['users'] ) ) ) ) {
					foreach ( ( $active_users['users'] ) as $user ) {
						if ( strlen( $names_str ) ) {
							$names_str .= ", ";
						}
						$names_str .= '<span class="psource-chat-user psource-chat-user-active">' . $user['name'] . '</span>';
					}
				}
			}


			$sql_str     = $wpdb->prepare( "SELECT DISTINCT name, auth_hash, moderator FROM " . PSOURCE_Chat::tablename( 'message' ) . " WHERE blog_id = %d AND chat_id = %s AND archived=%s  AND log_id=%d AND moderator=%s ORDER by name ASC", $item->blog_id, $item->chat_id, $item->archived, $item->id, 'no' );
			$names_users = $wpdb->get_results( $sql_str );
			foreach ( $names_users as $name ) {
				if ( ! isset( $active_users['users'][ $name->auth_hash ] ) ) {
					if ( strlen( $names_str ) ) {
						$names_str .= ", ";
					}
					$names_str .= '<span class="psource-chat-user">' . $name->name . '</strong>';
				}
			}

			echo $names_str;
		}

		function column_moderators( $item ) {
			global $psource_chat, $wpdb;

			$names_str = '';

			if ( $item->archived == "no" ) {
				$chat_session = array(
					'id'                          => $item->chat_id,
					'blog_id'                     => $item->blog_id,
					'session_type'                => $item->session_type,
					'users_list_threshold_delete' => $psource_chat->_chat_options_defaults['page']['users_list_threshold_delete']
				);
				$active_users = $psource_chat->chat_session_get_active_users( $chat_session );
			}

			// First show moderators
			if ( ( isset( $active_users['moderators'] ) ) && ( is_array( ( $active_users['moderators'] ) ) ) && ( count( ( $active_users['moderators'] ) ) ) ) {
				foreach ( ( $active_users['moderators'] ) as $user ) {
					if ( strlen( $names_str ) ) {
						$names_str .= ", ";
					}
					$names_str .= '<span class="psource-chat-moderator psource-chat-moderator-active">' . $user['name'] . '</span>';
				}
			}

			global $wpdb;
			$sql_str          = $wpdb->prepare( "SELECT DISTINCT name, auth_hash, moderator FROM " . PSOURCE_Chat::tablename( 'message' ) . " WHERE blog_id = %d AND chat_id = %s AND log_id=%d AND moderator=%s ORDER by name ASC", $item->blog_id, $item->chat_id, $item->id, 'yes' );
			$names_moderators = $wpdb->get_results( $sql_str );
			if ( ( $names_moderators ) && ( ! empty( $names_moderators ) ) ) {
				foreach ( $names_moderators as $name ) {
					if ( ! isset( $active_users['moderators'][ $name->auth_hash ] ) ) {
						if ( strlen( $names_str ) ) {
							$names_str .= ", ";
						}
						$names_str .= '<span class="psource-chat-moderator">' . $name->name . '</span>';
					}
				}
			}

			echo $names_str;
		}

		function column_messages_count( $item ) {
			unset( $item->messages_count );
			if ( ( isset( $item->messages_count ) ) && ( ! empty( $item->messages_count ) ) ) {
				echo $item->messages_count;
			} else {
				global $wpdb;
				if ( $item->archived == "no" ) {
					$sql_str = $wpdb->prepare( "SELECT count(*) count FROM " . PSOURCE_Chat::tablename( 'message' ) . " WHERE blog_id = %d AND chat_id = %s AND archived=%s AND log_id=%d", $item->blog_id, $item->chat_id, $item->archived, $item->id );
				} else {
					$sql_str = $wpdb->prepare( "SELECT count(*) count FROM " . PSOURCE_Chat::tablename( 'message' ) . " WHERE blog_id = %d AND chat_id = %s AND archived=%s AND log_id=%d", $item->blog_id, $item->chat_id, 'yes', $item->id );
				}
				$counts = $wpdb->get_row( $sql_str );
				if ( ( $counts ) && ( count( $counts ) ) ) {
					echo $counts->count;
				}

				if ( $item->archived == "no" ) {
					$sql_str = $wpdb->prepare( "SELECT timestamp FROM " . PSOURCE_Chat::tablename( 'message' ) . " WHERE blog_id = %d AND chat_id = %s AND archived=%s AND log_id=%d ORDER BY timestamp DESC LIMIT 1", $item->blog_id, $item->chat_id, $item->archived, $item->id );
					//echo "sql_str=[". $sql_str ."]<br />";
					$timestamp = $wpdb->get_var( $sql_str );
					if ( ! empty( $timestamp ) ) {
						$last_ts    = strtotime( $timestamp );
						$current_ts = current_time( 'timestamp' );

						// The WordPress function 'human_time_diff' doesn't handle values less than a minute. So we need to.
						$diff = (int) abs( intval( $last_ts ) - intval( $current_ts ) );
						if ( $diff < MINUTE_IN_SECONDS ) {
							echo sprintf( _n( ' (%s Sekunde zuvor)', ' (%s Sekunden zuvor)', $diff ), $diff );
						} else {
							echo ' (' . human_time_diff( intval( $last_ts ), intval( $current_ts ) ) . ' ago)';
						}
					}
				}

			}
		}

		function column_blog( $item ) {

			if ( isset( $item->blog_id ) ) {
				$blog = get_blog_details( $item->blog_id );
				if ( $blog ) {
					echo $blog->blogname . "<br /> (" . $blog->domain . ")";
				} else {
					echo "&nbsp;";
				}
			} else {
				echo "&nbsp;";
			}
		}

//		function column_chat_id($item) {
//			echo $item->chat_id;
//		}


		function human_time_diff( $from, $to = '' ) {
			if ( empty( $to ) ) {
				$to = time();
			}

			$diff = (int) abs( $to - $from );

			if ( $diff < MINUTE_IN_SECONDS ) {
				$since = sprintf( _n( '%s Sekunde', '%s Sekunden', $diff ), $diff );
			} else if ( $diff < HOUR_IN_SECONDS ) {
				$mins = round( $diff / MINUTE_IN_SECONDS );
				if ( $mins <= 1 ) {
					$mins = 1;
				}
				/* translators: min=minute */
				$since = sprintf( _n( '%s Minute', '%s Minuten', $mins ), $mins );
			} elseif ( $diff < DAY_IN_SECONDS && $diff >= HOUR_IN_SECONDS ) {
				$hours = round( $diff / HOUR_IN_SECONDS );
				if ( $hours <= 1 ) {
					$hours = 1;
				}
				$since = sprintf( _n( '%s Stunde', '%s Stunden', $hours ), $hours );
			} elseif ( $diff < WEEK_IN_SECONDS && $diff >= DAY_IN_SECONDS ) {
				$days = round( $diff / DAY_IN_SECONDS );
				if ( $days <= 1 ) {
					$days = 1;
				}
				$since = sprintf( _n( '%s Tag', '%s Tage', $days ), $days );
			} elseif ( $diff < 30 * DAY_IN_SECONDS && $diff >= WEEK_IN_SECONDS ) {
				$weeks = round( $diff / WEEK_IN_SECONDS );
				if ( $weeks <= 1 ) {
					$weeks = 1;
				}
				$since = sprintf( _n( '%s Woche', '%s Wochen', $weeks ), $weeks );
			} elseif ( $diff < YEAR_IN_SECONDS && $diff >= 30 * DAY_IN_SECONDS ) {
				$months = round( $diff / ( 30 * DAY_IN_SECONDS ) );
				if ( $months <= 1 ) {
					$months = 1;
				}
				$since = sprintf( _n( '%s Monat', '%s Monate', $months ), $months );
			} elseif ( $diff >= YEAR_IN_SECONDS ) {
				$years = round( $diff / YEAR_IN_SECONDS );
				if ( $years <= 1 ) {
					$years = 1;
				}
				$since = sprintf( _n( '%s Jahr', '%s Jahre', $years ), $years );
			}

			return $since;
		}


		function get_hidden_columns() {
			$screen = get_current_screen();

			$hidden = get_hidden_columns( $screen );

			return $hidden;
		}


		function get_sortable_columns() {

			$sortable_columns = array();

			return $sortable_columns;
		}

		function display() {
			extract( $this->_args );
			$this->display_tablenav( 'top' );
			?>
			<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>" cellspacing="0">
				<thead>
				<tr>
					<?php $this->print_column_headers(); ?>
				</tr>
				</thead>
				<tbody id="the-list"<?php if ( $singular ) {
					echo " class='list:$singular'";
				} ?>>
				<?php $this->display_rows_or_placeholder(); ?>
				</tbody>
				<tfoot>
				<tr>
					<?php $this->print_column_headers( false ); ?>
				</tr>
				</tfoot>
			</table>
			<?php
			$this->display_tablenav( 'bottom' );
		}


		function prepare_items() {
			global $wpdb, $blog_id;

			$columns  = $this->get_columns();
			$hidden   = $this->get_hidden_columns();
			$sortable = $this->get_sortable_columns();

			$this->_column_headers = array( $columns, $hidden, $sortable );

			$per_page = 20;
			$per_page = get_user_meta( get_current_user_id(), 'chat_page_chat_session_logs_per_page', true );
			if ( ( ! $per_page ) || ( $per_page < 1 ) ) {
				$per_page = 20;
			}

			$current_page = $this->get_pagenum();
			$page_offset  = ( $current_page - 1 ) * intval( $per_page );

			if ( ( is_multisite() ) && ( is_network_admin() ) ) {
				$_blog_id = 0;
			} else {
				$_blog_id = $blog_id;
			}

			if ( ( ! empty( $this->filters['search'] ) ) || ( $this->filters['session_type'] == "private" ) ) {

				$sql_str_filters = '';
				$sql_str_filters .= " AND (blog_id=" . $_blog_id . " OR blog_id=0) ";

				if ( ( isset( $this->filters['search'] ) ) && ( ! empty( $this->filters['search'] ) ) ) {
					$sql_str_filters .= " AND `message` like '%" . $this->filters['search'] . "%' ";
				}

				if ( ( isset( $this->filters['chat_id'] ) ) && ( ! empty( $this->filters['chat_id'] ) ) ) {
					$sql_str_filters .= " AND `chat_id`='" . $this->filters['chat_id'] . "' ";
				}

				if ( ( isset( $this->filters['session_type'] ) ) && ( ! empty( $this->filters['session_type'] ) ) ) {
					if ( $this->filters['session_type'] == "private" ) {
						global $current_user;
						$sql_str_filters .= " AND session_type='" . $this->filters['session_type'] . "' AND `auth_hash`='" . md5( $current_user->ID ) . "' ";
					} else {
						$sql_str_filters .= " AND `session_type`='" . $this->filters['session_type'] . "' ";
					}
				} else {
					$sql_str_filters .= " AND `session_type`!='private' ";
				}

				if ( ( isset( $this->filters['start'] ) ) && ( ! empty( $this->filters['start'] ) ) ) {
					$sql_str_filters .= " AND `timestamp` >='" . $this->filters['start'] . " 00:00:00' ";
				}

				if ( ( isset( $this->filters['end'] ) ) && ( ! empty( $this->filters['end'] ) ) ) {
					$sql_str_filters .= " AND `timestamp` <='" . $this->filters['end'] . " 23:59:59' ";
				}

				if ( ( isset( $this->filters['status'] ) ) && ( ! empty( $this->filters['status'] ) ) ) {
					if ( $this->filters['status'] == "hidden" ) {
						$sql_str_filters .= " AND `deleted` ='yes' ";
					} else {
						$sql_str_filters .= " AND `archived` ='" . $this->filters['status'] . "' ";
					}
				}
				$sql_str = "SELECT DISTINCT log_id FROM " . PSOURCE_Chat::tablename( 'message' ) . " WHERE 1=1 ";

				$sql_str .= $sql_str_filters;
				$log_ids = $wpdb->get_col( $sql_str );
				if ( ( $log_ids ) && ( is_array( $log_ids ) ) && ( count( $log_ids ) ) ) {
					$total_items = count( $log_ids );

					$sql_str = "SELECT log.* FROM " . PSOURCE_Chat::tablename( 'log' ) . " as log ";
					$sql_str .= " WHERE 1=1 ";

					$sql_str .= " AND id IN (" . implode( ',', $log_ids ) . ")";

					$sql_str .= " ORDER BY log.start DESC LIMIT " . $page_offset . ", " . $per_page;
					$items = $wpdb->get_results( $sql_str );

				}
			} else {

				$sql_str = "SELECT count(*) as total_items FROM " . PSOURCE_Chat::tablename( 'log' ) . " as log";
				$sql_str .= " WHERE 1=1 ";

				$sql_str_filters = '';
				$sql_str_filters .= " AND blog_id=" . $_blog_id . " ";

				if ( ( isset( $this->filters['chat_id'] ) ) && ( ! empty( $this->filters['chat_id'] ) ) ) {
					$sql_str_filters .= " AND chat_id='" . $this->filters['chat_id'] . "' ";
				}
				if ( ( isset( $this->filters['session_type'] ) ) && ( ! empty( $this->filters['session_type'] ) ) ) {
					$sql_str_filters .= " AND session_type='" . $this->filters['session_type'] . "' ";
				}

				if ( ( isset( $this->filters['start'] ) ) && ( ! empty( $this->filters['start'] ) ) ) {
					$sql_str_filters .= " AND start >='" . $this->filters['start'] . " 00:00:00' ";
				}
				if ( ( isset( $this->filters['end'] ) ) && ( ! empty( $this->filters['end'] ) ) ) {
					$sql_str_filters .= " AND end <='" . $this->filters['end'] . " 23:59:59' ";
				}
				if ( ( isset( $this->filters['status'] ) ) && ( ! empty( $this->filters['status'] ) ) ) {
					if ( $this->filters['status'] == "hidden" ) {
						$sql_str_filters .= " AND `deleted` ='yes' ";
					} else {
						$sql_str_filters .= " AND `archived` ='" . $this->filters['status'] . "' ";
					}
				}

				$sql_str .= $sql_str_filters;
				//echo "sql_str_filters=[". $sql_str_filters ."]<br />";

				//echo "sql_str=[". $sql_str ."]<br />";
				$result = $wpdb->get_row( $sql_str );
				if ( $result->total_items ) {
					$total_items = $result->total_items;
				} else {
					$total_items = 0;
				}
				//echo "total_items[". $total_items ."]<br />";

				$sql_str = "SELECT log.* FROM " . PSOURCE_Chat::tablename( 'log' ) . " as log ";
				$sql_str .= " WHERE 1=1 AND log.session_type != 'private' ";

				$sql_str .= $sql_str_filters;
				$sql_str .= " ORDER BY log.start DESC LIMIT " . $page_offset . ", " . $per_page;

				$items = $wpdb->get_results( $sql_str );
			}

			if ( ( isset( $items ) ) && ( count( $items ) ) ) {

				$this->items = $items;

				$this->set_pagination_args( array(
						'total_items' => $total_items,
						// WE have to calculate the total number of items
						'per_page'    => intval( $per_page ),
						// WE have to determine how many items to show on a page
						'total_pages' => ceil( intval( $total_items ) / intval( $per_page ) )
						// WE have to calculate the total number of pages
					)
				);
			}
		}
	}
}