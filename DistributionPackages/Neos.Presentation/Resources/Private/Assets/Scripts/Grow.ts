import { Alpine as AlpineType } from 'alpinejs';
import { rafTimeOut } from './raf';

export default function (Alpine: AlpineType) {
    Alpine.directive('grow', (el, { expression }) => {
        let timer: { clear: () => void } | null = null;
        window.addEventListener('resize', () => {
            if (timer) {
                timer.clear();
            }
            timer = rafTimeOut(() => autogrow(el), 100);
        });

        el.addEventListener(expression || 'input', () => autogrow(el));
    });
}

function setHeight(el: HTMLElement, height: string) {
    el.style.height = height;
}

function autogrow(el: HTMLElement) {
    setHeight(el, 'auto');
    setHeight(el, `${el.scrollHeight}px`);
}
