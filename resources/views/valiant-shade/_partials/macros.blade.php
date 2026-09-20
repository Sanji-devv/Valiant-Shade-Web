@php
  $width = $width ?? '34';
  $height = $height ?? '34';
  $classes = $classes ?? '';
@endphp

<span class="app-brand-logo-wrapper d-inline-flex align-items-center justify-content-center">
  <img src="{{ asset('static/valiant-shade/assets/img/branding/valiant-shade-logo-small.png') }}"
       alt="{{ config('valiant-shade-variables.templateName', 'Valiant Shade') }}"
       width="{{ $width }}"
       height="{{ $height }}"
       class="app-brand-img {{ $classes }}"
       style="object-fit: contain; filter: drop-shadow(0px 2px 4px rgba(0, 0, 0, 0.25));" />
</span>
