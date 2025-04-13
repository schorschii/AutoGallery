<?php

function existsCommand($cmd) {
	$return = shell_exec(sprintf('which %s', escapeshellarg($cmd)));
	return !empty($return);
}

function divmod($val1, $val2) {
	return [ intval($val1 / $val2), $val1 % $val2 ];
}

function formatSeconds($seconds) {
	$decimal_split = explode('.', strval($seconds));
	$milliSeconds = substr($decimal_split[1], 0, 3);
	$totalSeconds = intval($decimal_split[0]);
	list($m, $s) = divmod($totalSeconds, 60);
	list($h, $m) = divmod($m, 60);
	return str_pad($h,1,'0',STR_PAD_LEFT).':'.str_pad($m,2,'0',STR_PAD_LEFT).':'.str_pad($s,2,'0',STR_PAD_LEFT).'.'.$milliSeconds;
}

function extractSubtitlesVtt($videoFile) {
	if(!existsCommand('ffprobe')) return [];
	$return = shell_exec('ffprobe -show_chapters -print_format json '.escapeshellarg($videoFile));
	$vttChapters = [];
	$chapterData = json_decode($return, true)['chapters'];
	if(!empty($chapterData)) {
		$vttContent = 'WEBVTT'."\n\n";
		foreach($chapterData as $chapter) {
			$start = formatSeconds($chapter['start_time']);
			$end = formatSeconds($chapter['end_time']);
			$vttContent .= $start.' --> '.$end."\n";
			$vttContent .= $chapter['tags']['title']."\n\n";
		}
		$vttChapters['chapters'] = $vttContent;
	}
	return $vttChapters;
}
