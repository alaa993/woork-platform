<nav class="px-3 py-4 text-sm">
  <ul class="space-y-1">
    <li><a class="block px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10" href="{{ route('app') }}">{{ __('dashboard.dashboard') }}</a></li>
    <li><a class="block px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10" href="{{ route('rooms.index') }}">{{ __('dashboard.rooms') }}</a></li>
    <li><a class="block px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10" href="{{ route('cameras.index') }}">{{ __('dashboard.cameras') }}</a></li>
    <li><a class="block px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10" href="{{ route('employees.index') }}">{{ __('dashboard.employees') }}</a></li>
    <li><a class="block px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10" href="{{ route('alerts.index') }}">{{ __('dashboard.alerts') }}</a></li>
    <li><a class="block px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10" href="{{ route('policies.index') }}">{{ __('dashboard.policies') }}</a></li>
    <li><a class="block px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10" href="{{ route('billing.index') }}">{{ __('dashboard.subscription') }}</a></li>
    <li><a class="block px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10" href="{{ route('export.csv') }}">{{ __('dashboard.export_csv') }}</a></li>
    <li><a class="block px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10" href="{{ route('settings.index') }}">{{ __('dashboard.settings') }}</a></li>
  </ul>
</nav>
