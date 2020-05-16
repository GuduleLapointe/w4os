<?php
// Use GitHub Updater plugin instead, kept for reference
//
// $repo_api="https://git.magiiic.com/api/v4/projects/38";
//
// $version=get_plugin_data(dirname(dirname(__FILE__)) . "/w4os.php")['Version'];
// $version_info=__("Version:") . " $version";
//
// $repo = wp_remote_get( "$repo_api", array(
// 	'timeout' => 10,
// 	'headers' => array(
// 		'Accept' => 'application/json'
// 	) )
// );
// $repo_url = json_decode( $repo['body'] )->web_url;
// $repo_path = json_decode( $repo['body'] )->path;
// $tags = wp_remote_get( "$repo_api/repository/tags", array(
// 	'timeout' => 10,
// 	'headers' => array(
// 		'Accept' => 'application/json'
// 	) )
// );
// $tag_name = json_decode( $tags['body'] )[0]->name;
// $remote_version = preg_replace("/^v/", "", $tag_name);
// if( $remote_version && version_compare($version, $remote_version, "<"))
// {
// 	$updateavailable=true;
// 	$download_link = "$repo_url/-/archive/$tag_name/$repo_path-$tag_name.zip";
// 	$version_info="$version_info (" . __("update availailable:") . " $remote_version, <a href='$download_link'>" . __("download zip") . "</a> " . __("or") . " <a href='$repo_url' target=_blank>" . __("pull from git repository") . "</a>)";
// }
