<?php

function galleryZip($directory, $photos) {
	$manifest = [];
	foreach($photos as $photo) {
		$file = $directory.'/'.$photo['filename'];
		$manifest[] = [$photo['filename'], filesize($file), filemtime($file)];
	}
	$fingerprint = hash('sha256', serialize($manifest));
	$cache = $directory.'/.download.zip';
	$zip = new ZipArchive();
	if(is_file($cache) && $zip->open($cache) === true) {
		$matches = $zip->getArchiveComment() === $fingerprint;
		$zip->close();
		if($matches) return $cache;
	}

	// Build separately so readers never receive a partially written archive.
	$temp = @tempnam($directory, '.zip-');
	if($temp === false) $temp = tempnam(sys_get_temp_dir(), 'gallery-zip-');
	if($temp === false) throw new Exception('Unable to create temporary ZIP file');
	register_shutdown_function(function() use ($temp) {
		if(is_file($temp)) unlink($temp);
	});
	if($zip->open($temp, ZipArchive::OVERWRITE) !== true)
		throw new Exception('Unable to open temporary ZIP file');
	foreach($photos as $photo) {
		if(!$zip->addFile($directory.'/'.$photo['filename'], $photo['filename'])
		|| !$zip->setCompressionName($photo['filename'], ZipArchive::CM_STORE)) {
			$zip->close();
			throw new Exception('Unable to add file to ZIP archive');
		}
	}
	$zip->setArchiveComment($fingerprint);
	if(!$zip->close()) throw new Exception('Unable to finish ZIP archive');
	// ZipArchive removes empty archives; produce a valid empty ZIP for empty folders.
	if(!$photos && !is_file($temp)) file_put_contents($temp, "PK\x05\x06".str_repeat("\0", 16).pack('v', strlen($fingerprint)).$fingerprint);
	@chmod($temp, 0644);
	if(@rename($temp, $cache)) return $cache;
	// Preserve downloads when the media folder is not writable.
	return $temp;
}
