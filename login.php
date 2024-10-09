<?php
require_once('conf.php');

session_start();

if(empty(PASSWORD) || !empty($_SESSION['username'])) {
	header('Location: index.php');
	die('Already logged in');
}

$info = null;
if(!empty($_POST['password'])) {
	if((is_string(PASSWORD) && $_POST['password'] === PASSWORD)
	|| (is_array(PASSWORD) && in_array($_POST['password'], PASSWORD, true))) {
		$_SESSION['username'] = 'anonymous';
		header('Location: index.php');
		die('Login OK');
	} else {
		$info = 'Ungültiges Kennwort';
	}
}
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset='utf-8'>
		<title><?php echo htmlspecialchars(TITLE); ?> - Login</title>
		<link rel='stylesheet' href='css/main.css' type='text/css' />
		<meta name='viewport' content='width=device-width, initial-scale=1.0'>
	</head>
	<body>
		<h1><?php echo htmlspecialchars(TITLE); ?></h1>

		<form id='frmLogin' method='POST'>
			<?php if($info) { ?>
				<p><?php echo htmlspecialchars($info); ?></p>
			<?php } ?>
			<input type='password' name='password' placeholder='Kennwort' autofocus='true'>
			<button>Login</button>
		</form>

		<div id='footer'>
			<a target='_blank' href='https://github.com/schorschii/autogallery'>AutoGallery</a>
			© <a target='_blank' href='https://georg-sieber.de'>Georg Sieber</a>
		</div>
	</body>
</html>
