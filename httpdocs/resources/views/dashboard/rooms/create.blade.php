@extends('layouts.app')
@section('title','Create Rooms')
@section('page','Create Rooms')
@section('content')
<div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/60 dark:bg-white/5 backdrop-blur p-5">
  <form method="POST" action="{{ route('rooms.store') }}" class="grid md:grid-cols-2 gap-4">@csrf

    <div>
      <label class="block text-sm mb-1">Name</label>
      <input name="name" value="{{ old('name', $item->name ?? '') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 placeholder-slate-400 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700">
    </div>

    <div>
      <label class="block text-sm mb-1">Location</label>
      <input name="location" value="{{ old('location', $item->location ?? '') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 placeholder-slate-400 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700">
    </div>

    <div class="md:col-span-2 flex gap-3">
      <button class="rounded-xl bg-emerald-600 text-white px-4 py-2">Save</button>
      <a href="{{ route('rooms.index') }}" class="px-4 py-2 rounded-xl border dark:border-white/10">Cancel</a>
    </div>
  </form>
</div>
@endsection
