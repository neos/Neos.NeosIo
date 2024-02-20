import Alpine from 'alpinejs';

Alpine.data('LogoBar', () => ({
    init() {
        const logos = this.$refs.logos;
        logos.replaceChildren(...[...logos.children].sort(() => Math.random() - 0.5));
        this.$nextTick(() => {
            for (let i = 1; i <= 3; i++) {
                logos.insertAdjacentHTML('afterend', logos.outerHTML);
                logos.nextSibling.setAttribute('aria-hidden', 'true');
            }
        });
    },
}));
