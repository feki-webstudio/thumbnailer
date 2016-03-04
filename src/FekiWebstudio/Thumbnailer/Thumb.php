<?php

namespace FekiWebstudio\Thumbnailer;

/*
$t = new thumb();
$t->source = 'kep.jpg';
$t->dest = 'kep2.jpg';
$t->height = 800;
$t->width = 200;
//$t->height = 100;
$t->crop = true;
//$t->face = true;
//$t->addWaterMark(array('img'=>'icon-pdf.png','position'=>'rb'));
//$t->addWaterMark(array('img'=>'icon-pdf.png','position'=>'lb'));
//$t->addWaterMark(array('img'=>'icon-pdf.png','position'=>'l'));
$t->resize();
*/
class Thumb
{
    /**
     * The source file.
     *
     * @var string
     */
    public $source;

    /**
     * The destination file.
     *
     * @var string
     */
    public $dest;

    /**
     * Target width.
     *
     * @var int
     */
    public $width = 0;

    /**
     * Target height.
     *
     * @var int
     */
    public $height = 0;

    /**
     * Value indicating whether the image should be cropped.
     *
     * @var bool
     */
    public $crop = false;

    /**
     * The resize ration. False means the ratio of the image.
     *
     * @var float
     */
    public $resizeRatio = 1.33333333;

    /**
     * Image quality.
     *
     * @var int
     */
    public $quality = 90;

    /**
     * The crop position.
     *
     * Characters:
     *  - c: center
     *  - m: middle
     *  - b: bottom
     *  - r: right
     *
     * @var string
     */
    public $cropPos = "cm";

    /**
     * Watermarks to add.
     *
     * Használható karakterek: c - Center (vízszintesen középre); r - Right (jobbra); m - Middle (függőleges közép); b - Bottom (lent). (Sorrend, kis és nagybetű nem számít).
     * array('img'=>'path.png','position'=>'cm')
     *
     * @var array
     */
    public $watermark = array();

    public $colorize;

    public $_oWidth;

    public $_dWidth;

    public $_oHeight;

    public $_dHeight;

    public $_source;

    public $_newImg;

    public $_ratio;

    public $_wRatio;

    public $_hRatio;

    public $_type;

    /**
     * Copies web images.
     *
     * @param string $source
     * @param string $dest
     * @return string
     */
    function copyWebImages($source, $dest = "")
    {
        if (preg_match("/^http:\/\//", $source)) {
            if ($dest == "") {
                preg_match("/\.([^\.]{1,4}+)$/", $source, $res);
                if (isset($res[1])) {
                    $dest = CACHE_DIR . "/" . md5($source) . $res[0];
                } else {
                    $info = getimagesize($source);
                    $type = $info[2];
                    switch ($type) {
                        case IMAGETYPE_GIF:
                            $ft = '.gif';
                            break;
                        case IMAGETYPE_JPEG:
                            $ft = '.jpg';
                            break;
                        case IMAGETYPE_PNG:
                            $ft = '.png';
                            break;
                        default:
                            $ft = '.jpg';
                            break;
                    }
                    $dest = CACHE_DIR . "/copy-" . md5($source) . $ft;
                }
            }
            if (! is_file($dest)) {
//				$f = file_get_contents($source, "r");
//				file_put_contents($dest,$f);
                copy($source, $dest);
            }
            return $dest;
        }
        return $source;
    }

    function addWaterMark($arg)
    {
        $this->watermark[] = $arg;
    }

    function resize()
    {
        if ($this->dest == "") {
            return false;
        }
        
        if (is_file($this->source)) {
            if ($this->_source = $this->loadImage($this->source)) {
                // Forrás fájl mérete
                $this->_oWidth = imagesx($this->_source);
                $this->_oHeight = imagesy($this->_source);

                // Cél fájl méretének kiszámítása
                $this->setSize();

                // Új kép létrehozása
                $this->createEmptyImg();
                if ($this->crop) {

                    // Fekvő
                    if ($this->_oWidth / ($this->_dWidth / $this->_dHeight) < $this->_oHeight) {
                        $srcH = $this->_oWidth / ($this->_dWidth / $this->_dHeight);
                        $srcW = $this->_oWidth;
                        $srcPos = $this->getCropPos($srcW, $srcH);
                    } else {
                        $srcH = $this->_oHeight;
                        $srcW = ($this->_dWidth / $this->_dHeight) * $this->_oHeight;
                        $srcPos = $this->getCropPos($srcW, $srcH);
                    }

                    imagecopyresampled($this->_newImg, $this->_source, 0, 0, $srcPos["x"], $srcPos["y"], $this->_dWidth,
                        $this->_dHeight, $srcW, $srcH);
                } else {
                    imagecopyresampled($this->_newImg, $this->_source, 0, 0, 0, 0, $this->_dWidth, $this->_dHeight,
                        $this->_oWidth, $this->_oHeight);
                }

                // Vízjel ráhelyezés
                if (is_array($this->watermark) && ! empty($this->watermark)) {
                    foreach ($this->watermark as $array) {
                        if (isset($array['img']) && $this->isImage($array['img']) && isset($array['position'])) {
                            $img = $this->loadImage($array['img']);
                            $this->copyWatermark($this->_newImg, $img, $array['position']);
                        }
                    }
                }

                // Színezés
                if (! empty($this->colorize)) {
                    if ($this->colorize == "grayscale") {

                        imagefilter($this->_newImg, IMG_FILTER_GRAYSCALE);

                    } elseif ($this->colorize == 'sepia') {

                        imagefilter($this->_newImg, IMG_FILTER_GRAYSCALE);
                        imagefilter($this->_newImg, IMG_FILTER_COLORIZE, 100, 50, 0);

                    } elseif (substr($this->colorize, 0, 1) == '#') {

                        $rgb = $this->HexToRGB($this->colorize);
                        //imagefilter($this->_newImg, IMG_FILTER_MEAN_REMOVAL);
                        //imagefilter($this->_newImg, IMG_FILTER_GRAYSCALE);
                        //imagefilter($this->_newImg, IMG_FILTER_CONTRAST, 255);
                        //imagefilter($this->_newImg, IMG_FILTER_BRIGHTNESS, -100);
                        //imagefilter($this->_newImg, IMG_FILTER_CONTRAST, -50);
                        imagefilter($this->_newImg, IMG_FILTER_COLORIZE, $rgb['r'], $rgb['g'], $rgb['b']);

                    }
                }

                // Mentés
                $this->createImg();

                // Tisztítás
                imagedestroy($this->_newImg);
            } else {
                // Csak png, gif, jpg a támogatott
            }
        } else {
            trigger_error("Nem létező fájl", E_USER_WARNING);
        }
    }

    function loadImage($path)
    {
        $info = getimagesize($path);
        $this->_type = $info[2];
        switch ($this->_type) {
            case IMAGETYPE_GIF:
                return imagecreatefromgif($path);
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($path);
            default:
                return false;
        }
    }

    function setSize()
    {
        // Ha nincs megadva mindegyik paraméter akkor megadjuk a méreteket
        if ($this->resizeRatio === false) {
            $this->resizeRatio = $this->_oWidth / $this->_oHeight;
        }
        if ($this->width == 0 && $this->height > 0) {
            $this->width = $this->height * $this->resizeRatio;
        }
        if ($this->height == 0 && $this->width > 0) {
            $this->height = $this->width / $this->resizeRatio;
        }
        if ($this->width > $this->_oWidth && $this->height > $this->_oHeight) {
            $this->_dWidth = $this->_oWidth;
            $this->_dHeight = $this->_oHeight;
        } else {
            // Ha vágjuk
            if ($this->crop) {
                $this->_dWidth = min($this->width, $this->_oWidth);
                $this->_dHeight = min($this->height, $this->_oHeight);
            } else {
                // Álló
                if ($this->_oWidth / $this->_oHeight * $this->height < $this->width) {
                    $this->_dWidth = $this->_oWidth / $this->_oHeight * $this->height;
                    $this->_dHeight = $this->height;
                } else {
                    $this->_dWidth = $this->width;
                    $this->_dHeight = $this->_oHeight / $this->_oWidth * $this->width;
                }
            }
        }
        // Meghatározzuk a régi és az új kép arányát
        //$this->_ratio = min($this->_oWidth / $this->width, $this->_oHeight / $this->height);
    }

    function createEmptyImg()
    {
        $this->_newImg = imagecreatetruecolor($this->_dWidth, $this->_dHeight);
        if (($this->_type == IMAGETYPE_GIF) || ($this->_type == IMAGETYPE_PNG)) {
            $trnprt_indx = imagecolortransparent($this->_source);
            if ($trnprt_indx >= 0) {
                $trnprt_color = @imagecolorsforindex($this->_source, $trnprt_indx);
                $trnprt_indx = imagecolorallocate($this->_newImg, $trnprt_color['red'], $trnprt_color['green'],
                    $trnprt_color['blue']);
                imagefill($this->_newImg, 0, 0, $trnprt_indx);
                imagecolortransparent($this->_newImg, $trnprt_indx);
            } elseif ($this->_type == IMAGETYPE_PNG) {
                imagealphablending($this->_newImg, false);
                $color = imagecolorallocatealpha($this->_newImg, 0, 0, 0, 127);
                imagefill($this->_newImg, 0, 0, $color);
                imagesavealpha($this->_newImg, true);
            }
        }
    }

    function getCropPos($width, $height)
    {
        // Függőleges igazítás
        $focusArray = explode(';', $this->cropPos);
        if (! isset($focusArray[1])) {
            unset($focusArray);
        }
        $posArray = array("x" => 0, "y" => 0);
        if ($this->_oWidth == $width) {
            // Vertikálisan középre
            if (isset($focusArray)) {
                $posArray["y"] = max(0, ((min(($this->_oHeight - ($height / 2)),
                        ($this->_oHeight / 2 * (($focusArray[1] < 0) ? (abs($focusArray[1]) + 1) : (1 - $focusArray[1]))))) - ($height / 2)));
                return $posArray;
            }
            if (preg_match("/[mM]/", $this->cropPos)) {
                $posArray["y"] = max(0, ($this->_oHeight - $height) / 2);
                return $posArray;
            }
            if (preg_match("/[bB]/", $this->cropPos)) {
                $posArray["y"] = max(0, $this->_oHeight - $height);
                return $posArray;
            }
        } else {
            if (isset($focusArray)) {
                $posArray["x"] = max(0, ((min(($this->_oWidth - ($width / 2)),
                        ($this->_oWidth / 2 * (($focusArray[0] < 0) ? (1 + $focusArray[0]) : ($focusArray[0] + 1))))) - ($width / 2)));
                return $posArray;
            }
            if (preg_match("/[cC]/", $this->cropPos)) {
                $posArray["x"] = max(0, ($this->_oWidth - $width) / 2);
                return $posArray;
            }
            if (preg_match("/[rR]/", $this->cropPos)) {
                $posArray["x"] = max(0, $this->_oWidth - $width);
                return $posArray;
            }
        }
        return $posArray;
    }

    function isImage($pathname)
    {
        // File lézetik?
        if (! is_file($pathname)) {
            return false;
        }
        // Megfelelő formátum?
        $info = getimagesize($pathname);
        if (! in_array($info[2], array(1, 2, 3))) {
            return false;
        }
        return true;
    }

    function copyWatermark($dest, $src, $pos)
    {
        // Alap koordináták
        $destx = 0;
        $desty = 0;

        // Méretek
        $srcw = imagesx($src);
        $srch = imagesy($src);
        $destw = imagesx($dest);
        $desth = imagesy($dest);

        // Horizontális (vízsintes) igazítás
        if (preg_match("/[cC]/", $pos)) {
            // Középre
            $destx = (int)(($destw - $srcw) / 2);
        } else {
            if (preg_match("/[rR]/", $pos)) {
                // Jobbra
                $destx = $destw - $srcw;
            }
        }
        // Vertikális (függőleges) igazítás
        if (preg_match("/[mM]/", $pos)) {
            // Középre
            $desty = (int)(($desth - $srch) / 2);
        } else {
            if (preg_match("/[bB]/", $pos)) {
                // Jobbra
                $desty = $desth - $srch;
            }
        }
        // Másolás a célkoordinátákra
        imagecopy($dest, $src, $destx, $desty, 0, 0, $srcw, $srch);
    }

    function HexToRGB($hex)
    {
        $hex = str_replace("#", "", $hex);
        $color = array();

        if (strlen($hex) == 3) {
            $color['r'] = hexdec(substr($hex, 0, 1) . $r);
            $color['g'] = hexdec(substr($hex, 1, 1) . $g);
            $color['b'] = hexdec(substr($hex, 2, 1) . $b);
        } else {
            if (strlen($hex) == 6) {
                $color['r'] = hexdec(substr($hex, 0, 2));
                $color['g'] = hexdec(substr($hex, 2, 2));
                $color['b'] = hexdec(substr($hex, 4, 2));
            }
        }

        return $color;
    }

    function createImg()
    {
        $info = getimagesize($this->source);

        $dest = public_path($this->dest);

        switch ($info[2]) {
            case 1:
                imagegif($this->_newImg, $this->dest, $this->quality);
                break;
            case 2:
                if (extension_loaded('imagick') || class_exists("Imagick")) {
                    ob_start();                   // starts output buffering
                    imagejpeg($this->_newImg);      // writes image to that buffer
                    $blob = ob_get_clean();       // gets buffer as a string and clean it
                    $img = new Imagick();
                    $img->readImageBlob($blob);
                    //$img->setImageCompression(imagick::COMPRESSION_LOSSLESSJPEG);
                    $img->setImageCompression(imagick::COMPRESSION_JPEG);
                    $img->setImageCompressionQuality($this->quality);
                    $img->stripImage();
                    $img->writeImage($dest);
                } else {
                    imagejpeg($this->_newImg, $dest, $this->quality);
                }
                break;
            case 3:
                if (extension_loaded('imagick') || class_exists("Imagick")) {
                    ob_start();                   // starts output buffering
                    imagepng($this->_newImg);      // writes image to that buffer
                    $blob = ob_get_clean();       // gets buffer as a string and clean it
                    $img = new Imagick();
                    $img->readImageBlob($blob);
                    $img->setImageCompression(imagick::COMPRESSION_ZIP);
                    $img->setImageCompressionQuality($this->quality);
                    $img->stripImage();
                    $img->writeImage($dest);
                } else {
                    imagepng($this->_newImg, $dest);
                }

                break;
            default:
                return false;
        }
    }

    function rotate($degrees)
    {
        if ($this->_source = $this->loadImage($this->source)) {
            $this->_newImg = imagerotate($this->_source, $degrees, 0);
            $this->createImg();
            // Tisztítás
            imagedestroy($this->_newImg);
        }
    }
}