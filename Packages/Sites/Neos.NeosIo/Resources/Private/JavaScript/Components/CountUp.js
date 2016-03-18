import {component} from '@reduct/component';
import propTypes from '@reduct/nitpick';
import raf from 'raf';

@component({
	to: propTypes.number.isRequired
})
export default class CountUpComponent {
	constructor() {
		let isAnimating = false;

		raf(function tick() {
			const isInViewPort = this.isElementInViewport();

			if (isInViewPort && isAnimating === false) {
				// Prevent loops in the rAF.
				isAnimating = true;

				setTimeout(() => this.animate(), 300);
			} else {
				raf(tick.bind(this));
			}
		}.bind(this));
	}

	getInitialState() {
		return {
			isAnimating: false
		};
	}

	isElementInViewport() {
		const el = this.el;
		const rect = el.getBoundingClientRect();

		return (
			rect.top >= 0 &&
			rect.left >= 0 &&
			rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
			rect.right <= (window.innerWidth || document.documentElement.clientWidth)
		);
	}

	animate() {
		const {to} = this.props;
		const {el} = this;
		let delay = 0;
		let count = to * 0.5;

		function ease() {
			count += 1;

			if (count > 30) {
				delay += 5;
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
