# Woork Agent Windows Support Matrix

Woork Agent should be distributed as platform-specific builds, not as a single
"universal" Windows binary.

## Production targets

- `windows-x64`: Windows 10/11 64-bit. This is the primary production build.
- `windows-x86`: Windows 10 32-bit. Use only for customer PCs that still run
  a 32-bit OS.
- `windows-7-legacy`: Windows 7 compatibility build with reduced runtime
  features and a legacy control panel.

## Operational guidance

- Publish each platform as a separate `AgentRelease` record.
- Keep the same `version` across platforms when they represent the same product
  release, for example `1.0.0` on `windows-x64` and `windows-x86`.
- Use the same release notes when behavior is shared, and add platform-specific
  caveats when needed.
- Never distribute the Windows 7 legacy build to Windows 10/11 customer PCs.
- Prefer signed installers for all production-facing builds.

## Build prerequisites

- `windows-x64`: 64-bit Python, `WinSW-x64.exe`, Inno Setup 6.
- `windows-x86`: 32-bit Python, `WinSW-x86.exe`, Inno Setup 6.
- `windows-7-legacy`: legacy Python toolchain, `WinSW-x64.exe`, Inno Setup 6.
  Build this target with Python 3.8.x exactly.

## Naming conventions

- `WoorkAgentSetup-1.0.0.exe`: Windows 10/11 64-bit installer.
- `WoorkAgentSetup-x86-1.0.0.exe`: Windows 10 32-bit installer.
- `WoorkAgentSetup-LegacyWin7-1.0.0.exe`: Windows 7 legacy installer.

## Windows 7 runtime notes

- Use Windows 7 SP1.
- Ensure `KB2533623` is installed.
- If `_socket` fails to load at startup, the legacy package was likely built
  with the wrong Python version or the target PC is missing the required system
  update.
