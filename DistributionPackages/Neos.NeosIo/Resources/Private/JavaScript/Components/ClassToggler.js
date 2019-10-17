import { component } from '@reduct/component';
import propTypes from '@reduct/nitpick';

@component({
    activeClass: propTypes.string.isRequired,
    targetSelector: propTypes.string.isRequired,
    targetActiveClass: propTypes.string.isRequired,
    documentActiveClass: propTypes.string
})
export default class ClassToggler {
    constructor() {
        this.target = document.querySelector(this.props.targetSelector);

        this.el.addEventListener('click', e => {
            e.preventDefault();

            this.toggleClass();
        });
    }

    toggleClass() {
        const { activeClass, targetActiveClass, documentActiveClass } = this.props;

        let otherElementsWithTargetClass = document.querySelectorAll('.' + targetActiveClass);
        let otherElementsWithActiveClass = document.querySelectorAll('.' + activeClass);
        let target = this.target;
        let self = this.el;

        otherElementsWithTargetClass.forEach(function(el) {
            if (el !== target) {
                el.classList.remove(targetActiveClass);
            }
        });

        otherElementsWithActiveClass.forEach(function(el) {
            if (el !== self) {
                el.classList.remove(activeClass);
            }
        });

        this.el.classList.toggle(activeClass);
        this.target.classList.toggle(targetActiveClass);

        if (documentActiveClass && documentActiveClass.length) {
            document.documentElement.classList.toggle(documentActiveClass);
        }
    }
}
