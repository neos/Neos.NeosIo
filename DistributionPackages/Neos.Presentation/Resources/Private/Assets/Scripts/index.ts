import Alpine from 'alpinejs';
import anchor from './anchor';
import './magics';
import './directives';
import focus from '@alpinejs/focus';
import intersect from '@alpinejs/intersect';
import collapse from '@alpinejs/collapse';
import clipboard from '@ryangjchandler/alpine-clipboard';
import typewriter from '@marcreichel/alpine-typewriter';
import disclosure from './ui/disclosure';
import '../../Fusion/Presentation/LogoBar';
import '../../Fusion/Organism/ImageCollage';
import '../../Fusion/Organism/Navigation/Navigation.js';

Alpine.data('exclusiveDisclosure', function (id) {
    return {
        id,
        get expanded() {
            return this.active === this.id;
        },
        set expanded(id) {
            this.active = id ? this.id : null;
        },
        init() {
            Alpine.bind(this.$root, this.root);
        },
        root: {
            ['x-model']: 'expanded',
        },
    };
});

// @ts-ignore
Alpine.plugin([anchor, clipboard, collapse, focus, intersect, typewriter, disclosure]);

window.Alpine = Alpine;

export { Alpine };
