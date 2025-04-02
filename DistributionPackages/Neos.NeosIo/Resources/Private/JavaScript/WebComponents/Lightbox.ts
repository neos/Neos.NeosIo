const template = document.createElement('template');
template.innerHTML = `
    <style>
        .lightbox {
            align-content: center;
            backdrop-filter: blur(2px);
            background-color: rgba(0, 0, 0, .7);
            bottom: 0;
            color: white;
            display: none;
            justify-content: center;
            left: 0;
            position: fixed;
            right: 0;
            top: 0;
            transition: background-color .5s ease-in;
            z-index: 9999;
        }

        .lightbox--open {
            animation: lightboxFadeIn .2s linear;
            display: grid;
        }
        .lightbox--open img {
          animation: lightboxZoom .15s ease-in;
        }

        .lightbox figure {
            display: flex;
            margin: 0;
            max-height: 95vh;
            max-width: 95vw;
        }

        .lightbox img {
            display: block;
            height: auto;
            max-width: 100%;
            object-fit: contain;
        }

        .lightbox figcaption {
            background: rgba(0, 0, 0, 0.5);
            bottom: 0;
            left: 0;
            padding: .5rem .8rem;
            position: absolute;
            right: 0;
            text-align: center;
        }

        .lightbox-close {
            cursor: pointer;
            padding: 1rem;
            position: absolute;
            right: .5rem;
            top: .5rem;
            user-select: none;
        }

        .lightbox-close svg {
          height: 1.5rem;
          width: 1.5rem;
        }

        @keyframes lightboxFadeIn {
            0% {
                opacity: 0;
            }
            100% {
                opacity: 1;
            }
        }

        @keyframes lightboxZoom {
            0% {
                transform: scale(0.5);
            }
            100% {
                transform: scale(1);
            }
        }
    </style>
    <div class="lightbox" aria-hidden="true" aria-modal="true">
        <figure>
            <img src="" alt="" />
        </figure>
        <figcaption></figcaption>
        <span class="lightbox-close" aria-label="Close">
            <svg aria-hidden="true" data-prefix="far" data-icon="times-circle" class="svg-inline--fa fa-times-circle fa-w-16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm0 448c-110.5 0-200-89.5-200-200S145.5 56 256 56s200 89.5 200 200-89.5 200-200 200zm101.8-262.2L295.6 256l62.2 62.2c4.7 4.7 4.7 12.3 0 17l-22.6 22.6c-4.7 4.7-12.3 4.7-17 0L256 295.6l-62.2 62.2c-4.7 4.7-12.3 4.7-17 0l-22.6-22.6c-4.7-4.7-4.7-12.3 0-17l62.2-62.2-62.2-62.2c-4.7-4.7-4.7-12.3 0-17l22.6-22.6c4.7-4.7 12.3-4.7 17 0l62.2 62.2 62.2-62.2c4.7-4.7 12.3-4.7 17 0l22.6 22.6c4.7 4.7 4.7 12.3 0 17z"/></svg>
        </span>
    </div>
`;

class Lightbox extends HTMLElement {
    _shadowRoot: ShadowRoot;
    $img: Element;
    $wrapper: Element;
    $caption: Element;

    constructor() {
        super();
        this._shadowRoot = this.attachShadow({ mode: 'open' });
        this._shadowRoot.appendChild(template.content.cloneNode(true));
        this.$img = this._shadowRoot.querySelector('img');
        this.$wrapper = this._shadowRoot.querySelector('.lightbox');
        this.$caption = this._shadowRoot.querySelector('figcaption');

        // Close lightbox on escape
        document.addEventListener('keydown', (evt: KeyboardEvent) => {
            if (evt.key === 'Escape' || evt.key === 'Esc') {
                this.setAttribute('open', 'false');
            }
        });

        // Close lightbox if background or close button are clicked
        this.$wrapper.addEventListener('click', (e) => {
            if (e.target === this.$img || e.target === this.$caption) return;
            this.setAttribute('open', 'false');
        });
    }

    static get observedAttributes() {
        return ['src', 'title', 'alt', 'caption', 'open'];
    }

    get open() {
        return this.getAttribute('open');
    }

    set open(value: string) {
        this.setAttribute('open', value);
    }

    get src(): string {
        return this.getAttribute('src');
    }

    set src(value) {
        this.setAttribute('src', value);
    }

    get caption(): string {
        return this.getAttribute('caption');
    }

    set caption(value) {
        this.setAttribute('caption', value);
    }

    get alt(): string {
        return this.getAttribute('alt');
    }

    set alt(value) {
        this.setAttribute('alt', value);
    }

    get title(): string {
        return this.getAttribute('title');
    }

    set title(value) {
        this.setAttribute('title', value);
    }

    attributeChangedCallback(name, oldVal, newVal): void {
        this.render();
        if (name === 'open' && oldVal !== newVal) {
            this.dispatchEvent(new CustomEvent(newVal === 'true' ? 'onOpen' : 'onClose'));
        }
    }

    render() {
        this.$img.setAttribute('src', this.src);
        this.$img.setAttribute('alt', this.alt);
        this.$img.setAttribute('title', this.title);
        this.$caption.textContent = this.caption || this.alt || this.title || '';

        this.$wrapper.classList.toggle('lightbox--open', this.open == 'true');
        this.$wrapper.setAttribute('aria-hidden', this.open === 'true' ? 'false' : 'true');
    }
}

window.customElements.define('sh-lightbox', Lightbox);

const initLightboxes = (): void => {
    const body = document.querySelector('body');
    const lightbox = document.querySelector('sh-lightbox');

    lightbox.addEventListener('onOpen', () => {
        body.classList.add('has-lightbox');
    });

    lightbox.addEventListener('onClose', () => {
        body.classList.remove('has-lightbox');
    });

    const openLightbox = ({ target }) => {
        lightbox.setAttribute('src', target.dataset.lightbox);
        lightbox.setAttribute('alt', target.getAttribute('alt') || '');
        lightbox.setAttribute('title', target.getAttribute('title') || '');
        lightbox.setAttribute('caption', target.getAttribute('title') || '');
        lightbox.setAttribute('open', 'true');
    };

    const images = document.querySelectorAll('img[data-lightbox]');

    images.forEach((image: HTMLImageElement) => {
        if (!image.dataset.lightbox) {
            return;
        }
        image.addEventListener('click', openLightbox);
    });
};

export { initLightboxes };
