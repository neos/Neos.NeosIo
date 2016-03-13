import {component} from '@reduct/component';
import propTypes from '@reduct/nitpick';
import 'substituteteacher.js/src/substituteteacher.js';

const {Sub} = window;

@component({
	sentenceSelector: propTypes.string.isRequired,
	id: propTypes.string.isRequired
})
export default class SentenceSwitcher {
	constructor() {
		this.parseSentences();

		return new Sub(this.state.sentences, {
			containerId: this.props.id,
			namespace: "sub",
			interval: 5000,
			speed: 200,
			verbose: false,
			random: false,
			best: true
		}).run();
	}

	getDefaultProps() {
		return {
			sentenceSelector: '[data-sentenceSwitcher__sentence]'
		};
	}

	getInitialState() {
		return {
			sentences: []
		};
	}

	parseSentences() {
		const items = this.findAll(this.props.sentenceSelector);
		const sentences = items.map(node => node.innerHTML);

		this.setState({
			sentences
		});
	}
}
