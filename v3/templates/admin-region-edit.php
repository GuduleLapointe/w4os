<?php
if ( is_object( $region ) ) {
	$title = sprintf(
		__( 'Region: %s', 'w4os' ),
		esc_html( $region->get_name() ),
	);

	printf( '<h1 class="wp-heading-inline">%s</h1>', $title );

	$actions = $region->get_actions( 'page-title' );
	unset( $actions['edit'] );
	unset( $actions['teleport'] );
	echo ' ' . implode( ' ', $actions );
	// echo '<pre>' . print_r($actions, true) . '</pre>';

	$map_uuid = $region->item->regionMapTexture;
	$size     = $region->item->sizeX;
	// 1 => __( 'Default Region', 'w4os' ),
	// 4 => __( 'Region Online', 'w4os' ),
	// 8 => __( 'No Direct Login', 'w4os' ),
	// 16 => __( 'Persistent', 'w4os' ),
	// 32 => __( 'Locked Out', 'w4os' ),
	// 64 => __( 'No Move', 'w4os' ),
	// 128 => __( 'Reservation', 'w4os' ),
	// 256 => __( 'Authenticate', 'w4os' ),
	// 512 => __( 'Hyperlink', 'w4os' ),
	// 1024 => __( 'Default HG Region', 'w4os' ),
	$check_flags = $region->item->flags;
	// $check_flags = $region->item->flags & ( 1 + 8 + 128 + 256 + 512 + 1024); // All but region online
	$check_flags = $check_flags & ~ 4; // All but region online and persistent;

	$data = array_filter(
		array(
			__( 'Status', 'w4os' )   => $region->format_region_status( $region->item )
			. sprintf( ' (%s %s)', __( 'last seen', 'w4os' ), $region->last_seen( $region->item ) )
			. $region->format_flags( $check_flags ),
			__( 'Owner', 'w4os' )    => $region->owner_name( $region->item ),
			__( 'Teleport', 'w4os' ) => $region->get_tp_link(),
			__( 'Map', 'w4os' )      => ( ! W4OS3::empty( $map_uuid ) ) ? sprintf(
				'<img src="%1$s" class="asset asset-%3$d region-map" alt="%2$s" loading="lazy" width="%3$d" height="%4$d">',
				w4os_get_asset_url( $map_uuid ),
				sprintf( __( '%s region map', 'w4os' ), esc_attr( $title ) ),
				$region->item->sizeX ?? 256,
				$region->item->sizeY ?? 256,
			) : '',
			__( 'Size', 'w4os' )     => $region->format_region_size( $region->item ),
		// __('Last Seen', 'w4os' ) => $region->last_seen( $region->item ),
		)
	);

	if ( isset( $_GET['action'] ) && $_GET['action'] == 'edit' ) {
		$menu_slug    = $_GET['page'];
		$option_name  = $menu_slug;
		$option_group = $menu_slug . '_group';

		$settings = apply_filters( 'w4os_settings', array() );
		// $page_tabs       = $settings[ $menu_slug ]['tabs'] ?? array();
		// $page_tabs['edit'] = array(
		// 'title' => __( 'Edit Region', 'w4os' ),
		// 'url'   => '#',
		// );
		$selected_tab = 'edit';
	}
	$current_section = $option_group . '_section_' . $selected_tab;

	if ( ! empty( $data ) ) {
		echo '<div class="w4os-region-data">';
		echo '<div class="column">';
		printf(
			'<form action="options.php" method="post">
            <input type="hidden" name="%s[%s][prevent-empty-array]" value="1">',
			esc_attr( $option_name ),
			esc_attr( $selected_tab ),
		);
		settings_fields( $option_group );
		do_settings_sections( $menu_slug );
		submit_button();
		echo '</form>';

		echo '<table class="form-table">';
		foreach ( $data as $key => $value ) {
			if ( ! is_string( $value ) ) {
				// $value = "$key is not a string " . print_r( $value, true);
				continue;
			}
			printf( '<tr><th>%s</th><td>%s</td></tr>', $key, $value );
		}
		echo '</table>';
		echo '</div><div class="column w4os-parcels-data">';
		echo '<h2>' . __( 'Parcels', 'w4os' ) . '</h2>';
		echo $region->get_parcels();
		echo '</div></div>';
	}
}
