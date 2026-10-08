#!/usr/bin/env python3
"""Ikon PWA dari logo resmi (parts digabung)."""
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
SCR = Path(__file__).resolve().parent

def make_square(im, size, pad_ratio=0.06):
    canvas = Image.new('RGBA', (size, size), (255, 255, 255, 255))
    margin = int(size * pad_ratio)
    box = size - 2 * margin
    fitted = im.copy()
    fitted.thumbnail((box, box), Image.Resampling.LANCZOS)
    x = (size - fitted.width) // 2
    y = (size - fitted.height) // 2
    canvas.paste(fitted, (x, y), fitted)
    return canvas

parts = sorted(SCR.glob('logo.part*.b64'))
if not parts:
    raise SystemExit('logo.part*.b64 tidak ada')
b64 = ''.join(p.read_text().strip() for p in parts)
if len(b64) < 1000:
    raise SystemExit('logo b64 terlalu pendek')
src = Image.open(BytesIO(base64.b64decode(b64))).convert('RGBA')
for s in (192, 512, 180):
    img = make_square(src, s)
    for side in ('warga', 'pengurus'):
        path = OUT / f'{side}-{s}.png'
        img.save(path, 'PNG', optimize=True)
        assert path.read_bytes()[:8] == b'\x89PNG\r\n\x1a\n'
        print('wrote', path, path.stat().st_size)
print('icons ok (logo resmi)')
