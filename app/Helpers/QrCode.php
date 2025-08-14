<?php

namespace App\Helpers;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCode
{
    protected static $size = 200;
    
    public static function size($size)
    {
        self::$size = $size;
        return new static;
    }
    
    public static function generate($data)
    {
        $renderer = new ImageRenderer(
            new RendererStyle(self::$size),
            new SvgImageBackEnd()
        );
        
        $writer = new Writer($renderer);
        
        // Reset size for next use
        $svg = $writer->writeString($data);
        self::$size = 200;
        
        return $svg;
    }
}