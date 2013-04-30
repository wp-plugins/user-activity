<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
class Bu_User_Activity_Table extends WP_List_Table {
	private $short_name = 'buua';

	function __construct() {
		parent::__construct(
			array(
				'singular' => 'user_activity',
				'plural' => 'user_activities',
				'ajax'	=> true,
			)
		);
	}

	function get_columns() {
		return $columns = array(
			'userid' => __( 'ID', $this->short_name ),
			'username' => __( 'Name', $this->short_name ),
			'total' => __( 'Total', $this->short_name ),
			'latest' => __( 'Latest post date', $this->short_name ),
		);
	}

	function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'userid':
			case 'total':
			case 'latest':
				return $item->$column_name;
			default:
				return var_export( $item, true );
		}
	}

	public function column_username( $item ) {
		$userdata = get_userdata( $item->userid );
		echo '<a href="'. get_edit_user_link( $item->userid ) .'">'. esc_attr( $userdata->display_name ) . '</a> (' . $item->username . ')';
	}

	public function get_sortable_columns() {
		return $sortable = array(
			'userid' => array( 'b.ID', false ),
			'username' => array( 'user_nicename', false ),
			'total' => array( 'total', false ),
			'latest' => array( 'latest', false ),
		);
	}

	public function prepare_items() {
		global $wpdb, $_wp_column_headers;
		$screen = get_current_screen();

		/**
		 * Handle request parameters
		 */
		$ptype     = ( isset( $_REQUEST['ptype'] )  && !empty( $_REQUEST['ptype'] ) ? $_REQUEST['ptype'] : 'all');
		$startdate = ( isset( $_REQUEST['startdate'] ) ? $_REQUEST['startdate'] : '');
		$enddate   = ( isset( $_REQUEST['enddate'] ) ? $_REQUEST['enddate'] : '');
		$username  = ( isset( $_REQUEST['s'] ) ? $_REQUEST['s'] : '');

		$prepare_vars = array();

		/**
		 * User search
		 */
		$search_clause = '';
		if ( !empty( $username ) ) {
			$search_clause  = 'and b.user_nicename = %s';
			$prepare_vars[] = $username;
		}

		/**
		 * Post type search
		 */
		$ptype_clause = '';
		if ( 'all' !== $ptype ) {
			$ptype_clause   = 'and a.post_type = %s';
			$prepare_vars[] = $ptype;
		} else {
			$args = array( 'public' => true );
			$post_types   = get_post_types( $args );
			$ptype_clause = sprintf(
				'and a.post_type IN (%s)',
				implode( ',', array_map( function( $a ) { return "'" . $a . "'"; }, $post_types ) )
			);
		}

		/**
		 * Start date search
		 */
		$startdate_clause = $enddate_clause = '';
		if ( !empty( $startdate ) ) {
			$startdate_clause = 'and a.post_date >= %s';
			$prepare_vars[]   = $startdate;
		}

		/**
		 * End date search
		 */
		if ( !empty( $enddate ) ) {
			$enddate_clause = 'and a.post_date <= %s';
			$prepare_vars[] = $enddate;
		}

		/**
		 * Setup the query
		 */
		$query = 'select b.ID userid, b.user_nicename username, count(*) as total, max(a.post_date) as latest '
			. "from {$wpdb->users} b, {$wpdb->posts} a "
			. "where a.post_author = b.ID {$ptype_clause} {$search_clause} {$startdate_clause} {$enddate_clause} "
			. 'group by b.user_nicename';

		/**
		 * Handle the ordering
		 */
		$orderby = !empty( $_GET['orderby'] ) ? mysql_real_escape_string( $_GET['orderby'] ) : 'total';
		$order   = !empty( $_GET['order'] ) ? mysql_real_escape_string( $_GET['order'] ) : 'DESC';
		if ( !empty( $orderby ) && !empty( $order ) )
			$query .= ' ORDER BY ' . $orderby . ' ' . $order;

		$totalitems = $wpdb->query( $wpdb->prepare( $query, $prepare_vars ) );
		if ( empty ( $per_page) || $per_page < 1 ) {
			$per_page = $screen->get_option( 'per_page', 'default' );
		}

		/**
		 * Paging
		 */
		$paged = !empty( $_GET['paged'] ) ? mysql_real_escape_string( $_GET['paged'] ) : '';
		if ( empty( $paged ) || !is_numeric( $paged ) || $paged <= 0 )
			$paged = 1;

		$totalpages = ceil( $totalitems / $per_page );

		if ( !empty( $paged ) && !empty( $per_page ) ) {
			$offset = ( $paged - 1 ) * $per_page;
			$query .= ' LIMIT '. (int) $offset . ',' . (int) $per_page;
		}

		/* -- Register the pagination -- */
		$this->set_pagination_args(
			array(
				'total_items' => $totalitems,
				'total_pages' => $totalpages,
				'per_page' => $per_page,
			)
		);

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		/**
		 * Fetch the items
		 */
		$this->items = $wpdb->get_results( $wpdb->prepare( $query, $prepare_vars ) );
	}

	/**
	 * Quick links to post type views
	 *
	 */
	public function get_views(){
		$views   = array();
		$current = ( !empty($_REQUEST['ptype']) ? $_REQUEST['ptype'] : 'all');

		//All link
		$class = ( $current == 'all' ? ' class="current"' :'');
		$all_url = remove_query_arg( 'ptype' );
		$views['all'] = "<a href='{$all_url }' {$class} >All</a>";

		$args = array( 'public' => true );
		$post_types = get_post_types( $args );
		foreach ( $post_types  as $post_type ) {
			$name = $post_type;
			$url = add_query_arg( 'ptype', $name );
			$class = ( $current == $name ? ' class="current"' : '' );
			$ucname = ucfirst( $name );
			$views[$name] = "<a href='{$url}' {$class} >{$ucname}</a>";
		}
		return $views;
	}

	/**
	 * Filter form items
	 * post type, start date and end date
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' != $which )
			return;
		$ptype     = isset( $_GET['ptype'] ) ? $_GET['ptype'] : '';
		$startdate = isset( $_GET['startdate'] ) ? $_GET['startdate'] : '';
		$enddate   = isset( $_GET['enddate'] ) ? $_GET['enddate'] : '';
		?>
		<div class="alignleft actions">
			<label class="screen-reader-text" for="ptype"><?php _e(  'Post type&hellip;', $this->short_name ) ?></label>
			<select name="ptype" id="ptype">
				<option <?php selected( $ptype, '' ); ?> value=''><?php _e( 'Post type&hellip;', $this->short_name ) ?></option>
				<?php
				$args = array( 'public' => true );
				$post_types = get_post_types( $args );
				foreach ( $post_types  as $post_type ) : ?>
					<option <?php selected( $ptype, $post_type ); ?> value="<?php echo $post_type; ?>"><?php echo ucfirst( $post_type ); ?></option>
				<?php endforeach ?>
			</select>
			<input placeholder="<?php _e( 'Start date', $this->short_name ); ?>" type="text" id="startdate" name="startdate" class="startdate datepicker" value="<?php echo $startdate; ?>" />
			<input placeholder="<?php _e( 'End date', $this->short_name ); ?>" type="text" id="enddate" name ="enddate" class="enddate datepicker" value="<?php echo $enddate; ?>" />
			<?php submit_button( __( 'Filter', $this->short_name ), 'button', false, false, array( 'id' => 'post-query-submit' ) ); ?>
		</div>
		<?php
	}

}
