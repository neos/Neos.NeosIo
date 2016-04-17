import {component} from '@reduct/component';
import propTypes from '@reduct/nitpick';
import moment from 'moment';

const cacheTimeStampFormat = 'YYYYMMDD';

@component({
	// The GitHub API v3 endpoint to fetch, e.g. `repos/Neos/neos-development-collection/stats/contributors`
	endpoint: propTypes.string.isRequired,

	// The property of the fetched JSON. Nested properties aren't supported yet.
	property: propTypes.string.isRequired,

	// If passed a truthy boolean, we will format the property with moment.js.
	formatDate: propTypes.bool
})
export default class GitHubAPI {
	constructor() {
		const {endpoint} = this.props;

		this.cacheKeys = {
			timeStamp: `gh_${endpoint}_timeStamp`,
			data: `gh_${endpoint}_data`
		};

		const cachedResult = this.getCachedResults();

		if (cachedResult) {
			this.render(cachedResult);
		} else {
			this.fetchData().then(data => this.render(data));
		}
	}

	getDefaultProps() {
		return {
			formatDate: false
		};
	}

	getCachedResults() {
		try {
			const timeStamp = window.localStorage.getItem(this.cacheKeys.timeStamp);
			const data = window.localStorage.getItem(this.cacheKeys.data);
			const today = moment();
			const compareTo = moment(timeStamp, cacheTimeStampFormat);
			const isCacheValid = today.startOf('day').isSame(compareTo.startOf('day'));

			if (isCacheValid) {
				return data;
			}
		} catch (e) {}

		return null;
	}

	render(data) {
		data = this.formatData(data);

		this.el.innerHTML = data;
	}

	formatData(data) {
		if (this.props.formatDate) {
			return moment(data).format('MMMM Do YYYY, hh:mm:ss a');
		}

		return data;
	}

	fetchData() {
		const {
			endpoint,
			property
		} = this.props;

		return fetch(`https://api.github.com/${endpoint}`)
			.then(response => response.json())
			.then(json => {
				const content = json[property];

				if (!content) {
					return Promise.reject(`Unknown property "${property}" in json structure.`);
				}

				return Promise.resolve(content);
			}).catch(e => {
				throw new Error(`Error in Component "GitHubAPI": ${e.message}`);
			}).then(data => this.persistData(data));
	}

	persistData(data) {
		const today = moment().format(cacheTimeStampFormat);

		window.localStorage.setItem(this.cacheKeys.timeStamp, today);
		window.localStorage.setItem(this.cacheKeys.data, data);

		return Promise.resolve(data);
	}
}
