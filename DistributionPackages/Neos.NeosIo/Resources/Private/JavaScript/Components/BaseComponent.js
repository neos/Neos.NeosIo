export default class BaseComponent {
    constructor(el) {
        this.el = el;

        for (const prop in this.props) {
            const datasetProp = prop.toLowerCase();
            if (this.el.dataset[datasetProp]) {
                try {
                    this[prop] = JSON.parse(this.el.dataset[datasetProp]);
                } catch (e) {
                    this[prop] = this.el.dataset[datasetProp];
                }
            } else {
                this[prop] = this.props[prop];
            }
        }
    }
}
