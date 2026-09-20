@isset($pageConfigs)
  {!! \App\ValiantShade\Helpers\Helpers::updatePageConfig($pageConfigs) !!}
@endisset
@php
  $configData = \App\ValiantShade\Helpers\Helpers::appClasses();
@endphp

@isset($configData['layout'])
  @include(
      $configData['layout'] === 'horizontal'
          ? 'valiant-shade.layouts.horizontalLayout'
          : ($configData['layout'] === 'blank'
              ? 'valiant-shade.layouts.blankLayout'
              : ($configData['layout'] === 'front'
                  ? 'valiant-shade.layouts.layoutFront'
                  : 'valiant-shade.layouts.contentNavbarLayout')))
@endisset
