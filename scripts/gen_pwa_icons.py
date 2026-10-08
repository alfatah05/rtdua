#!/usr/bin/env python3
"""Generate PWA icons from scripts/logo-master*.b64 (logo resmi rumah rtdua).
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
OUT = ROOT / 'frontend' / 'public' / 'icons'
OUT.mkdir(parents=True, exist_ok=True)

parts = []
for name in ('logo-master.b64', 'logo-master-a.b64', 'logo-master-b.b64'):
    p = ROOT / 'scripts' / name
    if p.is_file() and p.stat().st_size > 100:
        parts.append(p.read_text().strip())

if not parts:
    print('ERROR: logo-master*.b64 tidak ada', file=sys.stderr)
    sys.exit(1)

# Jika ada a+b, utamakan a+b (full); jika ada single file full, pakai itu
a = ROOT / 'scripts' / 'logo-master-a.b64'
b = ROOT / 'scripts' / 'logo-master-b.b64'
if a.is_file() and b.is_file() and a.stat().st_size > 1000 and b.stat().st_size > 1000:
    raw = a.read_text().strip() + b.read_text().strip()
else:
    raw = max(parts, key=len)

src_bytes = base64.b64decode(raw)
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

print('icons ok (dari logo-master)')
