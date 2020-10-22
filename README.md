# Pimcore Social Data - LinkedIn Connector

This Connector allows you to fetch social posts from LinkedIn. 
Before you start be sure you've checked out the [Setup Instructions](../00_Setup.md).

![image](https://user-images.githubusercontent.com/700119/96862352-a3f61d00-1465-11eb-9ea0-1c0d676bda01.png)

#### Requirements
* [Pimcore Social Data Bundle](https://github.com/dachcom-digital/pimcore-social-data)

## Installation

### I. Add Dependency
```json
"require" : {
    "dachcom-digital/social-data-linkedin-connector" : "~1.0.0",
}
```

### II. Register Connector Bundle
```php
// src/AppKernel.php
use Pimcore\Kernel;
use Pimcore\HttpKernel\BundleCollection\BundleCollection;

class AppKernel extends Kernel
{
    public function registerBundlesToCollection(BundleCollection $collection)
    {
        $collection->addBundle(new SocialData\Connector\LinkedIn\SocialDataLinkedInConnectorBundle());
    }
}
```

### III. Install Assets
```bash
bin/console assets:install web --relative --symlink
```

## Enable Connector

```yaml
# app/config/config.yml
social_data:
    social_post_data_class: SocialPost
    available_connectors:
        -   connector_name: linkedIn
```

## Connector Configuration
![image](https://user-images.githubusercontent.com/700119/96862232-7c9f5000-1465-11eb-9f54-aa5eecde76ed.png)

Now head back to the backend (`System` => `Social Data` => `Connector Configuration`) and checkout the linkedIn tab.
- Click on `Install`
- Click on `Enable`
- Before you hit the `Connect` button, you need to fill you out the Connector Configuration. After that, click "Save".
- Click `Connect`
  
## Connection
![image](https://user-images.githubusercontent.com/700119/96862278-8d4fc600-1465-11eb-8950-e8b32890f60d.png)

This will guide you through the linkedIn token generation. 
After hitting the "Connect" button, a popup will open to guide you through linkedIn authentication process. 
If everything worked out fine, the connection setup is complete after the popup closes.
Otherwise, you'll receive an error message. You may then need to repeat the connection step.

## Feed Configuration

| Name | Description
|------|----------------------|
| `Company ID` | Set company id to fetch posts from |
| `Limit` | Define a limit to restrict the amount of social posts to import (Default: 20) |

## Extended Connector Configuration
Normally you don't need to modify connector (`connector_config`) configuration, so most of the time you can skip this step.
However, if you need to change some core setting of a connector, you're able to change them of course.

```yaml
# app/config/config.yml
social_data:
    available_connectors:
        -   connector_name: linkedIn
            connector_config:
                api_connect_permission: ['r_liteprofile', 'r_emailaddress', 'r_organization_social'] # default value
```

***

## Copyright and license
Copyright: [DACHCOM.DIGITAL](http://dachcom-digital.ch)  
For licensing details please visit [LICENSE.md](LICENSE.md)  

## Upgrade Info
Before updating, please [check our upgrade notes!](UPGRADE.md)
