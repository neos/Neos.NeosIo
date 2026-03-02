import {h, render} from 'preact';
import * as React from "preact/compat";
import {CaseStudyListing, CasesData, TranslationData} from '../JavaScript/index';

const casesListing: HTMLElement = document.getElementById('cases-listing');

if (casesListing) {
    const casesData: CaseStudy[] = JSON.parse(casesListing.querySelector('[name="cases-data"]').textContent);
    const translationData: string[] = JSON.parse(casesListing.querySelector('[name="translation-data"]').textContent);

    render(
        (
            <CasesData.Provider value={casesData}>
                <TranslationData.Provider value={translationData}>
                    <CaseStudyListing />
                </TranslationData.Provider>
            </CasesData.Provider>
        ), casesListing
    );
}
