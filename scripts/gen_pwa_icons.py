#!/usr/bin/env python3
"""Generate PWA icons from scripts/logo-*.b64 (logo resmi). Hanya resize."""
from pathlib import Path
import sys, base64
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
for i in range(5):
    p = ROOT / 'scripts' / f'logo-{i}.b64'
    if not p.is_file():
        print('ERROR missing', p, file=sys.stderr)
        sys.exit(1)
    parts.append(p.read_text().strip())
src_bytes = base64.b64decode(''.join(parts))
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
print('icons ok (logo resmi)')
