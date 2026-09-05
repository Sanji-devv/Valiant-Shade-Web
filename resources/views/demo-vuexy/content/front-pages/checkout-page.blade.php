@extends('demo-vuexy.layouts/layoutMaster')

@section('title', 'Checkout - Front Pages')

<!-- Vendor Styles -->
@section('vendor-style')
@vite(['resources/demo-vuexy/assets/vendor/libs/select2/select2.scss',
'resources/demo-vuexy/assets/vendor/libs/bs-stepper/bs-stepper.scss',
'resources/demo-vuexy/assets/vendor/libs/raty-js/raty-js.scss',
'resources/demo-vuexy/assets/vendor/libs/@form-validation/form-validation.scss'], 'static/demo-vuexy/build')
@endsection

<!-- Page Styles -->
@section('page-style')
@vite(['resources/demo-vuexy/assets/vendor/scss/pages/wizard-ex-checkout.scss'], 'static/demo-vuexy/build')
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
@vite(['resources/demo-vuexy/assets/vendor/libs/jquery/jquery.js',
'resources/demo-vuexy/assets/vendor/libs/select2/select2.js',
'resources/demo-vuexy/assets/vendor/libs/bs-stepper/bs-stepper.js',
'resources/demo-vuexy/assets/vendor/libs/raty-js/raty-js.js',
'resources/demo-vuexy/assets/vendor/libs/cleave-zen/cleave-zen.js',
'resources/demo-vuexy/assets/vendor/libs/@form-validation/popular.js',
'resources/demo-vuexy/assets/vendor/libs/@form-validation/bootstrap5.js',
'resources/demo-vuexy/assets/vendor/libs/@form-validation/auto-focus.js'], 'static/demo-vuexy/build')
@endsection

<!-- Page Scripts -->
@section('page-script')
@vite(['resources/demo-vuexy/assets/js/modal-add-new-address.js',
'resources/demo-vuexy/assets/js/wizard-ex-checkout.js'], 'static/demo-vuexy/build')
@endsection


@section('content')
<section class="section-py bg-body first-section-pt">
  <div class="container">
    <!--/ Checkout Wizard -->
    @include('demo-vuexy._partials/wizard-ex-checkout')

    <!-- Add new address modal -->
    @include('demo-vuexy._partials/_modals/modal-add-new-address')
  </div>
</section>
@endsection