##Simple Graphic Captcha Generator

A simple graphic captcha generator driven by the GD extension

###Usage:

```
<?php

$captcha = new Captcha($font); // initialization, with a fully qualified TrueType font file

$captcha_string = $captcha->getCaptcha(); // retrieve your catpcha string

$captcha_iamge = $captcha->getGraphicCaptcha(100, 40, 'png');

header('Content-type: image/png');

echo $captcha_image; // output captcha image to browser

```
