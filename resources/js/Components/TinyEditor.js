document.addEventListener('alpine:init', () => {
    window.Alpine.data('tinyEditor', ({ statePath, height, menubar, toolbar, plugins, apiKey, disabled }) => ({
        editor: null,

        initEditor() {
            const self = this;

            const loadAndInit = () => {
                window.tinymce.init({
                    target: this.$refs.editor,
                    height: height,
                    menubar: menubar,
                    toolbar: toolbar,
                    plugins: plugins,
                    readonly: disabled,
                    skin: document.documentElement.classList.contains('dark') ? 'oxide-dark' : 'oxide',
                    content_css: document.documentElement.classList.contains('dark') ? 'dark' : 'default',
                    promotion: false,
                    branding: false,
                    setup(editor) {
                        self.editor = editor;

                        editor.on('init', () => {
                            const current = self.$wire.get(statePath) ?? '';
                            editor.setContent(current);
                        });

                        editor.on('change input keyup', () => {
                            self.$wire.set(statePath, editor.getContent(), false);
                        });
                    },
                });
            };

            if (window.tinymce) {
                loadAndInit();
            } else {
                const script = document.createElement('script');
                script.src = `https://cdn.tiny.cloud/1/${apiKey}/tinymce/7/tinymce.min.js`;
                script.referrerPolicy = 'origin';
                script.onload = loadAndInit;
                document.head.appendChild(script);
            }

            const observer = new MutationObserver(() => {
                if (self.editor) {
                    const isDark = document.documentElement.classList.contains('dark');
                    self.editor.dom.styleSheetLoader.load(isDark ? 'dark' : 'default', () => {});
                }
            });
            observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        },

        destroy() {
            if (this.editor) {
                this.editor.destroy();
                this.editor = null;
            }
        },
    }));
});
