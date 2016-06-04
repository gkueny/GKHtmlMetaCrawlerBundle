<?php

namespace GK\HtmlMetaCrawlerBundle\Services;

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

        // set context
        $context = stream_context_create(array(
                'http' => array(
                    'follow_location' => $followRedirect
                )
            ));


        //get page by url
        $page = @file_get_contents($url, false, $context);

        if (!$page) {
            return false;
        }

        //create simplexml object
        $doc = new \DOMDocument();
        $doc->loadHTML($page);
        $xml = simplexml_import_dom($doc); // just to make xpath more simple

        libxml_use_internal_errors(false);

        return $xml;
    }
}
