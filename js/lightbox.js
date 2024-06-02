const LIGHTBOX_ANIM_DURATION = 600;
var lightboxSlideIndex = 0;
var lightboxSlides = [];

document.addEventListener('DOMContentLoaded', function() {
	var elements = gallery.querySelectorAll('.photo-item, .video-item');
	for(i = 0; i < elements.length; i ++) {
		elements[i].addEventListener('click', function(e) {
			e.preventDefault();

			// get all images in gallery
			var elements = gallery.querySelectorAll('.photo-item img, .video-item video');
			//elements = Array.prototype.slice.call(elements, 0);
			lightboxSlides = [];
			for(var i = 0; i < elements.length; i++) {
				lightboxSlides.push(elements[i]);
				if(elements[i].parentNode == this) {
					lightboxSlideIndex = i;
				}
			}
			lightboxNext(0);
			lightboxShow(this);
		});
	}
}, false);
document.addEventListener('keydown', function(event) {
	// arrow left + arrow right - prev/next image
	if((event.which || event.keyCode) == 37) {
		lightboxNext(-1);
	}
	if((event.which || event.keyCode) == 39) {
		lightboxNext(1);
	}
	// esc - close lightbox
	if((event.which || event.keyCode) == 27) {
		lightboxClose();
	}
});
var mouseMoveTimeout = null;
document.addEventListener('mousemove', function() {
	lightboxControls.classList.add('visible');
	clearTimeout(mouseMoveTimeout);
	mouseMoveTimeout = setTimeout(function(e){ lightboxControls.classList.remove('visible'); }, 2000);
});

function formatSeconds(totalSeconds) {
	hours = Math.floor(totalSeconds / 3600);
	totalSeconds %= 3600;
	minutes = Math.floor(totalSeconds / 60);
	seconds = Math.round(totalSeconds % 60); // Math.round() for mobile Safari, the new Internet Explorer...
	return pad(hours,1)+':'+pad(minutes,2)+':'+pad(seconds,2);
}
function pad(num, size) {
	num = num.toString();
	while (num.length < size) num = '0' + num;
	return num;
}

function lightboxShow(element) {
	if(lightboxSlides.length > 1) {
		btnLightboxNext.classList.remove('hidden');
		btnLightboxPrev.classList.remove('hidden');
		btnLightboxSlideshowPlay.classList.remove('hidden');
	} else {
		btnLightboxNext.classList.add('hidden');
		btnLightboxPrev.classList.add('hidden');
		btnLightboxSlideshowPlay.classList.add('hidden');
	}

	let maximizeElement = lightboxImg;
	if(element.classList.contains('video-item')) {
		maximizeElement = lightboxVideo;
		lightboxVideo.classList.remove('hidden');
		lightboxImg.classList.add('hidden');
	} else {
		lightboxVideo.classList.add('hidden');
		lightboxImg.classList.remove('hidden');
	}

	let viewportOffset = element.getBoundingClientRect();
	maximizeElement.animate(
		[
			{ top:viewportOffset.top+'px', left:viewportOffset.left+'px', width:viewportOffset.width+'px', height:viewportOffset.height+'px' },
			{ top:'calc(var(--lightboxpad) * 2)', left:'var(--lightboxpad)', width:'calc(100% - (var(--lightboxpad) * 2))', height:'calc(100% - (var(--lightboxpad) * 3))' },
		],
		{ duration: LIGHTBOX_ANIM_DURATION, easing: 'ease-in-out' }
	);
	maximizeElement.animate(
		[
			{ opacity: '0' },
			{ opacity: '1' },
		],
		{ duration: LIGHTBOX_ANIM_DURATION/4, easing: 'ease-in-out' }
	);
	[lightboxBack, lightboxControls, btnLightboxPrev, btnLightboxNext].forEach(function(item) {
		item.animate(
			[
				{ opacity: '0' },
				{ opacity: '1' },
			],
			{ duration: LIGHTBOX_ANIM_DURATION, easing: 'ease-in-out' }
		);
	});
	lightbox.style.display = 'block';
}

function lightboxClose() {
	lightboxSlideshowPause();

	let maximizeElement = lightboxImg;
	if(lightboxSlides[lightboxSlideIndex].tagName == 'VIDEO') {
		maximizeElement = lightboxVideo;
		lightboxVideo.pause();
	}

	let viewportOffset = lightboxSlides[lightboxSlideIndex].getBoundingClientRect();
	maximizeElement.animate(
		[
			{ top: viewportOffset.top+'px', left: viewportOffset.left+'px', width: viewportOffset.width+'px', height: viewportOffset.height+'px' },
		],
		{ duration: LIGHTBOX_ANIM_DURATION, easing: 'ease-in-out' }
	).onfinish = (event) => {
		lightbox.style.display = '';
	};
	setTimeout(function(e){
		maximizeElement.animate(
			[
				{ opacity: '1' },
				{ opacity: '0' },
			],
			{ duration: LIGHTBOX_ANIM_DURATION/4, easing: 'ease-in-out' }
		);
	}, LIGHTBOX_ANIM_DURATION/4*3).onfinish = (event) => {
		lightbox.style.display = '';
	};
	[lightboxBack, lightboxControls, btnLightboxPrev, btnLightboxNext].forEach(function(item) {
		item.animate(
			[
				{ opacity: '1' },
				{ opacity: '0' },
			],
			{ duration: LIGHTBOX_ANIM_DURATION, easing: 'ease-in-out' }
		).onfinish = (event) => {
			lightbox.style.display = '';
		};
	});
	lightboxFullscreenExit();
}

function lightboxNext(step) {
	lightboxVideo.pause();

	if(lightboxSlideIndex + step >= lightboxSlides.length) {
		// jump to first if next btn clicked on last element
		lightboxSlideIndex = 0;
	} else if(lightboxSlideIndex + step < 0) {
		// jump to last if prev btn clicked on first element
		lightboxSlideIndex = lightboxSlides.length - 1;
	} else {
		// jump to direct neighbour
		lightboxSlideIndex += step;
	}

	// setup download btn
	btnLightboxDownload.href = lightboxSlides[lightboxSlideIndex].src;
	btnLightboxDownload.download = lightboxSlides[lightboxSlideIndex].getAttribute('media_title');

	// setup video or img element
	if(lightboxSlides[lightboxSlideIndex].tagName == 'VIDEO') {
		// remove prev text tracks
		// creating a new video element is necessary for Firefox
		let element = document.getElementById('lightboxVideo');
		if(element) element.remove();
		element = document.createElement('VIDEO');
		element.id = 'lightboxVideo';
		element.controls = true;
		element.addEventListener('loadeddata', function(){
			for(var i=0; i<lightboxVideo.textTracks.length; i++) {
				if(lightboxVideo.textTracks[i].kind != 'chapters') continue;
				for(var n=0; n<lightboxVideo.textTracks[i].cues.length; n++) {
					//console.log(lightboxVideo.textTracks[i].cues[n]);
					let cuePoint = lightboxVideo.textTracks[i].cues[n];
					let btn = document.createElement('A');
					btn.classList.add('chapter');
					let spnTime = document.createElement('SPAN');
					spnTime.classList.add('time');
					spnTime.innerText = formatSeconds(cuePoint.startTime);
					let spnText = document.createElement('SPAN');
					spnText.innerText = cuePoint.text;
					btn.appendChild(spnTime);
					btn.appendChild(spnText);
					btn.addEventListener('click', function(e){
						e.preventDefault();
						lightboxVideo.currentTime = cuePoint.startTime;
						lightboxVideo.play();
					});
					lightboxCaptionText.appendChild(btn);
				}
			}
		});
		lightbox.insertBefore(element, lightboxCaptionContainer);
		// add new text tracks
		let elements = lightboxSlides[lightboxSlideIndex].querySelectorAll('track');
		for(i = 0; i < elements.length; i ++) {
			let clone = elements[i].cloneNode();
			lightboxVideo.appendChild(clone);
		}

		lightboxVideo.classList.remove('hidden');
		lightboxImg.classList.add('hidden');
		lightboxVideo.src = lightboxSlides[lightboxSlideIndex].src;
	} else {
		lightboxVideo.classList.add('hidden');
		lightboxImg.classList.remove('hidden');
		lightboxImg.src = lightboxSlides[lightboxSlideIndex].src;
	}

	// update text
	lightboxCaptionTitle.innerText = lightboxSlides[lightboxSlideIndex].getAttribute('media_title');
	lightboxCaptionText.innerText = lightboxSlides[lightboxSlideIndex].getAttribute('media_subtitle');
}

function lightboxInfoOpen() {
	lightboxCaptionContainer.classList.add('visible');
	btnLightboxInfoOpen.classList.add('hidden');
	btnLightboxInfoClose.classList.remove('hidden');
}
function lightboxInfoClose() {
	lightboxCaptionContainer.classList.remove('visible');
	btnLightboxInfoOpen.classList.remove('hidden');
	btnLightboxInfoClose.classList.add('hidden');
}

function lightboxFullscreen() {
	if(lightbox.requestFullscreen) {
		lightbox.requestFullscreen();
	} else if(lightbox.webkitRequestFullscreen) {
		lightbox.webkitRequestFullscreen();
	}
	btnLightboxFullscreen.classList.add('hidden');
	btnLightboxFullscreenExit.classList.remove('hidden');
	lightbox.classList.add('fullscreen');
}
function lightboxFullscreenExit() {
	if(!window.screenTop && !window.screenY) {
		if(document.exitFullscreen) {
			document.exitFullscreen();
		} else if(document.webkitExitFullscreen) {
			document.webkitExitFullscreen();
		}
	}
	btnLightboxFullscreen.classList.remove('hidden');
	btnLightboxFullscreenExit.classList.add('hidden');
	lightbox.classList.remove('fullscreen');
}

var lightboxSlideshowInterval = null;
function lightboxSlideshowPlay() {
	lightboxSlideshowInterval = setInterval(function(e){
		lightboxNext(1);
	}, 3000);
	lightbox.classList.add('slideshow');
	btnLightboxSlideshowPlay.classList.add('hidden');
	btnLightboxSlideshowPause.classList.remove('hidden');
}
function lightboxSlideshowPause() {
	clearInterval(lightboxSlideshowInterval);
	lightbox.classList.remove('slideshow');
	btnLightboxSlideshowPlay.classList.remove('hidden');
	btnLightboxSlideshowPause.classList.add('hidden');
}
