[![SensioLabsInsight](https://insight.sensiolabs.com/projects/131bf750-6742-4baf-b5bb-1b4ba74e0622/big.png)](https://insight.sensiolabs.com/projects/131bf750-6742-4baf-b5bb-1b4ba74e0622)

Installation
============

Step 1: Download the Bundle
---------------------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require gkueny/html-meta-crawler-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Step 2: Enable the Bundle
-------------------------

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...

            new GK\HtmlMetaCrawlerBundle\GKHtmlMetaCrawlerBundle(),
        );

        // ...
    }

    // ...
}
```

Controller Example
-------------------------

```php

<?php

namespace GK\HtmlCrawlerBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DefaultController
 *
 * @package GK\HtmlCrawlerBundle\Controller
 */
class DefaultController extends Controller
{
    /**
     * @Route("/", name="crawler_homepage")
     *
     * @return Response
     */
    public function indexAction()
    {

        $url = 'http://gkueny.fr';

        $myMetasFb = $this->get("gk.html_meta_crawler")->getMetaFacebook($url);

        $myMetasTw = $this->get("gk.html_meta_crawler")->getMetaTwitter($url);

        $myMetasBasic = $this->get("gk.html_meta_crawler")->getBasicMeta($url);

        $myMetasAll = $this->get("gk.html_meta_crawler")->getAllMeta($url);

        if(!$myMetasFb || !$myMetasTw || !$myMetasBasic || !$myMetasAll ) {
            echo "error";
            exit;
        }

        echo "<p> Facebook : <br/>";

        foreach ($myMetasFb as $myMeta ) {
            echo $myMeta['property'] . " = " . $myMeta['content'] . '<br/>';
        }

        echo "</p>";


        echo "<p> Twitter : <br/>";

        foreach ($myMetasTw as $myMeta ) {
            echo $myMeta['name'] . " = " . $myMeta['content'] . '<br/>';
        }

        echo "</p>";


        echo "<p> Basic : <br/>";

        foreach ($myMetasBasic as $myMeta ) {
            print_r($myMeta);
            echo "<br/>";
        }

        echo "</p>";

        echo "<p> All : <br/>";

        foreach ($myMetasAll as $myMeta ) {
            print_r($myMeta);
            echo "<br/>";
        }

        echo "</p>";

        exit;

    }
}
```
