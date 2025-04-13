# AutoGallery
AutoGallery is a simple PHP web app for presenting images, videos and other files with some special features.

## Features
- images
  - optional display of IPTC fields
  - lightbox with slideshow, fullscreen & download option
- videos
  - display chapters and subtitles
- nice 3D effect on mouse hover
- optional password protection
- dark mode

![Gallery](.github/gallery.png)
![Lightbox](.github/lightbox.png)

## Installation
1. Copy all files onto your web server.
2. Copy your files (images, videos and other files; sub-directories possible) into the `media` dir.
3. Create `conf.php` from `conf.php.example`.  
   Optional: change values:
   - change gallery title in `const TITLE`
   - set a password in `const PASSWORD` (can also be an array with multiple passwords)
   - set which IPTC fields should be shown in `const PHOTO_TITLE` and `const PHOTO_SUBTITLE`

## Usage
### Symlinks
The application supports symlinks which allows you to create easy-to-read URLs. Example: you have a folder called "Holidays June 2014". You can create a symlink via `ln -s "Holidays June 2014" "holidays2014"` so you can share the simpler URL `https://gallery.example.com/holidays2014` with your friends. The web app still shows the nice name "Holidays June 2014" as headline even when accessed via shortlink.

### HTML Description
You can display a descriptive text to each folder by creating a file `index.html` in your media folder with the desired HTML content.

### Hide Folders
You can hide folders the common Unix way by adding a "." at the beginning of the folder name. The folder is still accessible!

### Videos with Chapters, Subtitles and Thumbnail
Video subtitles and chapters are read by Browsers from .vtt files. If you want to add such to a video, you need to create a folder with the same name as the video file (without file extension). Inside this folder, place your .vtt files with the following file name schema.
- Subtitles: `subtitles.<LANGCODE>.vtt`, e.g. `subtitles.de.vtt`
- Chapters: `chapters.<LANGCODE>.vtt`

If you want to show a custom thumbnail, place a file called `thumbnail.jpg` in this folder.

A file tree could look like this:
```
media/
- myvideo.mp4
- myvideo/
-- subtitles.en.vtt
-- subtitles.de.vtt
-- chapters.en.vtt
-- thumbnail.jpg
```

If available, embedded chapters from video files can be extracted automatically using `ffprobe`. Make sure ffmpeg is installed and then set `VIDEO_EXTRACT_METADATA = true` in the config file.

### Statistics
If you want to capture user statistics, you need to create a MySQL/MariaDB database and import the database schema from `sql/SCHEMA.sql`. Then, enter your database credentials in the `STAT_DB_*` constants in `conf.php`. And all requests will be logged into that database.
