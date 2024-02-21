import Alpine from 'alpinejs';

Alpine.data('LogoBar', (duplications) => ({
    init() {
        if (!duplications) {
            return;
        }
        this.$nextTick(() => {
            const element = this.$el.firstElementChild.cloneNode(true);
            element.removeAttribute('x-random-children');
            element.setAttribute('aria-hidden', 'true');
            for (let i = 1; i <= duplications; i++) {
                this.$el.appendChild(element.cloneNode(true));
            }
        });
    },
}));
