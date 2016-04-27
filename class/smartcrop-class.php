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
                          'cropWidth' => 0,
                          'cropHeight' => 0,
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

                          'scoreDownSample' => 8,
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
        if ($this->options['aspect']) {
            $this->options['width'] = $this->options['aspect'];
            $this->options['height'] = 1;
        }

        $this->originalimage = imagecreatefromjpeg($image);
        $this->image=$this->originalimage;

        $scale=1;
        $prescale=1;

        if ($this->options['width'] && $this->options['height']) {
            $scale = min(imagesx($this->image) / $this->options['width'], imagesy($this->image)/ $this->options['height']);
            $this->options['cropWidth'] = floor($this->options['width'] * $scale);
            $this->options['cropHeight']= floor($this->options['height'] * $scale);
            $this->options['minScale'] = min($this->options['maxScale'], max(1 / $scale, $this->options['minScale']));

            if ($this->options['prescale'] !== false) {
              $prescale = 1 / $scale / $this->options['minScale'];
                if ($prescale < 1) {
                    $prescaledCanvas = imagecreatetruecolor(imagesx($this->image) * $prescale, imagesy($this->image) * $prescale);

                    $this->options['cropWidth'] = floor($this->options['cropWidth'] * $prescale);
                    $this->options['cropHeight']= floor($this->options['cropHeight'] * $prescale);
                } else {
                    $prescale = 1;
                }
            }
        }

      //$this->image = $image;
      return 0;
    }

    public function __construct($image)
    {
        $this->crop($image);

        return 0;
    }
}
