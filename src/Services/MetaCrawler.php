<?php

namespace GK\HtmlMetaCrawlerBundle\Services;

use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Class MetaCrawler
 *
 * @package GK\HtmlCrawlerBundle\Services
 */
class MetaCrawler
{

    /**
     * get all meta
     *
     * @param      $url
     * @param bool $followRedirect
     *
     * @return array|bool|string
     */
    public function getAllMeta($url, $followRedirect = false)
    {


        $response = $this->getBasicMeta($url, $followRedirect);

        if (!$response) return false;

        $response = array_merge($this->getMetaFacebook($url, $followRedirect), $response);

        if (!$response) return false;

        $response = array_merge($this->getMetaTwitter($url, $followRedirect), $response);

        return $response;
    }

    /**
     * get basic meta without fb and twitter meta
     *
     * @param      $url
     * @param bool $followRedirect
     *
     * @return array|string
     * @throws \Exception
     */
    public function getBasicMeta($url, $followRedirect = false)
    {
        $xml = $this->getDocument($url, $followRedirect);
        if (!$xml) return false;


        $metas = $xml->xpath('//meta');

        $response = array();

        foreach ($metas as $meta) {
            if (strpos($meta['property'], 'og:') === false && strpos($meta['name'], 'twitter:') === false && strpos($meta['property'], 'fb:') === false) {
                $response[] = $meta;
            }
        }

        return $response;
    }

    /**
     * get all facebook meta
     *
     * @param      $url
     * @param bool $followRedirect
     *
     * @return array|string
     * @throws \Exception
     */
    public function getMetaFacebook($url, $followRedirect = false)
    {

        $xml = $this->getDocument($url, $followRedirect);
        if (!$xml) return false;


        $metas = $xml->xpath('//meta');

        $response = array();

        foreach ($metas as $meta) {
            if (strpos($meta['property'], 'og:') !== false || strpos($meta['property'], 'fb:') !== false) {
                $response[] = $meta;
            }
        }

        return $response;

    }

    /**
     * get all twitter meta
     *
     * @param      $url
     * @param bool $followRedirect
     *
     * @return array|string
     * @throws \Exception
     */
    public function getMetaTwitter($url, $followRedirect = false)
    {


        $xml = $this->getDocument($url, $followRedirect);
        if (!$xml) return false;


        $metas = $xml->xpath('//meta');

        $response = array();

        foreach ($metas as $meta) {
            if (strpos($meta['name'], 'twitter:') !== false) {
                $response[] = $meta;
            }
        }

        return $response;

    }

    /**
     * get meta url, type, tile, description, image which faceboo get from website
     * return null for one of this meta if crawler find nothing about it
     *
     * @param string $url
     * @param bool   $followRedirect
     *
     * @return array|bool
     */
    public function getOpenGraph($url, $followRedirect = true)
    {
        $xml = $this->getDocument($url, $followRedirect);


        if (!$xml) return false;

        $metas = $xml->xpath('//meta');



        $url_meta          = null;
        $type_meta         = "website";
        $title_meta        = null;
        $description_meta  = null;
        $image_meta        = null;
        $image_width_meta  = null;
        $image_height_meta = null;

        $other_description_meta = null;
        $other_title_meta       = null;

        foreach ($metas as $meta) {

            if (isset($meta['property'])) {

                switch ($meta['property']) {


                    case "og:url":
                        if(!$url_meta)
                            $url_meta = (string)$meta['content'];
                        break;
                    case "og:type":
                        if(!$type_meta)
                            $type_meta = (string)$meta['content'];
                        break;
                    case "og:title":
                        if(!$title_meta)
                            $title_meta = (string)$meta['content'];
                        break;
                    case "og:description":
                        if(!$description_meta)
                            $description_meta = (string)$meta['content'];
                        break;
                    case "og:image":
                        if(!$image_meta)
                            $image_meta = (string)$meta['content'];
                        break;
                    case "og:image:width":
                        if(!$image_width_meta)
                            $image_width_meta = (integer)$meta['content'];
                        break;
                    case "og:image:height":
                        if(!$image_height_meta)
                            $image_height_meta = (integer)$meta['content'];
                        break;
                    default :
                        break;
                }
            } else if (isset($meta['name'])) {
                switch ($meta['name']) {
                    case "description":
                        $other_description_meta = $meta['content'];
                        break;
                    default :
                        break;
                }
            }
        }

        $other_title_meta = $xml->xpath('//title')[0];

        if ($image_meta) {
            if (strpos($image_meta, "http") === false) {
                $image_meta = "http:" . $image_meta;
            }
        }

        if (is_null($url_meta)) {
            $url_meta = $url;
        }
        if (is_null($title_meta)) {

            $title_meta = (string)$other_title_meta[0];
        }
        if (is_null($description_meta)) {
            $description_meta = (string)$other_description_meta[0];
        }


        if (!$image_height_meta && $image_meta) {
            $image_height_meta = getimagesize($image_meta)[1];
        }
        if (!$image_width_meta && $image_meta) {
            $image_width_meta = getimagesize($image_meta)[0];
        }
        if (!$image_height_meta) {
            $image_height_meta = 205;
        }
        if (!$image_width_meta) {
            $image_width_meta = 205;
        }



        $response = [
            "og:url"          => $url_meta,
            "og:type"         => $type_meta,
            "og:title"        => $title_meta,
            "og:description"  => $description_meta,
            "og:image"        => $image_meta,
            "og:image:width"  => $image_width_meta,
            "og:image:height" => $image_height_meta
        ];

        return $response;
    }
    
    /**
     * @param $url
     * @param $followRedirect
     *
     * @return \SimpleXMLElement
     * @throws Exception
     */
    private function getDocument($url, $followRedirect)
    {

        //disable html5 error
        libxml_use_internal_errors(true);

        $webPage = $this->getWebPage($url, $followRedirect);

        $page = $webPage["content"];

        if (!$page) {
            throw new Exception($webPage['errno']." : ".$webPage['errmsg']);
        }

        //create simplexml object
        $doc = new \DOMDocument();

        $doc->loadHTML($page);
        $xml = simplexml_import_dom($doc); // just to make xpath more simple

        libxml_use_internal_errors(false);

        return $xml;
    }

    /**
     * Get a web file (HTML, XHTML, XML, image, etc.) from a URL.  Return an
     * array containing the HTTP server response header fields and content.
     *
     * @param $url
     * @param $followRedirect
     *
     * @return mixed
     */
    private function getWebPage($url, $followRedirect)
    {

        $options = array(
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            // return web page
            CURLOPT_HEADER         => true,
            // don't return headers
            CURLOPT_FOLLOWLOCATION => $followRedirect,
            // follow redirects
            CURLOPT_ENCODING       => "",
            // handle all encodings
            CURLOPT_AUTOREFERER    => true,
            // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,
            // timeout on connect
            CURLOPT_TIMEOUT        => 120,
            // timeout on response
            CURLOPT_MAXREDIRS      => 10,
            // stop after 10 redirects
        );

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response["content"] = curl_exec($ch);
        $httpCode            = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $response['errno']  = $httpCode;
        $response['errmsg'] = null;
        if ($httpCode != 200) {
            $response['errmsg'] = curl_error($ch);
        }

        return $response;
    }
}
