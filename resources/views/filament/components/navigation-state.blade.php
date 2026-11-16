<script data-navigate-once>
    (() => {
        if (window.__adminNavigationStateInitialized) {
            return;
        }

        window.__adminNavigationStateInitialized = true;

        const expandActiveNavigationGroup = () => {
            window.requestAnimationFrame(() => {
                const activeGroup = document.querySelector('.fi-sidebar-group.fi-active');
                const label = activeGroup?.dataset.groupLabel;
                const sidebar = window.Alpine?.store('sidebar');

                if (! label || ! sidebar?.collapsedGroups?.includes(label)) {
                    return;
                }

                sidebar.collapsedGroups = sidebar.collapsedGroups.filter((group) => group !== label);
            });
        };

        const closeMobileNavigationOnEscape = (event) => {
            if (event.key !== 'Escape' || event.defaultPrevented) {
                return;
            }

            const visibleDialog = Array.from(document.querySelectorAll('[role="dialog"], [role="alertdialog"]'))
                .some((dialog) => dialog.getClientRects().length > 0);
            const sidebar = document.querySelector('.fi-sidebar.fi-sidebar-open');
            const closeButton = document.querySelector('.fi-topbar-close-sidebar-btn');

            if (visibleDialog || ! sidebar || ! closeButton?.getClientRects().length) {
                return;
            }

            event.preventDefault();
            closeButton.click();

            window.setTimeout(() => {
                document.querySelector('.fi-topbar-open-sidebar-btn')?.focus();
            }, 50);
        };

        document.addEventListener('alpine:initialized', expandActiveNavigationGroup);
        document.addEventListener('DOMContentLoaded', expandActiveNavigationGroup);
        document.addEventListener('livewire:navigated', expandActiveNavigationGroup);
        document.addEventListener('keydown', closeMobileNavigationOnEscape);
        expandActiveNavigationGroup();
    })();
</script>
