@extends('layouts.app')
@section('title','Users')
@section('page','Users')
@section('content')
<div class="overflow-auto rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/60 dark:bg-white/5 backdrop-blur">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600 dark:bg-white/[0.04] dark:text-slate-300">
      <tr><th class="px-3 py-2 text-start font-medium">Name</th><th class="px-3 py-2 text-start font-medium">Email</th><th class="px-3 py-2 text-start font-medium">Phone</th></tr>
    </thead>
    <tbody class="divide-y divide-slate-200/70 dark:divide-white/10">
      @foreach($users as $u)
        <tr>
          <td class="px-3 py-2">{{ $u->name }}</td>
          <td class="px-3 py-2">{{ $u->email }}</td>
          <td class="px-3 py-2">{{ $u->phone }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection
