import debounce from 'lodash.debounce';
import BaseComponent from "DistributionPackages/Neos.NeosIo/Resources/Private/JavaScript/Components/BaseComponent";

class ScrollClassToggler extends BaseComponent {

    constructor(el) {
        super(el);
        const handler = debounce(() => {
            const currentScrollPos = window.scrollY;
            const lastScrollPos = this.currentScrollPos;
            this.currentScrollPos = currentScrollPos;

            this.evaluateState(currentScrollPos, lastScrollPos);
        });

        window.addEventListener('scroll', handler);
    }

    evaluateState = (currentScrollPos = 0, lastScrollPos = 0) => {
        Object.keys(this.scrollClasses).forEach(key => {
            const targetScrollPoint = Math.abs(key);
            const data = this.scrollClasses[key];
            const { className, removeOnScrollDecrease } = data;
            const method =
                // General check if the class should be added or removed.
                currentScrollPos > targetScrollPoint &&
                // In case `removeOnScrollDecrease` was set to `true`, remove the class on scroll up.
                !(currentScrollPos < lastScrollPos && removeOnScrollDecrease === true)
                    ? 'add'
                    : 'remove';

            this.el.classList[method](className);
        });
    }
}

ScrollClassToggler.prototype.props = {
    scrollClasses: '',
    currentScrollPos: window.scrollY,
    removeClassOnScrollDecrease: false,
}

export default ScrollClassToggler;
