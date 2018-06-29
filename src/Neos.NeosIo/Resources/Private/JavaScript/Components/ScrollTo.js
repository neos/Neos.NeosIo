import {component} from '@reduct/component';
import propTypes from '@reduct/nitpick';

//
// Due to an inconsistency between browsers we need to check the UA and set
// the document scroll element.
//
const scrollDocument = /Firefox/.test(navigator.userAgent) || /Trident.*rv[ :]*11\./.test(navigator.userAgent) || /MSIE/.test(navigator.userAgent) ?
	document.documentElement :
	document.body;

function scrollTo(to, duration) {
	if (duration <= 0) {
		return;
	}

	if(typeof window.scrollTo === 'function') { // browser feature check
		window.scrollTo({
			top: to,
			behavior: 'smooth'
		});
		return;
	}

	const difference = to - scrollDocument.scrollTop;
	const perTick = difference / duration * 10;

	setTimeout(() => {
		scrollDocument.scrollTop += Number(perTick);

		if (scrollDocument.scrollTop !== to) {
			scrollTo(scrollDocument, to, duration - 10);
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
		scrollTo(this.target.getBoundingClientRect().top + window.scrollY, 600);
	}
}
