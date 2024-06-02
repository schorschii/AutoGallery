<?php
session_start();

if(!empty(PASSWORD) && empty($_SESSION['username'])) {
	header('Location: login.php');
	die('Login required');
}


function startsWith($haystack, $needle) {
	$length = strlen($needle);
	return substr($haystack, 0, $length) === $needle;
}
