<?php

namespace Webravolab\Cdn\Adapters;


use Gregwar\Image\Image;
use Gregwar\Image\ImageColor;
use Gregwar\Image\Adapter\GD;

class GD_extended extends GD
{
    /**
     * Resize image canvas adapting the image inside the new canvas
     * @param $width            width of the new canvas
     * @param $height           height of the new canvas
     * @param $posX             position X where copy the image, or 'left','right','center' to auto-align
     * @param $posY             position Y where copy the image, or 'top','bottom','center' to auto-align
     * @param $bg               background color (default transparent)
     * @return $this
     */
    public function resizeCanvas($width, $height, $posX = 'center', $posY = 'center', $bg = 'transparent')
    {
        $w = $this->width();
        $h = $this->height();

        if ($width < $w || $height < $h) {
            // Resize to current width
            $ratio = $w / $h;
            $new_ratio = $width / $height;
            if ($ratio >= 1 && $new_ratio >= 1) {
                // Same horizontal aspect
                $new_height = $height;
                $new_width = round($new_height * $ratio);
                if ($new_width > $width) {
                    // too wide ... make some adjustment
                    $new_width = $width;
                    $new_height = round($new_width / $ratio);
                }
            }
            elseif ($ratio < 1 && $new_ratio >= 1) {
                // ratio changed from vertical to horizontal
                $new_height = $height;
                $new_width = round($new_height * $ratio);
            }
            elseif ($ratio >= 1 && $new_ratio < 1) {
                // ration changed from horizontal to vertical
                $new_width = $width;
                $new_height = round($new_width / $ratio);
            }
            $r_temp = imagecreatetruecolor($width, $height);
            $r_image = $this->resource;
            imagecopyresampled($r_temp, $r_image, 0, 0, 0, 0, $new_width, $new_height, $w, $h);
            $w = $new_width;
            $h = $new_height;
        }
        else {
            $r_temp = $this->resource;
        }

        if ($width >= $w) {
            // Expand canvas width
            switch ($posX) {
                case 'left':
                    $posX = 0;
                    break;
                case 'right':
                    $posX = $width - $w;
                    break;
                case 'center':
                    $posX = round(($width - $w) / 2);
                    break;
            }
        }
        else {
            $posX = 0;
        }
        if ($height >= $h) {
            // Expand canvas height
            switch ($posY) {
                case 'top':
                    $posY = 0;
                    break;
                case 'bottom':
                    $posY = $height - $h;
                    break;
                case 'center':
                    $posY = round(($height - $h) / 2);
                    break;
            }
        }
        else {
            $posY = 0;
        }
        // Make a new image with the new canvas size
        $r_new = imagecreatetruecolor($width, $height);
        if ($bg != 'transparent') {
            imagefill($r_new, 0, 0,  ImageColor::gdAllocate($r_new, $bg));
        } else {
            imagealphablending($r_new, false);
            $color = ImageColor::gdAllocate($r_new, 'transparent');
            imagefill($r_new, 0, 0, $color);
            imagesavealpha($r_new, true);
        }

        // Copy original image to the new canvas
        imagecopyresampled($r_new, $r_temp, $posX, $posY, 0, 0, $w, $h, $w, $h);

        // Replace resource
        $this->resource = $r_new;

        return $this;
    }

    /**
     * Auto-crop image removing background
     * @param int $mode             one of white/black/threshold/side/transparent (see GD docs)
     * @param float $threshold      used only in threshold mode
     * @param string $bg            used only in threshold mode
     */
    public function cropAuto($mode = IMG_CROP_DEFAULT, $threshold = 0.5, $bg = 'transparent') {
        switch ($mode) {
            case 'white':
                $mode = IMG_CROP_WHITE;
                break;
            case 'black':
                $mode = IMG_CROP_BLACK;
                break;
            case 'threshold':
                $mode = IMG_CROP_THRESHOLD;
                break;
            case 'side':
                $mode = IMG_CROP_SIDES;
                break;
            case 'transparent':
                $mode = IMG_CROP_TRANSPARENT;
                break;
            default:
                $mode = IMG_CROP_DEFAULT;
                break;
        }
        $r_new = imagecropauto($this->resource, $mode, $threshold, ImageColor::gdAllocate($this->resource, $bg));
        if ($r_new !== false) {
            imagesavealpha($r_new, TRUE);
            $this->resource = $r_new;
        }
    }
}
