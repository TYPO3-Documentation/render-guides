<?php

function reverseTransformUrl($newUrl) {
    // Parse the URL to get the path
    $parsedUrl = parse_url($newUrl);
    $path = $parsedUrl['path'];

    // Check if the path starts with /main/classes/
    if (strpos($path, 'classes/') === 0) {
        // Remove the 'classes/' prefix and the initial part of the path
        $classPart = str_replace('classes/', '', $path);

        // Remove the .html extension
        $classPart = str_replace('.html', '', $classPart);

        // Split the class part by hyphens
        $parts = explode('-', $classPart);

        // Initialize an array to hold the transformed parts
        $transformedParts = [];

        // Iterate over the parts to construct the old path
        foreach ($parts as $part) {
            // Convert the part to capitalized format with underscores
            $transformedPart = preg_replace_callback('/[A-Z]/', function($matches) {
                return '_' . strtolower($matches[0]);
            }, $part);

            // Add the transformed part to the array
            $transformedParts[] = $transformedPart;
        }

        // Join the transformed parts with '_1_1_'
        $oldClassPart = 'class_' . implode('_1_1_', $transformedParts);

        // Remove any consecutive underscores
        $oldClassPart = preg_replace('/_{2,}/', '_', $oldClassPart);

        // Add the .html extension
        $oldClassPart .= '.html';

        // Construct the old path
        $oldPath = '' . $oldClassPart;

        // Reconstruct the old URL
        $oldUrl = $oldPath;

        return $oldUrl;
    } else {
        // If the path does not match the expected pattern, return the original URL
        return $newUrl;
    }
}

// Read new URLs from a JSON file
$newUrlsFile = __DIR__ . '/new_urls.json';
if (!file_exists($newUrlsFile)) {
    die("File not found: $newUrlsFile");
}

$newUrlsJson = file_get_contents($newUrlsFile);
$newUrlsArray = json_decode($newUrlsJson, true);

// Check if JSON was parsed correctly
if (json_last_error() !== JSON_ERROR_NONE) {
    die("Error parsing JSON: " . json_last_error_msg());
}

// Prepare the redirect array
$redirects = [];
foreach ($newUrlsArray as $newUrl) {
    $oldUrl = reverseTransformUrl($newUrl);
    echo $oldUrl;
    $redirects[$oldUrl] = $newUrl;
}

// Write the redirects to a new JSON file
$redirectsFile = __DIR__ . '/redirects.json';
$redirectsJson = json_encode($redirects, JSON_PRETTY_PRINT);

if (file_put_contents($redirectsFile, $redirectsJson) === false) {
    die("Error writing to file: $redirectsFile");
}

echo "Redirects have been written to $redirectsFile\n";
