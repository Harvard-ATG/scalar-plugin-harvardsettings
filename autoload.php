<?php
spl_autoload_register(function ($class_name) {
    $basedir = dirname(__FILE__);
		$class_map = array(
			"Harvardsettings" => "$basedir/harvardsettings_pi.php",
		);
    if(isset($class_map[$class_name])) {
        require_once($class_map[$class_name]);
    }
});
