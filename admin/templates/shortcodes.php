<h1><?php _e( 'Available Shortcodes', 'w4os' ); ?></h1>
<div class=shortcodes>
  <table class="w4os-table shortcodes">
	<tr><th>
	  <code>[grid-info]</code>
	  <p><?php _e( 'General information (grid name and login uri)', 'w4os' ); ?></p>
	</th><td>
	  <?php echo w4os_gridinfo_shortcode(); ?>
	</td></tr>
	<tr><th>
	  <code>[grid-status]</code>
	  <p><?php _e( 'Online users, regions, etc.', 'w4os' ); ?></p>
	</th><td>
	  <?php echo w4os_gridstatus_shortcode(); ?>
	</td></tr>
	<tr><th>
	  <code>[avatar-profile]</code>
	  <p><?php _e( 'Grid profile if user is connected and has an avatar, avatar registration form otherwise', 'w4os' ); ?>
		<?php echo sprintf( __( '(formerly %s)', 'w4os' ), '<code>[gridprofile]</code>' ); ?>
	  </p>
	</th><td>
	  <?php echo do_shortcode( '[avatar-profile]' ); ?>
	</td></tr>
	<tr><th>
	  <code>[popular-places]</code>
	  <p>
		<?php _e( 'Most visited regions in your grid.', 'w4os' ); ?>
	  </p>
	</th><td colspan="2">
	  <?php echo do_shortcode( '[popular-places]' ); ?>
	</td><td>
	  <?php
		$parameters = array(
			'title' => __( 'Bloc title', 'w4os' ),
			'max=n' => __( 'Show maximum n results', 'w4os' ),
			'level=h3' => __( 'Title level (h1 to h6)', 'w4os' ),
		);
		$options    = '<p>' . __( 'Options', 'w4os' ) . '</p>';
		$options   .= '<table>';
		foreach ( $parameters as $key => $value ) {
			$options .= '<tr><th>' . $key . '</th><td>' . $value . '</td></tr>';
		}
		$options .= '</table>';
		echo $options;
		?>
	</tr>
  </table>
</div>
