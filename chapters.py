
import json
import subprocess
import sys

if(len(sys.argv) != 2):
    print('Please enter a video file as first parameter.')
    sys.exit()

jsonout = subprocess.check_output([
    'ffprobe', '-show_chapters', '-print_format', 'json', sys.argv[1]
])

print('WEBVTT')
print()

chapters = json.loads(jsonout)['chapters']
for chapter in chapters:
    decimalSplit = chapter['start_time'].split('.')
    milliSeconds = decimalSplit[1][0:3]
    totalSeconds = int(decimalSplit[0])
    m, s = divmod(totalSeconds, 60)
    h, m = divmod(m, 60)
    #print(f'{int(h):01d}:{int(m):02d}:{int(s):02d}', chapter['tags']['title'])
    print(f'{int(h):01d}:{int(m):02d}:{int(s):02d}.{milliSeconds} --> {int(h):01d}:{int(m):02d}:{int(s+1):02d}.{milliSeconds}')
    print(chapter['tags']['title'])
    print()
