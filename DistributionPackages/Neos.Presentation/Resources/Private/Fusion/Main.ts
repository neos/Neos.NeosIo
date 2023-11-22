import Alpine from "alpinejs";
import anchor from "@alpinejs/anchor";
import focus from "@alpinejs/focus";
import intersect from "@alpinejs/intersect";
import collapse from "@alpinejs/collapse";
import clipboard from "@ryangjchandler/alpine-clipboard";
import typewriter from "@marcreichel/alpine-typewriter";
import LogoBar from './Molecule/LogoBar/LogoBar';

// @ts-ignore
Alpine.plugin([
    anchor,
    clipboard,
    collapse,
    focus,
    intersect,
    typewriter,
]);

window.Alpine = Alpine;

Alpine.data('LogoBar', LogoBar);

Alpine.start();

