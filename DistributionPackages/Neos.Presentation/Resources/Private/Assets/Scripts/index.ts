import Alpine from 'alpinejs';
import anchor from './Anchor';
import clipboard from '@ryangjchandler/alpine-clipboard';
import collapse from '@alpinejs/collapse';
import counter from './Counter';
import disclosure from './UI/Disclosure';
import exclusiveDisclosure from './ExclusiveDisclosure';
import focus from './Focus';
import intersect from '@alpinejs/intersect';
import randomChildren from './RandomChildren';
import tash from './Tash';
import tooltip from './Tooltip';
import typewriter from './Typewriter';
import './magics';

Alpine.plugin([
    anchor,
    clipboard,
    collapse,
    counter,
    disclosure,
    exclusiveDisclosure,
    focus,
    intersect,
    randomChildren,
    tash,
    tooltip,
    typewriter,
]);

export default Alpine;
