import {component} from '@reduct/component';
import propTypes from '@reduct/nitpick';
import moment from 'moment';

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
		const {
			endpoint,
			property,
			formatDate
		} = this.props;

		fetch(`https://api.github.com/${endpoint}`)
			.then(response => response.json())
			.then(json => {
				let content = json[property];

				if (!content) {
					return Promise.reject();
				}

				if (formatDate) {
					content = moment(content).format('MMMM Do YYYY, hh:mm:ss a');
				}

				return Promise.resolve(content);
			}).catch(e => {
				throw new Error(`Error in Component "GitHubAPI": ${e.message}`);
			}).then(html => {
				this.el.innerHTML = html;
			});
	}

	getDefaultProps() {
		return {
			formatDate: false
		};
	}
}
