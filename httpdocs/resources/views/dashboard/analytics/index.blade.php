@extends('layouts.app')
@section('title','Analytics')
@section('page','Analytics')
@section('content')
<div class="grid gap-4">
  <x-card title="Room activity (heatmap)"><div class="h-64 grid place-items-center text-slate-400">Heatmap (bind data)</div></x-card>
  <div class="grid md:grid-cols-2 gap-4">
    <x-card title="Event breakdown">
      <div id="chart-pie" class="h-64"></div>
      <script>
        document.addEventListener('DOMContentLoaded', function(){
          var options = { chart:{ type:'donut', height:240 }, labels:['Work','Phone','Away'],
            series:[68,20,12], theme:{ mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' } };
          new ApexCharts(document.querySelector("#chart-pie"), options).render();
        });
      </script>
    </x-card>
    <x-card title="Cameras uptime">
      <div id="chart-bars" class="h-64"></div>
      <script>
        document.addEventListener('DOMContentLoaded', function(){
          var options = { chart:{ type:'bar', height:240, toolbar:{show:false} },
            series:[{ name:'Uptime %', data:[99, 97, 98, 94, 99, 96] }],
            xaxis:{ categories:['Cam 1','Cam 2','Cam 3','Cam 4','Cam 5','Cam 6'] },
            theme:{ mode: document.documentElement.classList.contains('dark') ? 'dark':'light' } };
          new ApexCharts(document.querySelector("#chart-bars"), options).render();
        });
      </script>
    </x-card>
  </div>
</div>
@endsection
