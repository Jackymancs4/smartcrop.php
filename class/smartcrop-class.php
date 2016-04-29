<?php

class SmartCrop
{
    public $originalimage = null;
    public $image = null;
    public $finalimage = null;

    public $options = array(
                          'width' => 0,
                          'height' => 0,
                          'aspect' => 0,
                          'cropWidth' => 400,
                          'cropHeight' => 400,
                          'detailWeight' => 0.2,
                          'skinColor' => array(0.78, 0.57, 0.44),
                          'skinBias' => 0.01,
                          'skinBrightnessMin' => 0.2,
                          'skinBrightnessMax' => 1.0,
                          'skinThreshold' => 0.8,
                          'skinWeight' => 1.8,
                          'saturationBrightnessMin' => 0.05,
                          'saturationBrightnessMax' => 0.9,
                          'saturationThreshold' => 0.4,
                          'saturationBias' => 0.2,
                          'saturationWeight' => 0.3,

                          'scoreDownSample' => 1,
                          'step' => 8,
                          'scaleStep' => 0.1,
                          'minScale' => 0.9,
                          'maxScale' => 1.0,
                          'edgeRadius' => 0.4,
                          'edgeWeight' => -20.0,
                          'outsideImportance' => -0.5,
                          'ruleOfThirds' => true,
                          'prescale' => true,
                          'canvasFactory' => null,
                          'debug' => false,
                        );

    public function crop($image)
    {
        // if ($this->options['aspect']) {
        //     $this->options['width'] = $this->options['aspect'];
        //     $this->options['height'] = 1;
        // }

        $this->originalimage = imagecreatefromjpeg($image);
        $this->image = $this->originalimage;

        $scale = 1;
        $prescale = 1;

        $result = $this->analyse();

        // if ($this->options['width'] && $this->options['height']) {
        //     $scale = min(imagesx($this->image) / $this->options['width'], imagesy($this->image)/ $this->options['height']);
        //     $this->options['cropWidth'] = floor($this->options['width'] * $scale);
        //     $this->options['cropHeight']= floor($this->options['height'] * $scale);
        //     $this->options['minScale'] = min($this->options['maxScale'], max(1 / $scale, $this->options['minScale']));
        //
        //     if ($this->options['prescale'] !== false) {
        //       $prescale = 1 / $scale / $this->options['minScale'];
        //         if ($prescale < 1) {
        //             $prescaledCanvas = imagecreatetruecolor(imagesx($this->image) * $prescale, imagesy($this->image) * $prescale);
        //
        //             $this->options['cropWidth'] = floor($this->options['cropWidth'] * $prescale);
        //             $this->options['cropHeight']= floor($this->options['cropHeight'] * $prescale);
        //         } else {
        //             $prescale = 1;
        //         }
        //     }
        // }

      //$this->image = $image;
      return 0;
    }

    public function saturation($r, $g, $b)
    {
        $maximun = max(($r / 255), ($g / 255), ($b / 255));
        $minimun = min(($r / 255), ($g / 255), ($b / 255));
        if ($maximun == $minimun) {
            $ret = 0;
        } else {
            $l = ($maximun + $minimun) / 2;
            $d = $maximun - $minimun;

            if ($l > 0.5) {
                $ret = $d / (2 - $maximun - $minimun);
            } else {
                $ret = $d / ($maximun + $minimun);
            }
        }

        return $ret;
    }

    public function skinColor($r, $g, $b, $skin)
    {
        $mag = sqrt($r * $r + $g * $g + $b * $b);

        if ($mag == 0) {
            $mag = 1;
        }

        $rd = $r / $mag - $skin[0];
        $gd = $g / $mag - $skin[1];
        $bd = $b / $mag - $skin[2];
        $d = sqrt($rd * $rd + $gd * $gd + $bd * $bd);

        return 1 - $d;
    }

    public function thirds($x)
    {
        $x = (($x - (1 / 3) + 1.0) % 2.0 * 0.5 - 0.5) * 16;

        return max(1.0 - $x * $x, 0);
    }

    public function cie($r, $g, $b)
    {
        return 0.5126 * $b + 0.7152 * $g + 0.0722 * $r;
    }

    public function cie_array($cc)
    {
        return $this->cie($cc[0], $cc[1], $cc[2]);
    }

    public function cie_cc($x, $y)
    {
        $a = $this->cie_array($this->color_picker($x, $y));
        $b = $this->cie_array($this->color_picker($x, $y - 1));
        $c = $this->cie_array($this->color_picker($x - 1, $y));
        $d = $this->cie_array($this->color_picker($x + 1, $y));
        $f = $this->cie_array($this->color_picker($x, $y + 1));

        return  ($a * 4) - $b - $c - $d - $f;
    }

    public function color_picker($x, $y, $image = false)
    {
        if ($image == false) {
            $image = $this->originalimage;
        }

        $color_index = imagecolorat($image, $x, $y);
        $color_tran = imagecolorsforindex($image, $color_index);
        $cc = array($color_tran['red'], $color_tran['green'], $color_tran['blue']);

        return $cc;
    }

    public function edgeDetect($all = false)
    {
        $imx = imagesx($this->image);
        $imy = imagesy($this->image);

        $img = imagecreatetruecolor($imx, $imy);

        $lightness = 0;

        for ($i = 1; $i < $imy - 1; ++$i) {
            for ($j = 1; $j < $imx - 1; ++$j) {
                if ($j == 0 || $j >= ($imx - 1) || $i == 0 || $i >= ($imy - 1)) {
                    $cc = $this->color_picker($j, $i);
                    $lightness = $this->cie_array($cc);
                } else {
                    $lightness = $this->cie_cc($j, $i);
                }
                $cc = $this->color_picker($j, $i);

                if ($lightness < 0) {
                    $lightness = 0;
                }

                $pixel = imagecolorallocate($img, $cc[0], (int) $lightness, $cc[2]);
                imagesetpixel($img, $j, $i, $pixel);
            }
        }

        $this->finalimage = $img;

        return $img;
    }

    public function skinDetect($all = true)
    {
        $imx = imagesx($this->originalimage);
        $imy = imagesy($this->originalimage);

        $img = imagecreatetruecolor($imx, $imy);

        $lightness = 0;
        $skin = array(0.78, 0.57, 0.44);
        $skinThreshold = 0.8;
        $skinBrightnessMin = 0.2;
        $skinBrightnessMax = 1;

        for ($i = 1; $i < $imy - 1; ++$i) {
            for ($j = 1; $j < $imx - 1; ++$j) {
                $cc = $this->color_picker($j, $i);
                $lightness = $this->cie_array($cc) / 255;
                $skind = $this->skinColor($cc[0], $cc[1], $cc[2], $skin);
                if ($skind > $skinThreshold && $lightness >= $skinBrightnessMin && $lightness <= $skinBrightnessMax) {
                    $color = ($skind - $skinThreshold) * (255 / (1 - $skinThreshold));

                    $cc = $this->color_picker($j, $i, $this->finalimage);

                    $pixel = imagecolorallocate($img, $color, $cc[1], $cc[2]);
                } else {
                    $cc = $this->color_picker($j, $i, $this->finalimage);
                    $pixel = imagecolorallocate($img, 0, $cc[1], $cc[2]);
                }
                imagesetpixel($img, $j, $i, $pixel);
            }
        }

        $this->finalimage = $img;

        return $img;
    }

    public function saturationDetect($all = true)
    {
        $imx = imagesx($this->originalimage);
        $imy = imagesy($this->originalimage);

        $img = imagecreatetruecolor($imx, $imy);

        $lightness = 0;
        $saturationThreshold = 0.4;
        $saturationBrightnessMin = 0.05;
        $saturationBrightnessMax = 0.9;

        for ($i = 1; $i < $imy - 1; ++$i) {
            for ($j = 1; $j < $imx - 1; ++$j) {
                $cc = $this->color_picker($j, $i);
                $lightness = $this->cie_array($cc) / 255;

                $sat = $this->saturation($cc[0], $cc[1], $cc[2]);

                if ($sat > $saturationThreshold && $lightness >= $saturationBrightnessMin && $lightness <= $saturationBrightnessMax) {
                    $color = ($sat - $saturationThreshold) * (255 / (1 - $saturationThreshold));

                    $cc = $this->color_picker($j, $i, $this->finalimage);
                    $pixel = imagecolorallocate($img, $cc[0], $cc[1], $color);
                } else {
                    $cc = $this->color_picker($j, $i, $this->finalimage);
                    $pixel = imagecolorallocate($img, $cc[0], $cc[1], 0);
                }

                imagesetpixel($img, $j, $i, $pixel);
            }
        }

        $this->finalimage = $img;

        return $img;
    }

    public function crops()
    {
        $crops = array();
        $width = imagesx($this->finalimage);
        $height = imagesy($this->finalimage);
        $minDimension = min($width, $height);
        $cropWidth = $this->options['cropWidth'] || $minDimension;
        $cropHeight = $this->options['cropHeight'] || $minDimension;

        for ($scale = $this->options['maxScale']; $scale >= $this->options['minScale']; $scale -= $this->options['scaleStep']) {
            for ($y = 0; $y + $cropHeight * $scale <= $height; $y += $this->options['step']) {
                for ($x = 0; $x + $cropWidth * $scale <= $width; $x += $this->options['step']) {
                    $crops[] = array(
                                    'x' => $x,
                                    'y' => $y,
                                    'width' => $cropWidth * $scale,
                                    'height' => $cropHeight * $scale,
                                    );
                }
            }
        }

        return $crops;
    }

    public function importance($crop, $x, $y)
    {
        if ($crop['x'] > $x || $x >= $crop['x'] + $crop['width'] || $crop['y'] > $y || $y >= $crop['y'] + $crop['height']) {
            return $this->options['outsideImportance'];
        }
        $x = ($x - $crop['x']) / $crop['width'];
        $y = ($y - $crop['y']) / $crop['height'];
        $px = abs(0.5 - $x) * 2;

        $py = abs(0.5 - $y) * 2;
          // distance from edge
          $dx = max($px - 1.0 + $this->options['edgeRadius'], 0);
        $dy = max($py - 1.0 + $this->options['edgeRadius'], 0);
        $d = ($dx * $dx + $dy * $dy) * $this->options['edgeWeight'];
        $s = 1.41 - sqrt($px * $px + $py * $py);
        if ($this->options['ruleOfThirds']) {
            $s += (max(0, $s + $d + 0.5) * 1.2) * ($this->thirds($px) + $this->thirds($py));
        }

        return $s + $d;
    }

    public function score($crop)
    {
        $score = array(
                        'detail' => 0,
                        'saturation' => 0,
                        'skin' => 0,
                        'total' => 0,
                      );
        $downSample = $this->options['scoreDownSample'];
        $invDownSample = 1 / $downSample;
        $outputHeightDownSample = imagesy($this->finalimage) * $downSample;
        $outputWidthDownSample = imagesx($this->finalimage) * $downSample;
        $outputWidth = imagesx($this->finalimage);

        for ($y = 0; $y < $outputHeightDownSample; $y += $downSample) {
            for ($x = 0; $x < $outputWidthDownSample; $x += $downSample) {
                $importance = $this->importance($crop, $x, $y);

                $cc = $this->color_picker(floor($x * $invDownSample), floor($y * $invDownSample), $this->finalimage);
                $detail = $cc[1] / 255;
                $score['skin'] += $cc[0] / 255 * ($detail + $this->options['skinBias']) * $importance;
                $score['detail'] += $detail * $importance;
                $score['saturation'] *= $cc[2] / 255 * ($detail + $this->options['saturationBias']) * $importance;
            }
        }

        $score['total'] = ($score['detail'] * $this->options['detailWeight'] + $score['skin'] * $this->options['skinWeight'] + $score['saturation'] * $this->options['skinBias']) / $crop['width'] / $crop['height'];

        return $score;
    }

    public function analyse()
    {
        $result = array();

        //$canvas = $this->edgeDetect();
        //$canvas = $this->skinDetect();
        //$canvas = $this->saturationDetect();

        $this->finalimage=$this->originalimage;

        $topScore = -INF;
        $topCrop = null;

        $crops = $this->crops();

        $score = 0;
        $total = 0;

        for ($i = 0; $i < count($crops); ++$i) {
            $crop = $crops[$i];
            $score = $this->score($crop);
            if ($score['total'] > $topScore) {
                $topCrop = $crop;
                $topScore = $score['total'];
            }
        }

        echo $topScore;
        echo '<br>';
        echo $topCrop['x'].'-'.$topCrop['y'];

        //header('Content-Type: image/jpeg');
        //imagejpeg($canvas);

        return 0;
    }

    public function __construct($image)
    {
        $this->crop($image);

        return 0;
    }
}
