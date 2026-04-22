@extends('layouts.public')
@section('title','Contact')
@section('content')
<div class='max-w-3xl mx-auto px-4 py-16'><x-card title='Contact us'><form class='grid md:grid-cols-2 gap-4'><x-input placeholder='Full name'/><x-input placeholder='Work email'/><x-input placeholder='Company' class='md:col-span-2'/><x-textarea rows='5' placeholder='How can we help?' class='md:col-span-2'/><x-button class='md:col-span-2'>Send</x-button></form></x-card></div>
@endsection
