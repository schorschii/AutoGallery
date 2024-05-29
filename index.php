<?php
require_once('conf.php');
require_once('session.php');

$photos = [];
$dirs = [];
$folderTitle = TITLE;
$searchPath = ROOT_DIR;

if(!empty($_GET['dir']) && is_dir(ROOT_DIR.'/'.$_GET['dir'])
&& startsWith(realpath(ROOT_DIR.'/'.$_GET['dir']), realpath(ROOT_DIR))) {
	$folderTitle = basename($_GET['dir']);
	$searchPath = ROOT_DIR.'/'.$_GET['dir'];
}
$files = scandir($searchPath);
$ignoreDirs = [];
foreach($files as $file) {
	if(substr($file, 0, 1) == '.') continue;
	if(!is_file($searchPath.'/'.$file)) continue;
	$photo = [
		'type' => mime_content_type($searchPath.'/'.$file),
		'path' => substr($searchPath.'/'.$file, strlen(ROOT_DIR)+1),
		'title' => basename($file),
		'subtitle' => '',
		'tracks' => [],
	];
	if(startsWith($photo['type'], 'image/')) {
		$size = getimagesize($searchPath.'/'.$file, $info);
		if(isset($info['APP13'])) { // read IPTC tags
			$iptcData = iptcparse($info['APP13']);
			if(PHOTO_TITLE && isset($iptcData[PHOTO_TITLE])) {
				$photo['title'] = trim($iptcData[PHOTO_TITLE][0]);
			}
			if((PHOTO_SUBTITLE && isset($iptcData[PHOTO_SUBTITLE]))) {
				$photo['subtitle'] = trim($iptcData[PHOTO_SUBTITLE][0]);
			}
		}
	}
	if(startsWith($photo['type'], 'video/')) {
		foreach(glob($searchPath.'/'.pathinfo($file, PATHINFO_FILENAME).'/*.vtt') as $vttfile) {
			$splitter = explode('.', basename($vttfile));
			if(count($splitter) != 3) continue;
			$photo['tracks'][] = [
				'path' => substr($vttfile, strlen(ROOT_DIR)+1),
				'kind' => $splitter[0],
				'srclang' => $splitter[1],
			];
			$ignoreDirs[] = $searchPath.'/'.pathinfo($file, PATHINFO_FILENAME);
		}
	}
	$photos[] = $photo;
}
foreach($files as $file) {
	if(substr($file, 0, 1) == '.') continue;
	if(!is_dir($searchPath.'/'.$file)) continue;
	if(in_array($searchPath.'/'.$file, $ignoreDirs)) continue;
	$dirs[] = [
		'path' => substr($searchPath.'/'.$file, strlen(ROOT_DIR)+1),
		'title' => $file,
	];
}
function startsWith( $haystack, $needle ) {
	$length = strlen( $needle );
	return substr( $haystack, 0, $length ) === $needle;
}
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset='utf-8'>
		<title><?php echo htmlspecialchars(TITLE); ?></title>
		<link rel='stylesheet' href='css/main.css' type='text/css' />
		<script src='js/3dfx.js'></script>
		<script src='js/lightbox.js'></script>
		<meta name='viewport' content='width=device-width, initial-scale=1.0'>
	</head>
	<body>
		<h1><?php echo htmlspecialchars($folderTitle); ?></h1>

		<div id='gallery' class='photos'>
			<?php foreach($dirs as $dir) { ?>
				<a class='dir-item' href='?dir=<?php echo urlencode($dir['path']); ?>'>
					<img src='img/folder.svg'>
					<div>
						<div><?php echo htmlspecialchars($dir['title']); ?></div>
					</div>
				</a>
			<?php } ?>
			<?php foreach($photos as $photo) { ?>
				<?php if(startsWith($photo['type'], 'image/')) { ?>
					<a class='photo-item' href='media.php?file=<?php echo urlencode($photo['path']); ?>'>
						<img loading='lazy' src='media.php?file=<?php echo urlencode($photo['path']); ?>' media_title='<?php echo htmlspecialchars($photo['title'],ENT_QUOTES); ?>' media_subtitle='<?php echo htmlspecialchars($photo['subtitle'],ENT_QUOTES); ?>'>
						<div>
							<div><?php echo htmlspecialchars($photo['title']); ?></div>
							<div><?php echo htmlspecialchars($photo['subtitle']); ?></div>
						</div>
					</a>
				<?php } elseif(startsWith($photo['type'], 'video/')) { ?>
					<a class='video-item' href='media.php?file=<?php echo urlencode($photo['path']); ?>'>
						<video src='media.php?file=<?php echo urlencode($photo['path']); ?>' media_title='<?php echo htmlspecialchars($photo['title'],ENT_QUOTES); ?>' media_subtitle='<?php echo htmlspecialchars($photo['subtitle'],ENT_QUOTES); ?>'>
							<?php foreach($photo['tracks'] as $track) { ?>
								<track default kind='<?php echo htmlspecialchars($track['kind'],ENT_QUOTES); ?>' label='<?php echo htmlspecialchars($track['srclang'],ENT_QUOTES); ?>' srclang='<?php echo htmlspecialchars($track['srclang'],ENT_QUOTES); ?>' src='media.php?file=<?php echo urlencode($track['path']); ?>' />
							<?php } ?>
						</video>
						<div>
							<div><?php echo htmlspecialchars($photo['title']); ?></div>
							<div><?php echo htmlspecialchars($photo['subtitle']); ?></div>
						</div>
					</a>
				<?php } else { ?>
					<a class='file-item' href='media.php?file=<?php echo urlencode($photo['path']); ?>'>
						<img src='img/file.svg'>
						<div>
							<div><?php echo htmlspecialchars($photo['title']); ?></div>
							<div><?php echo htmlspecialchars($photo['subtitle']); ?></div>
						</div>
					</a>
				<?php } ?>
			<?php } ?>
		</div>
		<div id='lightbox'>
			<div id='lightboxBack'></div>
			<img id='lightboxImg'></img>
			<video id='lightboxVideo' controls></video>
			<!-- Next/previous controls -->
			<div id='lightboxControls'>
				<a id='btnLightboxSlideshowPlay' onclick='lightboxSlideshowPlay()'>
					<svg xmlns="http://www.w3.org/2000/svg" width="32px" height="32px" viewBox="0 -960 960 960" fill="#000000"><path d="M372-294v-372l292 186-292 186Zm28-186Zm0 134 212-134-212-134v268Z"/></svg>
				</a>
				<a id='btnLightboxSlideshowPause' onclick='lightboxSlideshowPause()' class='hidden'>
					<svg xmlns="http://www.w3.org/2000/svg" width="32px" height="32px" viewBox="0 -960 960 960" fill="#000000"><path d="M546-252v-456h162v456H546Zm-294 0v-456h162v456H252Zm322-28h106v-400H574v400Zm-294 0h106v-400H280v400Zm0-400v400-400Zm294 0v400-400Z"/></svg>
				</a>
				<a id='btnLightboxFullscreen' onclick='lightboxFullscreen()'>
					<svg xmlns="http://www.w3.org/2000/svg" width="32px" height="32px" viewBox="0 -960 960 960" fill="#000000"><path d="M172-172v-170h28v142h142v28H172Zm447 0v-28h142v-142h28v170H619ZM172-618v-170h170v28H200v142h-28Zm589 0v-142H619v-28h170v170h-28Z"/></svg>
				</a>
				<a id='btnLightboxFullscreenExit' onclick='lightboxFullscreenExit()' class='hidden'>
					<svg xmlns="http://www.w3.org/2000/svg" width="32px" height="32px" viewBox="0 -960 960 960" fill="#000000"><path d="M314-172v-142H172v-28h170v170h-28Zm305 0v-170h170v28H647v142h-28ZM172-618v-28h142v-142h28v170H172Zm447 0v-170h28v142h142v28H619Z"/></svg>
				</a>
				<a id='btnLightboxDownload' download href=''>
					<svg xmlns="http://www.w3.org/2000/svg" width="32px" height="32px" viewBox="0 -960 960 960" fill="#000000"><path d="M480-342 356-466l20-20 90 90v-352h28v352l90-90 20 20-124 124ZM272-212q-26 0-43-17t-17-43v-90h28v90q0 12 10 22t22 10h416q12 0 22-10t10-22v-90h28v90q0 26-17 43t-43 17H272Z"/></svg>
				</a>
				<a id='btnLightboxInfo' onclick='lightboxInfo()'>
					<svg xmlns="http://www.w3.org/2000/svg" width="32px" height="32px" viewBox="0 -960 960 960" fill="#000000"><path d="M466-306h28v-214h-28v214Zm14-264q8.5 0 14.25-5.75T500-590q0-8.5-5.75-14.25T480-610q-8.5 0-14.25 5.75T460-590q0 8.5 5.75 14.25T480-570Zm.17 438q-72.17 0-135.73-27.39-63.56-27.39-110.57-74.35-47.02-46.96-74.44-110.43Q132-407.65 132-479.83q0-72.17 27.39-135.73 27.39-63.56 74.35-110.57 46.96-47.02 110.43-74.44Q407.65-828 479.83-828q72.17 0 135.73 27.39 63.56 27.39 110.57 74.35 47.02 46.96 74.44 110.43Q828-552.35 828-480.17q0 72.17-27.39 135.73-27.39 63.56-74.35 110.57-46.96 47.02-110.43 74.44Q552.35-132 480.17-132Zm-.17-28q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/></svg>
				</a>
				<a id='btnLightboxClose' onclick='lightboxClose()'>
					<svg xmlns="http://www.w3.org/2000/svg" width="32px" height="32px" viewBox="0 -960 960 960" fill="#000000"><path d="m256-236-20-20 224-224-224-224 20-20 224 224 224-224 20 20-224 224 224 224-20 20-224-224-224 224Z"/></svg>
				</a>
			</div>
			<a id='btnLightboxPrev' onclick='lightboxNext(-1)'>&#10094;</a>
			<a id='btnLightboxNext' onclick='lightboxNext(1)'>&#10095;</a>
			<!-- Caption text -->
			<div id='lightboxCaptionContainer'>
				<div id='lightboxCaptionTitle'></div>
				<div id='lightboxCaptionText'></div>
			</div>
		</div>

		<div id='footer'>
			<a target='_blank' href='https://github.com/schorschii/autogallery'>AutoGallery</a>
			© <a target='_blank' href='https://georg-sieber.de'>Georg Sieber</a>
		</div>
	</body>
</html>
