import {component} from '@reduct/component';
import propTypes from '@reduct/nitpick';
import CountUp from 'countup';

const easingFn = function (t, b, c, d) {
	const ts = (t /= d) * t;
	const tc = ts * t;

	return b + c * (tc * ts + -5 * ts * ts + 10 * tc + -10 * ts + 5 * t);
};

@component({
	from: propTypes.number.isRequired,
	to: propTypes.number.isRequired,
	id: propTypes.string.isRequired
})
export default class CountUpComponent {
	constructor() {
		const {id, to} = this.props;
		const options = {
			useEasing: true,
			easingFn,
			useGrouping: true,
			separator: ',',
			decimal: '.',
			prefix: '',
			suffix: ''
		};
		const instance = new CountUp(id, this.props.from, to, 0, 12, options);

		setTimeout(() => instance.start(), 200);
	}
}
