<?php
/**
 * - Extend WP_List_Table class for custom post-types
 * - use parameters from registered post_type and registered meta fields
 * - create a submenu to combined list/settings page with tabs. Default tab shows the list.
 * - disable default edit.php access for the custom post type.
 * 
 * This class should be agnostic, so it can be used for any custom post type, any class, in any context.
 * It cannot contain any specific name of table or column.
 * All the specific have to be passed as parameters by the calling class.
 * This class only provide the engine to make a list table.
 */

 // Ensure WP_List_Table is loaded before using it
add_action( 'admin_menu', function() {
	class W4OS_List_Table extends WP_List_Table {
		private $db;
		private $columns;
		private $sortable;
		private $searchable;
		private $id_field;
		private $table;
		private $render_callbacks; // Add property for render callbacks
		private $views_columns;
		private $admin_columns;
		private $php_filters = []; // Add property for PHP-based filters
		private $views_html = [];

		/** Class constructor */
		public function __construct( $db, $table, $args ) {
			$args = WP_parse_args( $args, [
				'singular'         => 'Item',
				'plural'           => 'Items',
				'ajax'             => false,
				'admin_columns'    => [], // Initialize admin columns
			] );
			$this->table            = sanitize_text_field( $table ); // Ensure table name is safe
			$this->columns          = array();
			$this->sortable         = array();
			$this->searchable       = array();
			$this->render_callbacks = array();
			$this->views_columns	= array();
			$this->admin_columns 	= $args['admin_columns'];
			$this->primary 			= ( isset( $args['primary'] ) ) ? $args['primary'] : array_key_first( $this->admin_columns );

			// Extract admin_columns
			foreach ( $args['admin_columns'] as $key => $column ) {
				// Set column title
				$this->columns[ $key ] = isset( $column['title'] ) ? $column['title'] : ucfirst( $key );

				// Set sortable
				if ( isset( $column['sortable'] ) && $column['sortable'] ) {
					# Not here, the menu has to always use the column key even if overidden by sort_column
					if( ! empty( $column['sort_column'] ) && $column['sort_column'] != 'callback' ) {
						$sort_column = $column['sort_column'];
					} else {
						$sort_column = $key;
					}
					$order = isset( $column['order'] ) ? strtoupper( $column['order'] ) : 'ASC';
					$this->sortable[ $key ] = [ $sort_column, ( $order === 'DESC' ) ];
				}

				// Set searchable
				if ( isset( $column['searchable'] ) && $column['searchable'] ) {
					$this->searchable[] = $key;
				}

				// Set render callbacks
				if ( isset( $column['render_callback'] ) && is_callable( $column['render_callback'] ) ) {
					$this->render_callbacks[ $key ] = $column['render_callback'];
				}

				if ( isset ( $column['views'] ) ) {
					$this->views_columns[ $key ] = $column['views'];
				}

				if ( isset( $column['size'] ) ) {
					$this->column_sizes[ $key ] = $column['size'];
				}
			}

			parent::__construct( [
				'singular' => $args['singular'],
				'plural'   => $args['plural'],
				'ajax'     => $args['ajax']
			] );

			// Use the passed DB connection
			$this->db = $db;
		}

		/** Define the columns */
		public function get_columns() {
			$columns = WP_parse_args( $this->columns, [
				'cb' => '<input type="checkbox" />',
			] );

			// Add classes or styles for column sizes
			// This methods has no effect
			// - $title value is only calculated, not used
			foreach ( $columns as $key => &$title ) {
				if ( isset( $this->column_sizes[ $key ] ) && is_numeric( $this->column_sizes[ $key ] ) ) {
					$size = intval( $this->column_sizes[ $key ] );
					$title = '<span style="display: inline-block; width: ' . $size . 'px;">' . $title . '</span>';
				}
			}
			
			return $columns;
		}

		/** Define sortable columns */
		public function get_sortable_columns() {
			return $this->sortable;
		}

		/** Prepare the items for the table */
		public function prepare_items() {
			$columns  = $this->get_columns();
			$hidden   = [];
			$sortable = $this->get_sortable_columns();
			$primary  = $this->primary ?? null;

			$this->_column_headers = [ $columns, $hidden, $sortable, $primary ];

			if( empty( $this->args['query'] ) ) {
				$query = "SELECT * FROM `{$this->table}`";
			} else {
				// Use custom query from arguments
			   $query = $this->args['query'];
			}

			$conditions = [];

			// Handle search
			if ( ! empty( $_REQUEST['s'] ) ) {
				$search = '%' . $this->db->esc_like( $_REQUEST['s'] ) . '%';
				$search_conditions = [];
				foreach ( $this->searchable as $field ) {
					$search_conditions[] = $this->db->prepare( "`$field` LIKE %s", $search );
				}
				if ( ! empty( $search_conditions ) ) {
					$conditions[] = '(' . implode( ' OR ', $search_conditions ) . ')';
				}
			}

			// Handle filters using 'views_columns' instead of 'filterable'
			foreach ( $this->views_columns as $key => $type ) {
				if ( ! empty( $_GET['filter_' . $key ] ) ) {
					$filter_value = sanitize_text_field( $_GET['filter_' . $key ] );

					if ( $type === 'callback' && isset( $this->render_callbacks[ $key ] ) && is_callable( $this->render_callbacks[ $key ] ) ) {
						// For columns with render_callback, apply PHP-based filtering after fetching results
						$this->php_filters[] = [
							'column' => $key,
							'value'  => $filter_value,
						];
					}
				}
			}

			if ( ! empty( $conditions ) ) {
				$query .= ' WHERE ' . implode( ' AND ', $conditions );
			}

			$orderby = null;
			$sort_column = null;
			// Handle sorting by callback
			if ( ! empty( $_REQUEST['orderby'] ) && ! empty( $_REQUEST['order'] ) ) {
				$orderby = sanitize_text_field( $_REQUEST['orderby'] );
				$order   = sanitize_text_field( $_REQUEST['order'] ) === 'desc' ? 'DESC' : 'ASC';
				$allowed_orderbys = array_keys( $this->sortable );

				# Wrong hardcoded method, disabled until fixed
				if ( in_array( $orderby, $allowed_orderbys, true ) ) {
					$column = $this->admin_columns[ $orderby ];
					$sort_column = empty( $column['sort_column'] ) ? $orderby : $column['sort_column'];
					// We deal with callbacks in another way
					if ( $sort_column !== 'callback' ) {
						$query .= " ORDER BY `{$sort_column}` {$order}";
					}
				}
			} else {
				// If we get here, we have no sorting applied. All sort orders must pass through
				// the test above, to ensure that only valid columns are sorted.
				// id_field is irrelevant anyway.
				// $query .= " ORDER BY `{$this->id_field}` ASC";
			}

			$results = $this->db->get_results( $query );

			// Apply PHP-based filters if any
			if ( ! empty( $this->php_filters ) ) {
				foreach ( $this->php_filters as $filter ) {
					$column = $filter['column'];
					$value  = $filter['value'];
					if ( isset( $this->render_callbacks[ $column ] ) && is_callable( $this->render_callbacks[ $column ] ) ) {
						$results = array_filter( $results, function( $item ) use ( $column, $value ) {
							$rendered = call_user_func( $this->render_callbacks[ $column ], $item );
							return strcmp( strtolower( $rendered ), strtolower( $value ) ) === 0;
						} );
					}
				}
			}

			if ( ! empty( $results[0] ) && is_object( $results[0] ) ) {
				// Set the ID field based on the first property of the first result
				$this->id_field = array_key_first( get_object_vars( $results[0] ) );
			} else {
				$this->id_field = null; // Handle cases where results are empty or invalid
			}

			# Good method, but hardcoded, disabled until fixed
			// Handle custom sorting for columns sorted by render callback
			if( $orderby === 'callback' ) {
				error_log( "post-process sort by callback for column $orderby" );
			}

			if($sort_column === 'callback') {
				if ( ! empty($orderby) && ! empty( $order ) ) {
					if ( is_callable( $this->render_callbacks[$orderby] ) ) {
						usort( $results, function( $a, $b ) use ( $orderby, $order ) {
							$value_a = $this->render_callbacks[$orderby]( $a );
							$value_b = $this->render_callbacks[$orderby]( $b );

							if ( $value_a == $value_b ) {
								return 0;
							}

							if ( $order === 'ASC' ) {
								return ($value_a < $value_b) ? -1 : 1;
							} else {
								return ($value_a > $value_b) ? -1 : 1;
							}
						} );
					}
				}
			}

			$this->items = $results;
		}

		/** Render a column when no specific column handler is provided */
		public function column_default( $item, $column_name ) {
			if ( isset( $this->render_callbacks[ $column_name ] ) && is_callable( $this->render_callbacks[ $column_name ] ) ) {
				return call_user_func( $this->render_callbacks[ $column_name ], $item );
			}

			return isset( $item->$column_name ) ? esc_html( $item->$column_name ) : '';
		}

		/** 
		 * Render the bulk actions dropdown
		 * 
		 * DO NOT DELETE. Not implemented yet, kept for future reference
		 */
		protected function bulk_actions( $which = '' ) {
			if ( $which === 'top' || $which === 'bottom' ) {
				?>
				<label class="screen-reader-text" for="bulk-action-selector-<?php echo $which; ?>"><?php _e( 'Select bulk action', 'w4os' ); ?></label>
				<select name="action" id="bulk-action-selector-<?php echo "$which"; ?>" disabled>
					<option value=""><?php _e( 'Bulk Actions', 'w4os' ); ?></option>
					<option value="start"><?php _e( 'Start', 'w4os' ); ?></option>
					<option value="restart"><?php _e( 'Restart', 'w4os' ); ?></option>
					<option value="stop"><?php _e( 'Stop', 'w4os' ); ?></option>
					<option value="disable"><?php _e( 'Disable', 'w4os' ); ?></option>
				</select>
				<?php
				submit_button( __( 'Apply', 'w4os' ), 'button', 'submit', false, array( 'disabled' => "1" ) );
			}
		}
		
		/** 
		 * Process bulk actions
		 * 
		 * DO NOT DELETE. Not implemented yet, kept for future reference
		 */
		protected function process_bulk_action() {
			// TODO: Implement bulk actions
			// Check if a callback is set for the current action in $this->admin_columns['bulk_actions']
			// If so, call the callback with the selected items
			// If not, add admin notice and return;
			$table = $this->table;
			$actions = $this->admin_columns['bulk_actions'];
			$action = $this->current_action();
			if ( ! isset( $actions[ $action ] ) ) {
				return;
			}
		}

		/**
		 * Render the checkbox column
		 */
		function column_cb( $item ) {
			$id = isset( $this->id_field ) ? $item->{$this->id_field} : '';
			return sprintf(
				'<input type="checkbox" name="%s[]" value="%s" />',
				$this->table,
				esc_attr( $id )
			);
		}

		/**
		 * Build the views menu displayed by $this->views()
		 * 
		 * Should generate the views (subsubsub) menu, in the format used by WP_List_Table::views()
		 * 	- "All" always present
		* 	- Other views added for columns with 'views' => true, based on columns (rendered) values
		* 
		* @param string $which
		* 
		* @return void
		*/
		function get_views() {
			// Get unfiltered items to build views action links
			$unfiltered_query = "SELECT * FROM `{$this->table}`";
			if (!empty($this->args['query'])) {
				$unfiltered_query = $this->args['query'];
			}
			$items = $this->db->get_results($unfiltered_query);
			
			if(empty($items)) {
				return;
			}
			$page_url = ( isset( $_GET['page'] ) ) ? admin_url( 'admin.php?page=' . $_GET['page'] ) : $_SERVER['REQUEST_URI'];
			
			// Determine the current filter by scanning $_GET for 'filter_' parameters
			$current_filter = '';
			foreach ($this->views_columns as $column => $enable_views) {
				if (!empty($_GET['filter_' . $column])) {
					$current_filter = sanitize_text_field($_GET['filter_' . $column]);
					break;
				}
			}

			$views = array(
				'all' => sprintf(
					'<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
					esc_url( $page_url ),
					empty($current_filter) ? 'current' : '',
					__( 'All', 'w4os' ),
					count( $items )
				)
			);
			// First scan the values to use for the views
			foreach ( $this->views_columns as $column => $enable_views ) {
				if( empty( $enable_views ) || $enable_views === false ) {
					continue;
				}

				$column_values = array();
				foreach ( $items as $item ) {
					$view_column = 'view_' . $column;
					if ( isset( $this->render_callbacks[ $column ] ) && is_callable( $this->render_callbacks[ $column ] ) ) {
						$item->$view_column = call_user_func( $this->render_callbacks[ $column ], $item );
					} else {
						$item->$view_column = isset( $item->$column ) ? $item->$column : '';
					}
					$column_values[] = $item->$view_column;
				}

				$column_values = array_unique( $column_values );
				sort( $column_values );

				foreach ( $column_values as $value ) {
					$count = 0;
					foreach( $items as $item ) {
						if ( $item->$view_column === $value ) {
							$count++;
						}
					}
					$views[ $value ] = sprintf(
						'<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
						esc_url( add_query_arg( array( 'filter_' . $column => $value ) ) ),
						($current_filter === $value) ? 'current' : '',
						esc_html($value),
						$count
					);
				}
			}

			$this->views_html = $views;
			return $views;
		}

		/**
		 * Add extra markup in the toolbars before or after the list (filter buttons, filter menus, pagination, etc.)
		 */
		function extra_tablenav( $which ) {
			if ( $which === 'top' ) {
				// Tbc
			}
		}
	}
});
