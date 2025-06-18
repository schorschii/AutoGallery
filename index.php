<?php
require_once('conf.php');
require_once('session.php');

$photos = [];
$dirs = [];
$folderTitle = TITLE;
$searchPath = ROOT_DIR;
$pathDepth = 0;

// set search path if requested path if it is below ROOT_DIR (avoid path traversal attacks)
$requestPath = urldecode(trim($_SERVER['PATH_INFO'] ?? null, '/'));
if(!empty($requestPath)) {
	if(is_dir(ROOT_DIR.'/'.$requestPath)
	&& startsWith(realpath(ROOT_DIR.'/'.$requestPath), realpath(ROOT_DIR))) {
		$folderTitle = basename(realpath(ROOT_DIR.'/'.$requestPath));
		if(substr($folderTitle, 0, 1) == '.') $folderTitle = substr($folderTitle, 1);
		$searchPath = ROOT_DIR.'/'.$requestPath;
		$pathDepth = count(explode('/', $requestPath));
		if(!startsWith($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME'])) {
			// support for URLs: http://host/index.php/foldername AND http://host/foldername
			// the latter is only supported if .htaccess overrides are allowed in Apache config
			$pathDepth -= 1;
		}
	} else {
		header('HTTP/1.1 404 NOT FOUND');
		header('Location: '.$_SERVER['SCRIPT_NAME']);
		die();
	}
}

// statistic
require_once(__DIR__.'/stat.php');
writeStat($requestPath.'/');

// scan dir contents
$files = scandir($searchPath);
$ignoreDirs = [];
$descriptionHtml = '';
foreach($files as $file) {
	if(substr($file, 0, 1) == '.') continue;
	if(is_link($searchPath.'/'.$file)) continue;
	if(!is_file($searchPath.'/'.$file)) continue;
	if($file == 'index.html') {
		$descriptionHtml = file_get_contents($searchPath.'/'.$file);
		continue;
	}
	$photo = [
		'type' => mime_content_type($searchPath.'/'.$file),
		'filename' => $file,
		'path' => substr($searchPath.'/'.$file, strlen(ROOT_DIR)+1),
		'title' => PHOTO_TITLE=='FILENAME.EXT' ? basename($file) : (PHOTO_TITLE=='FILENAME' ? pathinfo($file,PATHINFO_FILENAME) : ''),
		'subtitle' => PHOTO_SUBTITLE=='FILENAME.EXT' ? basename($file) : (PHOTO_SUBTITLE=='FILENAME' ? pathinfo($file,PATHINFO_FILENAME) : ''),
		'tracks' => [],
		'thumbnail' => null,
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
		$metadataDir = $searchPath.'/'.pathinfo($file, PATHINFO_FILENAME);
		$ignoreDirs[] = $metadataDir;
		// start chapter extraction if enabled and not already done
		if(VIDEO_EXTRACT_METADATA && !file_exists($metadataDir)) {
			require_once('lib/video.php');
			@mkdir($metadataDir);
			// proceed only if dir could be created (skip e.g. in case of permission errors)
			if(file_exists($metadataDir)) {
				foreach(extractSubtitlesVtt($searchPath.'/'.$file) as $vttName => $vttContent) {
					file_put_contents($metadataDir.'/'.$vttName.'.en.vtt', $vttContent);
				}
			}
		}
		// search for existing video metatdate/chapters
		foreach(glob($metadataDir.'/*.vtt') as $vttfile) {
			$splitter = explode('.', basename($vttfile));
			if(count($splitter) != 3) continue;
			$photo['tracks'][] = [
				'path' => substr($vttfile, strlen(ROOT_DIR)+1),
				'kind' => $splitter[0],
				'srclang' => $splitter[1],
			];
		}
		foreach(glob($metadataDir.'/thumbnail.*') as $thumb) {
			$photo['thumbnail'] = substr($thumb, strlen(ROOT_DIR)+1);
		}
	}
	$photos[] = $photo;
}
foreach($files as $file) {
	if(substr($file, 0, 1) == '.') continue;
	if(is_link($searchPath.'/'.$file)) continue;
	if(!is_dir($searchPath.'/'.$file)) continue;
	if(in_array($searchPath.'/'.$file, $ignoreDirs)) continue;
	$dirs[] = [
		'path' => substr($searchPath.'/'.$file, strlen(ROOT_DIR)+1),
		'title' => $file,
	];
}

function urlencodePath($path) {
	$parts = explode('/', $path);
	return implode('/', array_map('urlencode', $parts));
}
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset='utf-8'>
		<title><?php echo htmlspecialchars($folderTitle); ?></title>
		<link rel='stylesheet' type='text/css' href='<?php echo str_repeat('../', $pathDepth); ?>css/main.css' />
		<script src='<?php echo str_repeat('../', $pathDepth); ?>js/3dfx.js'></script>
		<script src='<?php echo str_repeat('../', $pathDepth); ?>js/lightbox.js'></script>
		<meta name='viewport' content='width=device-width, initial-scale=1.0'>
	</head>
	<body>
		<h1><?php echo htmlspecialchars($folderTitle); ?></h1>

		<?php echo $descriptionHtml; ?>
		<div id='gallery' class='photos'>
			<?php if(empty($dirs) && empty($photos)) { ?>
				<div id='galleryempty'>This directory is empty.</div>
			<?php } ?>
			<?php foreach($dirs as $dir) { ?>
				<a class='dir-item' href='<?php echo ($pathDepth==0 ? 'index.php/' : '').urlencodePath($dir['path']); ?>'>
					<img src='<?php echo str_repeat('../', $pathDepth); ?>img/folder.svg'>
					<div>
						<div><?php echo htmlspecialchars($dir['title']); ?></div>
					</div>
				</a>
			<?php } ?>
			<?php foreach($photos as $photo) { ?>
				<?php
				$pathPrefix = str_repeat('../', $pathDepth).'media.php/';
				$mediaPath = $pathPrefix.urlencodePath($photo['path']);
				?>
				<?php if(startsWith($photo['type'], 'image/')) { ?>
					<a class='photo-item' href='<?php echo htmlspecialchars($mediaPath,ENT_QUOTES); ?>'>
						<img loading='lazy' src='<?php echo htmlspecialchars($mediaPath,ENT_QUOTES); ?>' media_title='<?php echo htmlspecialchars($photo['title'],ENT_QUOTES); ?>' media_subtitle='<?php echo htmlspecialchars($photo['subtitle'],ENT_QUOTES); ?>' media_filename='<?php echo htmlspecialchars($photo['filename'],ENT_QUOTES); ?>'>
						<?php if(!empty($photo['title']) || !empty($photo['subtitle'])) { ?>
						<div>
							<div><?php echo htmlspecialchars($photo['title']); ?></div>
							<div><?php echo htmlspecialchars($photo['subtitle']); ?></div>
						</div>
						<?php } ?>
					</a>
				<?php } elseif(startsWith($photo['type'], 'video/')) { ?>
					<a class='video-item' href='<?php echo htmlspecialchars($mediaPath,ENT_QUOTES); ?>'>
						<video src='<?php echo htmlspecialchars($mediaPath,ENT_QUOTES); ?>' poster='<?php if($photo['thumbnail']) echo htmlspecialchars($pathPrefix.$photo['thumbnail'],ENT_QUOTES); ?>' media_title='<?php echo htmlspecialchars($photo['title'],ENT_QUOTES); ?>' media_subtitle='<?php echo htmlspecialchars($photo['subtitle'],ENT_QUOTES); ?>' media_filename='<?php echo htmlspecialchars($photo['filename'],ENT_QUOTES); ?>'>
							<?php foreach($photo['tracks'] as $track) { ?>
								<track default kind='<?php echo htmlspecialchars($track['kind'],ENT_QUOTES); ?>' label='<?php echo htmlspecialchars($track['srclang'],ENT_QUOTES); ?>' srclang='<?php echo htmlspecialchars($track['srclang'],ENT_QUOTES); ?>' src='<?php echo htmlspecialchars($pathPrefix.$track['path'],ENT_QUOTES); ?>' />
							<?php } ?>
						</video>
						<?php if(!empty($photo['title']) || !empty($photo['subtitle'])) { ?>
						<div>
							<div><?php echo htmlspecialchars($photo['title']); ?></div>
							<div><?php echo htmlspecialchars($photo['subtitle']); ?></div>
						</div>
						<?php } ?>
					</a>
				<?php } else { ?>
					<a class='file-item' href='<?php echo $mediaPath; ?>'>
						<img src='<?php echo str_repeat('../', $pathDepth); ?>img/file.svg'>
						<?php if(!empty($photo['title']) || !empty($photo['subtitle'])) { ?>
						<div>
							<div><?php echo htmlspecialchars($photo['title']); ?></div>
							<div><?php echo htmlspecialchars($photo['subtitle']); ?></div>
						</div>
						<?php } ?>
					</a>
				<?php } ?>
			<?php } ?>
		</div>
		<div id='lightbox'>
			<div id='lightboxBack'></div>
			<!-- main content elements -->
			<img id='lightboxImg'></img>
			<video id='lightboxVideo' controls></video>
			<!-- caption text -->
			<div id='lightboxCaptionContainer'>
				<div id='lightboxCaptionTitle'></div>
				<div id='lightboxCaptionText'></div>
			</div>
			<!-- top menu controls -->
			<div id='lightboxControls'>
				<a id='btnLightboxSlideshowPlay' onclick='lightboxSlideshowPlay()'>
					<svg xmlns="http://www.w3.org/2000/svg" width="32px" height="32px" viewBox="0 -960 960 960" fill="#000000"><path d="M292-172v-28h174v-120H192q-26 0-43-17t-17-43v-348q0-26 17-43t43-17h576q26 0 43 17t17 43v348q0 26-17 43t-43 17H494v120h174v28H292ZM160-380q0 12 10 22t22 10h576q12 0 22-10t10-22v-348q0-12-10-22t-22-10H192q-12 0-22 10t-10 22v348Zm0 0v-380 412-32Z"/><path d="m 381.7857,-400.54281 v -302.06733 l 237.10659,151.03366 z m 22.73625,-151.03367 z m 0,108.80921 172.14586,-108.80921 -172.14586,-108.8092 z"/></svg>
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
				<a id='btnLightboxInfoOpen' onclick='lightboxInfoOpen()'>
					<svg xmlns="http://www.w3.org/2000/svg" width="32px" height="32px" viewBox="0 -960 960 960" fill="#000000"><path d="M172-332v-28h269v28H172Zm0-160v-28h426v28H172Zm0-160v-28h426v28H172Zm491 480v-213l166 107-166 106Z"/></svg>
				</a>
				<a id='btnLightboxInfoClose' onclick='lightboxInfoClose()' class='hidden'>
					<svg xmlns="http://www.w3.org/2000/svg" width="32px" height="32px" viewBox="0 -960 960 960" fill="#000000"><path d="m 172,-332 v -28 h 269 v 28 z m 0,-160 v -28 h 426 v 28 z m 0,-160 v -28 h 426 v 28 z"/><path d="m 607.86442,-140.40677 -20,-20 104,-104 -104,-104 20,-20 104,104 104,-104 20,20 -104,104 104,104 -20,20 -104,-104 z"/></svg>
				</a>
				<a id='btnLightboxClose' onclick='lightboxClose()'>
					<svg xmlns="http://www.w3.org/2000/svg" width="32px" height="32px" viewBox="0 -960 960 960" fill="#000000"><path d="m256-236-20-20 224-224-224-224 20-20 224 224 224-224 20 20-224 224 224 224-20 20-224-224-224 224Z"/></svg>
				</a>
			</div>
			<!-- prev/next buttons -->
			<a id='btnLightboxPrev' onclick='lightboxNext(-1)'>&#10094;</a>
			<a id='btnLightboxNext' onclick='lightboxNext(1)'>&#10095;</a>
		</div>

		<div id='footer'>
			<a target='_blank' href='https://github.com/schorschii/autogallery'>AutoGallery</a>
			© <a target='_blank' href='https://georg-sieber.de'>Georg Sieber</a>
		</div>
	</body>
</html>
