<?php

/*
	Define the gallery title here.
*/
const TITLE = 'Galerie';

/*
	Define the view password here.
*/
const PASSWORD = '';

/*
	Define the photo root dir here.
*/
const ROOT_DIR = 'media';

/*
	Define what should be displayed as image title and subtile.
	Enter the ID of a IPTC tag to display the IPTC field value, e.g. '2#120'.
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
