import 'babel-polyfill';
import assembler from '@reduct/assembler';
import * as components from './Components/';

const app = assembler();

app.registerAll(components);

setTimeout(() => app.run(), 0);
