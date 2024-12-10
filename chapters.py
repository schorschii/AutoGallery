
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

def format_seconds(seconds:str):
    decimal_split = seconds.split('.')
    milliSeconds = decimal_split[1][0:3]
    totalSeconds = int(decimal_split[0])
    m, s = divmod(totalSeconds, 60)
    h, m = divmod(m, 60)
    return f'{int(h):01d}:{int(m):02d}:{int(s):02d}.{milliSeconds}'

chapters = json.loads(jsonout)['chapters']
for chapter in chapters:
    start = format_seconds(chapter['start_time'])
    end = format_seconds(chapter['end_time'])
    print(f'{start} --> {end}')
    print(chapter['tags']['title'])
    print()
