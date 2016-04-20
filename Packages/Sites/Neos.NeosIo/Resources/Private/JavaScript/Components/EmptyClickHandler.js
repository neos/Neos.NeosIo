import {component} from '@reduct/component';

@component({

})
export default class EmptyClickHandler {
	constructor() {
		this.el.addEventListener('click', e => {
			e.stopPropagation();
		});
	}
}
