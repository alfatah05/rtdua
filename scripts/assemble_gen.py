#!/usr/bin/env python3
from pathlib import Path
SCR = Path(__file__).resolve().parent
parts = sorted(SCR.glob('_gen_src_*.txt'))
if not parts:
    raise SystemExit('missing scripts/_gen_src_*.txt')
out = SCR / 'gen_pwa_icons.py'
out.write_text(''.join(p.read_text() for p in parts))
print('assembled', out, out.stat().st_size)
