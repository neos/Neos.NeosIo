<?php
if (FLOW_PATH_ROOT !== '/Users/jensen/Sites/Neos.NeosIo/' || !file_exists('/Users/jensen/Sites/Neos.NeosIo/Data/Temporary/Development/Configuration/DevelopmentConfigurations.php')) {
	unlink(__FILE__);
	return array();
}
return require '/Users/jensen/Sites/Neos.NeosIo/Data/Temporary/Development/Configuration/DevelopmentConfigurations.php';