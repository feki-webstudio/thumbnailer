<?php

namespace FekiWebstudio\Thumbnailer;

use Request;

class ThumbnailManager
{
    /**
     * Gets the generated image element.
     *
     * @param string $file
     * @param array $params
     * @return string
     */
    public function image($file, $params)
    {
        $params['file'] = $file;
        return $this->generateThumbnail($params);
    }

    /**
     * Gets the url to the generated image.
     *
     * @param string $file
     * @param array $params
     * @return string
     */
    public function url($file, $params)
    {
        $params['file'] = $file;
        $params['onlylink'] = true;
        return $this->generateThumbnail($params);
    }

    /**
     * Generates a thumbnail.
     *
     * @param array $params
     * @return string
     */
    private function generateThumbnail($params)
    {
        $outputPath = config('thumbnailer.output_path');

        $t = new Thumb();
        $t->source = preg_replace("/^\//", "", $params['file']);

        if (isset($params['width'])) {
            $t->width = $params['width'];
        }

        if (isset($params['height'])) {
            $t->height = $params['height'];
        }

        if (isset($params['crop'])) {
            $t->crop = $params['crop'];
        }

        if (isset($params['forceSize'])) {
            //ide majd be kell építeni hogy kis képből is vágjon, hogy arányos legyen
        }

        if (isset($params['quality'])) {
            $t->quality = (int)$params['quality'];
        }

        if (isset($params['resizeRatio'])) {
            $t->resizeRatio = $params['resizeRatio'];
        }

        if (isset($params['cropPos'])) {
            $t->cropPos = $params['cropPos'];
        }

        if (! isset($params['wPos']) || $params['wPos'] == '') {
            $params['wPos'] = 'cm';
        }

        if (isset($params['watermark']) && $params['watermark'] !== "") {
            $arg['img'] = $params['watermark'];
            $arg['position'] = $params['wPos'];
            $t->addWaterMark($arg);
        }

        if (isset($params['colorize']) && $params['colorize'] !== "") {
            $t->colorize = $params['colorize'];
        }

        $t->source = $t->copyWebImages($t->source);
        preg_match('/[^.]+$/', $params['file'], $math);

        $f = explode('/', $t->source);
        $fn = array_pop($f);
        $prefilename = str_replace('.' . $math[0], '', $fn);

        if (isset($params['seo'])) {
            if ($params['seo'] == '' && isset($params['title']) && $params['title'] != "") {
                $prefilename = str_slug($params['title']);
            }

            if ($params['seo'] != '') {
                $prefilename = str_slug($params['seo']);
            }
        }

        $t->dest = $origdest = $outputPath . "/" . $prefilename . '-' . substr(md5(implode('-',
                $params)), 0, 4) . '-' . filemtime($t->source) . '.' . $math[0];

        if (! is_file($t->dest)) {
            $t->resize();
        }

        if (isset($params['x']) && $params['x'] !== "") {
            $params['srcset'] = "";
            $x = explode(',', $params['x']);
            foreach ($x as $m) {
                if ((int)$m > 0) {
                    if ((int)$m > 1) {
                        $t->width = (int)$params['width'] * (int)$m;
                        $t->height = (int)$params['height'] * (int)$m;
                        $d = str_replace('.' . $math[0], '', $origdest);
                        $t->dest = $d . '-' . $m . 'x.' . $math[0];
                        if (! is_file($t->dest)) {
                            $t->resize();
                        }
                    }
                    $params['srcset'] .= '/' . $t->dest . ' ' . $t->width . 'w ' . $m . 'x, ';
                }
            }

            if ($params['srcset'] !== "") {
                $params['srcset'] = substr($params['srcset'], 0, -2);
            }
            $t->dest = $origdest;
            $t->width = $params['width'];
            $t->height = $params['height'];
        }

        return $this->createImg($t, $params);
    }

    /**
     * Creates the image.
     *
     * @param Thumb $t
     * @param array $params
     * @return string
     */
    private function createImg($t, $params)
    {
        $this->setThumbnailDestination($t, $params);

        if (isset($params['onlylink']) && $params['onlylink'] === true) {
            return $t->dest;
        }

        if (! isset($params['title'])) {
            $params['title'] = "";
        }

        if (isset($params['lazy'])) {
            $img = '<img src="' . $params['lazy'] . '" data-original="' . $t->dest . '"';
        } else {
            $img = '<img src="' . $t->dest . '"';
        }

        if (isset($params['title'])) {
            $img .= ' alt="' . htmlspecialchars($params['title'], ENT_QUOTES,
                    'UTF-8') . '" title="' . htmlspecialchars($params['title'],
                    ENT_QUOTES, 'UTF-8') . '"';
        }

        if (isset($params['forceSize'])) {
            $img .= ' style="width:' . $params['width'] . 'px;height:' . $params['height'] . 'px;"';
        }
        if (isset($params['html'])) {
            $img .= ' ' . $params['html'];
        }

        $img .= ' />';

        if (isset($params['lazy'])) {
            $img .= '<noscript><img';
            if (isset($params['title'])) {
                $img .= ' alt="' . htmlspecialchars($params['title'], ENT_QUOTES,
                        'UTF-8') . '" title="' . htmlspecialchars($params['title'],
                        ENT_QUOTES, 'UTF-8') . '"';
            }

            $img .= ' src="' . $t->dest . '"/></noscript>';
        }

        return $img;
    }

    /**
     * Sets the destination filename of the thumbnail image.
     *
     * @param string $t
     * @param array $params
     */
    private function setThumbnailDestination($t, $params)
    {
        $cacheDomain = config('thumbnailer.subdomain');

        if ($cacheDomain && ! isset($params['nosubdomain'])) {
            // Get the protocol
            $protocol = Request::secure() ? 'https' : 'http';

            // Get the current domain
            $domainParts = explode('.', Request::getHost());
            $domain = implode('.', array_slice($domainParts, -2));
            $t->dest = sprintf('%s://%s.%s/%s', $protocol, $cacheDomain, $domain, $t->dest);
        } else {
            $t->dest = '/' . $t->dest;
        }
    }
}
