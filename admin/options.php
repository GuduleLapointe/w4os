<div class="wrap">
	<!-- <h1><?php esc_html( get_admin_page_title() ); ?></h1> -->
	<h1>OpenSimulator</h1>	<?php screen_icon(); ?>
	<form method="post" action="options.php">
		<?php settings_fields( 'opensim_options_group' ); ?>
		<table class=form-table>
			<tr><th colspan=2>
				<h2><?php _e("Grid");?></h2>
			</th></tr>
			<tr valign="top">
				<th scope="row"><label for="opensim_grid_name"><?php _e("Grid name");?></label></th>
				<td><input type="text" class=regular-text id="opensim_grid_name" name="opensim_grid_name" value="<?php echo get_option('opensim_grid_name'); ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="opensim_login_uri"><?php _e("Login URI");?></label></th>
				<td><input type="text" class=regular-text id="opensim_login_uri" name="opensim_login_uri" value="<?php echo get_option('opensim_login_uri'); ?>" /></td>
			</tr>
			<tr><th colspan=2>
				<h2><?php _e("Database connection");?></h2>
			</th></tr>
			<tr valign="top">
				<th scope="row"><label for="opensim_db_host"><?php _e("Host");?></label></th>
				<td><input type="text" class=regular-text id="opensim_db_host" name="opensim_db_host" value="<?php echo get_option('opensim_db_host'); ?>" /></td>
			</tr>
			<tr valign="top">
			  <th scope="row"><label for="opensim_db_database"><?php _e("Database");?></label></th>
			  <td><input type="text" class=regular-text id="opensim_db_database" name="opensim_db_database" value="<?php echo get_option('opensim_db_database'); ?>" /></td>
			</tr>
			<tr valign="top">
			  <th scope="row"><label for="opensim_db_user"><?php _e("User");?></label></th>
			  <td><input type="text" class=regular-text id="opensim_db_user" name="opensim_db_user" value="<?php echo get_option('opensim_db_user'); ?>" /></td>
			</tr>
			<tr valign="top">
			  <th scope="row"><label for="opensim_db_pass"><?php _e("Password");?></label></th>
			  <td><input type="password" class=regular-text id="opensim_db_pass" name="opensim_db_pass" value="<?php echo get_option('opensim_db_pass'); ?>" /></td>
			</tr>
		</table>
		<?php  submit_button(); ?>
	</form>
</div>
