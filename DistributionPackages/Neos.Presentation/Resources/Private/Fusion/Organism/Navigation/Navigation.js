import Alpine from 'alpinejs';

Alpine.data('NavigationItem', () => ({
    open: false,
    init() {
        Alpine.bind(this.$root, this.root);
    },
    root: {
        ['x-on:focusout'](event) {
            if (event.currentTarget.contains(event.relatedTarget)) {
            } else {
                this.open = false;
            }
        },
    },
}));
