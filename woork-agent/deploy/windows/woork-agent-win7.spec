# PyInstaller spec for Windows 7 legacy builds.
# Use with Python 3.8.x only. Prefer onedir (not onefile) for Win7 DLL loading.

block_cipher = None

a = Analysis(
    ['agent_entry.py'],
    pathex=[],
    binaries=[],
    datas=[],
    hiddenimports=[
        'socket',
        '_socket',
        'ssl',
        '_ssl',
        'select',
        'email',
        'http',
        'http.client',
        'urllib',
        'urllib.request',
        'json',
        'sqlite3',
    ],
    hookspath=[],
    hooksconfig={},
    runtime_hooks=[],
    excludes=[
        'multiprocessing',
        'cv2',
        'numpy',
        'tkinter',
        '_tkinter',
    ],
    win_no_prefer_redirects=False,
    win_private_assemblies=False,
    cipher=block_cipher,
    noarchive=False,
)

pyz = PYZ(a.pure, a.zipped_data, cipher=block_cipher)

exe = EXE(
    pyz,
    a.scripts,
    [],
    exclude_binaries=True,
    name='woork-agent',
    debug=False,
    bootloader_ignore_signals=False,
    strip=False,
    upx=False,
    console=True,
)

coll = COLLECT(
    exe,
    a.binaries,
    a.zipfiles,
    a.datas,
    strip=False,
    upx=False,
    upx_exclude=[],
    name='woork-agent',
)
