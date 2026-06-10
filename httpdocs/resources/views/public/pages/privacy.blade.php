@extends('layouts.landing')
@section('title', __('public.footer.privacy'))

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10 prose dark:prose-invert">
  <h1>{{ __('public.footer.privacy') }}</h1>
  <p>Woork processes workplace analytics from customer-owned cameras. Video stays on the customer network; only derived events and operational metadata are sent to the cloud.</p>
  <p>For privacy requests contact your organization administrator or Woork support.</p>
</div>
@endsection
