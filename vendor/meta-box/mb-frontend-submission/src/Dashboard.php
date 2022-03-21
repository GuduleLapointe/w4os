<?php
namespace MBFS;

use WP_Query;
use WP_Error;
use MetaBox\Support\Arr;

class Dashboard {
	private $query;
	private $edit_page_atts;

	public function __construct() {
		add_shortcode( 'mb_frontend_dashboard', [ $this, 'shortcode' ] );
	}

	public function shortcode( $atts ) {
		/*
		 * Do not render the shortcode in the admin.
		 * Prevent errors with enqueue assets in Gutenberg where requests are made via REST to preload the post content.
		 */
		if ( is_admin() ) {
			return '';
		}

		if ( ! is_user_logged_in() ) {
			return esc_html__( 'Please login to view the dashboard.', 'mb-frontend-submission' );
		}

		$this->edit_page_atts = $this->get_edit_page_attrs( $atts['edit_page'] );
		if ( is_wp_error( $this->edit_page_atts ) ) {
			return '<div class="rwmb-error">' . $this->edit_page_atts->get_error_message() . '</div>';
		}

		$atts = shortcode_atts( [
			// Meta box id.
			'id'           => $this->edit_page_atts['id'],

			// Edit page id.
			'edit_page'    => '',

			// Add new post button text
			'add_new'      => __( 'Add New', 'mb-frontend-submission' ),

			// Delete permanently.
			'force_delete' => 'false',

			// Columns to display.
			'columns'      => 'title,date,status',

			// Column header labels.
			'label_title'   => __( 'Title', 'mb-frontend-submission' ),
			'label_date'    => __( 'Date', 'mb-frontend-submission' ),
			'label_status'  => __( 'Status', 'mb-frontend-submission' ),
			'label_actions' => __( 'Actions', 'mb-frontend-submission' ),

			// Link action for post title (view|edit).
			'title_link'    => '',

			// Flag to hide/show welcome message.
			'show_welcome_message' => 'true',
		], $atts );

		ob_start();
		$this->query_posts();

		if ( $atts['show_welcome_message'] === 'true' ) {
			$this->show_welcome_message();
		}

		$this->show_user_posts( $atts );

		return ob_get_clean();
	}

	private function query_posts() {
		$args = [
			'author'                 => get_current_user_id(),
			'post_type'              => $this->edit_page_atts['post_type'],
			'posts_per_page'         => -1,
			'post_status'            => 'any',
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		];
		$args = apply_filters( 'mbfs_dashboard_query_args', $args );
		$this->query = new WP_Query( $args );
	}

	private function show_welcome_message() {
		$post_type        = $this->edit_page_atts['post_type'];
		$post_type_object = get_post_type_object( $post_type );
		$user             = wp_get_current_user();
		$query            = $this->query;

		// Translators: %s - user display name.
		$message = '<h3>'. esc_html( sprintf( __( 'Howdie, %s!', 'mb-frontend-submission' ), $user->display_name ) ).'</h3>';
		// Translators: %1$d - number of posts, %2$s - post label name.
		$message .= '<p>'. esc_html( sprintf( __( 'You have %1$d %2$s.', 'mb-frontend-submission' ), $query->post_count, strtolower( $post_type_object->labels->name ) ) ).'</p>';
		$output   = apply_filters( 'mbfs_welcome_message', $message, $user, $query );
		echo $output;
	}

	private function get_edit_page_attrs( $edit_page_id ) {
		$edit_page = get_post( $edit_page_id );
		$pattern   = get_shortcode_regex( ['mb_frontend_form'] );
		$content   = $this->get_edit_page_content( $edit_page );

		if ( ! preg_match_all( '/' . $pattern . '/s', $content, $matches ) || empty( $matches[2] ) || ! in_array( 'mb_frontend_form', $matches[2] ) ) {
			// Translators: %d - page ID.
			return new WP_Error( 'mbfs-no-edit-page', sprintf( __( 'Something is not right with the shortcode on page ID: %d', 'mb-frontend-submission' ), $edit_page_id ) );
		}

		// Get shortcode attributes.
		$key            = array_search( 'mb_frontend_form', $matches[2] );
		$shortcode_atts = explode( ' ', $matches[3][ $key ] );

		// Get only 'id' and 'post_type' attributes.
		$attributes = [
			'id'        => '',
			'post_type' => 'post',
			'url'       => get_permalink( $edit_page ),
		];
		foreach ( $shortcode_atts as $attribute ) {
			$attribute = explode( '=', $attribute );

			if ( in_array( $attribute[0], ['id', 'post_type'] ) ) {
				$attributes[ $attribute[0] ] = str_replace( ['"', "'"], '', $attribute[1] );
			}
		}

		return $attributes;
	}

	private function get_edit_page_content( $post ) {
		$content = $post->post_content;

		// Oxygen Builder.
		if ( defined( 'CT_VERSION' ) && ! defined( 'SHOW_CT_BUILDER' ) ) {
			$shortcode = get_post_meta( $post->ID, 'ct_builder_shortcodes', true );
			$content = $shortcode ? $shortcode : $content;
		}

		$content = apply_filters( 'mbfs_dashboard_edit_page_content', $content );

		return $content;
	}

	private function show_user_posts( $atts ) {
		$this->enqueue();
		?>
		<a class="mbfs-add-new-post" href="<?= esc_url( $this->edit_page_atts['url'] ) ?>">
			<?= esc_html( $atts['add_new'] ) ?>
		</a>
		<?php
		if ( ! $this->query->have_posts() ) {
			return;
		}
		?>
		<table class="mbfs-posts">
			<tr>
				<?php
				$columns = Arr::from_csv( $atts['columns'] );
				if ( in_array( 'title', $columns ) ) {
					echo '<th>', esc_html( $atts['label_title'] ), '</th>';
				}
				if ( in_array( 'date', $columns ) ) {
					echo '<th>', esc_html( $atts['label_date'] ), '</th>';
				}
				if ( in_array( 'status', $columns ) ) {
					echo '<th>', esc_html( $atts['label_status'] ), '</th>';
				}
				?>
				<th><?= esc_html( $atts['label_actions'] ) ?></th>
			</tr>
			<?php while( $this->query->have_posts() ) : $this->query->the_post(); ?>
				<tr>
					<?php if ( in_array( 'title', $columns ) ) : ?>
						<?php
						if ( $atts['title_link'] === 'edit' ) {
							$title_link = add_query_arg( 'rwmb_frontend_field_post_id', get_the_ID(), $this->edit_page_atts['url'] );
						} else {
							$title_link = get_the_permalink();
						}
						?>
						<td><a href="<?= esc_url( $title_link ) ?>"><?php the_title(); ?></a></td>
					<?php endif; ?>

					<?php if ( in_array( 'date', $columns ) ) : ?>
						<td align="center"><?php the_time( get_option( 'date_format' ) ) ?></td>
					<?php endif; ?>

					<?php if ( in_array( 'status', $columns ) ) : ?>
						<td align="center"><?= get_post_status_object( get_post_status() )->label ?></td>
					<?php endif; ?>

					<td align="center" class="mbfs-actions">
						<a href="<?= esc_url( add_query_arg( 'rwmb_frontend_field_post_id', get_the_ID(), $this->edit_page_atts['url'] ) ) ?>" title="<?php esc_html_e( 'Edit', 'mb-frontend-submission' ) ?>">
							<img src="<?= MBFS_URL . 'assets/pencil.svg' ?>">
						</a>
						<?= do_shortcode( '[mb_frontend_form id="' . $this->edit_page_atts['id'] . '" post_id="' . get_the_ID() . '" ajax="true" allow_delete="true" force_delete="' . $atts['force_delete'] . '" only_delete="true" delete_button="<img src=\'' . MBFS_URL . 'assets/trash.svg' . '\'>"]' ); ?>
					</td>
				</tr>
			<?php endwhile ?>
		</table>
		<?php
		wp_reset_postdata();
	}

	private function enqueue() {
		wp_enqueue_style( 'mbfs-dashboard', MBFS_URL . 'assets/dashboard.css', '', MBFS_VER );
		wp_enqueue_script( 'mbfs', MBFS_URL . 'assets/frontend-submission.js', array( 'jquery' ), MBFS_VER, true );
	}
}