<?php

namespace Shapito27\ImageCreator\Models;

/**
 * Class Color
 * @package Shapito27\ImageCreator\Models
 */
class Color
{
    /** @var int */
    public $red = 255;
    /** @var int */
    public $green = 255;
    /** @var int */
    public $blue = 255;

    public const BLACK_COLOR='#000000';

    /**
     * Color constructor. Set by hex code
     *
     * @param string|null $hexCode
     */
    public function __construct(?string $hexCode)
    {
        if ($hexCode !== null) {
            $this->setByHexCode($hexCode);
        }
    }

    /**
     * Set color by Hex Code
     *
     * @param  string  $hexCode
     */
    public function setByHexCode(string $hexCode): void
    {
        [$this->red, $this->green, $this->blue] = sscanf($hexCode, "#%02x%02x%02x");
    }

    /**
     * Set color by RGB absolute values
     *
     * @param  int  $red
     * @param  int  $green
     * @param  int  $blue
     */
    public function setByAbsoluteRGB(int $red, int $green, int $blue): void
    {
        $this->red   = $red;
        $this->green = $green;
        $this->blue  = $blue;
    }
}
