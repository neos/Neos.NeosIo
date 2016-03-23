import {component} from '@reduct/component';
import propTypes from '@reduct/nitpick';
import debounce from 'lodash.debounce';

@component({
	scrollClasses: propTypes.object.isRequired
})
export default class ScrollClassToggler {
	constructor() {
		const handler = debounce(() => {
			const currentScrollPos = window.scrollY;
			const lastScrollPos = this.state.currentScrollPos;

			this.setState({
				currentScrollPos
			});

			this.evaluateState(currentScrollPos, lastScrollPos);
		});

		window.addEventListener('scroll', handler);
	}

	getDefaultProps() {
		return {
			removeClassOnScrollDecrease: false
		};
	}

	getInitialState() {
		return {
			currentScrollPos: window.scrollY
		};
	}

	evaluateState(currentScrollPos = 0, lastScrollPos = 0) {
		const {scrollClasses} = this.props;

		Object.keys(scrollClasses).forEach(key => {
			const targetScrollPoint = Math.abs(key);
			const data = scrollClasses[key];
			const {className, removeOnScrollDecrease} = data;
			const method = (
				// General check and add / remove logic.
				currentScrollPos > targetScrollPoint &&

				// In case `removeOnScrollDecrease` was set to `true`, remove the class on scroll up.
				!(currentScrollPos < lastScrollPos && removeOnScrollDecrease === true)
			) ? 'add' : 'remove';

			this.el.classList[method](className);
		});
	}
}
