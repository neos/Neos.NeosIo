import {h} from 'preact';
import * as React from "preact/compat";

const projectVolumesValueMap = {
    1: 'n/a',
    5: '< 100 h',
    10: '100 - 499h',
    15: '500 - 999h',
    20: '1000 - 3000h',
    25: '> 3000h'
};

export default function CaseStudyGridItem({caseStudy}: {caseStudy: CaseStudy}) {
    return (
        <a key={caseStudy.identifier} class={'imageTeaser imageTeaser--isLink' + (caseStudy.featured ? ' references__item--featured' : '')} href={caseStudy.url} target="_blank" rel="noopener">
            {caseStudy.image
                ?
                <img src="/_Resources/Static/Packages/Neos.NeosIo/Images/Loader.svg" data-image-normal={caseStudy.image} class="imageTeaser__image" loading="lazy" title={caseStudy.title} alt={caseStudy.title} />
                : ''
            }
                <div class="imageTeaser__contents u-invertText">
                    <h4 class="imageTeaser__contents__heading">{caseStudy.title}</h4>
                    <footer class="references__data">
            			<span><i class="fas fa-users"></i>&nbsp;{projectVolumesValueMap[caseStudy.projectVolume]}</span><br />
                        <span><i class="fas fa-industry"></i>&nbsp;{caseStudy.projectType}</span>
                    </footer>
                </div>
        </a>
    )
}
