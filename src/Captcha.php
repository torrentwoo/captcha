<?php

namespace WooGoo;

use \Exception;

class Captcha
{
    /**
     * Width of output image.
     *
     * @var int
     */
    protected $width;

    /**
     * Height of output image.
     *
     * @var int
     */
    protected $height;

    /**
     * Types of image that can be output.
     *
     * @var array
     */
    protected $capableOf = array();

    /**
     * The captcha string which contains random characters combination.
     *
     * @var string
     */
    protected $captcha;

    /**
     * Length of captcha string.
     *
     * @var int
     */
    protected $length;

    /**
     * The fully qualified filename of the TrueType font.
     *
     * @var string
     */
    protected $font;

    /**
     * The font size in points.
     *
     * @var int
     */
    protected $fontSize = 14;

    /**
     * Array of area occupied by text, made from width, height.
     *
     * @var array
     */
    protected $textArea = array(0, 0);

    /**
     * Array set of the desired colors for the text.
     *
     * @var mixed
     */
    protected $textColor = array();

    /**
     * The space in pixel that between two character neighbours.
     *
     * @var int
     */
    protected $charMargin = 2; // pixel

    /**
     * Available character angles.
     *
     * @var array
     */
    protected $charAngleRange = array();

    /**
     * Array of each character angle.
     *
     * @var array
     */
    protected $charAngleSequences = array();

    /**
     * Array of horizontal position about each character.
     *
     * @var array
     */
    protected $charCoordinateSequences = array();

    /**
     * Grade of interference.
     *
     * @var string
     */
    protected $interference = 'none';

    /**
     * ImageCaptcha constructor.
     *
     * @param  string   $font
     * @param  int      $length
     * @param  int      $fontSize
     * @param  int      $characterMargin
     * @param  int      $characterAngleMin
     * @param  int      $characterAngleMax
     * @param  string   $interference
     * @throws \Exception
     */
    public function __construct(
        $font,
        $length = 6,
        $fontSize = 14,
        $characterMargin = 2,
        $characterAngleMin = -20,
        $characterAngleMax = 20,
        $interference = 'medium'
    ) {
        try {
            if (! extension_loaded('gd')) {
                throw new Exception('The GD extension is required.');
            }
            foreach (gd_info() as $item => $value) {
                if ($item === 'FreeType Support' && ! $value) {
                    throw new Exception('The FreeType Support is not installed.');
                }
                if ($item === 'GIF Create Support' && $value) {
                    $this->capableOf[] = 'gif';
                }
                if (($item === 'JPEG Support' || $item === 'JPG Support') && $value) {
                    array_push($this->capableOf, 'jpeg', 'jpg');
                }
                if ($item === 'PNG Support' && $value) {
                    $this->capableOf[] = 'png';
                }
            }
            if (empty($font)) {
                throw new Exception('A TrueType font must be specified.');
            }
            if (! is_readable($font) || ! ($this->font = realpath($font))) {
                throw new Exception('The TrueType font file does not exist or permission denied.');
            }

            $this->length = (int) $length;
            $this->fontSize = (int) $fontSize;

            $this->charMargin = (int) $characterMargin;
            $this->charAngleRange = range(min($characterAngleMin, $characterAngleMax), max($characterAngleMin, $characterAngleMax));

            $this->interference = (string) $interference;

            $this->captcha = $this->generateCaptcha();

            $this->getTextSize();

        } catch (Exception $exception) {
            throw $exception;
        }
    }

    /**
     * Generate a captcha string which is combined from the specified length of random characters.
     *
     * The character list does not contain number zero and one (too easy to be confused with letter O and L).
     *
     * @return string
     * @throws \Exception
     */
    protected function generateCaptcha()
    {
        $mixin = array_merge(range('a', 'z'), range('A', 'Z'), range(2, 9));

        for ($i = 0; $i < $this->length; $i++) {
            $combination[] = $mixin[mt_rand(0, count($mixin) - 1)];
        }
        if (! isset($combination)) {
            throw new Exception('Failed to generate random characters combination.');
        }

        return implode('', $combination);
    }

    /**
     * Measure and set the text area size of captcha image.
     *
     * @return void
     */
    protected function getTextSize()
    {
        $this->setCharAngleSequences();

        $widths = $heights = array();
        $promise = $this->charMargin;
        for ($i = 0; $i < $this->length; $i++) {
            list($lbx, $lby, $rbx, $rby, $rtx, $rty, $ltx, $lty) = imagettfbbox(
                $this->fontSize,
                $this->charAngleSequences[$i],
                $this->font,
                $this->captcha[$i]
            );
            $width = max(array($lbx, $rbx, $rtx, $ltx)) - min(array($lbx, $rbx, $rtx, $ltx));
            $widths[] = $width + $i * $this->charMargin;
            $heights[] = $height = max(array($lby, $rby, $rty, $lty)) - min(array($lby, $rby, $rty, $lty));
            $promise = $promise + $width;
            $this->charCoordinateSequences[] = $promise;
        }
        $this->textArea = array(array_sum($widths), max($heights));
    }

    /**
     * Set the drawing angle for each character.
     *
     * @return void
     */
    protected function setCharAngleSequences()
    {
        for ($i = 0; $i < $this->length; $i++) {
            $this->charAngleSequences[] = $this->charAngleRange[mt_rand(0, count($this->charAngleRange) - 1)];
        }
    }

    /**
     * Draw the image which contains the captcha string.
     *
     * @param  string   $type
     * @return string
     */
    protected function draw($type)
    {
        $canvas = imagecreatetruecolor($this->width, $this->height);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $alpha = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
        imagefill($canvas, 0, 0, $alpha);
        imageantialias($canvas, true);

        for ($i = 0; $i < $this->length; $i++) {
            if (empty($this->textColor)) {
                $textColor = imagecolorallocate($canvas, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            } else {
                list($red, $green, $blue) = $this->textColor[mt_rand(0, count($this->textColor) - 1)];
                $textColor = imagecolorallocate($canvas, $red, $green, $blue);
            }
            imagettftext(
                $canvas,
                $this->fontSize,
                $this->charAngleSequences[$i],
                $this->charCoordinateSequences[$i],
                ($this->height + $this->textArea[1]) / 2 - 1,
                $textColor,
                $this->font,
                $this->captcha[$i]
            );
        }

        $canvas = $this->interfere($canvas, $this->interference);

        ob_start();

        switch ($type) {
            case 'gif':
                imagegif($canvas);
                break;
            case 'jpeg':
            case 'jpg':
                imagejpeg($canvas);
                break;
            case 'png':
            default:
                imagepng($canvas);
        }
        imagedestroy($canvas);

        $data = ob_get_contents();
        ob_end_clean();

        return $data;
    }

    /**
     * Interfere the output image.
     *
     * @param  resource $image
     * @param  mixed    $level
     * @return mixed
     */
    protected function interfere($image, $level = null)
    {
        switch (true) {
            case ($level === 'low'):
                return $this->interfereDots($image);
            case ($level === 'medium'):
                return $this->interfereLines($this->interfereDots($image), 4);
            case ($level === 'high'):
                return $this->interfereLines($this->interfereDots($image, 200));
            case ($level === 'none'):
            default:
                return $image;
        }
    }

    /**
     * Draw dots to image.
     *
     * @param  resource $image
     * @param  int      $amount
     * @return mixed
     */
    protected function interfereDots($image, $amount = 100)
    {
        for ($i = 0; $i < $amount; $i++) {
            $dotColor = imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagesetpixel($image, mt_rand(0, $this->width), mt_rand(0, $this->height), $dotColor);
        }

        return $image;
    }

    /**
     * Draw lines to image.
     *
     * @param  resource $image
     * @param  int      $amount
     * @return mixed
     */
    protected function interfereLines($image, $amount = 10)
    {
        for ($i = 0; $i < $amount; $i++) {
            $lineColor = imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imageline($image, mt_rand(0, $this->height), mt_rand(0, $this->width), mt_rand(0, $this->width), mt_rand(0, $this->height), $lineColor);
        }

        return $image;
    }

    /**
     * Set the desired colors for the captcha text.
     *
     * @param  mixed
     * @return void
     */
    public function allocateTextColor()
    {
        foreach (func_get_args() as $color) {
            if ($this->verifyHexColor($color)) {
                $this->textColor[] = $this->parseHexColorToRGBColor($color);
            }
        }
    }

    /**
     * Verify a color code is hexadecimal.
     *
     * @param  mixed  $colorCode
     * @return bool
     */
    protected function verifyHexColor($colorCode)
    {
        if (is_string($colorCode)) {
            $colorCode = ltrim($colorCode, '#');

            if (ctype_xdigit($colorCode) && (strlen($colorCode) === 6 || strlen($colorCode) === 3)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert a hexadecimal color code to an array represented by RGB index.
     *
     * @param  string  $colorCode
     * @return array
     */
    protected function parseHexColorToRGBColor($colorCode)
    {
        $hex = ltrim($colorCode, '#');
        $len = strlen($hex);

        if ($len === 6) { // #000000
            $rgb[] = hexdec(substr($hex, 0, 2));
            $rgb[] = hexdec(substr($hex, 2, 2));
            $rgb[] = hexdec(substr($hex, 4, 2));
        } else { // shorthand color
            $rgb[] = hexdec(str_repeat(substr($hex, 0, 1), 2));
            $rgb[] = hexdec(str_repeat(substr($hex, 1, 1), 2));
            $rgb[] = hexdec(str_repeat(substr($hex, 2, 1), 2));
        }

        return $rgb;
    }

    /**
     * Get the raw captcha string.
     *
     * @return string
     */
    public function getCaptcha()
    {
        return $this->captcha;
    }

    /**
     * Output the graphic captcha.
     *
     * @param  int      $minWidth
     * @param  int      $minHeight
     * @param  string   $outputType
     * @return string
     * @throws \Exception
     */
    public function getGraphicCaptcha($minWidth = 88, $minHeight = 28, $outputType = 'png')
    {
        if (! in_array($outputType, $this->capableOf)) {
            throw new Exception(strtoupper($outputType) . ' support for GD extension is not included');
        }

        $this->width = max((int) $minWidth, $this->textArea[0]);
        $this->height = max((int) $minHeight, $this->textArea[1]);

        return $this->draw($outputType);
    }
}
