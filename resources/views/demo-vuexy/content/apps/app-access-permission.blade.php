@extends('demo-vuexy.layouts/layoutMaster')

@section('title', 'Permission - Apps')

@section('vendor-style')
@vite(['resources/demo-vuexy/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/demo-vuexy/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/demo-vuexy/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/demo-vuexy/assets/vendor/libs/@form-validation/form-validation.scss'], 'static/demo-vuexy/build')
@endsection

@section('vendor-script')
@vite(['resources/demo-vuexy/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/demo-vuexy/assets/vendor/libs/@form-validation/popular.js', 'resources/demo-vuexy/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/demo-vuexy/assets/vendor/libs/@form-validation/auto-focus.js'], 'static/demo-vuexy/build')
@endsection

@section('page-script')
@vite(['resources/demo-vuexy/assets/js/app-access-permission.js', 'resources/demo-vuexy/assets/js/modal-add-permission.js', 'resources/demo-vuexy/assets/js/modal-edit-permission.js'], 'static/demo-vuexy/build')
@endsection

@section('content')
<!-- Permission Table -->
<div class="card">
  <div class="card-datatable table-responsive">
    <table class="datatables-permissions table border-top">
      <thead>
        <tr>
          <th></th>
          <th></th>
          <th>Name</th>
          <th>Assigned To</th>
          <th>Created Date</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>
</div>
<!--/ Permission Table -->

<!-- Modal -->
@include('demo-vuexy._partials/_modals/modal-add-permission')
@include('demo-vuexy._partials/_modals/modal-edit-permission')
<!-- /Modal -->
@endsection