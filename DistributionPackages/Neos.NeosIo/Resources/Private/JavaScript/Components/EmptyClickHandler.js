import BaseComponent from "DistributionPackages/Neos.NeosIo/Resources/Private/JavaScript/Components/BaseComponent";

export default class EmptyClickHandler extends BaseComponent {
    constructor(el) {
        super(el);
        el.addEventListener('click', e => e.stopPropagation());
    }
}
