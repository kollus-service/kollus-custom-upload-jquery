# Kollus Upload JS

Upload media javascript library by Kollus Http-endpoint API

## Requirement
* [php](http://php.net) : 5.5 above
   * module
      * [slimphp](https://www.slimframework.com/) : for smaple code's web framework
      * [slim-twig](https://github.com/slimphp/Twig-View)
* [node.js](https://nodejs.org)
* [jQuery](https://jquery.com) : 3.2.1
   * [Kollus Custom Upload By jQuery](https://github.com/kollus-service/kollus-custom-upload-jquery) library
* [Boostrap](https://getbootstrap.com/docs/3.3/) : for smaple code
      
## Installation

```bash
git clone https://github.com/kollus-service/kollus-custom-upload-jquery
cd kollus-custom-upload-jquery

composer install
npm install
```
Copy .config.yml to config.yml and Edit this.

```yaml
kollus:
  domain: [kollus domain]
  version: 0
  service_account:
    key : [service account key]
    api_access_token: [api access token]
    custom_key: [custom key]
    security_key: [security_key]
```

## built source
* /dist/kollus-upload.js

## How to use

```bash
php -S localhost:8080 -t public public/index.php

...
Listening on http://localhost:8080
Document root is /Users/yupmin/PhpstormProjects/kollus-custom-upload-jquery/public
Press Ctrl-C to quit.
```

Open browser '[http://localhost:8080](http://localhost:8080)'

## If you use modern browser

* IE 10 above and other latest browser is best
* Don't use 'iframe upload' and 'kollus progress api'

## Development flow
1. Reqeust local server api for create 'upload url' on browser
   * '/api/upload/create_url' in public/index.php 
2. Local server call kollus api and create kollus 'upload url'
   * use get_upload_url_response in \Kollus\Component\Client\ApiClient.php
3. Upload file to kollus 'upload url'
   * use upload-file event in public/js/default.js

## License
See `LICENSE` for more information

