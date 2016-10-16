<?php
function filetojson($file)
{
	return json_decode( file_get_contents($file) );
}
?>