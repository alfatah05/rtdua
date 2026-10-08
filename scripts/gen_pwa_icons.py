#!/usr/bin/env python3
"""Generate PWA icons from embedded logo resmi (rumah rtdua). Hanya resize."""
from pathlib import Path
import sys, base64
from io import BytesIO
try:
    from PIL import Image
except ImportError:
    import subprocess
    subprocess.check_call([sys.executable, '-m', 'pip', 'install', 'pillow', '-q'])
    from PIL import Image

LOGO_B64 = """/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAUDBAQEAwUEBAQFBQUGBwwIBwcHBw8LCwkMEQ8SEhEPERETFhwXExQaFRERGCEYGh0dHx8fExciJCIeJBweHx7/2wBDAQUFBQcGBw4ICA4eFBEUHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh7/wAARCAEAAQADASIAAhEBAxEB/8QAHQAAAgEFAQEAAAAAAAAAAAAAAAEHAwQFBggCCf/EAFEQAAEDAgMEBAYOBQoFBQAAAAEAAgMEBQYHESExQWESUZLRCBMiRXGRFBYjMlJVYnOBk6GxssEVNUJDsxclJzNjcnSCwtIkVmSi4TdERlTw/8QAGwEBAAEFAQAAAAAAAAAAAAAAAAMBAgQFBgf/xAA9EQACAQICBwQHBgUFAQAAAAAAAQIDBAURBhIhMVGh0UFDcZEHE0JTgbHBMlJhotLhFBUjJPAWIjM0VHL/2gAMAwEAAhEDEQA/AOykJIQDQkhACaEkA0JIQAhCEA0JIQAhCaASEI4oBoSTQCQhCAE0IQAhCSAaEkIBpIKEA0JIQDQkmgBJNJACEIQAhCaAEJIQDQkhANJCEA0kFCAE0JIAQhCAEJpIATQkgGkml6UAIWAuuLLTRSOiZI6plbsLYtoB5u3LG+3gF3k27Zzm2/ctHc6SYZbT1KlVZ/hm/kmZ1PDbqpHWjB5eXzNxTWuW/F1tqHhlQ2SlceLtrfWNy2Fj2vYHscHNI1BB1BWfZYja30da3mpeH1W9GPWt6tB5VI5DTSQs0hBNCEAJJoQAkmkgGkmkgGkhCAaEka8j6kAIQmgBCEkAIQhACEIQAmkhANJCEAKOcw8UvdVyWeglLY4/JqHtO1zuLQeocVvV9rBb7NWVvGCB8g9IGz7Vz6Kh73l73Fz3HVxJ2kneVx2l2IVKNGNvSeTlv8OHxOo0bw2NzOVaazUd3j+xlo5uauY5eaw0c3NXMc3NeXTonYToGYZLqtiwjfX2+pbTTvLqOQ6EH92TxHLrWnRyk8VcMlV1nc1rCvGvReTXP8H+DNdc2cK0HCa2Mm4bdqFiMH1bq3DtLK92r2tMbjzadPu0WYXvNrcRuaEK0d0kn5o88q03SnKD3p5CTSTU5GCEJIBpJoQCTSQ5wa0ucQABqSUBQuNbSW6hmrq+pipqaB4fFKw6Frh/wDt3FVjNxNhh+kVzaTSm9aHB/R/4julC1XKzF0OMsJwXMNbHVMPiauJu5kgG3TkRoR6dOC2pZCeazPS6FaFemqkHmntGkhCqSjSTQgEhCEAITSQGGxzC+owhdooxq80ryB6Br+S54jnBGoOwrp97Q9ha4AtI0IPELm7Hdimw1iKahc13sZ5MlK/g6M7d6RuP/lcZpXZynqV1uWx/T6ndaGXEH6y2lve1fj2P6FCObbormKbmsLHLzVxHPzXCzonbToGbim56K5ZNzWFim5rLWCiqbxdIbfSjV8p2ngxvFx5BYytZVJqEFm2YFenGnFzlsSJfy4Y5uFad7h/WPe8ejpf+FsioUFNFRUUNJANI4WBjfQAq69rsLf+GtqdH7qS8keS3VVVq06i7W2JNJNZZACSaSAaSaEAKMPCYxPJhvLCqjpZTHV3SQUMTmna1rgTIR/kBH0hSeubPDVrdZMMUDZG7PZMz2A7dfc2gkev7VSW41+KVnStJyW/LLz2HO7FcR7VaxnaFcxqFnm80XcRVxGVaRlXEZ2qwwpovIyqw3K1jcq4cFazFmtpL/gv3mSjxrVWdzz4m4UpcG6/vI9oPZLl0suRvB/6bs2rKGE/vifR4p+q6"""

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'frontend' / 'public' / 'icons'
OUT.mkdir(parents=True, exist_ok=True)

src_bytes = base64.b64decode(LOGO_B64)
im = Image.open(BytesIO(src_bytes)).convert('RGBA')
bg = Image.new('RGBA', im.size, (255, 255, 255, 255))
bg.paste(im, (0, 0), im if im.mode == 'RGBA' else None)
im = bg

for size in (192, 512, 180):
    resized = im.resize((size, size), Image.Resampling.LANCZOS)
    for side in ('warga', 'pengurus'):
        path = OUT / f'{side}-{size}.png'
        resized.save(path, 'PNG', optimize=True)
        assert path.read_bytes()[:8] == b'\x89PNG\r\n\x1a\n'
        print('wrote', path, path.stat().st_size)
print('icons ok (logo resmi embedded)')
