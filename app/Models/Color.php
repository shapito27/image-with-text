<?php

namespace App\Models;

/**
 * Class Color
 * @package App\Models
 */
class Color
{
    /** @var int */
    public $red = 255;
    /** @var int */
    public $green = 255;
    /** @var int */
    public $blue = 255;

    /**
     * Color constructor.
     *
     * @param  int  $red
     * @param  int  $green
     * @param  int  $blue
     */
    public function __construct(int $red, int $green, int $blue)
    {
        $this->red   = $red;
        $this->green = $green;
        $this->blue  = $blue;
    }
}