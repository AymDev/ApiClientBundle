# API Client Bundle
A **PHP 8.3+** & **Symfony 5 / 6 / 7** bundle extending the **Symfony HttpClient** component with extra features: authentication, caching, DTOs, ...

![Testing](https://github.com/AymDev/ApiClientBundle/workflows/Testing/badge.svg)
![Coding Standards](https://github.com/AymDev/ApiClientBundle/workflows/Coding%20Standards/badge.svg)
![Bundle installation](https://github.com/AymDev/ApiClientBundle/workflows/Bundle%20installation/badge.svg)
[![Latest Stable Version](https://poser.pugx.org/aymdev/api-client-bundle/v)](//packagist.org/packages/aymdev/api-client-bundle)
[![License](https://poser.pugx.org/aymdev/api-client-bundle/license)](//packagist.org/packages/aymdev/api-client-bundle)

>This bundle is a *work in progress* and should get a stable release soon.

# Why this bundle

The **Symfony HttpClient** component is a very powerful component but you can write the same code over and over if you
want to make it work with authentication, DTOs, caching, etc.

This bundle extends the **HttpClient** component and offers features through the `user_data` option key in your requests.
All you need to know are the features described in this documentation and the official
[HttpClient documentation](https://symfony.com/doc/current/http_client.html) !

# Usage

Install the bundle with **Composer**:
```shell
composer require aymdev/api-client-bundle
```

Configure it depending on the features you want to use by creating a **aymdev_api_client.yaml** file:
```yaml
aymdev_api_client:
    # enable features
```

Fetch the service using the `AymDev\ApiClientBundle\Client\ApiClientInterface` and use its constants in the `user_data`
options of your requests:

```php
<?php

declare(strict_types=1);

namespace App;

use AymDev\ApiClientBundle\Client\ApiClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class MyService
{
    public function __construct(
        private readonly ApiClientInterface $apiClient,
    ) {
    }
    
    public function apiCall(): ResponseInterface
    {
        return $this->apiClient->request('GET', 'https://example.com', [
            'user_data' => [
                ApiClientInterface::REQUEST_ID => 'api.example_com.unique_id',
            ],
        ]);
    }
}
```

# Request ID

Multiple features will need you to define a *request ID* identifying the HTTP calls. This can then be used to create a
cache key, log the correct response when doing concurrent requests, etc.

Your *request ID* must be unique to the call and is defined with the `REQUEST_ID` constant:
```php
use AymDev\ApiClientBundle\Client\ApiClientInterface;

// ... 

public function getOne(int $id): ResponseInterface
{
    return $this->apiClient->request('GET', 'https://api.com/item', [
        'query' => [
            'id' => $id,
        ],
        'user_data' => [
            // Note that the "dynamic property" is part of the request ID
            ApiClientInterface::REQUEST_ID => 'api_com.item.' . $id,
        ]
    ]);
}
```

# Logging

You can enable detailed logging by providing a **PSR logger** service:
```yaml
aymdev_api_client:
    logger: my.psr.logger.service
```

Then it will log any call with a defined *request ID* with the following properties:

| Key               | Description                                     |
|-------------------|-------------------------------------------------|
| `method`          | HTTP method                                     |
| `url`             | URL endpoint                                    |
| `response_status` | HTTP status code of the response                |
| `time`            | duration of the request (in seconds)            |
| `cache`           | if the response has been fetched from the cache |
| `error`           | error message if anything occured               |

# Cache

You can save responses in cache by providing a **PSR cache pool** service:
```yaml
aymdev_api_client:
    cache: my.psr.cache.service
```

Then you can enable caching per request using the following constants:

 - `CACHE_DURATION`: number of seconds to keep in cache
 - `CACHE_EXPIRATION`: expiration time of the response (overrides `CACHE_DURATION`)
 - `CACHE_ERROR_DURATION`: same as `CACHE_DURATION` but will be applied if the response status is >=300

The cache key will be determined based on the *request ID*:
```php
use AymDev\ApiClientBundle\Client\ApiClientInterface;

$apiClient->request('GET', 'https://example.com', [
    'user_data' => [
        ApiClientInterface::REQUEST_ID => 'my.request.id',
        
        // You actually need to define only one of those options
        ApiClientInterface::CACHE_DURATION => 86400,
        ApiClientInterface::CACHE_EXPIRATION => new \DateTime('Tomorrow 6 am'),
        
        // Will override previous options if an error occurs
        ApiClientInterface::CACHE_ERROR_DURATION => 3600,
    ]
]);
```
