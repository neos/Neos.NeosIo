import Alpine from 'alpinejs';

Alpine.data('LogoBar', (duplications: number) => ({
    init() {
        if (!duplications) {
            return;
        }
        this.$nextTick(() => {
            const element = this.$el.firstElementChild?.cloneNode(true) as HTMLElement | null;
            if (!element) {
                return;
            }
            element.removeAttribute('x-random-children');
            element.setAttribute('aria-hidden', 'true');
            for (let i = 1; i <= duplications; i++) {
                this.$el.appendChild(element.cloneNode(true) as HTMLElement);
            }
        });
    },
}));
