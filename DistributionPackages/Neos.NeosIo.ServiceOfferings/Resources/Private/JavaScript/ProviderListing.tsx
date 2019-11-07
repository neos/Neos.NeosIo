import {h} from 'preact';
import * as React from "preact/compat";
import {useContext, useState, useEffect} from "preact/hooks";
import ProviderData from "./Context/ProviderData";

export default function ProviderListing() {
    const providerData: Provider[] = useContext(ProviderData);
    const countries = providerData.reduce((carry: string[], provider) => {
        carry.push(provider.country);
        return carry;
    }, []).filter((v, i, a) => v && a.indexOf(v) === i);

    const [searchWord, setSearchWord] = useState('');
    const [countryFilter, setCountryFilter] = useState('');
    const [providers, setProviders] = useState(providerData);

    const search = (value: string) => {
        setSearchWord(value.toLowerCase());
    };

    const filterByCountry = (value: string) => {
        setCountryFilter(value);
    };

    useEffect(() => {
        const filteredProviders = providerData.filter(provider => {
            return (!searchWord || provider.title.toLowerCase().includes(searchWord))
                && (!countryFilter || provider.country == countryFilter);
        });
        setProviders(filteredProviders);
    }, [searchWord, countryFilter]);

    return (
        <div>
            <div>
                <input type="text" placeholder="Search..." onKeyUp={e => search(e.target['value'])}/>

                <select id="redirects-filter-status-code"
                        onChange={e => filterByCountry(e.target['value'])}>
                    <option value="">All countries</option>
                    {countries.map(country => <option key={country} value={country}>{country}</option>)}
                </select>
            </div>
            <table>
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Location</th>
                    <th>Services</th>
                    <th>Size</th>
                </tr>
                </thead>
                <tbody>
                {providers.map((provider: Provider) => (
                    <tr key={provider.identifier}>
                        <td><a href={provider.url} title={provider.title}>{provider.title}</a></td>
                        <td>
                            {provider.street ? (
                                <address>{provider.street} {provider.zipcode} {provider.city} {provider.country}</address>
                            ) : 'N/A'}
                        </td>
                        <td>{provider.typesOfService.length ? provider.typesOfService.join(', ') : 'N/A'}</td>
                        <td>{provider.size ? provider.size : 'N/A'}</td>
                    </tr>
                ))}
                </tbody>
            </table>
        </div>
    )
}

