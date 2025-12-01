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

// download all as zip
if(isset($_GET['zip'])) {
	session_write_close();
	$zip = new ZipArchive();
	$file = '/tmp/'.uniqid().'.zip';
	if($zip->open($file, ZipArchive::CREATE) !== true)
		throw new Exception('Unable to open temp zip file');

	foreach($photos as $photo) {
		$zip->addFile(ROOT_DIR.'/'.$photo['path'], $photo['filename']);
		$zip->setCompressionName(ROOT_DIR.'/'.$photo['path'], ZipArchive::CM_STORE);
	}
	$zip->close();

	header('Content-Type: application/octet-stream');
	header('Content-Transfer-Encoding: Binary');
	header('Content-Length: '.filesize($file));
	header('Content-Disposition: attachment; filename="'.$folderTitle.'.zip"');
	readfile($file);
	unlink($file);
	die();
}

// track page view
if(defined('MATOMO_URL') && MATOMO_URL
&& defined('MATOMO_IDSITE') && MATOMO_IDSITE) {
	require_once('lib/MatomoTracker.php');
	$matomoTracker = new MatomoTracker(MATOMO_IDSITE, MATOMO_URL);
	$matomoTracker->setTokenAuth(MATOMO_TOKEN);
	$matomoTracker->disableCookieSupport();
	$matomoTracker->doTrackPageView($folderTitle);
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
		<link rel='stylesheet' type='text/css' href='<?php echo str_repeat('../', $pathDepth); ?>css/main.css?v=2' />
		<script src='<?php echo str_repeat('../', $pathDepth); ?>js/3dfx.js'></script>
		<script src='<?php echo str_repeat('../', $pathDepth); ?>js/lightbox.js'></script>
		<meta name='viewport' content='width=device-width, initial-scale=1.0'>
		<script>
			function downloadAll() {
				let items = gallery.querySelectorAll('a.photo-item>img, a.video-item>video, a.file-item>img');
				for(i=0; i<items.length; i++) {
					const a = document.createElement('a');
					a.setAttribute('href', items[i].getAttribute('media_src'));
					a.setAttribute('download', items[i].getAttribute('media_filename'));
					a.style.display = 'none';
					document.body.appendChild(a);
					a.click();
					document.body.removeChild(a);
				}
			}
			function loader(container, enable=false, timeout=8000) {
				container.style.pointerEvents = 'none';
				var prevInnerHTML = container.innerHTML;
				container.innerHTML = divLoader.innerHTML;
				let items = container.querySelectorAll('svg');
				items[0].classList.add('animRotate');
				setTimeout(() => {
					container.innerHTML = prevInnerHTML;
					if(enable)
						container.style.pointerEvents = 'all';
					else
						container.style.opacity = '0.4';
				}, timeout);
			}
		</script>
	</head>
	<body>
		<h1>
			<?php echo htmlspecialchars($folderTitle); ?>
			<?php if(!empty($photos)) { ?>
				<a id='btnDownloadAll' class='button' href='#' title='Download all (separate files)' onclick='downloadAll(); loader(this); return false;'>
					<?php require('img/download-all.svg'); ?>
				</a>
				<a id='btnDownloadAllZip' class='button' href='?zip' title='Download all (ZIP archive)' onclick='loader(this);'>
					<?php require('img/zip.svg'); ?>
				</a>
				<div id='divLoader' style='display:none'>
					<?php require('img/loader.svg'); ?>
				</div>
			<?php } ?>
		</h1>

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
						<img loading='lazy'
						src='<?php echo htmlspecialchars($mediaPath,ENT_QUOTES); ?>'
						media_src='<?php echo htmlspecialchars($mediaPath,ENT_QUOTES); ?>'
						media_title='<?php echo htmlspecialchars($photo['title'],ENT_QUOTES); ?>'
						media_subtitle='<?php echo htmlspecialchars($photo['subtitle'],ENT_QUOTES); ?>'
						media_filename='<?php echo htmlspecialchars($photo['filename'],ENT_QUOTES); ?>'
						>
						<?php if(!empty($photo['title']) || !empty($photo['subtitle'])) { ?>
						<div>
							<div><?php echo htmlspecialchars($photo['title']); ?></div>
							<div><?php echo htmlspecialchars($photo['subtitle']); ?></div>
						</div>
						<?php } ?>
					</a>
				<?php } elseif(startsWith($photo['type'], 'video/')) { ?>
					<a class='video-item' href='<?php echo htmlspecialchars($mediaPath,ENT_QUOTES); ?>'>
						<video
						src='<?php echo htmlspecialchars($mediaPath,ENT_QUOTES); ?>'
						poster='<?php if($photo['thumbnail']) echo htmlspecialchars($pathPrefix.$photo['thumbnail'],ENT_QUOTES); ?>'
						media_src='<?php echo htmlspecialchars($mediaPath,ENT_QUOTES); ?>'
						media_title='<?php echo htmlspecialchars($photo['title'],ENT_QUOTES); ?>'
						media_subtitle='<?php echo htmlspecialchars($photo['subtitle'],ENT_QUOTES); ?>'
						media_filename='<?php echo htmlspecialchars($photo['filename'],ENT_QUOTES); ?>'
						>
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
						<img
						src='<?php echo str_repeat('../', $pathDepth); ?>img/file.svg'
						media_src='<?php echo htmlspecialchars($mediaPath,ENT_QUOTES); ?>'
						media_filename='<?php echo htmlspecialchars($photo['filename'],ENT_QUOTES); ?>'
						>
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
					<?php require('img/play.svg'); ?>
				</a>
				<a id='btnLightboxSlideshowPause' onclick='lightboxSlideshowPause()' class='hidden'>
					<?php require('img/pause.svg'); ?>
				</a>
				<a id='btnLightboxFullscreen' onclick='lightboxFullscreen()'>
					<?php require('img/fullscreen.svg'); ?>
				</a>
				<a id='btnLightboxFullscreenExit' onclick='lightboxFullscreenExit()' class='hidden'>
					<?php require('img/fullscreen-exit.svg'); ?>
				</a>
				<a id='btnLightboxDownload' download href='' onclick='loader(this, true, 1000)'>
					<?php require('img/download.svg'); ?>
				</a>
				<a id='btnLightboxInfoOpen' onclick='lightboxInfoOpen()'>
					<?php require('img/info.svg'); ?>
				</a>
				<a id='btnLightboxInfoClose' onclick='lightboxInfoClose()' class='hidden'>
					<?php require('img/info-close.svg'); ?>
				</a>
				<a id='btnLightboxClose' onclick='lightboxClose()'>
					<?php require('img/close.svg'); ?>
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
