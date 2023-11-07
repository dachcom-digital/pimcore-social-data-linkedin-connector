# Pimcore Social Data - LinkedIn Connector

[![Software License](https://img.shields.io/badge/license-GPLv3-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Latest Release](https://img.shields.io/packagist/v/dachcom-digital/social-data-linkedin-connector.svg?style=flat-square)](https://packagist.org/packages/dachcom-digital/social-data-linkedin-connector)
[![Tests](https://img.shields.io/github/actions/workflow/status/dachcom-digital/pimcore-social-data-linkedin-connector/.github/workflows/codeception.yml?branch=master&style=flat-square&logo=github&label=codeception)](https://github.com/dachcom-digital/pimcore-social-data-linkedin-connector/actions?query=workflow%3ACodeception+branch%3Amaster)
[![PhpStan](https://img.shields.io/github/actions/workflow/status/dachcom-digital/pimcore-social-data-linkedin-connector/.github/workflows/php-stan.yml?branch=master&style=flat-square&logo=github&label=phpstan%20level%204)](https://github.com/dachcom-digital/pimcore-social-data-linkedin-connector/actions?query=workflow%3A"PHP+Stan"+branch%3Amaster)

This Connector allows you to fetch social posts from LinkedIn. 

![image](https://user-images.githubusercontent.com/700119/96862352-a3f61d00-1465-11eb-9ea0-1c0d676bda01.png)

### Release Plan
| Release | Supported Pimcore Versions | Supported Symfony Versions | Release Date | Maintained     | Branch                                                                                    |
|---------|----------------------------|----------------------------|--------------|----------------|-------------------------------------------------------------------------------------------|
| **3.x** | `11.0`                     | `6.2`                      | --           | Feature Branch | master                                                                                    |
| **2.x** | `10.1` - `10.6`            | `5.4`                      | 05.01.2022   | Unsupported    | [2.x](https://github.com/dachcom-digital/pimcore-social-data-linkedin-connector/tree/2.x) |
| **1.x** | `6.0` - `6.9`              | `3.4`, `^4.4`              | 22.10.2020   | Unsupported    | [1.x](https://github.com/dachcom-digital/pimcore-social-data-linkedin-connector/tree/1.x) |

## Installation

```json
"require" : {
    "dachcom-digital/social-data" : "~3.0.0",
    "dachcom-digital/social-data-linkedin-connector" : "~3.0.0"
}
```

Add Bundle to `bundles.php`:
```php
return [
    SocialData\Connector\LinkedIn\SocialDataLinkedInConnectorBundle::class => ['all' => true],
];
```

### Install Assets
```bash
bin/console assets:install public --relative --symlink
```

## Enable Connector
```yaml
# config/packages/social_data.yaml
social_data:
    social_post_data_class: SocialPost
    available_connectors:
        -   connector_name: linkedIn
```

### Set Cookie SameSite to Lax
Otherwise, the oauth connection won't work.
> If you have any hints to allow processing an oauth connection within `strict` mode, 
> please [tell us](https://github.com/dachcom-digital/pimcore-social-data-instagram-connector/issues).

```yaml
framework:
    session:
        cookie_samesite: 'lax'
```

## LinkedIn Backoffice
Some hints to set up your LinkedIn app:
- Create App via https://www.linkedin.com/developers/apps
- Add `https://YOURDOMAIN/admin/social-data/connector/linkedin/check` in `Authorized redirect URLs for your app`
- Apply for verification (We wish you the best of luck!)

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
| Name         | Description                                                                   |
|--------------|-------------------------------------------------------------------------------|
| `Company ID` | Set company id to fetch posts from                                            |
| `Limit`      | Define a limit to restrict the amount of social posts to import (Default: 20) |

## Extended Connector Configuration
Normally you don't need to modify connector (`connector_config`) configuration, so most of the time you can skip this step.
However, if you need to change some core setting of a connector, you're able to change them of course.

```yaml
# config/packages/social_data.yaml
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
