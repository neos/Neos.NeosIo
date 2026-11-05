# Neos Market Place

This package is a prototype for the Extension Repository for the Neos Project.

The goal is to make available packages and vendors more visible on the main [website](http://www.neos.io) of the Neos Project.

Flow Framework and Neos CMS use composer to handle packages, so this project is a simple frontend on top of Packagist. We
synchronize in a regulary basic the package from Packagist.

## Features

-   [x] Import / Update packages and versions from Packagist
-   [x] Import / Update maintainers from Packagist
-   [x] Handle abandonned packages
-   [x] Basic integration with ElasticSearch
-   [x] Listing of packages
-   [x] Vendor detail page
-   [x] Package detail page
-   [x] Handle deleted packages
-   [x] Get metrics from Packagist (downloads)
-   [x] Get metrics from Github (star, watch, fork, issues)
-   [ ] Some utility NodeType to show case specific packages in neos.io
-   [ ] More advanced search configuration
-   [ ] ElasticSearch Aggregation support

### Search

#### Without query (sorted by last activity)

![Search without Query](https://dl.dropboxusercontent.com/s/bfcbpenwly726ix/2016-03-31%20at%2010.36%202x.png?dl=0)

#### With query (sorted by pertinance, todo)

![Search with Query](https://dl.dropboxusercontent.com/s/437t8sy0n1of630/2016-03-31%20at%2010.36%202x%20%281%29.png?dl=0)

### Vendor Page

![Vendor](https://dl.dropboxusercontent.com/s/8fe4c7jjsj9i49m/2016-03-31%20at%2010.37%202x.png?dl=0)

### Package Page

![Package](https://dl.dropboxusercontent.com/s/ixsc449cxt7jemg/2016-03-31%20at%2010.37%202x%20%281%29.png?dl=0)

## Configuration

To not hit the github API request limitation, please configuration your account username and password in
`Settings.yaml` at `Neos.MarketPlace.github.account` and `Neos.MarketPlace.github.password`.

## CLI Tools

You can run the full sync for all packages:

    flow marketplace:sync

Or run it for a sinlge package:

    flow marketplace:sync --package ttree/jobbutler

The sync only writes to the content repository if something actually changed. The state of the last sync is
kept in the `NeosMarketPlace_PackageSyncCache` (Redis in production) and contains the timestamp of the last
sync as well as a hash of the imported versions. Packages whose versions did not change are not read from
the content repository at all.

> **Note:** The cache entries must not expire on their own. If they do, every package is treated as new and
> the whole marketplace is re-imported on every sync run.

At the end of a sync run a summary is printed:

    1217 package(s) checked, 1198 skipped, 19 changed, 19 version sync(s), 1198 version sync(s) skipped

## Development

If you want to test the sync functionality make sure to have ElasticSearch running on your machine or use the localbeach
setup.

Also you need to create a GitHub token and add it to your local settings to make the sync also include forks and 
other properties:

```yaml
Neos:
  MarketPlace:
    github:
      token: <my-token>
```

### Unit tests

The unit tests of the marketplace importer run without a database or ElasticSearch:

    bin/phpunit -c DistributionPackages/Neos.MarketPlace/Tests/phpunit.xml

## License

The MIT License (MIT). Please see [LICENSE](LICENSE.txt) for more information.
