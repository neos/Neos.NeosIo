import {component} from '@reduct/component';
import propTypes from '@reduct/nitpick';

//
// Due to an inconsistency between browsers we need to check the UA and set
// the document scroll element.
//
const scrollDocument = /Firefox/.test(navigator.userAgent) || /Trident.*rv[ :]*11\./.test(navigator.userAgent) || /MSIE/.test(navigator.userAgent) ?
	document.documentElement :
	document.body;

function scrollTo(element, to, duration) {
	if (duration <= 0) {
		return;
	}

	const difference = to - element.scrollTop;
	const perTick = difference / duration * 10;

	setTimeout(() => {
		element.scrollTop += Number(perTick);

		if (element.scrollTop !== to) {
			scrollTo(element, to, duration - 10);
		}
	}, 10);
}

@component({
	targetSelector: propTypes.string.isRequired
})
export default class ScrollTo {
	constructor() {
		this.target = document.querySelector(this.props.targetSelector);

		if (!this.target) {
			throw new Error(`ScrollTo: Cannot find target node with selector "${this.props.targetSelector}"`);
		}

		this.el.addEventListener('click', e => {
			e.preventDefault();

			this.scrollTo();
		});
	}

	scrollTo() {
		scrollTo(scrollDocument, this.target.getBoundingClientRect().top + window.scrollY, 600);
	}
}
