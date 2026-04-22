@extends('layouts.app')
@section('title', __('woork.cameras'))
@section('page', __('woork.cameras'))
@section('actions')<a href="{{ route('cameras.create') }}" class="rounded-xl bg-emerald-600 text-white px-3 py-1.5 text-sm">{{ __('woork.add') }}</a>@endsection
@section('content')
<div class="overflow-auto rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/60 dark:bg-white/5 backdrop-blur">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600 dark:bg-white/[0.04] dark:text-slate-300">
      <tr>
        <th class="px-3 py-2 text-start font-medium">{{ __('woork.name') }}</th>
        <th class="px-3 py-2 text-start font-medium">{{ __('woork.room') }}</th>
        <th class="px-3 py-2 text-start font-medium">{{ __('woork.agent_device') }}</th>
        <th class="px-3 py-2 text-start font-medium">{{ __('woork.analysis_mode') }}</th>
        <th class="px-3 py-2 text-start font-medium">{{ __('woork.stream_status') }}</th>
        <th class="px-3 py-2 text-start font-medium">{{ __('woork.last_seen_at') }}</th>
        <th class="px-3 py-2 text-start font-medium">{{ __('woork.actions') }}</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-200/70 dark:divide-white/10">
      @forelse($cameras as $item)
        <tr>
          <td class="px-3 py-2">
            <div class="font-medium">{{ $item->name }}</div>
            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $item->purpose }}</div>
          </td>
          <td class="px-3 py-2">{{ $item->room?->name ?? '—' }}</td>
          <td class="px-3 py-2">{{ $item->agentDevice?->name ?? __('woork.unassigned') }}</td>
          <td class="px-3 py-2">{{ $item->analysis_mode }}</td>
          <td class="px-3 py-2">
            <div>{{ $item->stream_status ?: ($item->status ?: 'unknown') }}</div>
            @if($item->health_message)
              <div class="text-xs text-slate-500 dark:text-slate-400">{{ $item->health_message }}</div>
            @endif
          </td>
          <td class="px-3 py-2">{{ optional($item->last_seen_at)->diffForHumans() ?? '—' }}</td>
          <td class="px-3 py-2">
            <a class="text-emerald-600 hover:underline" href="{{ route('cameras.edit', $item) }}">{{ __('woork.edit') }}</a>
          </td>
        </tr>
      @empty
        <tr><td colspan="7" class="py-6 text-center text-slate-400">{{ __('woork.no_data') }}</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div class="mt-4">{{ $cameras->links() }}</div>
@endsection
