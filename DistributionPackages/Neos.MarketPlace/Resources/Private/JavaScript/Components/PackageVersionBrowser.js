import 'whatwg-fetch';
import {component} from '@reduct/component';
import propTypes from '@reduct/nitpick';
import hashChange from 'hash-change';

const serviceUrlPattern = '/ttree/outofbandrendering?preset=marketplace:version&node={{ path }}&version={{ version }}';

@component({
  version: propTypes.string.isRequired,
  path: propTypes.string.isRequired
})
export default class PackageVersionBrowserComponent {
  constructor() {
    this.version = this.props.version;
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
    let {path} = this.props;

    this.el.classList.toggle('version--hide');

    let url = serviceUrlPattern
      .replace('{{ path }}', path)
      .replace('{{ version }}', version);

    fetch(url)
      .then((response) => {
        return response.text()
      })
      .then((body) => {
        this.wrapper.innerHTML = body;
        let article = this.wrapper.querySelector('article');

        while (this.el.firstChild) {
          this.el.removeChild(this.el.firstChild);
        }

        this.el.appendChild(article);
        this.el.classList.toggle('version--hide');
        this.version = version;
      })
  }
}
