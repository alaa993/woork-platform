@extends('layouts.app')
@section('title', __('woork.edit_employee'))
@section('page', __('woork.edit_employee'))
@section('content')
<div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/60 dark:bg-white/5 backdrop-blur p-5">
  <form method="POST" action="{{ route('employees.update',$emp->id) }}" class="grid md:grid-cols-2 gap-4">@csrf @method('PUT')

    <div class="md:col-span-2">
      <div class="text-xs text-slate-500 dark:text-slate-400">
        {{ __('woork.company_type') }}:
        <span class="font-medium text-slate-700 dark:text-slate-200">
          {{ auth()->user()->organization?->company_type === 'restaurant' ? __('woork.company_type.restaurant') : __('woork.company_type.company') }}
        </span>
      </div>
    </div>

    <div>
      <label class="block text-sm mb-1">{{ __('woork.room') }}</label>
      <select name="room_id" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700">
        <option value="">{{ __('woork.select_room') }}</option>
        @foreach($rooms as $room)
          <option value="{{ $room->id }}" @selected(old('room_id', $emp->room_id) == $room->id)>{{ $room->name }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="block text-sm mb-1">{{ __('woork.name') }}</label>
      <input name="name" value="{{ old('name', $emp->name ?? '') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 placeholder-slate-400 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700">
    </div>

    <div>
      <label class="block text-sm mb-1">{{ __('woork.title') }}</label>
      <input name="title" value="{{ old('title', $emp->title ?? '') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 placeholder-slate-400 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700">
    </div>

    <div class="md:col-span-2 flex gap-3">
      <button class="rounded-xl bg-emerald-600 text-white px-4 py-2">{{ __('woork.save') }}</button>
      <a href="{{ route('employees.index') }}" class="px-4 py-2 rounded-xl border dark:border-white/10">{{ __('woork.cancel') }}</a>
    </div>
  </form>
</div>
@endsection
