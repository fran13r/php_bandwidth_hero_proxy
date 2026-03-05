<?php

namespace staifa\php_bandwidth_hero_proxy\proxy;

use staifa\php_bandwidth_hero_proxy\validation;

use function staifa\php_bandwidth_hero_proxy\util\doto;
use function staifa\php_bandwidth_hero_proxy\redirect\redirect;

function request_opts($request_headers, &$response_headers, $target_url)
{
    return [
      CURLOPT_HTTPHEADER => $request_headers,
      CURLOPT_URL => $target_url,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_AUTOREFERER => true, // ADDS THE AUTOMATIC REFERER
      CURLOPT_CONNECTTIMEOUT => 5,
      CURLOPT_TIMEOUT => 10,
      CURLOPT_MAXREDIRS => 5,
      CURLOPT_SSL_VERIFYPEER => false, // BYPASSES SSL CHECK
      CURLOPT_SSL_VERIFYHOST => false, // BYPASSES HOST CHECK
      CURLOPT_RETURNTRANSFER => 1,
      // ... keep the rest of your HEADERFUNCTION as is
      CURLOPT_FAILONERROR => 1,
      CURLOPT_HEADERFUNCTION => function ($curl, $header) use (&$response_headers) {
          $len = strlen($header);
          $header = explode(":", $header, 2);
          if (count($header) < 2) { // ignore invalid headers
              return $len;
          }
          $response_headers[strtolower(trim($header[0]))] = trim($header[1]);
          return $len;
      }
    ];
}

function send_request($config)
{
    $error_msg = null;
    $response_headers = [];
    $request_headers = [
      "x-forwarded-for" => $_SERVER["HTTP_X_FORWARDED_FOR"] || $_SERVER["REMOTE_ADDR"] || $_SERVER["SERVER_ADDR"],
      "cookie" => $_SERVER["HTTP_COOKIE"],
      "dnt" => $_SERVER["HTTP_DNT"],
      "referer" => $config["target_url"], // SPROOFS REFERER AS THE IMAGE ITSELF
      "user-agent" => $_SERVER["HTTP_USER_AGENT"], // USES YOUR ACTUAL BROWSER AGENT
      "via" => "1.1 bandwidth-hero",
      "content-encoding" => "gzip"
    ];


    $ch = curl_init();
    doto(
        fn ($c, $o, $v) => curl_setopt($c, $o, $v),
        $ch,
        request_opts($request_headers, $response_headers, $config["target_url"])
    );
    $data = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($status >= 300 && $status < 400) {
        http_response_code($status);
        $location = preg_replace('(https?:)', '', $response_headers["location"]);
        header('location: ' . $location);
        return false;
    };

    $response_headers = array_merge($response_headers, ["content-encoding" => "identity"]);
    $headers = array_merge(
        $request_headers,
        ["origin_type" => $response_headers["content-type"] ?? '',
           "origin_size" => strlen($data)]
    );

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
    };
    curl_close($ch);

    if ($error_msg || $status >= 300) {
        redirect($config);
        return false;
    };

    return validation\should_compress($config, $data, $headers, $response_headers);
};
