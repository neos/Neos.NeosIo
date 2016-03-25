import {component} from '@reduct/component';
import propTypes from '@reduct/nitpick';

@component({
	activeClass: propTypes.string.isRequired,
	targetSelector: propTypes.string.isRequired,
	targetActiveClass: propTypes.string.isRequired
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
		const {activeClass, targetActiveClass} = this.props;

		this.el.classList.toggle(activeClass);
		this.target.classList.toggle(targetActiveClass);
	}
}
