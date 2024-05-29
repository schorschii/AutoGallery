document.addEventListener('DOMContentLoaded', function() {
	init3dfx();
}, false);

function init3dfx() {
	const Z = 60;
	var elements = document.querySelectorAll('.photo-item, .video-item, .file-item, .dir-item');
	for(i = 0; i < elements.length; i ++) {
		elements[i].addEventListener('mousemove', function(e) {
			var rect = this.getBoundingClientRect();
			var offset = {
				top: rect.top + window.scrollY,
				left: rect.left + window.scrollX,
			};
			var XRel = e.pageX - offset.left;
			var YRel = e.pageY - offset.top;
			var XAngle = (0.5 - (YRel / this.offsetHeight)) * 15;
			var YAngle = -(0.5 - (XRel / this.offsetWidth)) * 15;
			this.style.transform = 'perspective(525px) translateZ(' + Z + 'px) rotateX(' + XAngle + 'deg) rotateY(' + YAngle + 'deg)';
		});
		elements[i].addEventListener('mouseleave', function() {
			this.style.transform = 'perspective(525px) translateZ(0) rotateX(0deg) rotateY(0deg)';
		});
	}
}
