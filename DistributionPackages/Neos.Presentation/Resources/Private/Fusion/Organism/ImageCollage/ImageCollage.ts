export default () => ({
    imgPoss: [],
    imgRendered: [],
    maxX: 0,
    maxY: 0,
    padding: 30,
    placeImg(imageTag) {
        const { random: r } = Math;
        const x = r() * this.maxX;
        const y = r() * this.maxY;

        let imageSize = { x: imageTag.clientWidth, y: imageTag.clientHeight };

        if (!this.isOverlap(x, y, imageSize)) {
            imageTag.style.setProperty('left', x + 'px');
            imageTag.style.setProperty('top', y + 'px');
            imageTag.classList.remove('hidden');

            this.imgPoss.push({ x, y });
            this.imgRendered.push(imageTag);
        }
    },
    isOverlap(x, y, imageSize) {
        // return true if overlapping
        for (const imgPos of this.imgPoss) {
            if (
                x > imgPos.x - imageSize.x &&
                x < imgPos.x + imageSize.x &&
                y > imgPos.y - imageSize.y &&
                y < imgPos.y + imageSize.y
            )
                return true;
        }
        console.log({ x, y });
        console.log(this.imgPoss);
        return false;
    },
    initializeMaxValues($el) {
        this.maxX = $el.clientWidth - 128;
        this.maxY = $el.clientHeight - 160;
    },
    init() {
        console.log('ImageCollage');
        this.initializeMaxValues(this.$el);
        let imageTags = this.$el.querySelectorAll('img');

        for (let i = 0; i < imageTags.length; ++i) {
            let image = imageTags[i];
            while (!this.imgRendered.includes(image)) {
                setInterval(this.placeImg(image), 10);
            }
        }
    },
});
