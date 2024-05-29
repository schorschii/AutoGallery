# Autogallery
Autogallery is a simple PHP web app for presenting images and videos with some special features.

## Features
- images
  - optional display of IPTC fields
  - lightbox with slideshow
- videos
  - display chapters and subtitles
- optional password protection
- dark mode

## Installation
1. Copy all files onto your web server.
2. Copy your files (images, videos and other files, optional with sub-directories) into the `media` dir.
3. Optional: change values in `conf.php`
   - change gallery title in `const TITLE`
   - set a password in `const PASSWORD`
   - set which IPTC fields should be shown in `const PHOTO_TITLE` and `const PHOTO_SUBTITLE`

## Videos with Chapters and Subtitles
Video subtitles and chapters are read from .vtt files. If you want to add such to a video, you need to create a folder with the same name as the video file (without file extension). Inside this folder, place your .vtt files with the following file name schema.
- Subtitles: `subtitles.<LANGCODE>.vtt`, e.g. `subtitles.de.vtt`
- Chapters: `chapters.<LANGCODE>.vtt`

A file tree could look like this:
```
media/
- myvideo.mp4
- myvideo/
-- subtitles.en.vtt
-- subtitles.de.vtt
-- chapters.en.vtt
```

Bonus: if available, you can extract embedded chapters from video files with `ffprobe` from ffmpeg using the `chapters.py` script:
```
python3 chapters.py video.mov > chapters.en.vtt
```
