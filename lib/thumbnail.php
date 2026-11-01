<?php

// Return a cached JPEG path, or null so callers can use the original image.
function createImageThumbnail($file, $thumbnail = null) {
	if(!extension_loaded('gd') || !function_exists('imagejpeg')) return null;
	$quality = max(0, min(100, (int)(defined('IMAGE_THUMBNAIL_QUALITY') ? IMAGE_THUMBNAIL_QUALITY : 80)));
	$maxResolution = max(1, (int)(defined('IMAGE_THUMBNAIL_MAX_RESOLUTION') ? IMAGE_THUMBNAIL_MAX_RESOLUTION : 1920));
	$size = @getimagesize($file);
	if(!$size) return null;
	// Leave animated GIFs and unsupported formats untouched.
	$decoders = [
		IMAGETYPE_JPEG => 'imagecreatefromjpeg',
		IMAGETYPE_PNG => 'imagecreatefrompng',
		IMAGETYPE_WEBP => 'imagecreatefromwebp'
	];
	if(!isset($decoders[$size[2]]) || !function_exists($decoders[$size[2]])) return null;
	// Animated WebP images must retain their animation as well.
	if($size[2] == IMAGETYPE_WEBP) {
		$handle = @fopen($file, 'rb');
		if(!$handle) return null;
		$header = fread($handle, 21);
		fclose($handle);
		if(strlen($header) >= 21 && substr($header, 12, 4) == 'VP8X' && (ord($header[20]) & 2)) return null;
	}
	if($thumbnail === null) $thumbnail = dirname($file).'/.thumbs/'.basename($file).'.jpg';
	$directory = dirname($thumbnail);
	if(is_file($thumbnail) && filesize($thumbnail) > 0) return $thumbnail;
	if(!is_dir($directory) && !mkdir($directory, 0755) && !is_dir($directory)) return null;

	$source = null;
	$target = null;
	$temp = null;
	try {
		$source = @$decoders[$size[2]]($file);
		if(!$source) return null;
		// Apply camera orientation before discarding EXIF metadata in the JPEG output.
		if($size[2] == IMAGETYPE_JPEG && function_exists('exif_read_data')) {
			$exif = @exif_read_data($file);
			$orientation = (int)($exif['Orientation'] ?? 1);
			if(in_array($orientation, [2, 5, 7])) imageflip($source, IMG_FLIP_HORIZONTAL);
			if($orientation == 4) imageflip($source, IMG_FLIP_VERTICAL);
			$angles = [3 => 180, 5 => 90, 6 => -90, 7 => -90, 8 => 90];
			if(isset($angles[$orientation])) {
				$rotated = imagerotate($source, $angles[$orientation], 0);
				if(!$rotated) return null;
				imagedestroy($source);
				$source = $rotated;
			}
		}
		$width = imagesx($source);
		$height = imagesy($source);
		$scale = min(1, $maxResolution / max($width, $height));
		$targetWidth = max(1, (int)round($width * $scale));
		$targetHeight = max(1, (int)round($height * $scale));
		$target = imagecreatetruecolor($targetWidth, $targetHeight);
		if(!$target) return null;
		// JPEG has no alpha channel: composite transparent pixels on white.
		imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
		if(!imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height)) return null;
		// Publish atomically so simultaneous page loads never serve a partial JPEG.
		$temp = @tempnam($directory, '.tmp-');
		if(!$temp || !@imagejpeg($target, $temp, $quality) || !filesize($temp)) return null;
		// tempnam creates private files; make the finished thumbnail readable by everyone.
		if(!@chmod($temp, 0644)) return null;
		if(!@rename($temp, $thumbnail)) return null;
		return $thumbnail;
	} catch(Throwable $error) {
		return null;
	} finally {
		if($source) imagedestroy($source);
		if($target) imagedestroy($target);
		if($temp && is_file($temp)) @unlink($temp);
	}
}

// Extract a frame one-third into the video, using the image preview JPEG settings.
function createVideoThumbnail($file) {
	$directory = dirname($file).'/.thumbs';
	// Use a new cache name so older first-frame thumbnails are regenerated.
	$thumbnail = $directory.'/'.basename($file).'.third.jpg';
	if(is_file($thumbnail) && filesize($thumbnail) > 0) return $thumbnail;
	if(!function_exists('exec') || !extension_loaded('gd')) return null;
	if(!is_dir($directory) && !@mkdir($directory, 0755) && !is_dir($directory)) return null;
	$temp = @tempnam($directory, '.video-');
	if(!$temp) return null;
	try {
		$input = escapeshellarg(realpath($file));
		exec('ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 '.$input.' 2>/dev/null', $durationOutput, $probeStatus);
		$duration = trim(implode("\n", $durationOutput));
		if($probeStatus !== 0 || !is_numeric($duration) || !is_finite((float)$duration) || (float)$duration <= 0) return null;
		$timestamp = number_format((float)$duration / 3, 6, '.', '');
		$maxResolution = max(1, (int)(defined('IMAGE_THUMBNAIL_MAX_RESOLUTION') ? IMAGE_THUMBNAIL_MAX_RESOLUTION : 1920));
		$filter = "scale=w='min(iw,$maxResolution)':h='min(ih,$maxResolution)':force_original_aspect_ratio=decrease";
		$command = 'ffmpeg -nostdin -hide_banner -loglevel error -y -ss '.escapeshellarg($timestamp).' -i '.$input
			.' -map 0:v:0 -frames:v 1 -vf '.escapeshellarg($filter)
			.' -c:v png -f image2 -update 1 '.escapeshellarg($temp).' 2>/dev/null';
		exec($command, $output, $status);
		if($status !== 0) return null;
		return createImageThumbnail($temp, $thumbnail);
	} catch(Throwable $error) {
		return null;
	} finally {
		if(is_file($temp)) @unlink($temp);
	}
}
