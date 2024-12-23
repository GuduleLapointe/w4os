<?php
if( is_object( $region ) ) {
    $title = $region->get_name();

    printf( '<h1 class="wp-heading-inline">%s</h1>', $title );

    $actions = $region->get_actions( 'page-title' );
    unset($actions['edit']);
    unset($actions['teleport']);
    echo " " . implode(' ', $actions);
    // echo '<pre>' . print_r($actions, true) . '</pre>';

    $map_uuid = $region->item->regionMapTexture;
    $size = $region->item->sizeX;
    $data = array_filter( array(
        __('Status', 'w4os' ) => $region->format_region_status( $region->item )
        . sprintf( ' (%s %s)', __('last seen', 'w4os' ), $region->last_seen( $region->item ) ),
        __('Owner', 'w4os' ) => $region->owner_name( $region->item ),
        __('Teleport', 'w4os' ) => $region->get_tp_link(),
        __('Map', 'w4os' ) => ( ! W4OS3::empty( $map_uuid ) ) ? sprintf(
            '<img src="%1$s" class="asset asset-%3$d region-map" alt="%2$s" loading="lazy" width="%3$d" height="%4$d">',
            w4os_get_asset_url( $map_uuid ),
            sprintf( __( '%s region map', 'w4os' ), esc_attr( $title ) ),
            $region->item->sizeX ?? 256,
            $region->item->sizeY ?? 256,
        ) : '',
        __('Size', 'w4os' ) => $region->format_region_size( $region->item ),
        // __('Last Seen', 'w4os' ) => $region->last_seen( $region->item ),
        __('Parcels', 'w4os' ) => $region->get_parcels(),
    ) );

    if ( ! empty( $data )) {
        echo '<table class="form-table">';
        foreach( $data as $key => $value ) {
            printf( '<tr><th>%s</th><td>%s</td></tr>', $key, $value );
        }
        echo '</table>';
    }
}
