const LIGHTBOX_ANIM_DURATION = 600;
var lightboxSlideIndex = 0;
var lightboxSlides = [];

document.addEventListener('DOMContentLoaded', function() {
	var elements = gallery.querySelectorAll('.photo-item');
	for(i = 0; i < elements.length; i ++) {
		elements[i].addEventListener('click', function(e) {
			e.preventDefault();

			// get all images in gallery
			var elements = gallery.querySelectorAll('.photo-item img');
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

	var elements = gallery.querySelectorAll('.video-item');
	for(i = 0; i < elements.length; i ++) {
		elements[i].addEventListener('click', function(e) {
			e.preventDefault();

			var elements = this.querySelectorAll('video');
			lightboxSlides = [ elements[0] ];
			lightboxSlideIndex = 0;
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
	maximizeElement.style.top = viewportOffset.top+'px';
	maximizeElement.style.left = viewportOffset.left+'px';
	maximizeElement.style.width = viewportOffset.width+'px';
	maximizeElement.style.height = viewportOffset.height+'px';

	maximizeElement.animate(
		[
			{ top:'calc(var(--lightboxpad) * 2)', left:'var(--lightboxpad)', width:'calc(100% - (var(--lightboxpad) * 2))', height:'calc(100% - (var(--lightboxpad) * 3))' },
		],
		{ duration: LIGHTBOX_ANIM_DURATION, easing: 'ease-in-out' }
	).onfinish = (event) => {
		maximizeElement.style.top = 'calc(var(--lightboxpad) * 2)';
		maximizeElement.style.left = 'var(--lightboxpad)';
		maximizeElement.style.width = 'calc(100% - (var(--lightboxpad) * 2))';
		maximizeElement.style.height = 'calc(100% - (var(--lightboxpad) * 3))';
	};
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
	if(lightboxSlideIndex + step >= lightboxSlides.length) {
		lightboxSlideIndex = 0;
	} else if(lightboxSlideIndex + step < 0) {
		lightboxSlideIndex = lightboxSlides.length - 1;
	} else {
		lightboxSlideIndex += step;
	}
	btnLightboxDownload.href = lightboxSlides[lightboxSlideIndex].src;
	btnLightboxDownload.download = lightboxSlides[lightboxSlideIndex].getAttribute('media_title');
	if(lightboxSlides[lightboxSlideIndex].tagName == 'VIDEO') {
		lightboxVideo.src = lightboxSlides[lightboxSlideIndex].src;
		// remove prev text tracks
		var elements = lightboxVideo.querySelectorAll('track');
		for(i = 0; i < elements.length; i ++) {
			elements[i].remove();
		}
		// add new text tracks
		var elements = lightboxSlides[lightboxSlideIndex].querySelectorAll('track');
		for(i = 0; i < elements.length; i ++) {
			let clone = elements[i].cloneNode();
			lightboxVideo.appendChild(clone);
		}
	} else {
		lightboxImg.src = lightboxSlides[lightboxSlideIndex].src;
	}
	lightboxCaptionTitle.innerText = lightboxSlides[lightboxSlideIndex].getAttribute('media_title');
	lightboxCaptionText.innerText = lightboxSlides[lightboxSlideIndex].getAttribute('media_subtitle');
}

function lightboxInfo() {
	lightboxCaptionContainer.classList.toggle('visible');
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
