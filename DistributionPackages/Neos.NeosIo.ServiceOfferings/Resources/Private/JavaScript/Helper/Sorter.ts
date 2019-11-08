export enum SortDirection {
    Asc,
    Desc,
}

export function sortObjects(objects: Array<any>, propertyName: string, sortDirection?: SortDirection): Array<any> {
    const sortedObjects = objects.sort((a, b) => {
        let x = a[propertyName];
        if (typeof x === 'string') {
            x = x.toLowerCase();
        } else if (x === null) {
            x = '';
        }
        let y = b[propertyName];
        if (typeof y === 'string') {
            y = y.toLowerCase();
        } else if (y === null) {
            y = '';
        }
        return x < y ? -1 : x > y ? 1 : 0;
    });

    if (sortDirection === SortDirection.Desc) {
        sortedObjects.reverse();
    }

    return sortedObjects;
}
