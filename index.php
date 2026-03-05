<?php

namespace staifa\php_bandwidth_hero_proxy\index;

/**
 * @author František Štainer <https://github.com/staifa>
 * @license https://opensource.org/license/mit
 * @package staifa\php_bandwidth_hero_proxy\main
 *
 * Credits to:
 *    https://github.com/ayastreb/bandwidth-hero
 *    https://github.com/ayastreb/bandwidth-hero-proxy
 *
 * Usage:
 *    Just run it.
 *    See docs at config.php for configuration options
 *    and other files for inner working explanations.
 *
 * Compatibility:
 *    PHP >=8.3.11
 *    libcurl
 *    GD
 */

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

include_once("auth.php");
include_once("config.php");
include_once("proxy.php");
include_once("redirect.php");
include_once("router.php");
require_once("validation.php");
require_once("util.php");

use staifa\php_bandwidth_hero_proxy\auth;
use staifa\php_bandwidth_hero_proxy\config;

$config = config\create();

if (!$config["target_url"] && $config["request_uri"] == "/") {
    die("bandwidth-hero-proxy");
}
 else {
    if (is_array($config["target_url"])) {
        $config["target_url"] = join("&url=", $config["target_url"]);
    };

    auth\start($config);
};
