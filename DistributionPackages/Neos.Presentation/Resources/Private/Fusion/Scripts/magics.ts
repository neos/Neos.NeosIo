import Alpine from 'alpinejs';
import { rafInterval, rafTimeOut } from './raf';

Alpine.magic('interval', (el, { cleanup }) => (callback, delay) => {
    const interval = rafInterval(callback, delay);
    cleanup(() => interval.clear());
    return interval;
});

Alpine.magic('timeout', (el, { cleanup }) => (callback, delay) => {
    const timeout = rafTimeOut(callback, delay);
    cleanup(() => timeout.clear());
    return timeout;
});
