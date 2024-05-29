<?php
session_start();

if(!empty(PASSWORD) && empty($_SESSION['username'])) {
	header('Location: login.php');
	die('Login required');
}
