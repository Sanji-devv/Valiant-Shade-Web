@isset($pageConfigs)
  {!! \DemoVuexy\Helpers\Helpers::updatePageConfig($pageConfigs) !!}
@endisset
@php
  $configData = \DemoVuexy\Helpers\Helpers::appClasses();
@endphp

@isset($configData['layout'])
  @include(
      $configData['layout'] === 'horizontal'
          ? 'demo-vuexy.layouts.horizontalLayout'
          : ($configData['layout'] === 'blank'
              ? 'demo-vuexy.layouts.blankLayout'
              : ($configData['layout'] === 'front'
                  ? 'demo-vuexy.layouts.layoutFront'
                  : 'demo-vuexy.layouts.contentNavbarLayout')))
@endisset
