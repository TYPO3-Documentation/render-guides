<?php

# This is a Proxy file to request a URL from docs.typo.org
# and pass the result along to the local development.
# It is used for debugging the docs.typo3.org/services/versionsJson.php
# endpoint.
# Since no active PHP files are part of the local DDEV instance by default,
# you need to manually put that file into the document root:
# $> ln -s ../packages/typo3-docs-theme/assets/js/versions-proxy.php Documentation-GENERATED-temp/versions-proxy.php

$proxyUrl = 'https://docs.typo3.org/services/versionsJson.php?url=' . urlencode($_REQUEST['url']);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $proxyUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);

if (curl_errno($ch) || $response === false) {
    header('404 Not Found');
    echo 'cURL error: ' . curl_error($ch);
    curl_close($ch);
    exit();
}
$response = (string)$response;

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $header_size);
$body = substr($response, $header_size);
curl_close($ch);
http_response_code($http_code);
$headers_array = explode("\r\n", $headers);

foreach ($headers_array as $header) {
    if (!empty($header) && !preg_match('/^Transfer-Encoding:/i', $header) && !preg_match('/^Content-Length:/i', $header)) {
        header($header);
    }
}

echo $body;
