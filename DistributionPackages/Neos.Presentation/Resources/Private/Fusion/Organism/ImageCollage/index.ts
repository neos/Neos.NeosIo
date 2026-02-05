import Alpine from 'alpinejs';

// function that returns a random number
function getRandomNumber(min: number, max: number, substract = 0) {
    return Math.round(min + Math.random() * (max - min) - substract);
}

// get the size of an element
function getSize(element: HTMLElement) {
    return { x: element.clientWidth, y: element.clientHeight };
}

Alpine.data('collage', () => ({
    atropos: null,
    figure: null as HTMLElement | null,
    positions: [] as { x: number; y: number; size: { x: number; y: number }; type: string }[],
    rendered: [] as HTMLElement[],
    elements: [] as HTMLElement[],
    maxX: 0,
    maxY: 0,
    padding: 30,
    objectMargin: 10,
    maxAttempts: 50,
    placeElement(element: HTMLElement, size: { x: number; y: number }, type: string, attempts = 0) {
        if (attempts >= this.maxAttempts) {
            // console.error('Max attempts reached');
            return;
        }

        const x = getRandomNumber(this.padding, this.maxX - this.padding, size.x / 2);
        const y = getRandomNumber(this.padding, this.maxY - this.padding - size.y);

        if (this.isOverlap(x, y, size, type)) {
            attempts++;
            this.placeElement(element, size, type, attempts);
            return;
        }

        element.style.setProperty('left', x + 'px');
        element.style.setProperty('top', y + 'px');
        this.positions.push({ x, y, size, type });

        // Push another element-box to prevent objects from different types to overlap entirely
        this.positions.push({
            x: x + size.x * 0.25,
            y: y + size.y * 0.25,
            size: { x: size.x / 2, y: size.y / 2 },
            type: '*',
        });

        this.rendered.push(element);
        element.classList.remove('opacity-0');
    },
    isOverlap(x: number, y: number, size: { x: number; y: number }, type: string): boolean {
        // return true if overlapping another element of the same type
        for (const p of this.positions.filter((p) => p.type === '*' || p.type === type)) {
            if (x - this.objectMargin > p.x + p.size.x || p.x > x + this.objectMargin + size.x) {
                continue;
            }
            if (y - this.objectMargin > p.y + p.size.y || p.y > y + this.objectMargin + size.y) {
                continue;
            }
            return true;
        }

        return false;
    },
    processElements() {
        this.maxX = this.figure?.clientWidth ?? 0;
        this.maxY = this.figure?.clientHeight ?? 0;
        this.positions = [];
        this.rendered = [];
        this.elements.forEach((element) => {
            element.classList.add('opacity-0');

            // Get the inner image if it exists and set max height and width
            const image = element.querySelector('img.image-collage-item') as HTMLImageElement | null;
            if (image && this.maxY) {
                image.style.setProperty('max-width', this.maxX / 3 + 'px');
            }
            if (image && this.maxX) {
                image.style.setProperty('max-height', this.maxY / 3 + 'px');
            }

            if (!image || image.complete) {
                // Element is not an image, or is already loaded; we can place
                // it right away
                this.placeElement(element, getSize(element), image ? 'img' : 'div');
                return;
            }

            // We need to wait for this image to load until we can place it
            image.addEventListener('load', () => {
                this.placeElement(element, getSize(element), 'img');
            });
        });
    },
    init() {
        this.figure = this.$el.querySelector('figure');
        this.elements = [...(this.figure?.children ?? [])] as HTMLElement[];

        // Init atropos
        // this.elements.forEach((item) => {
        //     Atropos({
        //         el: item,
        //         eventsEl: this.figure,
        //         commonOrigin: false,

        //         // SquareItems should elevate higher than image items
        //         activeOffset: item.querySelector('img.image-collage-item')
        //             ? Math.random() * 20
        //             : 50 + Math.random() * 10,
        //     });
        // });

        this.processElements();
    },
}));
