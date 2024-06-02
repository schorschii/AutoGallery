<?php

/*
	Define the gallery title here.
*/
const TITLE = 'Gallery';

/*
	Define the view password here.
*/
const PASSWORD = '';

/*
	Define the photo/video files root dir path here.
	This can be a path relative to index.php or an absolute path (starting with "/"),
	even outside the webroot dir. If you leave the media folder inside theautogallery
	folder, please make sure that you have "AllowOverride All" set in your Apache config
	in order to make the ".htaccess" file with "Deny from all" work. The media files
	should be accessible only through the media.php script, otherwise the password
	protection is useless.
*/
const ROOT_DIR = 'media';

/*
	Define what should be displayed as image title and subtile.
	Enter the ID of an IPTC tag to display the IPTC field value, e.g. '2#120' for the Caption.
	Leave PHOTO_TITLE empty to display the file name.
	Possible IPTC fields:
		2#005 -> DocumentTitle
		2#010 -> Urgency
		2#015 -> Category
		2#020 -> Subcategories
		2#040 -> SpecialInstructions
		2#055 -> CreationDate
		2#080 -> AuthorByline
		2#085 -> AuthorTitle
		2#090 -> City
		2#095 -> State
		2#101 -> Country
		2#103 -> OTR
		2#105 -> Headline
		2#110 -> Source
		2#115 -> PhotoSource
		2#116 -> Copyright
		2#120 -> Caption
		2#122 -> CaptionWriter
*/
const PHOTO_TITLE = '';
const PHOTO_SUBTITLE = '2#120';
