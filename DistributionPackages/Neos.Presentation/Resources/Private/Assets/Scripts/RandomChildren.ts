import { Alpine as AlpineType } from 'alpinejs';

export default function (Alpine: AlpineType) {
    Alpine.directive('random-children', (el) => {
        el.replaceChildren(...[...el.children].sort(() => Math.random() - 0.5));
    });
}
