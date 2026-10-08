#!/usr/bin/env python3
"""Generate PWA icons from scripts/logo-master.b64 (logo resmi rumah rtdua).
Hanya resize — tidak digambar ulang.
"""
from pathlib import Path
import sys
import base64
from io import BytesIO

try:
    from PIL import Image
except ImportError:
    import subprocess
    subprocess.check_call([sys.executable, '-m', 'pip', 'install', 'pillow', '-q'])
    from PIL import Image

ROOT = Path(__file__).resolve().parents[1]
MASTER_B64 = ROOT / 'scripts' / 'logo-master.b64'
OUT = ROOT / 'frontend' / 'public' / 'icons'
OUT.mkdir(parents=True, exist_ok=True)

if not MASTER_B64.is_file() or MASTER_B64.stat().st_size < 1000:
    print('ERROR: scripts/logo-master.b64 tidak ada / kosong', file=sys.stderr)
    sys.exit(1)

src_bytes = base64.b64decode(MASTER_B64.read_text().strip())
im = Image.open(BytesIO(src_bytes)).convert('RGBA')
bg = Image.new('RGBA', im.size, (255, 255, 255, 255))
bg.paste(im, (0, 0), im)
im = bg

for size in (192, 512, 180):
    resized = im.resize((size, size), Image.Resampling.LANCZOS)
    for side in ('warga', 'pengurus'):
        path = OUT / f'{side}-{size}.png'
        resized.save(path, 'PNG', optimize=True)
        if path.read_bytes()[:8] != b'\x89PNG\r\n\x1a\n':
            print('ERROR: bukan PNG valid', path, file=sys.stderr)
            sys.exit(1)
        print('wrote', path, path.stat().st_size)

print('icons ok (dari logo-master.b64)')
