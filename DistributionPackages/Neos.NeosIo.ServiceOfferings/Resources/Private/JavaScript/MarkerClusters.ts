import L from 'leaflet';
import 'leaflet.markercluster/src';

declare global {
    interface Window {
        clusterMarkersHook: Function
    }
}

L.Icon.Default.prototype.options.iconSize = [24, 27];
L.Icon.Default.prototype.options.iconAnchor = [12, 13];
L.Icon.Default.prototype.options.shadowAnchor = [12, 27];
L.Icon.Default.prototype.options.tooltipAnchor = [16, 0];
L.Icon.Default.prototype.options.popupAnchor = [0, -20];

export default () => {
    window.clusterMarkersHook = (layer) => {
        const clusterMarkers = L.markerClusterGroup();
        return clusterMarkers.addLayer(layer);
    };
}

import 'Packages/Plugins/WebExcess.OpenStreetMap/Resources/Private/Assets/Main';
