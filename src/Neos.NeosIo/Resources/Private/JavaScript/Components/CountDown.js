import {component} from '@reduct/component';
import propTypes from '@reduct/nitpick';

@component({
	toDateUnixTimestamp: propTypes.string.isRequired
})
export default class CountDownComponent {
	constructor() {
		this.startCountDown();
	}

	startCountDown() {
		const {toDateUnixTimestamp} = this.props;
		const {el} = this;

		let countDownTimestamp = toDateUnixTimestamp * 1000;

		let interval = setInterval(function () {
			let now = new Date().getTime();

			let timeBetween = countDownTimestamp - now;

			if (timeBetween < 0) {
				clearInterval(interval);

				el.innerHTML = "0d 0h 0m 0s";
			} else {
				let days = Math.floor(timeBetween / (1000 * 60 * 60 * 24));
				let hours = Math.floor((timeBetween % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
				let minutes = Math.floor((timeBetween % (1000 * 60 * 60)) / (1000 * 60));
				let seconds = Math.floor((timeBetween % (1000 * 60)) / 1000);

				el.innerHTML = days + "d " + hours + "h " + minutes + "m " + seconds + "s";
			}
		}, 1000);
	}
}
