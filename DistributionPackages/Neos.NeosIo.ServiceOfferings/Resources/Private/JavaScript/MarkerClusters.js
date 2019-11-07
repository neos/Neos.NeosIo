import L from 'leaflet';
import {MarkerClusterGroup} from 'leaflet.markercluster';

L.Icon.Default.prototype.options.iconSize = [24, 27];
L.Icon.Default.prototype.options.iconAnchor = [12, 13];
L.Icon.Default.prototype.options.shadowAnchor = [12, 27];
L.Icon.Default.prototype.options.tooltipAnchor = [16, 0];
L.Icon.Default.prototype.options.popupAnchor = [0, -20];

export default function() {
    window.clusterMarkersHook = function(layer) {
        var clusterMarkers = L.markerClusterGroup();
        return clusterMarkers.addLayer(layer);
    };
}

import 'Packages/Plugins/WebExcess.OpenStreetMap/Resources/Private/Assets/Main';
