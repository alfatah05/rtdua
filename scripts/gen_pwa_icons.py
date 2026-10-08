#!/usr/bin/env python3
"""Generate PWA icons from scripts/logo-master.png (atau .b64).
Logo resmi rumah rtdua — hanya resize, tidak digambar ulang.
"""
from pathlib import Path
import sys
import base64

try:
    from PIL import Image
except ImportError:
    import subprocess
    subprocess.check_call([sys.executable, '-m', 'pip', 'install', 'pillow', '-q'])
    from PIL import Image

ROOT = Path(__file__).resolve().parents[1]
MASTER_PNG = ROOT / 'scripts' / 'logo-master.png'
MASTER_B64 = ROOT / 'scripts' / 'logo-master.b64'
OUT = ROOT / 'frontend' / 'public' / 'icons'
OUT.mkdir(parents=True, exist_ok=True)

if MASTER_PNG.is_file():
    src_bytes = MASTER_PNG.read_bytes()
elif MASTER_B64.is_file():
    src_bytes = base64.b64decode(MASTER_B64.read_text().strip())
    MASTER_PNG.write_bytes(src_bytes)  # cache for next run
else:
    print('ERROR: scripts/logo-master.png atau .b64 tidak ada', file=sys.stderr)
    sys.exit(1)

from io import BytesIO
im = Image.open(BytesIO(src_bytes)).convert('RGBA')
bg = Image.new('RGBA', im.size, (255, 255, 255, 255))
bg.paste(im, (0, 0), im)
im = bg

for size in (192, 512, 180):
    resized = im.resize((size, size), Image.Resampling.LANCZOS)
    for side in ('warga', 'pengurus'):
        path = OUT / f'{side}-{size}.png'
        resized.save(path, 'PNG', optimize=True)
        head = path.read_bytes()[:8]
        if head != b'\x89PNG\r\n\x1a\n':
            print('ERROR: bukan PNG valid', path, file=sys.stderr)
            sys.exit(1)
        print('wrote', path, path.stat().st_size)

print('icons ok (dari logo-master)')
