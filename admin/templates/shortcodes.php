<h1><?php _e( 'Available Shortcodes', 'w4os' ); ?></h1>

<p class="description">
  <?php _e( 'Shortcodes can be included in any page or articles.' ); ?>
  <?php _e( 'They are also available as configurable Gutenberg blocks and Divi modules.' ); ?>
</p>

<div class=shortcodes>
  <table class="w4os-table shortcodes">
	<tr><th>
	  <code>[grid-info]</code>
	  <p><?php _e( 'General information (grid name and login uri)', 'w4os' ); ?></p>
	</th><td>
	  <?php echo w4os_grid_info_shortcode(); ?>
  </td><td>
	  <?php
		$parameters = array(
			'title'    => __( 'Bloc title', 'w4os' ),
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
	</td></tr>
	<tr><th>
	  <code>[grid-status]</code>
	  <p><?php _e( 'Online users, regions, etc.', 'w4os' ); ?></p>
	</th><td>
	  <?php echo w4os_grid_status_shortcode(); ?>
  </td><td>
	  <?php
		$parameters = array(
			'title'    => __( 'Bloc title', 'w4os' ),
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
	</td></tr>
	<tr><th>
	  <code>[avatar-profile]</code>
	  <p><?php _e( 'Avatar Profile if user is connected and has an avatar, avatar registration form otherwise', 'w4os' ); ?>
		<?php echo W4OS::sprintf_safe( __( '(formerly %s)', 'w4os' ), '<code>[gridprofile]</code>' ); ?>
	  </p>
	</th><td>
	  <?php echo do_shortcode( '[avatar-profile]' ); ?>
  </td><td>
	  <?php
		$parameters = array(
			'title'    => __( 'Bloc title', 'w4os' ),
			'level=h3' => __( 'Title level (h1 to h6)', 'w4os' ),
			'mini=1'   => __( 'Only show name and picture', 'w4os' ),
		);
		$options    = '<p>' . __( 'Options', 'w4os' ) . '</p>';
		$options   .= '<table>';
		foreach ( $parameters as $key => $value ) {
			$options .= '<tr><th>' . $key . '</th><td>' . $value . '</td></tr>';
		}
		$options .= '</table>';
		echo $options;
		?>
	</td></tr>
	<tr><th>
	  <code>[popular-places]</code>
	  <p>
		<?php _e( 'Most visited regions in your grid.', 'w4os' ); ?>
	  </p>
	</th><td>
	  <?php echo do_shortcode( '[popular-places max=3]' ); ?>
	</td><td>
	  <?php
		$parameters = array(
			'title'             => __( 'Bloc title', 'w4os' ),
			'level=h3'          => __( 'Title level (h1 to h6)', 'w4os' ),
			'max=n'             => __( 'Show maximum n results', 'w4os' ),
			'include-hypergrid' => __( 'Include results from other grids', 'w4os' ),
			'include-landsales' => __( 'Include land for sale', 'w4os' ),
		);
		$options    = '<p>' . __( 'Options', 'w4os' ) . '</p>';
		$options   .= '<table>';
		foreach ( $parameters as $key => $value ) {
			$options .= '<tr><th>' . $key . '</th><td>' . $value . '</td></tr>';
		}
		$options .= '</table>';
		$options .= '<ul class="description"><li>' . join(
			'</li><li>',
			array(
				W4OS::sprintf_safe(
					__( 'The options %1$s and %2$s are only provided to allow reverting to previous behaviour but in most cases they should not be enabled.', 'w4os' ),
					'<code>include-hypergrid</code>',
					'<code>include-landsales</code>',
				),
				__( 'Popular places are essentially intended for splash pages, where hypergrid links would not work.', 'w4os' ),
				__( 'Land sales usually generate additional traffic, which could make the search engine ranking unreliable for these regions.', 'w4os' ),
			)
		) . '</li></ul>';
		echo $options;
		?>
	</td>
	</tr>
  </table>
</div>
