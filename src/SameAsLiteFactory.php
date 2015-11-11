<?php

/**
 * SameAs Lite Web Application
 *
 * Factory for creating Stores and WebApps from a config file
 *
 * @package   SameAsLite
 * @author    Seme4 Ltd <sameAs@seme4.com>
 * @copyright 2009 - 2014 Seme4 Ltd
 * @link      http://www.seme4.com
 * @version   0.0.1
 * @license   MIT Public License
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * es
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

namespace SameAsLite;

/**
 * Class to create Stores and WebApps using PHP ini files for configuration
 */
class SameAsLiteFactory
{

    /** @var string[] $storeTypes Array of availble store types */
    private static $storeTypes;

    const INTERFACE_NAME = '\SameAsLite\StoreInterface';

    const STORE_NAMESPACE = 'SameAsLite\Store\\';


    /**
     * Private constructor so objects cannot be created
     */
    private function __construct()
    {
    }

    /**
     * Construct a sameAs Lite Stores from a php ini file
     *
     * @param string $file String to the location of the ini file
     *
     * @return \SameAsLite\StoreInterface[] Array of Stores
     * @throws \InvalidArgumentException When the options are invalid
     */
    public static function createStores($file)
    {
        return self::createStoresFromArray(parse_ini_file($file, true));
    }

    /**
     * Construct the sameAs Lite stores from the given array of options
     * Will also register all defaults given in the config under the [defaults] heading
     *
     * @param array $config Array of config for the Store
     *
     * @return \SameAsLite\StoreInterface[] Array of Stores
     * @throws \InvalidArgumentException When the options are invalid
     */
    public static function createStoresFromArray(array $config)
    {
        // Check for defaults
        if (isset($config['defaults'])) {
            foreach ($config['defaults'] as $storeType => $defs) {
                if (strpos($storeType, '\\') === false) {
                    // There is no namespace, use the default one
                    $class = self::STORE_NAMESPACE . $storeType;
                } else {
                    $class = $storeType;
                }
                $class::setDefaultOptions($defs);
            }
        }
        unset($config['defaults']);


        $stores = [];

        foreach ($config as $name => $options) {
            $type = $options['type'];
            unset($options['type']);

            if (strpos($type, '\\') === false) {
                $class = self::STORE_NAMESPACE . $type;
            } else {
                $class = $type;
            }

            // Whitelist to get the options that the store takes
            $o = array_intersect_key($options, array_flip($class::getAvailableOptions()));

            // Create the store
            $store = new $class($name, $o);
            $stores[] = $store;
        }

        return $stores;
    }



    /**
     * Construct a WebApp from a php ini file
     *
     * @param string $file The location of the ini file
     *
     * @return \SameAsLite\WebApp The created WebApp
     * @throws \InvalidArgumentException When the options are invalid
     */
    public static function createWebApp($file)
    {
        return self::createWebAppFromArray(parse_ini_file($file, true));
    }

    /**
     * Construct a sameAs Lite WebApp from the given array of options
     * Does NOT run the WebApp
     *
     * @param array $config Array of config for WebApp and associcated Stores
     *
     * @return \SameAsLite\WebApp The created WebApp
     * @throws \InvalidArgumentException When the options are invalid
     */
    public static function createWebAppFromArray(array $config)
    {

        /*
        //has no effect here

        //error reporting - need to have this as early as possible
        $mode = (isset($config['mode']) ? $config['mode'] : 'production');
        if ($mode === 'development') {
            // Dev error reporting
            ini_set('display_errors', 1);
            // ini_set('html_errors', 0); // disable xdebug var-dump formatting
            ini_set('display_startup_errors', 1);
            error_reporting(-1); // show all errors for development
        } else {
            error_reporting(0);
        }
        */

        if (!isset($config['webapp'])) {
            throw new \InvalidArgumentException('No WebApp Config found, must be under category "[webapp]"' .
                ' in config.ini');
        }

        $appcfg = $config['webapp'];
        unset($config['webapp']);

        // Pass the object on to create the store
        $stores = self::createStoresFromArray($config);

        $app = new \SameAsLite\WebApp($appcfg);

        foreach ($stores as $store) {
            $name = $store->getStoreName();
            $o = array_diff_key($config[$name], array_flip($store::getAvailableOptions()));

            $app->addDataset($store, $o);
        }

        return $app;
    }
}
