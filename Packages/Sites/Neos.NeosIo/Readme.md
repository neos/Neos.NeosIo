# Neos.io website

## Building SASS
To to a minified build of the SASS for production use, run the following command in the site package folder (where package.json is):

`npm run build:sass && npm run postbuild:sass && npm run minify:styles`

During development, you can use:

`npm run watch:sass`

## Crowd integration

The website has a plugin to connect to the crowd API which provides
a list of all groups and members of the Neos community at crowd.neos.io.

### Available commands

The cli commands to interact with will ignore cached entries
and always interact with crowd.neos.io directly.

Only in crowd defined IP's are allowed to interact with the API.
Or you can use username/password combination in `Configuration/Settings.yaml` at key `Neos.NeosIo.crowdApi`.

Only groups are rendered by the plugin which have the attribute `neos_group_type` set to either `team` or `guild`.
Currently attributes can only be changed via the cli commands you find in this readme. 
Later this will also be made possible with a backend module.

#### List all groups and their attributes

    ./flow crowd:listgroups 
    
#### Modify Group

See the list of valid attributes in `Configuration/Settings.yaml` at key `Neos.NeosIo.crowdApi.additionalAttributes.group`.

    ./flow  crowd:setgroupattribute --groupname <CrowdGroupName> --attribute <Attribute> --value <Value>      

#### Show user and their attributes

    ./flow crowd:showuser --username <CrowdUsername>
    
#### Modify user

See the list of valid attributes in `Configuration/Settings.yaml` at key `Neos.NeosIo.crowdApi.additionalAttributes.user`.

    ./flow crowd:setuserattribute --username <CrowdUsername> --attribute <Attribute> --value <Value>

#### Clearing the crowd api data cache

    ./flow flow:cache:flushone --identifier NeosNeosIo_CrowdApiCache

