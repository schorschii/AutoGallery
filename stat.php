<?php

function writeStat($url) {
	if(defined('DB_TYPE') && defined('DB_HOST') && defined('DB_NAME')
	&& !empty(DB_TYPE) && !empty(DB_HOST) && !empty(DB_NAME)) {
		$dbh = new PDO(
			DB_TYPE.':host='.DB_HOST.';dbname='.DB_NAME.';',
			DB_USER, DB_PASS,
			array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4')
		);
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$stmt = $dbh->prepare(
			'INSERT INTO stat (url, address, useragent, referer)
			VALUES (:url, :address, :useragent, :referer)'
		);
		$stmt->execute([
			':url' => $url,
			':address' => $_SERVER['REMOTE_ADDR'] ?? '',
			':useragent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
			':referer' => $_SERVER['HTTP_REFERER'] ?? '',
		]);
	}
}
