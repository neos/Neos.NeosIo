import Alpine from 'alpinejs';

Alpine.directive('random-children', (el) => {
    el.replaceChildren(...[...el.children].sort(() => Math.random() - 0.5));
});
