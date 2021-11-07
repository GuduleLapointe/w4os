<?php if ( ! defined( 'WPINC' ) ) die;

require_once __DIR__ . '/settings.php';
if($pagenow == "index.php") require_once __DIR__ .'/dashboard.php';
