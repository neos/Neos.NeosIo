import { Alpine as AlpineType } from 'alpinejs';
import { rafInterval } from './raf';

const frameDuration = 1000 / 60;

function ease(x: number): number {
    return x === 1 ? 1 : 1 - Math.pow(2, -10 * x);
}

function parse(raw: string): { prefix: string; value: number; suffix: string } | null {
    const match = raw.match(/^([^0-9]*)([0-9][0-9,.]*)(.*)$/);
    if (!match) return null;
    return { prefix: match[1], value: parseFloat(match[2].replace(/,/g, '')), suffix: match[3] };
}

/**
 * Component that animates a number from zero to its value when it enters the viewport. The value is read from the `data-value` attribute of the element with the `x-ref="number"` reference.
 * @param Alpine
 */
export default function (Alpine: AlpineType) {
    Alpine.data('countUp', (inBackend: false) => ({
        display: '',

        init() {
            if (!inBackend) {
                this.display = (this.$refs.number as HTMLElement).dataset.value ?? '';
            }
        },

        animate() {
            if (inBackend) {
                return;
            }

            const dataValue = (this.$refs.number as HTMLElement).dataset.value ?? '';
            const parsedValue = parse(dataValue);
            if (!parsedValue || isNaN(parsedValue.value)) return;

            const { prefix, value, suffix } = parsedValue;
            const totalFrames = Math.round(2000 / frameDuration);
            let frame = 0;
            const counter = rafInterval(() => {
                frame++;
                const current = Math.round(value * ease(frame / totalFrames));
                this.display = prefix + current + suffix;
                if (frame >= totalFrames) {
                    this.display = dataValue;
                    counter.clear();
                }
            }, frameDuration);
        },
    }));
}
