import Alpine from 'alpinejs';

Alpine.data('LogoBar', () => ({
    init() {
        const logos = this.$refs.logos;
        this.$nextTick(() => {
            for (let i = 1; i <= 3; i++) {
                logos.insertAdjacentHTML('afterend', logos.outerHTML);
                logos.nextSibling.setAttribute('aria-hidden', 'true');
            }
        });
    },
}));
