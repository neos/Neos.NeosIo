import BaseComponent from "DistributionPackages/Neos.NeosIo/Resources/Private/JavaScript/Components/BaseComponent";

class ShowMoreComponent extends BaseComponent {

    constructor(el) {
        super(el);
        this.target = el.querySelector(this.selector);
        this.isOpen = true;

        if (!this.target) {
            return;
        }

        const {offsetHeight} = this.target;

        this.init();
        if (offsetHeight > this.maximumHeight) {
            this.enable();
        } else {
            this.open();
        }
    }

    init = () => {
        const {wrapperClass, targetClass} = this;
        this.el.classList.add(wrapperClass);
        this.target.classList.add(targetClass);
    }

    enable = () => {
        this.close();
        this.appendButton();
    }

    appendButton = () => {
        let button = document.createElement('button');
        const {buttonClass, iconClass} = this;
        button.classList.add(buttonClass);

        let icon = document.createElement('i');
        icon.classList.add(...iconClass.split(' '));

        button.appendChild(icon);
        button.addEventListener('click', e => {
            e.preventDefault();
            this.toggle();
        });

        this.el.appendChild(button);
    }

    toggle() {
        if (this.isOpen === false) {
            this.open();
        } else {
            this.close();
        }
    }

    close() {
        this.target.style.height = this.maximumHeight + 'px';
        this.isOpen = false;
    }

    open() {
        this.target.style.height = null;
        this.isOpen = true;
    }
}

ShowMoreComponent.prototype.props = {
    isOpen: false,
    selector: '',
    maximumHeight: 210,
    targetClass: 'show-more__target',
    buttonClass: 'show-more__button',
    wrapperClass: 'show-more',
    iconClass: 'fas fa-chevron-down',
}

export default ShowMoreComponent;
