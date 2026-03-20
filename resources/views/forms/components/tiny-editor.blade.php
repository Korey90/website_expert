@php
    $statePath  = $getStatePath();
    $height     = $getHeight();
    $menubar    = $getMenubar();
    $toolbar    = $getToolbar();
    $plugins    = $getPlugins();
    $apiKey     = $getApiKey();
    $isDisabled = $isDisabled();
@endphp

@script
<script>
    Alpine.data('tinyEditor', ({ statePath, height, menubar, toolbar, plugins, apiKey, disabled }) => ({
        editor: null,

        init() {
            const self = this;

            const launchEditor = () => {
                const isDark = document.documentElement.classList.contains('dark');
                window.tinymce.init({
                    target: self.$refs.editor,
                    height: height,
                    menubar: menubar,
                    toolbar: toolbar,
                    plugins: plugins,
                    readonly: disabled,
                    skin: isDark ? 'oxide-dark' : 'oxide',
                    content_style: isDark
                        ? 'html, body { background-color: #111827 !important; color: #e2e8f0; margin: 8px; font-family: inherit; font-size: 14px; }'
                        : 'html, body { background-color: #ffffff !important; margin: 8px; font-family: inherit; font-size: 14px; }',
                    promotion: false,
                    branding: false,
                    setup(editor) {
                        self.editor = editor;
                        editor.on('init', () => {
                            editor.setContent(self.$wire.get(statePath) ?? '');
                        });
                        editor.on('change input keyup', () => {
                            self.$wire.set(statePath, editor.getContent(), false);
                        });
                    },
                });
            };

            if (window.tinymce) {
                launchEditor();
            } else {
                let cdn = document.getElementById('tinymce-cdn-script');
                if (!cdn) {
                    cdn = document.createElement('script');
                    cdn.id = 'tinymce-cdn-script';
                    cdn.src = `https://cdn.tiny.cloud/1/${apiKey}/tinymce/7/tinymce.min.js`;
                    cdn.referrerPolicy = 'origin';
                    document.head.appendChild(cdn);
                }
                cdn.addEventListener('load', launchEditor);
            }
        },

        destroy() {
            if (this.editor) {
                this.editor.destroy();
                this.editor = null;
            }
        },
    }));
</script>
@endscript

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        wire:ignore
        x-data="tinyEditor({
            statePath: @js($statePath),
            height: @js($height),
            menubar: @js($menubar),
            toolbar: @js($toolbar),
            plugins: @js($plugins),
            apiKey: @js($apiKey),
            disabled: @js($isDisabled),
        })"
    >
        <textarea x-ref="editor"></textarea>
    </div>
</x-dynamic-component>
