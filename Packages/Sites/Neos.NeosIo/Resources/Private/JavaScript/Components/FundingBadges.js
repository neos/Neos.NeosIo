import {component} from '@reduct/component';

const isSelected = (domNode, selectedState) => {
	const filters = JSON.parse(domNode.getAttribute('data-filters'));
	return (filters.indexOf(selectedState) !== -1);
};

/**
 * Randomize array element order in-place.
 * Using Durstenfeld shuffle algorithm.
 */
function shuffleArray(array) {
	for (let i = array.length - 1; i > 0; i--) {
		const j = Math.floor(Math.random() * (i + 1));
		const temp = array[i];

		array[i] = array[j];
		array[j] = temp;
	}
	return array;
}

@component()
export default class FundingBadges {
	constructor() {
		const list = this.find('.fundingBadges__list');
		const elements = this.findAll('.fundingBadges__singleElement');
		const shuffledElements = shuffleArray(elements);
		shuffledElements.forEach((value, i) => {
			list.appendChild(value);
			value.setAttribute('data-index', i);
		});

		this.findAll('.fundingBadges__sortingControl').forEach(value => {
			value.addEventListener('click', () => {
				delete this.queryCache['.fundingBadges__sortingControl--isActive'];

				this.findAll('.fundingBadges__sortingControl--isActive').forEach(value => {
					value.classList.remove('fundingBadges__sortingControl--isActive');
				});

				let selected = value.getAttribute('data-filter');

				if (selected === this.state.selected) {
					selected = '';
				} else {
					value.classList.add('fundingBadges__sortingControl--isActive');
				}

				this.setState({
					selected
				});
			});
		});

		this.on('change', () => {
			const {selected} = this.state;
			const orderingsForEachCustomer = {};

			let newOrdering = this.findAll('.fundingBadges__singleElement');

			newOrdering.forEach(element => {
				orderingsForEachCustomer[element.getAttribute('data-customer-name')] = element.getBoundingClientRect();

				if (isSelected(element, selected) || !selected) {
					element.classList.remove('fundingBadges__singleElement--isInactive');
				} else {
					element.classList.add('fundingBadges__singleElement--isInactive');
				}
			});

			newOrdering = newOrdering.sort((a, b) => {
				if (!isSelected(a, selected) && isSelected(b, selected)) {
					return 1;
				} else if (isSelected(a, selected) && !isSelected(b, selected)) {
					return -1;
				}

				// both selected or both not selected; so we sort by index
				return parseInt(a.getAttribute('data-index')) - parseInt(b.getAttribute('data-index'));
			});
			newOrdering.forEach(order => {
				order.getBoundingClientRect();
				list.appendChild(order);
			});

			newOrdering.forEach(element => {
				const beforeLayoutPosition = orderingsForEachCustomer[element.getAttribute('data-customer-name')];
				const afterLayoutPosition = element.getBoundingClientRect();
				const deltaX = beforeLayoutPosition.left - afterLayoutPosition.left;
				const deltaY = beforeLayoutPosition.top - afterLayoutPosition.top;

				requestAnimationFrame(() => {
					if (deltaX || deltaY) {
						// Before the DOM paints, Invert it to its old position

						// Ensure it inverts it immediately
						element.style.transition = 'transform 0s';
						element.style.transform = `translate(${deltaX}px, ${deltaY}px) translateZ(0)`;
					}

					requestAnimationFrame(() => {
						element.style.transition = 'transform 500ms';
						element.style.transform = '';
					});
				});
			});
		});
	}

	getInitialState() {
		return {
			selected: ''
		};
	}

}
