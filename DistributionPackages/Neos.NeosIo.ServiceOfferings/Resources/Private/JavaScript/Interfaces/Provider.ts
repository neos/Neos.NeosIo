interface Provider {
    identifier: string;
    title: string;
    description: string;
    website: string;
    logo?: string;
    bannerImage?: string;
    typesOfService: string[];
    street: string;
    city: string;
    zipcode: string;
    country: string;
    size: string;
    url: string;
    latitude: number;
    longitude: number;
    searchText: string;
    badges?: Badge[];
    awards?: string[];
}

interface Badge {
    alt: string;
    uri: string;
    width: number;
    height: number;
}
