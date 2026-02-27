import Alpine from 'alpinejs';

Alpine.data('NavigationItem', () => ({
    open: false,
    init() {
        Alpine.bind(this.$root, this.root);
    },
    root: {
        ['x-on:blur']({currentTarget, relatedTarget} : FocusEvent) {
            if (!(currentTarget as HTMLElement)?.contains(relatedTarget as Node)) {
                this.open = false;
            }
        },
    },
}));
