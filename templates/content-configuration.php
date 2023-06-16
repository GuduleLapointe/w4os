<?php
$gridname    = ( ! empty( get_option( 'w4os_grid_name' ) ) ) ? get_option( 'w4os_grid_name' ) : __( 'this grid', 'w4os' );
$loginuri    = w4os_grid_login_uri();
$viewers     = array(
	'Firestorm'      => 'https://www.firestormviewer.org/os-operating-system/',
	'Cool VL Viewer' => 'http://sldev.free.fr/cool_vl_viewer.php#DOWNLOAD',
	'SceneGate'      => 'https://downloads.infinitemetaverse.org/index.php/downloads',
);
$viewerslist = '<ul>';
foreach ( $viewers as $key => $value ) {
	$viewerslist .= sprintf( '<li><a href="%1$s" target=_blank>%2$s</a></li>', $value, $key );
}
$viewerslist      .= '</ul>';
$compatibleviewers = 'http://opensimulator.org/wiki/Compatible_Viewers';

$page_content = ( empty( $page_content ) ) ? '' : $page_content;

$page_content .= '
<div class="configuration">
<h2>' . __( 'Viewer configuration', 'w4os' ) . '</h3>
<ol>
  <li>
    ' . __( 'Install the viewer and open it.', 'w4os' ) . '
    <p class=description>
    ' . sprintf(
		__( 'You can use <a href="%1$s" target=_blank>any compatible viewer</a> to access %2$s.', 'w4os' ),
		$compatibleviewers,
		$gridname,
	) . '
    <p>
    ' . $viewerslist . '
  </li><li>' . sprintf( __( 'Add %1$s to your viewer', 'w4os' ), $gridname, ) . '
    <p class=description>' . __( 'Instructions may vary depending on the viewer', 'w4os' ) . '</p>
    <ul>
      <li>' . __( 'Select "Preferences" under the "Viewer" menu (or type Ctrl-P)', 'w4os' ) . '</li>
      <li>' . __( 'Select "OpenSim", then "Grid Manager" tab', 'w4os' ) . '</li>
      <li>' . __( 'Under "Add new grid", enter', 'w4os' ) . '<br>
      <code>' . w4os_grid_login_uri() . '</code></li>
      <li>' . __( 'Click the "Apply" button', 'w4os' ) . '</li>
    </ul>
  </li>
  <li>' . __( 'Log in', 'w4os' ) . '
    <ul>
      <li>' . sprintf( __( 'Make sure %1$s is selected in the Grid menu', 'w4os' ), $gridname ) . '</li>
      <li>' . __( 'Enter your avatar’s first and last name in the "Username" box and your password in the "Password" box', 'w4os' ) . '</li>
      <li>' . __( 'Click "Log In"', 'w4os' ) . '</li>
    </ul>
  </li>
</ol>
</div>
';
