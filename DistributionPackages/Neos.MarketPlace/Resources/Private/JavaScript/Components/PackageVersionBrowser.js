import 'whatwg-fetch';
import hashChange from 'hash-change';
import BaseComponent from "DistributionPackages/Neos.NeosIo/Resources/Private/JavaScript/Components/BaseComponent";

const serviceUrlPattern = '/ttree/outofbandrendering?preset=marketplace:version&node={{ path }}&version={{ version }}';

class PackageVersionBrowserComponent extends BaseComponent {

    constructor(el) {
        super(el);
        this.wrapper = document.createElement('div');

        hashChange.on('change', (hash) => {
            let [label, version] = hash.split(':');
            if (label === undefined || (label !== 'version' || version === undefined) || this.version === version) {
                return;
            }
            this.load(version);
        });
    }

    load(version) {
        let { path } = this;

        this.el.classList.toggle('version--hide');

        let url = serviceUrlPattern.replace('{{ path }}', path).replace('{{ version }}', version);

        fetch(url)
            .then((response) => response.text())
            .then((body) => {
                this.wrapper.innerHTML = body;
                let article = this.wrapper.querySelector('article');

                while (this.el.firstChild) {
                    this.el.removeChild(this.el.firstChild);
                }

                this.el.appendChild(article);
                this.el.classList.toggle('version--hide');
                this.version = version;
            });
    }
}

PackageVersionBrowserComponent.prototype.props = {
    version: '',
    path: '',
    wrapper: null,
};

export default PackageVersionBrowserComponent;
