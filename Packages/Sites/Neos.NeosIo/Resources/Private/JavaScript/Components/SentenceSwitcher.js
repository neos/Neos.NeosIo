import {component} from '@reduct/component';
import propTypes from '@reduct/nitpick';

function mapSentenceToParts(sentence) {
	const components = [];
	let start;
	let end;

	// Remove unused html tags from the sentence.
	sentence = sentence.replace(new RegExp('<br>', 'g'), '');

	for (start = 0, end = 0; end < sentence.length; end++) {
		const endChar = sentence.charAt(end);

		/**
		* Characters that should "detach" from strings are:
		*   ().,/![]*;:{}=?"+ or whitespace
		* Characters that remain that remain a part of the word include:
		*   -#$%^&_`~'
		*/
		if (endChar.match(/[\.,"\/!\?\*\+;:{}=()\[\]\s|]/g)) {
			// Append the word we've been building
			if (end > start) {
				const part = sentence.slice(start, end);

				if (endChar.match(/\s/g)) {
					components.push(`${part}&nbsp;`);
				} else {
					components.push(part);
				}
			}

			if (endChar === '|') {
				components.push('');
			// If the character is not whitespace, then it is a special character
			// and should be split off into its own string
			} else if (!endChar.match(/\s/g)) {
				if (end + 1 < sentence.length && sentence.charAt(end + 1).match(/\s/g)) {
					components.push(`${endChar}&nbsp;`);
				} else {
					components.push(endChar);
				}
			}

			// The start of the next word is the next character to be seen.
			start = end + 1;
		}
	}

	if (start < end) {
		components.push(sentence.slice(start, end));
	}

	return components;
}

@component({
	sentenceSelector: propTypes.string.isRequired,
	wordClassName: propTypes.string.isRequired,
	animatingWordClassName: propTypes.string.isRequired
})
export default class SentenceSwitcher {
	constructor() {
		//
		// Initialize this component only on the 'real' frontend,
		// not while editing the sentences.
		//
		if (!window.Typo3Neos) {
			this.parseSentences();
			this.createPartWrappers();
			this.startAnimationLoop();
		}
	}

	getDefaultProps() {
		return {
			sentenceSelector: '.typo3-neos-nodetypes-headline > div > *'
		};
	}

	getInitialState() {
		return {
			sentences: [],
			currentIndex: -1
		};
	}

	parseSentences() {
		const nodes = this.findAll(this.props.sentenceSelector);
		const sentences = nodes.map(node => mapSentenceToParts(node.innerHTML));

		// Remove the parsed sentences from the DOM.
		const children = Array.prototype.slice.call(this.el.children);
		children.forEach(node => this.el.removeChild(node));

		this.setState({
			sentences
		});
	}

	createPartWrappers() {
		const {wordClassName} = this.props;
		const max = this.state.sentences.map(i => i.length).sort().reverse().shift();

		for (let i = 0; i < max; i++) {
			const wrapper = document.createElement('span');

			wrapper.setAttribute('data-index', i);
			wrapper.classList.add(wordClassName);

			this.el.appendChild(wrapper);
		}
	}

	startAnimationLoop() {
		this.animateToIndex(0);

		setInterval(() => {
			const {currentIndex, sentences} = this.state;
			const nextIndex = currentIndex + 2 > sentences.length ? 0 : currentIndex + 1;

			this.animateToIndex(nextIndex);
		}, 4000);
	}

	animateToIndex(targetIndex) {
		const {animatingWordClassName} = this.props;
		const {sentences, currentIndex} = this.state;
		const targetSentence = sentences[targetIndex] || [];
		const currentSentence = sentences[currentIndex] || [];

		let maxLength = targetSentence.length;
		if (currentSentence.length > maxLength) {
			maxLength = currentSentence.length;
		}

		for (let index = 0; index < maxLength; index++) {
			const node = this.el.querySelector(`[data-index="${index}"]`);
			const currentText = node.innerHTML;
			const newPart = targetSentence[index];

			// The animation works as follows: (applying the FLIP technique: https://aerotwist.com/blog/flip-your-animations/)
			//
			// 0-0.5s: the class .headlineSlider__word--isAnimating is appended to the elements which change.
			//         This hides the words to be removed via CSS transitions. (0.5 secs)
			// 0.5s: a JS timeout fires; replacing the text to the new content; but manually sizing the width of the elements
			//       to the *old* size. Effectively, the DOM changes but the visual representation stays the same.
			// 0.5s-1s: we animate the sizing change using CSS transitions. (inline styles)
			// 1.1s: we remove the class .headlineSlider__word--isAnimating, leading to the fade-in of the new word.
			// 1.1s-1.6s: the word is faded in again.
			if (currentText !== newPart) {
				const finishAnimation = () => {
					const oldWidth = (currentText ? node.getBoundingClientRect().width : 0);

					node.innerHTML = (newPart || '');

					const newWidth = (newPart ? node.getBoundingClientRect().width : 0);

					// This doubly-nested requestAnimationFrame is needed to have the animation run smoothly in firefox.
					requestAnimationFrame(() => {
						// Before the DOM paints, Invert it to its old position
						node.style.width = `${oldWidth}px`;
						node.style.transition = 'width 0s';
						requestAnimationFrame(() => {
							node.style.width = `${newWidth}px`;
							node.style.transition = 'width 500ms';

							setTimeout(() => {
								node.style.width = '';
								node.style.transition = '';
								node.classList.remove(animatingWordClassName);
							}, 600);
						});
					});
				};

				node.classList.add(animatingWordClassName);

				setTimeout(finishAnimation, 500);
			}
		}

		this.setState({
			currentIndex: targetIndex
		});
	}
}
