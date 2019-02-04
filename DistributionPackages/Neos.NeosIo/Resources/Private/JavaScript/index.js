import 'babel-polyfill';
import assembler from '@reduct/assembler';
import * as siteComponents from './Components/';
import * as marketPlaceComponents from '../../../../../Packages/Application/Neos.MarketPlace/Resources/Private/JavaScript/Components/';
import * as neosConComponents from '../../../../Neos.NeosConIo/Resources/Private/JavaScript/Components/';
import Layzr from 'layzr.js';

const app = assembler();
const layzr = Layzr({
    normal: 'data-image-normal',
    retina: 'data-image-retina',
    srcset: 'data-image-srcset',
    threshold: 10
});

app.registerAll(siteComponents);
app.registerAll(marketPlaceComponents);
app.registerAll(neosConComponents);

setTimeout(() => app.run(), 0);
document.addEventListener('Neos.PageLoaded', app.run());

setTimeout(() => {
    layzr
    .update()
    .check()
    .handlers(true);
}, 0);

