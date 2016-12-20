import {component} from '@reduct/component';
import propTypes from '@reduct/nitpick';
import inViewport from 'in-viewport';

@component({
    delay: propTypes.number
})
export default class Slider {
    constructor() {
        const {el} = this;
        const isAlreadyVisible = inViewport(el);
        const onVisible = () => setTimeout(() => this.animate(), 100);

        if (isAlreadyVisible) {
            onVisible();
        } else {
            inViewport(this.el, onVisible);
        }
    }

    getDefaultProps() {
        return {
            delay: 5000
        };
    }

    getInitialState() {
        return {
            position: 0
        };
    }

    animate() {
        const {delay} = this.props;
        const {el} = this;
        const self = this;
        const slides = this.findAll('.slide');
        let {position} = this.state;

        el.classList.add('slider');

        if (slides.length === 0) {
            return;
        }

        function slide() {
            slides[position].classList.remove('active');

            if (position < slides.length - 1) {
                position++;
            } else {
                position = 0;
            }

            slides[position].classList.add('active');

            self.setState({
                position
            });

            setTimeout(slide, delay);
        }
        slide();
    }
}
