@extends('layouts.app')
@section('title','Rooms')
@section('page','Rooms')
@section('actions')<a href="{{ route('rooms.create') }}" class="rounded-xl bg-emerald-600 text-white px-3 py-1.5 text-sm">Add</a>@endsection
@section('content')
<div class="overflow-auto rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/60 dark:bg-white/5 backdrop-blur">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600 dark:bg-white/[0.04] dark:text-slate-300">
      <tr>
<th class='px-3 py-2 text-start font-medium'>Name</th>\n<th class='px-3 py-2 text-start font-medium'>Location</th>\n<th class='px-3 py-2 text-start font-medium'>Actions</th>\n      </tr>
    </thead>
    <tbody class="divide-y divide-slate-200/70 dark:divide-white/10">
      @forelse($rooms as $item)
        <tr>
<td class='px-3 py-2'>{{ $item->name }}</td>\n<td class='px-3 py-2'>{{ $item->location }}</td>\n          <td class='px-3 py-2'>
            <a class='text-emerald-600 hover:underline' href='{{ route("rooms.edit", $item) }}'>Edit</a>
          </td>
        </tr>
      @empty
        <tr><td colspan="3" class="py-6 text-center text-slate-400">No data.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div class="mt-4">{{ $rooms->links() }}</div>
@endsection
