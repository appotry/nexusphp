<?php
namespace Nexus\Attachment\Drivers;

use GuzzleHttp\Psr7;
use Nexus\Attachment\Storage;

class Chevereto extends Storage {

    function upload(string $filepath): string
    {
        $api = get_setting("image_hosting_chevereto.upload_api_endpoint");
        $token = get_setting("image_hosting_chevereto.upload_token");
        $logPrefix = "filepath: $filepath, api: $api, token: $token";
        $httpClient = new \GuzzleHttp\Client();
        $response = $httpClient->request('POST', $api, [
            'headers' => [
                'X-API-Key' => sprintf('%s', $token),
            ],
            'multipart' => [
                [
                    'name'     => 'source',
                    'contents' => Psr7\Utils::tryFopen($filepath, 'r')
                ]
            ]
        ]);
        $statusCode = $response->getStatusCode();
        $logPrefix .= ", status code: $statusCode";
        if ($statusCode != 200) {
            do_log("$logPrefix, statusCode != 200", "error");
            throw new \Exception("Unable to upload file, status code {$statusCode}");
        }
        $stringBody = (string)$response->getBody();
        $logPrefix .= ", body: $stringBody";
        $result = json_decode($stringBody, true);
        if (!is_array($result)) {
            do_log("$logPrefix, can not parse to array", "error");
            throw new \Exception("Unable to parse response body");
        }
        if (!isset($result["image"]["url"])) {
            do_log("$logPrefix, no image url", "error");
            throw new \Exception("upload fail: " . ($result["error"]["message"] ?? ""));
        }
        return $result["image"]["url"];
    }

    function getBaseUrl(): string
    {
        return get_setting("image_hosting_chevereto.base_url");
    }

    function getDriverName(): string
    {
        return static::DRIVER_CHEVERETO;
    }
}
