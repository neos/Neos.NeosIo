import Alpine from 'alpinejs';
import anchor from './anchor';
import './magics';
import focus from '@alpinejs/focus';
import intersect from '@alpinejs/intersect';
import collapse from '@alpinejs/collapse';
import clipboard from '@ryangjchandler/alpine-clipboard';
import typewriter from '@marcreichel/alpine-typewriter';

// @ts-ignore
Alpine.plugin([anchor, clipboard, collapse, focus, intersect, typewriter]);

window.Alpine = Alpine;

export { Alpine };
