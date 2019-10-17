import { component } from '@reduct/component';
import propTypes from '@reduct/nitpick';
import inViewport from 'in-viewport';

@component({
    to: propTypes.number.isRequired
})
export default class CountUpComponent {
    constructor() {
        const { el } = this;
        const isAlreadyVisible = inViewport(el);
        const onVisible = () => setTimeout(() => this.animate(), 400);

        if (isAlreadyVisible) {
            onVisible();
        } else {
            inViewport(this.el, onVisible);
        }
    }

    animate() {
        const { to } = this.props;
        const { el } = this;
        let delay = 2;
        let count = to - 62 > 0 ? to - 62 : 0;

        function ease() {
            count += 1;

            if (count > to - 40) {
                delay += 5;
            } else {
                delay += 0.3;
            }

            if (count < to) {
                el.innerHTML = count;
                setTimeout(ease, delay);
            } else {
                el.innerHTML = to;
            }
        }

        ease();
    }
}
