import {component} from '@reduct/component';
import propTypes from '@reduct/nitpick';
import debounce from 'lodash.debounce';

@component({
	scrollPoint: propTypes.number.isRequired,
	className: propTypes.string.isRequired,
	removeClassOnScrollDecrease: propTypes.bool.isRequired
})
export default class ScrollClassToggler {
	constructor() {
		const {removeClassOnScrollDecrease} = this.props;
		const handler = debounce(() => {
			const currentScrollPos = window.scrollY;
			const lastScrollPos = this.state.currentScrollPos;

			this.setState({
				currentScrollPos
			});

			if (currentScrollPos < lastScrollPos && removeClassOnScrollDecrease) {
				this.setClassName('remove');
			} else {
				this.evaluateState();
			}
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

	evaluateState() {
		const {currentScrollPos} = this.state;
		const method = currentScrollPos > this.props.scrollPoint ? 'add' : 'remove';

		this.setClassName(method);
	}

	setClassName(method = 'add') {
		this.el.classList[method](this.props.className);
	}
}
