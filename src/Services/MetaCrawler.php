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
     * @param $url
     *
     * @return \SimpleXMLElement
     * @throws \Exception
     */
    private function getDocument($url, $followRedirect)
    {

        //disable html5 error
        libxml_use_internal_errors(true);

        $webPage = $this->getWebPage($url, $followRedirect);

        $page = $webPage["content"] ;

        if (!$page) {
            throw new Exception( $webPage['errno'] . " : " . $webPage['errmsg']);
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
    private function getWebPage( $url , $followRedirect)
    {
        $user_agent='Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

        $options = array(
            CURLOPT_CUSTOMREQUEST  =>"GET",             //set request type post or get
            CURLOPT_POST           =>false,             //set to GET
            CURLOPT_USERAGENT      => $user_agent,      //set user agent
            CURLOPT_COOKIEFILE     =>"cookie.txt",      //set cookie file
            CURLOPT_COOKIEJAR      =>"cookie.txt",      //set cookie jar
            CURLOPT_RETURNTRANSFER => true,             // return web page
            CURLOPT_HEADER         => false,            // don't return headers
            CURLOPT_FOLLOWLOCATION => $followRedirect,  // follow redirects
            CURLOPT_ENCODING       => "",               // handle all encodings
            CURLOPT_AUTOREFERER    => true,             // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,              // timeout on connect
            CURLOPT_TIMEOUT        => 120,              // timeout on response
            CURLOPT_MAXREDIRS      => 10,               // stop after 10 redirects
        );

        $ch      = curl_init( $url );
        curl_setopt_array( $ch, $options );
        $content = curl_exec( $ch );
        $err     = curl_errno( $ch );
        $errmsg  = curl_error( $ch );
        $header  = curl_getinfo( $ch );
        curl_close( $ch );

        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
        $header['content'] = $content;

        return $header;
    }
}
