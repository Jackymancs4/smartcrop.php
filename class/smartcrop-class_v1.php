<?php

class SmartCrop {

  private $type=false;

  public $originalimage=false;
  public $newimage=false;

  public function update () {
    $this->originalimage=$this->newimage;
    $this->newimage=false;
    
    return true;
  }

  public function load ($address, $type="auto") {
           
    if(file_exists($address)) {
        
      if($type=="auto") {
        $ext=explode(".", $address);
        $type=$ext[count($ext)-1];
      }        
        
      switch ($type) { 
        case 'png':
          $this->originalimage=imagecreatefrompng($address);
        break;
        
        case 'jpg':
          $this->originalimage=imagecreatefromjpeg($address);
        break;
      }
      
      $this->type=$type;
      return true;
    } else {
      return false;
    }  
  }
  
  public function return_image ($type="auto") {
  
    if($type=="auto") {
      $type=$this->type;
    }
    
      switch ($type) { 
        case 'png':
          header('Content-Type: image/png') ;
          if($this->newimage==false) {
            imagepng($this->originalimage);
          } elseif ($this->newimage!=false && $this->originalimage!=false) {
            imagepng($this->newimage);
          }                       
        break;
        case 'jpg':
          header('Content-Type: image/jpeg') ;
          if($this->newimage==false) {
            imagejpeg($this->originalimage);
          } elseif ($this->newimage!=false && $this->originalimage!=false) {
            imagejpeg($this->newimage);
          }  
        break;
      }
      
      if($this->originalimage!=false) {
        imagedestroy($this->originalimage);
      }
      if($this->newimage!=false) {
        imagedestroy($this->newimage);
      }
                  
      return true;
    
  }


  public function cie ($r, $g, $b) {
    return 0.5126*$b+0.7152*$g+0.0722*$r;
  }

  public function cie_array ($cc) {
    return $this->cie($cc[0],$cc[1],$cc[2]);
  }

  public function cie_cc ($x, $y) {
    return $this->cie_array($this->color_picker($x, $y))*4 - $this->cie_array($this->color_picker($x, $y-1)) - $this->cie_array($this->color_picker($x-1, $y)) - $this->cie_array($this->color_picker($x+1, $y)) - $this->cie_array($this->color_picker($x, $y+1));
  }
  
  public function color_picker ($x, $y, $image=false) {
    if($image==false) {
      $image=$this->originalimage;
    }
  
    $color_index = imagecolorat($image, $x, $y);
    $color_tran = imagecolorsforindex($image, $color_index);
    $cc=array($color_tran['red'],$color_tran['green'],$color_tran['blue']);

    return $cc;   
  }
  
  public function skinColor ($r, $g, $b, $skin) {
    $mag = sqrt($r*$r+$g*$g+$b*$b);
    
    if($mag==0) $mag=1;
    
    $rd = $r/$mag - $skin[0];
    $gd = $g/$mag - $skin[1];
    $bd = $b/$mag - $skin[2];
    $d = sqrt($rd*$rd+$gd*$gd+$bd*$bd);
    
    return (1-$d);
  }
  
  public function saturation ($r, $g, $b) {
    $maximun = max(($r/255), ($g/255), ($b/255));
    $minimun = min(($r/255), ($g/255), ($b/255));
    if ($maximun==$minimun) {
      $ret = 0;
  
    } else {
    
      $l=($maximun+$minimun)/2;
      $d=$maximun-$minimun;
      
      if ($l>0.5) {
        $ret= $d/(2-$maximun-$minimun); 
      } else {
        $ret= $d/($maximun+$minimun);
      }
    }
    
    return $ret;
  
  }
  
  public function edgeDetect ($all=true) {
    $imx = imagesx($this->originalimage);
    $imy = imagesy($this->originalimage);
    
    $img = imagecreatetruecolor ($imx, $imy);
    
    $lightness=0;
    
    for($i=1; $i<$imx; $i++) {
      for($j=1; $j<$imy; $j++) {
        if ($j==0 || $j>=($imy-1) || $i==0 || $i>=($imx-1)) {
          $cc=$this->color_picker($i, $j);       
          $lightness=$this->cie_array($cc); 
        } else {
          $lightness=$this->cie_cc($i, $j);
        }
        if ($all==true) {
          $pixel=imagecolorallocate($img, $lightness, $lightness, $lightness);    
        } else {
          $cc=$this->color_picker($i, $j);
          $pixel=imagecolorallocate($img, $cc[0], $lightness, $cc[2]);    
        }
        imagesetpixel($img, $i, $j, $pixel);
      }
    }
    
    $this->newimage=$img;
    return $img;
  }

  public function skinDetect ($all=true) {
    $imx = imagesx($this->originalimage);
    $imy = imagesy($this->originalimage);
    
    $img = imagecreatetruecolor ($imx, $imy);
    
    $lightness=0;
    $skin=array(0.78, 0.57, 0.44);
    $skinThreshold = 0.8;
    $skinBrightnessMin=0.2;
    $skinBrightnessMax=1;
    
    for($i=1; $i<$imx; $i++) {
      for($j=1; $j<$imy; $j++) {
        $cc=$this->color_picker($i, $j);       
        $lightness=$this->cie_array($cc)/255; 
        $skind = $this->skinColor($cc[0],$cc[1],$cc[2],$skin);
        if ($skind>$skinThreshold && $lightness>=$skinBrightnessMin && $lightness<=$skinBrightnessMax) {
          $color=($skind-$skinThreshold)*(255/(1-$skinThreshold));
          
          if($all==true) {
            $pixel=imagecolorallocate($img, $color, $color, $color);
          } else {
            $cc=$this->color_picker($i, $j);
            $pixel=imagecolorallocate($img, $color, $cc[1], $cc[2]);
          }
        } else {
          if($all==true) {
            $pixel=imagecolorallocate($img, 0, 0, 0);
          } else {
            $cc=$this->color_picker($i, $j);
            $pixel=imagecolorallocate($img, 0, $cc[1], $cc[2]);
          }
        }
        imagesetpixel($img, $i, $j, $pixel);
      }
    }
    
    $this->newimage=$img;
    return $img;
  }

  public function saturationDetect ($all=true) {
    $imx = imagesx($this->originalimage);
    $imy = imagesy($this->originalimage);
    
    $img = imagecreatetruecolor ($imx, $imy);
    
    $lightness=0;
    $saturationThreshold=0.4;
    $saturationBrightnessMin=0.05;
    $saturationBrightnessMax=0.9;
    
    for($i=1; $i<$imx; $i++) {
      for($j=1; $j<$imy; $j++) {
        $cc=$this->color_picker($i, $j);       
        $lightness=$this->cie_array($cc)/255;
        
        $sat=$this->saturation($cc[0],$cc[1],$cc[2]);
    
        if ($sat>$saturationThreshold && $lightness>=$saturationBrightnessMin && $lightness<=$saturationBrightnessMax) {
          $color=($sat-$saturationThreshold)*(255/(1-$saturationThreshold));
          if($all==true) {
            $pixel=imagecolorallocate($img, $color, $color, $color);
          } else {
            $cc=$this->color_picker($i, $j);
            $pixel=imagecolorallocate($img, $cc[0], $cc[1], $color);
          }
        } else {
          if($all==true) {
            $pixel=imagecolorallocate($img, 0, 0, 0);
          } else {
            $cc=$this->color_picker($i, $j);
            $pixel=imagecolorallocate($img, $cc[0], $cc[1], 0);
          }    
        }
        
        imagesetpixel($img, $i, $j, $pixel);
      }
    } 
        
    $this->newimage=$img;
    return $img;
  }
  
  public function importance ($crop, $x, $y) {
    $edgeRadius=0.4;
    $edgeWeight=-20;
    $outsideImportance=-0.5;
    $ruleOfThirds=true;
    
    if ($crop[0]>$x || $x>=($crop[0]+$crop[2]) || $crop[1] > $y || $y >= ($crop[1]+$crop[3])) {
      return $outsideImportance;
    }
    
    $x=($x-$crop[0])/$crop[2];
    $y=($y-$crop[1])/$crop[3];
    
    $px =abs(0.5-$x)*2;
    $py =abs(0.5-$y)*2;
    
    $dx=max(($px-1+$edgeRadius), 0);
    $dy=max(($py-1+$edgeRadius), 0);
    
    $s=1.41-sqrt($px*$px+$py+$py);
    if($ruleOfThirds==true) {
      $s+=(max(0,$s+$d+0.5)*1.2)*($this->thirds($px)+$this->thirds($py));
    }
    return $s+$d;
  }

  public function thirds($x) {
    $x=(($x-(1/3)+1.0)%2.0*0.5-0.5)*16;
    return max(1.0-$x*$x, 0);
  }
  
  public function crops () {
    
  }
  
  public function score ($crop) {
    $skinBias=0;
    $saturationBias=0;
    $detailWeight=0;
    $skinWeight=0;
    $saturationWeight=0;
  
    $imx = imagesx($this->originalimage);
    $imy = imagesy($this->originalimage);
    
    $sskin=0;
    $sdetail=0;
    $ssaturation=0;
    $total=0;
    
    for($i=1; $i<$imx; $i++) {
      for($j=1; $j<$imy; $j++) {
        $cc=$this->color_picker($i, $j);       

        $importance=$this->importance($crop, $i, $j);
        $detail=$cc[1]/255;
        $sskin+=$cc[0]/255*($detail+$skinBias)*$importance;
        $sdetail+=$detail*$importance;
        $ssaturation+=$cc[2]/255*($detail+$saturationBias)*$importance;
      }
    } 
    
    $total = ($sdetail*$detailWeight+$sskin*$skinWeight+$ssaturation*$saturationWeight)/$crop[2]/$crop[3];    
    return $total;
  }
  

}

?>