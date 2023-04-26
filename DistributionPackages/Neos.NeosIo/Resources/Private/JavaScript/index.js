import * as siteComponents from './Components';
import * as marketPlaceComponents from 'DistributionPackages/Neos.MarketPlace/Resources/Private/JavaScript';
import * as neosConComponents from 'DistributionPackages/Neos.NeosConIo/Resources/Private/JavaScript/Components';

const components = {};

for (let componentKey in siteComponents) {
    components[componentKey] = siteComponents[componentKey];
}
for (let componentKey in marketPlaceComponents) {
    components[componentKey] = marketPlaceComponents[componentKey];
}
for (let componentKey in neosConComponents) {
    components[componentKey] = neosConComponents[componentKey];
}

const run = () => {
    const componentNames = Object.keys(components);
    for (let i = 0; i < componentNames.length; i++) {
        const componentName = componentNames[i];

        // Find all instances of the component
        const elements = document.querySelectorAll(`[data-component="${componentName}"]`);
        for (let j = 0; j < elements.length; j++) {
            const element = elements[j];
            if (element.dataset.initialized === 'true') {
                continue;
            }
            element.dataset.initialized = 'true';
            // Initialise component on element
            new components[componentName](element);
        }
    }
}

setTimeout(() => run(), 0);
document.addEventListener('Neos.PageLoaded', run);
