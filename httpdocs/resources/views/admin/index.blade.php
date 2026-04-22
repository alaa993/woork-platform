@extends('layouts.app')
@section('title','Admin')
@section('page','Admin')
@section('content')
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
  <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/60 dark:bg-white/5 backdrop-blur p-5">
    <div class="text-xs text-slate-500">Tenants</div><div class="mt-1 text-3xl font-semibold">{{ $stats['orgs'] ?? 0 }}</div>
  </div>
  <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/60 dark:bg-white/5 backdrop-blur p-5">
    <div class="text-xs text-slate-500">Users</div><div class="mt-1 text-3xl font-semibold">{{ $stats['users'] ?? 0 }}</div>
  </div>
  <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/60 dark:bg-white/5 backdrop-blur p-5">
    <div class="text-xs text-slate-500">Cameras</div><div class="mt-1 text-3xl font-semibold">{{ $stats['cameras'] ?? 0 }}</div>
  </div>
</div>
@endsection
