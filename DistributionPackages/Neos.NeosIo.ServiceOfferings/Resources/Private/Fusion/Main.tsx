import {h, render} from 'preact';
import * as React from "preact/compat";
import {initMarkerClusters, ProviderListing, ProviderData, TranslationData} from '../JavaScript/index';
import shuffleArray from "../JavaScript/Helper/Shuffle";

initMarkerClusters();

const providerListing: HTMLElement = document.getElementById('service-providers-listing');

if (providerListing) {
    const providerData: Provider[] = JSON.parse(providerListing.dataset.providerData);
    const translationData: string[] = JSON.parse(providerListing.dataset.translationData);
    shuffleArray(providerData);

    render(
        (
            <ProviderData.Provider value={providerData}>
                <TranslationData.Provider value={translationData}>
                    <ProviderListing />
                </TranslationData.Provider>
            </ProviderData.Provider>
        ), providerListing
    );
}
