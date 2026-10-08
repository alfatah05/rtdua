#!/usr/bin/env python3
"""Buat ikon PWA placeholder (huruf W / P). Butuh Pillow di CI."""
from pathlib import Path
try:
    from PIL import Image, ImageDraw, ImageFont
except ImportError:
    import subprocess, sys
    subprocess.check_call([sys.executable, '-m', 'pip', 'install', 'pillow', '-q'])
    from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'frontend' / 'public' / 'icons'
OUT.mkdir(parents=True, exist_ok=True)

def make(path, size, bg, circle, letter, letter_color):
    img = Image.new('RGBA', (size, size), bg)
    d = ImageDraw.Draw(img)
    pad = int(size * 0.08)
    d.ellipse([pad, pad, size - pad - 1, size - pad - 1], fill=circle)
    font = None
    for fp in (
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
        '/usr/share/fonts/truetype/freefont/FreeSansBold.ttf',
    ):
        if Path(fp).exists():
            font = ImageFont.truetype(fp, int(size * 0.42))
            break
    if font is None:
        font = ImageFont.load_default()
    bbox = d.textbbox((0, 0), letter, font=font)
    tw, th = bbox[2] - bbox[0], bbox[3] - bbox[1]
    x = (size - tw) // 2 - bbox[0]
    y = (size - th) // 2 - bbox[1]
    d.text((x, y), letter, fill=letter_color, font=font)
    img.save(path, 'PNG')
    print('wrote', path, path.stat().st_size)

G = (10, 143, 68, 255)
Gd = (8, 110, 52, 255)
W = (255, 255, 255, 255)
Bg = (255, 255, 255, 255)
Bg2 = (245, 250, 247, 255)

for s in (192, 512, 180):
    make(OUT / f'warga-{s}.png', s, Bg, G, 'W', W)
    make(OUT / f'pengurus-{s}.png', s, Bg2, Gd, 'P', W)
print('icons ok')
