import {component} from '@reduct/component';
import propTypes from '@reduct/nitpick';
import inViewport from 'in-viewport';
import getClosest from 'get-closest';
import debounce from 'lodash.debounce';

@component({
	src: propTypes.string.isRequired,
	srcSet: propTypes.string,
	isInline: propTypes.bool.isRequired
})
export default class ProgressiveImage {
	constructor() {
		const {el} = this;
		const isAlreadyVisible = inViewport(el);

		//
		// In case the mode is set to `inline` styles, we firstly convert the
		// srcset to an obj containing the target width and source, and Afterwards
		// attach an resize handler for the window object.
		//
		if (this.props.isInline) {
			this.transformInlineSourceMap();

			window.addEventListener('resize', debounce(() => this.loadAndReplaceImage()));
		}

		//
		// Now let's check initially for the visibility in the viewport.
		//
		if (isAlreadyVisible) {
			this.loadAndReplaceImage();
		} else {
			inViewport(el, {
				offset: 300
			}, () => this.loadAndReplaceImage());
		}
	}

	getDefaultProps() {
		return {
			isInline: false
		};
	}

	getInitialState() {
		return {
			inlineSourceMap: {}
		};
	}

	loadAndReplaceImage() {
		const {el, props} = this;
		const {src, srcSet, isInline} = props;
		const isSrcSetPresent = srcSet && srcSet.length;

		//
		// If an srcset is present and the mode is not inline,
		// set the srcset attr first.
		//
		if (isSrcSetPresent && !isInline) {
			el.setAttribute('srcset', srcSet);
		}

		//
		// Afterwards, we will set the src attribute.
		//
		if (isInline) {
			let url = src;

			//
			// But if the mode is inline, we check for an appropriate
			// viewport assigned source url.
			//
			if (isSrcSetPresent) {
				const viewportSource = this.getMatchingViewportSource();

				if (viewportSource && viewportSource.length) {
					url = viewportSource;
				}
			}

			el.style.backgroundImage = `url('${url}')`;
		} else {
			el.setAttribute('src', src);
		}
	}

	transformInlineSourceMap() {
		const {srcSet = ''} = this.props;
		const sources = srcSet.split(',').map(source => source.replace(/(\r\n|\n|\r|\t)/gm, ''));
		const inlineSourceMap = {};

		if (sources.length && srcSet !== '') {
			sources.forEach(source => {
				const parts = source.split(' ');
				const url = parts[0];
				const viewportWidth = Math.abs(parts[1].replace('w', ''));

				inlineSourceMap[viewportWidth] = url;
			});
		}

		this.setState({
			inlineSourceMap
		});
	}

	getMatchingViewportSource() {
		const {inlineSourceMap} = this.state;
		const sourceMapKeys = Object.keys(inlineSourceMap);
		const closestSourceIndex = getClosest.number(window.innerWidth, sourceMapKeys);
		const targetSource = inlineSourceMap[sourceMapKeys[closestSourceIndex]];

		return targetSource;
	}
}
