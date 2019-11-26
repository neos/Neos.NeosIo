import {h} from 'preact';
import * as React from "preact/compat";

export default function ProviderListEntry({provider}: {provider: Provider}) {
    return (
        <tr key={provider.identifier}>
            <td className="service-providers__list-entry__title">
                <a href={provider.url} title={provider.title}>{provider.title}</a>
            </td>
            <td className="service-providers__list-entry__location">
                {provider.city ? (
                    <address>{provider.city} {provider.country}</address>
                ) : 'N/A'}
            </td>
            <td className="service-providers__list-entry__services">
                {provider.typesOfService.length ? provider.typesOfService.join(', ') : 'N/A'}
            </td>
            <td className="service-providers__list-entry__size">{provider.size ? provider.size : 'N/A'}</td>
        </tr>
    )
}
