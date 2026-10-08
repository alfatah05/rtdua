#!/usr/bin/env python3
"""Ikon PWA dari logo rumah resmi (desain: rumah + pohon + matahari + tanah).
Bukan huruf W/P. Digambar ulang agar bisa masuk repo tanpa binary PNG."""
from pathlib import Path

try:
    from PIL import Image, ImageDraw
except ImportError:
    import subprocess, sys
    subprocess.check_call([sys.executable, '-m', 'pip', 'install', 'pillow', '-q'])
    from PIL import Image, ImageDraw

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'frontend' / 'public' / 'icons'
OUT.mkdir(parents=True, exist_ok=True)

def draw_logo(size: int) -> Image.Image:
    # Canvas putih, logo di tengah (padding ~8%)
    img = Image.new('RGBA', (size, size), (255, 255, 255, 255))
    d = ImageDraw.Draw(img)
    s = float(size)

    def xy(*pts):
        return [(p[0] * s, p[1] * s) for p in pts]

    # Tanah gradient (kiri kuning → kanan merah) digambar strip tipis
    ground_y0, ground_y1 = 0.82, 0.94
    steps = max(24, size // 8)
    for i in range(steps):
        t = i / (steps - 1)
        r = int(255 * (1 - t) + 239 * t)
        g = int(152 * (1 - t) + 68 * t)
        b = int(0 * (1 - t) + 68 * t)
        x0 = 0.12 + t * 0.76
        x1 = 0.12 + (t + 1 / steps) * 0.76
        d.rectangle([x0 * s, ground_y0 * s, x1 * s, ground_y1 * s], fill=(r, g, b, 255))
    # rounded ground ends approx
    d.ellipse([0.08 * s, ground_y0 * s, 0.22 * s, ground_y1 * s], fill=(255, 152, 0, 255))
    d.ellipse([0.78 * s, ground_y0 * s, 0.92 * s, ground_y1 * s], fill=(239, 68, 68, 255))

    # Pohon (kiri)
    d.ellipse([0.08 * s, 0.28 * s, 0.42 * s, 0.72 * s], fill=(16, 122, 58, 255))
    d.ellipse([0.14 * s, 0.22 * s, 0.40 * s, 0.50 * s], fill=(20, 140, 68, 255))
    d.ellipse([0.06 * s, 0.48 * s, 0.28 * s, 0.78 * s], fill=(12, 110, 50, 255))

    # Badan rumah kiri (putih kehijauan)
    d.polygon(xy((0.28, 0.42), (0.52, 0.28), (0.76, 0.42), (0.76, 0.82), (0.28, 0.82)), fill=(230, 250, 235, 255))
    # Sayap kanan
    d.polygon(xy((0.62, 0.48), (0.88, 0.48), (0.88, 0.82), (0.62, 0.82)), fill=(140, 220, 170, 255))
    # Atap
    d.polygon(xy((0.48, 0.30), (0.90, 0.30), (0.90, 0.50), (0.70, 0.50)), fill=(16, 130, 60, 255))
    d.polygon(xy((0.28, 0.42), (0.52, 0.28), (0.70, 0.42)), fill=(20, 145, 70, 255))

    # Pintu
    d.rounded_rectangle([0.40 * s, 0.58 * s, 0.52 * s, 0.82 * s], radius=max(2, size // 64), fill=(34, 180, 90, 255))
    # Jendela lingkaran
    d.ellipse([0.44 * s, 0.40 * s, 0.54 * s, 0.50 * s], fill=(40, 190, 100, 255))
    # Jendela kotak
    d.rounded_rectangle([0.70 * s, 0.58 * s, 0.82 * s, 0.70 * s], radius=max(2, size // 64), fill=(30, 160, 90, 255))

    # Matahari
    d.ellipse([0.72 * s, 0.10 * s, 0.92 * s, 0.30 * s], fill=(255, 170, 0, 255))
    d.ellipse([0.74 * s, 0.12 * s, 0.90 * s, 0.28 * s], fill=(255, 140, 0, 255))

    return img

for s in (192, 512, 180):
    logo = draw_logo(s)
    for side in ('warga', 'pengurus'):
        path = OUT / f'{side}-{s}.png'
        logo.save(path, 'PNG', optimize=True)
        assert path.read_bytes()[:8] == b'\x89PNG\r\n\x1a\n', path
        print('wrote', path, path.stat().st_size)
print('icons ok (logo rumah rtdua)')
