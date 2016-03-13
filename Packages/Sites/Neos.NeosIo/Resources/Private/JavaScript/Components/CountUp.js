import {component} from '@reduct/component';
import propTypes from '@reduct/nitpick';
import raf from 'raf';

@component({
	to: propTypes.number.isRequired
})
export default class CountUpComponent {
	constructor() {
		raf(function tick() {
			const isInViewPort = this.isElementInViewport();
			const {isAnimating} = this.state;

			if (isInViewPort && !isAnimating) {
				this.animate();
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

		// Prevent the loops in the rAF.
		this.setState({
			isAnimating: true
		});

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
