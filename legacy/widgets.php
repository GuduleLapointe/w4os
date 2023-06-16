<?php if ( ! defined( 'W4OS_PLUGIN' ) ) {
	die;}

/**
 * Adds W4OS_GridInfo_Widget widget.
 */
class W4OS_GridInfo_Widget extends WP_Widget {

	public function __construct() {
		$widget_ops = array(
			'description' => __( 'Grid info Widget', 'w4os' ),
		);
		parent::__construct(
			'W4OS_GridInfo_Widget', // Base ID
			'W4OS Grid info', // Name
			$widget_ops,
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 **/
	public function widget( $args, $instance ) {
		extract( $args );
		$instance['title'] = apply_filters( 'widget_title', $instance['title'] );
		$content           = w4os_gridinfo_html( $instance, $args );
		if ( empty( $content ) ) {
			return;
		}
		echo $before_widget . w4os_gridinfo_html( $instance, $args ) . $after_widget;
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 **/
	public function form( $instance ) {
		if ( isset( $instance['title'] ) ) {
			$title = $instance['title'];
		} else {
			$title = __( 'Grid info', 'w4os' );
		}
		?>
	<p>
	  <label for="<?php echo $this->get_field_name( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
	  <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
	</p>
		<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance          = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;
	}
}

class W4OS_GridStatus_Widget extends WP_Widget {

	public function __construct() {
		$widget_ops = array(
			'description' => __( 'Grid status', 'w4os' ),
		);
		parent::__construct(
			'W4OS_GridStatus_Widget', // Base ID
			'W4OS Grid status', // Name
			$widget_ops,
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 **/
	public function widget( $args, $instance ) {
		extract( $args );
		$instance['title'] = apply_filters( 'widget_title', $instance['title'] );
		$content           = w4os_gridstatus_html( $instance, $args );
		if ( empty( $content ) ) {
			return;
		}
		echo $before_widget . w4os_gridstatus_html( $instance, $args ) . $after_widget;
	}
	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 **/
	public function form( $instance ) {
		if ( isset( $instance['title'] ) ) {
			$title = $instance['title'];
		} else {
			$title = __( 'Grid status', 'w4os' );
		}
		?>
	<p>
	  <label for="<?php echo $this->get_field_name( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
	  <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
	</p>
		<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance          = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;
	}
}

add_action( 'widgets_init', 'w4os_register_widgets' );
function w4os_register_widgets() {
	register_widget( 'W4OS_GridInfo_Widget' );
	register_widget( 'W4OS_GridStatus_Widget' );
}
