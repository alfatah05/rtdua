#!/usr/bin/env python3
"""Generate PWA icons from embedded logo resmi (rumah rtdua). Hanya resize."""
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

# Logo resmi user (192x192 PNG base64) — dari gambar yang dikirim user
LOGO_B64 = open('/tmp/rtdua/scripts/gen_pwa_icons.py').read()  # WILL REPLACE
