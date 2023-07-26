import BaseComponent from "DistributionPackages/Neos.NeosIo/Resources/Private/JavaScript/Components/BaseComponent";
import inViewport from "in-viewport";

class Map extends BaseComponent {
    constructor(el) {
        super(el);
        const isAlreadyVisible = inViewport(el);

        //
        // Now let's check initially for the visibility in the viewport.
        //
        if (isAlreadyVisible) {
            this.loadMap();
        } else {
            inViewport(
                el,
                {
                    offset: 300
                },
                () => this.loadMap()
            );
        }
    }

    loadMap() {
        import('/_maptiles/frontend/v1.1/map-main.js')
            .then(async ({maplibregl, createMap}) => {
                let map = await createMap(window.location.protocol + '//' + window.location.host + '/_maptiles', {
                    container: this.el, // HTML Element
                    center: [this.lng, this.lat], // starting position [lng, lat]
                    zoom: this.zoom, // starting zoom
                });

                map.addControl(new maplibregl.NavigationControl(), 'top-left');

                new maplibregl.Marker()
                    .setLngLat([this.lng, this.lat])
                    .setPopup(new maplibregl.Popup({offset: 25}).setText(this.popupText))
                    .addTo(map);
            });
    }
}

Map.prototype.props = {
    lat: '',
    lng: '',
    zoom: '',
    popupText: '',
}

export default Map;
