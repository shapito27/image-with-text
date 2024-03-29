Image with text
===

Crate new image from given image and given text. Text will be placed on the image with align.

Example:
```php
require_once dirname(__DIR__).'/vendor/autoload.php';

use Shapito27\ImageCreator\Services\ImageGenerator;
use Shapito27\ImageCreator\Models\Color;

$imageGenerator      = new ImageGenerator();
    $imageGenerator
        ->setSourceImagePath('/var/www/exapmple/images/source/test.jpeg')
        ->setResultImagePath('/var/www/exapmple/images/result/test.jpeg')
        ->setFontPath('/var/www/exapmple/font/merriweatherregular.ttf')
        ->setTextColor(new Color('#000000'))
        ->setTextFontSize(25)
        ->setText('Test title')
        ->setCoeficientLeftRightTextPadding(20)
        ->setTextLinesTopBottomPadding(15)
        ->setImageQuality(100)
        ->generate();
```

Check demo:
===
```shell
make run
```

Open the tool in browser http://localhost:8000/example/index.php

Test
===
```shell
make test
```

Rebuild container
===
```shell
docker compose up --build
```

