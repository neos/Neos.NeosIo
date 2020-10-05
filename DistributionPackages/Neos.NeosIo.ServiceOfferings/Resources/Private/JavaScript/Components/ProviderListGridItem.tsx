import {h} from 'preact';
import * as React from "preact/compat";

export default function ProviderListEntry({provider}: {provider: Provider}) {
    return (
        <div key={provider.identifier} className="service-providers__grid-row">
            <div className="service-providers__grid-cell service-providers__list-entry__badge">
                {provider.badges && provider.badges.map((item) =>
                    <img src={item} alt="" class="service-providers__badge"/>
                )}
            </div>
            <div className="service-providers__grid-cell service-providers__list-entry__description">
                <h3>
                    <a href={provider.url} title={provider.title}>
                        {provider.title}
                    </a>
                </h3>
                <p>
                    {provider.typesOfService.length ? provider.typesOfService.join(', ') : 'N/A'}
                </p>
            </div>
            <div className="service-providers__grid-cell service-providers__list-entry__location">
                {provider.city ? (
                    <address>{provider.city}, {provider.country}</address>
                ) : 'N/A'}
            </div>
            <div className="service-providers__grid-cell service-providers__list-entry__size">
                <i class="fas fa-user-friends"/> {provider.size ? provider.size : 'N/A'}
            </div>
        </div>
    )
}
