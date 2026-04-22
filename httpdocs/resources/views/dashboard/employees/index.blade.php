@extends('layouts.app')
@section('title', __('woork.employees'))
@section('page', __('woork.employees'))
@section('actions')<a href="{{ route('employees.create') }}" class="rounded-xl bg-emerald-600 text-white px-3 py-1.5 text-sm">{{ __('woork.add') }}</a>@endsection
@section('content')
<div class="overflow-auto rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/60 dark:bg-white/5 backdrop-blur">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600 dark:bg-white/[0.04] dark:text-slate-300">
      <tr>
<th class='px-3 py-2 text-start font-medium'>{{ __('woork.name') }}</th>\n<th class='px-3 py-2 text-start font-medium'>{{ __('woork.title') }}</th>\n<th class='px-3 py-2 text-start font-medium'>{{ __('woork.actions') }}</th>\n      </tr>
    </thead>
    <tbody class="divide-y divide-slate-200/70 dark:divide-white/10">
      @forelse($employees as $item)
        <tr>
<td class='px-3 py-2'>{{ $item->name }}</td>\n<td class='px-3 py-2'>{{ $item->title }}</td>\n          <td class='px-3 py-2'>
            <a class='text-emerald-600 hover:underline' href='{{ route("employees.edit", $item) }}'>{{ __('woork.edit') }}</a>
          </td>
        </tr>
      @empty
        <tr><td colspan="3" class="py-6 text-center text-slate-400">{{ __('woork.no_data') }}</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div class="mt-4">{{ $employees->links() }}</div>
@endsection
