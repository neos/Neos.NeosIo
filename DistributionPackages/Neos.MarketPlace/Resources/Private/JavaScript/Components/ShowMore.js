import { component } from '@reduct/component';
import propTypes from '@reduct/nitpick';
import addClass from 'dom-add-class';

@component({
    maximumHeight: propTypes.number.isRequired,
    selector: propTypes.string.isRequired,
    targetClass: propTypes.string.isRequired,
    buttonClass: propTypes.string.isRequired,
    wrapperClass: propTypes.string.isRequired,
    iconClass: propTypes.string.isRequired
})
export default class ShowMoreComponent {
    getDefaultProps() {
        return {
            maximumHeight: 210,
            wrapperClass: 'show-more',
            targetClass: 'show-more__target',
            buttonClass: 'show-more__button',
            iconClass: 'fa fa-chevron-down'
        };
    }

    constructor() {
        const { maximumHeight, selector } = this.props;
        this.target = this.el.querySelector(selector);
        this.state.isOpen = true;

        if (!this.target) {
            return;
        }

        const { offsetHeight } = this.target;

        this.init();
        if (offsetHeight > maximumHeight) {
            this.enable();
        } else {
            this.open();
        }
    }

    init() {
        const { wrapperClass, targetClass } = this.props;
        addClass(this.el, wrapperClass);
        addClass(this.target, targetClass);
    }

    enable() {
        this.close();
        this.appendButton();
    }

    appendButton() {
        let that = this;
        let button = document.createElement('button');
        const { buttonClass, iconClass } = this.props;
        addClass(button, buttonClass);

        let icon = document.createElement('i');
        addClass(icon, iconClass);

        button.appendChild(icon);
        button.addEventListener('click', e => {
            e.preventDefault();
            that.toggle();
        });

        this.el.appendChild(button);
    }

    toggle() {
        if (this.state.isOpen === false) {
            this.open();
        } else {
            this.close();
        }
    }

    close() {
        this.target.style.height = this.props.maximumHeight + 'px';
        this.state.isOpen = false;
    }

    open() {
        this.target.style.height = null;
        this.state.isOpen = true;
    }
}
