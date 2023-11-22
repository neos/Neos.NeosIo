export default () => ({
    init() {
        let $list = this.$el.querySelector('.item-list');
        $list.replaceChildren(...[...$list.children].sort(() => Math.random() - 0.5));
        this.$nextTick(() => {
            let ul = this.$refs.logos;
            for (let i = 1; i <= 3; i++) {
                ul.insertAdjacentHTML('afterend', ul.outerHTML);
                ul.nextSibling.setAttribute('aria-hidden', 'true');
            }
        });
    },
});
