import Alpine from "alpinejs";
import anchor from "@alpinejs/anchor";
import focus from "@alpinejs/focus";
import intersect from "@alpinejs/intersect";
import collapse from "@alpinejs/collapse";
import clipboard from "@ryangjchandler/alpine-clipboard";
import typewriter from "@marcreichel/alpine-typewriter";

// @ts-ignore
Alpine.plugin([
    anchor,
    clipboard,
    collapse,
    fetch,
    focus,
    intersect,
    typewriter,
]);

window.Alpine = Alpine;

Alpine.start();
