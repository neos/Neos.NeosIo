import {h} from 'preact';
import * as React from "preact/compat";

export default function ProviderListEntry({provider}: {provider: Provider}) {
    return (
        <div key={provider.identifier} className="service-providers__grid-row">
            <div className="service-providers__grid-cell">
                {provider.badges
                    ? provider.badges.map((item) =>
                        <img src={item} alt="" class="service-providers__badge"/>
                    )
                    : ''
                }

            </div>
            <div className="service-providers__grid-cell">
                <h3>
                    <a href={provider.url} title={provider.title}>
                        {provider.title}
                    </a>
                </h3>
                <p>
                    {provider.typesOfService.length ? provider.typesOfService.join(', ') : 'N/A'}
                </p>
                <a href={provider.url} title={provider.title}>
                    {provider.logo
                        ? <img src={provider.logo} alt={provider.title} class="service-providers__grid-logo" />
                        : ''
                    }
                </a>

            </div>
            <div className="service-providers__grid-cell service-providers__list-entry__location">
                {provider.city ? (
                    <address>{provider.city}, {provider.country}</address>
                ) : 'N/A'}
            </div>
            <div className="service-providers__grid-cell service-providers__list-entry__size">
                <i class="fas fa-user-friends"></i> {provider.size ? provider.size : 'N/A'}
            </div>
        </div>
    )
}
