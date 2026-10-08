#!/usr/bin/env python3
"""Tulis ikon PWA dari logo resmi di scripts/logo-master.b64 (bukan generate huruf)."""
import base64
from io import BytesIO
from pathlib import Path

try:
    from PIL import Image
except ImportError:
    import subprocess, sys
    subprocess.check_call([sys.executable, '-m', 'pip', 'install', 'pillow', '-q'])
    from PIL import Image

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'frontend' / 'public' / 'icons'
OUT.mkdir(parents=True, exist_ok=True)
B64_FILE = Path(__file__).resolve().parent / 'logo-master.b64'

def make_square(im: Image.Image, size: int, pad_ratio: float = 0.08) -> Image.Image:
    canvas = Image.new('RGBA', (size, size), (255, 255, 255, 255))
    margin = int(size * pad_ratio)
    box = size - 2 * margin
    fitted = im.copy()
    fitted.thumbnail((box, box), Image.Resampling.LANCZOS)
    x = (size - fitted.width) // 2
    y = (size - fitted.height) // 2
    canvas.paste(fitted, (x, y), fitted)
    return canvas

if not B64_FILE.is_file():
    raise SystemExit(f'Logo master tidak ada: {B64_FILE}')

src = Image.open(BytesIO(base64.b64decode(B64_FILE.read_text().strip()))).convert('RGBA')
for s in (192, 512, 180):
    img = make_square(src, s)
    for side in ('warga', 'pengurus'):
        path = OUT / f'{side}-{s}.png'
        img.save(path, 'PNG', optimize=True)
        assert path.read_bytes()[:8] == b'\x89PNG\r\n\x1a\n', path
        print('wrote', path, path.stat().st_size)
print('icons ok (logo resmi)')
