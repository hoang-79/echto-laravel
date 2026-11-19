<echto-widget site="{{ $site }}" form="{{ $form }}" endpoint="{{ $endpoint }}" @if ($action) action="{{ $action }}" @endif @if ($theme) theme="{{ $theme }}" @endif><noscript>{{ __('Ohne JavaScript wird Ihre Nachricht von Hand geprüft, bevor sie ankommt.') }}</noscript></echto-widget>
@once
    <script>
        // Echto: lädt das Widget erst, wenn jemand ein Formular benutzt.
        (function (src) {
            var events = ['focusin', 'pointerdown', 'keydown'];
            function load() {
                events.forEach(function (type) { removeEventListener(type, load, true); });
                document.head.appendChild(Object.assign(document.createElement('script'), { src: src }));
            }
            events.forEach(function (type) { addEventListener(type, load, true); });
        })(@json($endpoint.'/widget/v1/echto.js'));
    </script>
@endonce
