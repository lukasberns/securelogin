<?php

$config_file = $securelogin_dir.'/securelogin.config.php';
if (!file_exists($config_file)) {
	$config_file = $securelogin_dir.'/../securelogin.config.php';
	if (!file_exists($config_file)) {
		die('Please edit the securelogin.config.sample.php file and rename it to securelogin.config.php in the '.$securelogin_dir.' directory. You can also put it into one directory higher.');
	}
}
require($config_file);
