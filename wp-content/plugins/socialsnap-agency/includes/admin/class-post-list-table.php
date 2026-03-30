<?php
/**
 * Add a new column to admin columns with share counts.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.1.5
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Post_List_Table {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.1.5
	 */
	public function __construct() {

		// Add column.
		add_filter( 'manage_post_posts_columns', array( $this, 'add_column' ) );
		add_filter( 'manage_page_posts_columns', array( $this, 'add_column' ) );

		// Print content to column.
		add_action( 'manage_posts_custom_column', array( $this, 'print_content' ), 10, 2 );
		add_action( 'manage_page_posts_custom_column', array( $this, 'print_content' ), 10, 2 );

		// Set sortable.
		add_filter( 'manage_edit-post_sortable_columns', array( $this, 'sortable' ) );
		add_filter( 'manage_edit-page_sortable_columns', array( $this, 'sortable' ) );

		add_action( 'pre_get_posts', array( $this, 'orderby' ) );

		add_action( 'quick_edit_custom_box', array( $this, 'quick_edit_add' ), 10, 2 );
		add_action( 'save_post', array( $this, 'quick_edit_save' ), 20, 1 );
		add_action( 'admin_footer', array( $this, 'quick_edit_javascript' ) );
		add_filter( 'post_row_actions', array( $this, 'expand_quick_edit_link' ), 10, 2 );
		add_filter( 'page_row_actions', array( $this, 'expand_quick_edit_link' ), 10, 2 );
	}

	/**
	 * Add custom column to post list admin page.
	 *
	 * @since  1.1.5
	 * @param  array $defaults The default columns registered with WordPress.
	 * @return array           The array modified with our new column.
	 */
	public function add_column( $defaults ) {
		$defaults['ss_social_shares'] = 'Shares';
		return $defaults;
	}

	/**
	 * Print content to custom column.
	 *
	 * @since  1.1.5
	 * @param  string $column_name Column to be modified.
	 * @param  int    $post_ID     Post ID.
	 * @return void
	 */
	public function print_content( $column_name, $post_ID ) {

		if ( 'ss_social_shares' !== $column_name ) {
			return;
		}

		$disabled = get_post_meta( $post_ID, 'ss_social_share_disable', true );
		if ( $disabled ) {
			esc_html_e( 'Disabled', 'socialsnap' );
		} else {
			// Get the share count, format it, echo it to the screen.
			$count = get_post_meta( $post_ID, 'ss_total_share_count', true );
			if ( ! empty( $count ) ) {
				echo esc_html( socialsnap_format_number( $count ) );
				return;
			}

			echo 0;
		}
	}

	/**
	 * Sortable column.
	 *
	 * @since  1.1.5
	 * @param  array The array of registered columns.
	 * @return array The array modified columns.
	 */
	public function sortable( $columns ) {
		$columns['ss_social_shares'] = 'Shares';
		return $columns;
	}

	/**
	 * Sort the column by share count.
	 *
	 * @since  1.1.5.
	 * @param  object $query The WordPress query object.
	 * @return void
	 */
	public function orderby( $query ) {

		if ( ! is_admin() ) {
			return;
		}

		if ( 'Shares' !== $query->get( 'orderby' ) ) {
			return;
		}

		$query->set( 'meta_key', 'ss_total_share_count' );
		$query->set( 'orderby', 'meta_value_num' );
	}



	/**
	 * Add custom fields to quick edit screen.
	 *
	 * @param string $column_name Custom column name, used to check
	 * @param string $post_type
	 *
	 * @return void
	 */
	public function quick_edit_add( $column_name, $post_type ) {

		if ( 'ss_social_shares' !== $column_name ) {
			return;
		}

		echo '
			<fieldset class="inline-edit-col-right" style="float:right;margin-right:1%;margin-top:10px;">
				<div class="inline-edit-col">
					<div class="inline-edit-group wp-clearfix">
						<label class="alignleft">
							<input type="checkbox" name="ss_social_share_disable" class="ss_social_share_disable">
							<span class="checkbox-title">' . esc_html__( 'Disable Share Buttons', 'socialsnap' ) . '</span>
						</label>
					</div>
				</div>
			</fieldset>';
	}

	/**
	 * Save custom fields to quick edit screen.
	 *
	 * @param  int $post_id Post ID.
	 * @return void
	 */
	public function quick_edit_save( $post_id ) {

		// phpcs:disable WordPress.Security.NonceVerification

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		if ( isset( $_POST['_inline_edit'], $_POST['post_view'] ) ) {
			$data = empty( $_POST['ss_social_share_disable'] ) ? 0 : 1;
			update_post_meta( $post_id, 'ss_social_share_disable', $data );
		}
	}

	/**
	 * Write javascript function to set checked to headline news checkbox
	 *
	 * @return void
	 */
	public function quick_edit_javascript() {
		?>
		<script type="text/javascript">
		function socialsnap_checked_disable_share( e, fieldValue ) {
			e.preventDefault();
			inlineEditPost.revert();
			document.querySelectorAll( '.ss_social_share_disable' ).forEach((item) => {
				item.checked = 0 == fieldValue ? false : true;
			})
		}
		</script>
		<?php
	}

	/**
	 * Pass headline news value to checked_headline_news javascript function
	 *
	 * @param array $actions
	 * @param array $post
	 *
	 * @return array
	 */
	public function expand_quick_edit_link( $actions, $post ) {
		$data                             = get_post_meta( $post->ID, 'ss_social_share_disable', true );
		$data                             = empty( $data ) ? 0 : 1;
		$actions['inline hide-if-no-js']  = '<a href="#" class="editinline" title="';
		$actions['inline hide-if-no-js'] .= esc_attr( 'Edit this item inline' ) . '"';
		$actions['inline hide-if-no-js'] .= " onclick=\"socialsnap_checked_disable_share(event,'{$data}')\" >";
		$actions['inline hide-if-no-js'] .= 'Quick Edit';
		$actions['inline hide-if-no-js'] .= '</a>';

		return $actions;
	}
}
new SocialSnap_Post_List_Table();
