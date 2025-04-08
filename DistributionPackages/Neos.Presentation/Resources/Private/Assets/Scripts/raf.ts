const dateNow = Date.now;
const raf = window.requestAnimationFrame;

const rafInterval = (callback: Function, delay: number) => {
    let start = dateNow();
    let stop = false;
    const intervalFunc = () => {
        dateNow() - start < delay || ((start += delay), callback());
        stop || raf(intervalFunc);
    };
    raf(intervalFunc);
    return {
        clear: () => (stop = true),
    };
};

const rafTimeOut = (callback: Function, delay: number) => {
    let start = dateNow();
    let stop = false;
    const timeoutFunc = () => {
        dateNow() - start < delay ? stop || raf(timeoutFunc) : callback();
    };
    raf(timeoutFunc);
    return {
        clear: () => (stop = true),
    };
};

export { raf, rafInterval, rafTimeOut };
