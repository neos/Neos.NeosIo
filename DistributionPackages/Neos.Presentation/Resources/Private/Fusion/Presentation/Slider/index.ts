import Alpine from 'alpinejs';
import Splide from '@splidejs/splide';
import { Video } from '@splidejs/splide-extension-video';

function getFirstNode(nodeList: NodeList) {
    return [...nodeList].filter((node) => (node as HTMLElement).tagName === 'LI')[0] as HTMLElement;
}

function getIndexOfElement(element: HTMLElement): number {
    const children = element?.parentElement?.children;
    if (!children) {
        return -1;
    }
    return Array.from(element.parentElement.children).indexOf(element);
}

Alpine.data('slider', () => ({
    init() {
        const rootElement = this.$root;
        const splide = new Splide(rootElement);
        const inNeosBackend = window.name === 'neos-content-main';

        // We are in the backend, so we need to refresh the instance on change
        if (inNeosBackend) {
            splide.on('mounted', function () {
                // Update if a slide is added or removed
                const observeTarget = rootElement.querySelector('.splide__list') as HTMLElement;
                const observer = new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        const addedNode = getFirstNode(mutation.addedNodes);
                        const removedNode = getFirstNode(mutation.removedNodes);
                        if (addedNode || removedNode) {
                            console.log('Refreshing instance');
                            splide.refresh();
                        }
                        if (addedNode) {
                            // Scroll to the new slide
                            splide.go(getIndexOfElement(addedNode));
                        }
                    });
                });
                observer.observe(observeTarget, { childList: true });

                // Go to the slide if it gets selceted in the node tree
                document.addEventListener('Neos.NodeSelected', (event: Event) => {
                    const customEvent = event as CustomEvent;
                    const element = customEvent.detail.element;
                    if (!element.classList.contains('splide__slide')) {
                        return;
                    }
                    splide.go(getIndexOfElement(element));
                });
            });
        }

        splide.mount({ Video });
        // Disable the play button in the backend
        splide?.Components?.Video?.disable(inNeosBackend);

        const maxIndex = splide.length - 1;
        splide.on('autoplay:playing', (rate) => {
            // Go to the first slide after the last slide
            if (rate === 1 && maxIndex === splide.index) {
                splide.go(0);
            }
        });
    },
}));
