@props(['name' => 'dashboard', 'class' => 'w-4 h-4'])
@php
  $paths = [
    'menu'      => 'M3 6h18M3 12h18M3 18h18',
    'dashboard' => 'M3 12l9-9 9 9v9H3z',
    'rooms'     => 'M4 6h16v12H4z M9 6v12',
    'camera'    => 'M3 7h12l3-3v16l-3-3H3z',
    'device'    => 'M5 4h14v12H5z M8 20h8 M10 16h4',
    'pulse'     => 'M3 12h4l2-4 4 10 3-6h5',
    'users'     => 'M12 12a4 4 0 100-8 4 4 0 000 8z M4 20a8 8 0 0116 0',
    'bell'      => 'M12 22a2 2 0 002-2H10a2 2 0 002 2z M18 16H6l1-6a5 5 0 019.9 0l1.1 6z',
    'policy'    => 'M6 4h9l3 3v13H6z M15 4v3h3',
    'billing'   => 'M3 6h18v12H3z M3 10h18',
    'export'    => 'M12 3v10 M8 7l4-4 4 4 M4 21h16',
    'download'  => 'M12 4v10 M8 10l4 4 4-4 M4 20h16',
    'settings'  => 'M12 8a4 4 0 100 8 4 4 0 000-8z M4 12h2 M18 12h2 M12 4v2 M12 18v2',
    'admin'     => 'M3 20h18M12 4l6 8H6l6-8z',
    'user'      => 'M12 12a4 4 0 100-8 4 4 0 000 8z M4 20a8 8 0 0116 0',
  ];
@endphp
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="{{ $class }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <path d="{{ $paths[$name] ?? $paths['dashboard'] }}"/>
</svg>
