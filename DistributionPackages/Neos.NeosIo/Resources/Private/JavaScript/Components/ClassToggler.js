import BaseComponent from "DistributionPackages/Neos.NeosIo/Resources/Private/JavaScript/Components/BaseComponent";

class ClassToggler extends BaseComponent {

    constructor(el) {
        super(el);
        this.target = document.querySelector(this.targetSelector);

        this.el.addEventListener('click', e => {
            e.preventDefault();

            this.toggleClass();
        });
    }

    toggleClass = () => {
        const { activeClass, targetActiveClass, documentActiveClass } = this;

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

        self.classList.toggle(activeClass);
        target.classList.toggle(targetActiveClass);

        if (documentActiveClass && documentActiveClass.length) {
            document.documentElement.classList.toggle(documentActiveClass);
        }
    }
}

ClassToggler.prototype.props = {
    activeClass: '',
    targetSelector: '',
    targetActiveClass: '',
    documentActiveClass: '',
    target: null
}

export default ClassToggler;
