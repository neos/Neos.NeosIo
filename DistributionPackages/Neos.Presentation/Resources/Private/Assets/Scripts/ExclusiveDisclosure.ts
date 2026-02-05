import { Alpine as AlpineType } from 'alpinejs';

export default function (Alpine: AlpineType) {
    Alpine.data('exclusiveDisclosure', function (id: string | number) {
        return {
            id,
            get expanded(): boolean {
                // @ts-ignore: Accessing parent component property
                return this.active === this.id;
            },
            set expanded(id: string | number) {
                // @ts-ignore: Accessing parent component property
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
}
