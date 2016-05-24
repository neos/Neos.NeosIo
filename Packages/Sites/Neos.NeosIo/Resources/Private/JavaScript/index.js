import 'babel-polyfill';
import assembler from '@reduct/assembler';
import * as siteComponents from './Components/';
import * as marketPlaceComponents from '../../../../../Application/Neos.MarketPlace/Resources/Private/JavaScript/Components/';

const app = assembler();

app.registerAll(siteComponents);
app.registerAll(marketPlaceComponents);

setTimeout(() => app.run(), 0);
document.addEventListener('Neos.PageLoaded', app.run());
