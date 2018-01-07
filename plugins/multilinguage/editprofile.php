<?php
function makeLangList()
{
	$data = array();
	$dir = @opendir("lib/lang");
	while ($file = readdir($dir))
	{
		//print $file;
		if (endsWith($file, "_lang.php"))
		{
			$file = substr($file, 0, strlen($file)-9);
			$data[$file] = $file;
		}
	}
	$data["en_US"] = "en_US";
	$data["-default"] = "Board default";
	closedir($dir);
	ksort($data);
	return $data;
}

AddField('general', 'presentation', 'linguage', __('Language'), 'radiogroup', array('options'=>makeLangList()));
