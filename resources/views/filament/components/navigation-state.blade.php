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

        document.addEventListener('alpine:initialized', expandActiveNavigationGroup);
        document.addEventListener('DOMContentLoaded', expandActiveNavigationGroup);
        document.addEventListener('livewire:navigated', expandActiveNavigationGroup);
        expandActiveNavigationGroup();
    })();
</script>
