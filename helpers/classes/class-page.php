<?php

class OpenSim_Page {
    protected $page_title;
    protected $content;

    public function __construct( $page_title = null, $content = null, $args = array() ) {
        $this->page_title = $page_title;
        $this->content = $content;
    }

    public function get_page_title() {
        return $this->page_title;
    }

    public function get_content() {
        return $this->content;
    }

    public function get_sidebar_left() {
        return '';
    }

    public function get_sidebar( $id = 'right' ) {
        if( empty( $id ) ) {
            return '';
        }
        $html = '';
        switch( $id ) {
            case 'left':
                // $html = OpenSim_Grid::grid_stats_card();
                break;
                case 'right':
                $html .= OpenSim_Grid::grid_info_card();
                $html .= OpenSim_Grid::grid_stats_card();
                break;
            default:
                break;
        }

        $class="flex";
        // if( ! empty( $html ) ) {
        //     $html = sprintf(
        //         '<div id="sidebar-%s" class="sidebar sidebar-%s flex-row">%s</div>',
        //         $id,
        //         $id,
        //         $html
        //     );
        // }
        return $html;
    }
}
