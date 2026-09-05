@php
$containerFooter =
isset($configData['contentLayout']) && $configData['contentLayout'] === 'compact'
? 'container-xxl'
: 'container-fluid';
@endphp

<!-- Footer-->
<footer class="content-footer footer bg-footer-theme">
    <div class="{{ $containerFooter }}">
        <div class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
            <div class="text-body">
                &#169;
                <script>
                document.write(new Date().getFullYear());
                </script>
                , made with ❤️ by <a href="{{ !empty(config('demo-vuexy-variables.creatorUrl')) ? config('demo-vuexy-variables.creatorUrl') : '' }}" target="_blank" class="footer-link">{{ !empty(config('demo-vuexy-variables.creatorName')) ? config('demo-vuexy-variables.creatorName') : '' }}</a>
            </div>
            <div class="d-none d-lg-inline-block">
                <a href="{{ config('demo-vuexy-variables.licenseUrl') ? config('demo-vuexy-variables.licenseUrl') : '#' }}" class="footer-link me-4" target="_blank">License</a>
                <a href="{{ config('demo-vuexy-variables.moreThemes') ? config('demo-vuexy-variables.moreThemes') : '#' }}" target="_blank" class="footer-link me-4">More Themes</a>
                <a href="{{ config('demo-vuexy-variables.documentation') ? config('demo-vuexy-variables.documentation') . '/laravel-introduction.html' : '#' }}" target="_blank" class="footer-link me-4">Documentation</a>
                <a href="{{ config('demo-vuexy-variables.support') ? config('demo-vuexy-variables.support') : '#' }}" target="_blank" class="footer-link">Support</a>
            </div>
        </div>
    </div>
</footer>
<!-- / Footer -->
