<?php
require_once('conf.php');
require_once('session.php');
session_write_close();

const CACHE_TIME = 60*60*24;

$file = null;

if(!empty($_GET['file']) && is_file(ROOT_DIR.'/'.$_GET['file'])
&& startsWith(realpath(ROOT_DIR.'/'.$_GET['file']), realpath(ROOT_DIR))) {
	$file = ROOT_DIR.'/'.$_GET['file'];
}
if($file) {
	header('Cache-Control: private, max-age=' . CACHE_TIME . ', immutable');
	header('Expires: ' . gmdate('D, d M Y H:i:s', time()+CACHE_TIME) . ' GMT');
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($file)) . ' GMT');
	header('Content-Type: ' . mime_content_type($file));
	header('Content-Length: ' . filesize($file));
	readfile($file);
} else {
	header('HTTP/1.1 404 NOT FOUND');
	die('nope.');
}
function startsWith( $haystack, $needle ) {
	$length = strlen( $needle );
	return substr( $haystack, 0, $length ) === $needle;
}
