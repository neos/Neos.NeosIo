import Alpine from 'alpinejs';

// function that returns a random number
function getRandomNumber(max, substract = 0) {
    return Math.round(Math.random() * max - substract);
}

// get the size of an element
function getSize(element) {
    return { x: element.clientWidth, y: element.clientHeight };
}

Alpine.data('collage', () => ({
    positions: [],
    rendered: [],
    elements: [],
    maxX: 0,
    maxY: 0,
    padding: 30,
    maxAttempts: 50,
    placeElement(element, size, attempts = 0) {
        if (attempts >= this.maxAttempts) {
            // console.error('Max attempts reached');
            return;
        }

        const x = getRandomNumber(this.maxX, size.x / 2);
        const y = getRandomNumber(this.maxY);

        if (this.isOverlap(x, y, size)) {
            attempts++;
            this.placeElement(element, size, attempts);
            return;
        }

        element.style.setProperty('left', x + 'px');
        element.style.setProperty('top', y + 'px');
        this.positions.push({ x, y });
        this.rendered.push(element);
        element.classList.remove('opacity-0');
    },
    isOverlap(x, y, size) {
        // return true if overlapping
        for (const position of this.positions) {
            if (
                x > position.x - size.x &&
                x < position.x + size.x &&
                y > position.y - size.y &&
                y < position.y + size.y
            ) {
                return true;
            }
        }
        // console.log({ x, y });
        // console.log(this.positions);
        return false;
    },
    processElements() {
        this.maxX = this.$el.clientWidth;
        this.maxY = this.$el.clientHeight;
        this.positions = [];
        this.rendered = [];
        this.elements.forEach((element) => {
            if (element.tagName !== 'IMG' || element.complete) {
                this.placeElement(element, getSize(element));
            } else {
                element.addEventListener('load', () => {
                    this.placeElement(element, getSize(element));
                });
            }
        });
    },
    init() {
        this.elements = [...this.$el.children];
        this.processElements();
    },
}));
