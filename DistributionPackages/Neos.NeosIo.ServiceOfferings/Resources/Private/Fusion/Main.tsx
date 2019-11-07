// load scss
import './Main.scss';

import {h, render} from 'preact';
import * as React from "preact/compat";
import {initMarkerClusters, ProviderListing, ProviderData} from '../JavaScript/index';
import shuffleArray from "../JavaScript/Helper/Shuffle";

initMarkerClusters();

const providerListing: HTMLElement = document.getElementById('service-providers-listing');

if (providerListing) {
    const providerData: Provider[] = JSON.parse(providerListing.dataset.providerData);
    shuffleArray(providerData);

    console.log(providerData);

    render(
        (
            <ProviderData.Provider value={providerData}>
                <ProviderListing/>
            </ProviderData.Provider>
        ), providerListing
    );
}
