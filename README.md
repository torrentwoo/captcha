## Simple Graphic Captcha Generator

A simple graphic captcha generator driven by the GD extension

### Quick start:

```php
<?php

use WooGoo\Captcha;

$font = './captcha-font.ttf';

$captcha = new Captcha($font); // initialization, with a fully qualified TrueType font file

$captcha_string = $captcha->getCaptcha(); // retrieve your captcha string

$captcha_image = $captcha->getGraphicCaptcha(100, 40, 'png'); // define min-width and min-height of output image

header('Content-type: image/png');

echo $captcha_image; // output captcha image to browser

```

### Initialization options (via "__construct" method)

- $font: the fully qualified TrueType font
- $length: the length of captcha string that you need
- $fontSize: the font size (in pt)
- $characterMargin: the space in pixel that between two character neighbours
- $characterAngleMin: the minimum available angle for each character
- $characterAngleMax: the maximum available angle for each character
- $interference: grade of interference for output image (none, low, medium, high)

### Works with Bootstrap

```html
<div class="input-group">
    <input type="text" name="captcha" class="form-control" id="inputCaptcha" placeholder="Captcha" aria-describedby="form-captcha-addon">
    <span class="input-group-addon" id="form-captcha-addon">
        <img id="imgCaptcha" src="/PATH-TO-YOUR/CAPTCHA-RENDER" alt="captcha" title="Refresh captcha on click" />
    </span>
</div>

```
