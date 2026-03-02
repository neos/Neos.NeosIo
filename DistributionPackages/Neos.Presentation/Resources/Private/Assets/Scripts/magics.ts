import Alpine from 'alpinejs';
import { rafInterval, rafTimeOut } from './raf';

Alpine.magic('interval', (el, { cleanup }) => (callback: () => void, delay: number) => {
    const interval = rafInterval(callback, delay);
    cleanup(() => interval.clear());
    return interval;
});

Alpine.magic('timeout', (el, { cleanup }) => (callback: () => void, delay: number) => {
    const timeout = rafTimeOut(callback, delay);
    cleanup(() => timeout.clear());
    return timeout;
});

Alpine.magic('sleep', (el) => {
    return (milliseconds: number) => new Promise((resolve) => rafTimeOut(resolve, milliseconds));
});

Alpine.magic('isCurrentPage', (el) => {
    return (subject: HTMLElement | string) => fullUrl == getFullHref(subject || el);
});

Alpine.magic('isActivePage', (el) => {
    return (subject: HTMLElement | string) => fullUrl.startsWith(getFullHref(subject || el));
});

const location = window.location;
const fullUrl = getFullUrl(location);

function getFullUrl(loc: Location | URL) {
    if (loc.pathname === '/neos/preview') {
        return loc.href;
    }
    const url = loc.origin + (loc.pathname || '');
    return url.endsWith('/') ? url : url + '/';
}

function getFullHref(input: HTMLElement | string) {
    if (typeof input != 'string') {
        input = input.getAttribute('href') || '';
    }
    return getFullUrl(new URL(input, location.origin));
}
