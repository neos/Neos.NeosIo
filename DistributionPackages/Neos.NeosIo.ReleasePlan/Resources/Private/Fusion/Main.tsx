import { AmChart } from '../JavaScript';

const run = () => {
    const elements = Array.from(document.querySelectorAll(`[data-component="AmChart"]`));
    elements.forEach((element) => {
        new AmChart(element);
    });
};

setTimeout(() => run(), 0);
document.addEventListener('Neos.PageLoaded', run);
