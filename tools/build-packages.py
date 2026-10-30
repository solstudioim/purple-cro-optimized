#!/usr/bin/env python3
"""Build separate installable WordPress packages from the current working tree."""
import argparse
import hashlib
import json
from pathlib import Path
import re
import zipfile

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--output', type=Path, default=Path('/Users/sol/Codex/artifacts/purple-cro-optimized/releases'))
args = parser.parse_args()
root = Path(__file__).resolve().parents[1]
output = args.output.expanduser().resolve()
output.mkdir(parents=True, exist_ok=True)
manifest = {}
for slug, source, entry in [
    ('purple-optimize', root / 'wp-content/themes/purple-optimize', 'style.css'),
    ('purple-optimize-toolkit', root / 'wp-content/plugins/purple-optimize-toolkit', 'purple-optimize-toolkit.php'),
]:
    version = re.search(r'^\s*(?:\*\s*)?Version:\s*(\S+)', (source / entry).read_text(), re.M)[1]
    destination = output / f'{slug}-{version}.zip'
    with zipfile.ZipFile(destination, 'w', zipfile.ZIP_DEFLATED) as archive:
        for file in sorted(source.rglob('*')):
            if file.is_symlink():
                raise ValueError(f'Package may not contain symlinks: {file}')
            if not file.is_file() or any(part.startswith('.') for part in file.relative_to(source).parts):
                continue
            info = zipfile.ZipInfo(f'{slug}/{file.relative_to(source).as_posix()}', (2026, 1, 1, 0, 0, 0))
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = 0o100644 << 16
            archive.writestr(info, file.read_bytes())
    manifest[destination.name] = {'version': version, 'sha256': hashlib.sha256(destination.read_bytes()).hexdigest()}
(output / 'packages.json').write_text(json.dumps(manifest, indent=2) + '\n')
print(json.dumps({'output': str(output), 'packages': manifest}, indent=2))
