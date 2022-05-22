<?php

namespace App\Helpers;

use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Configuration\Configuration;

class Cloudinary
{

    protected $config;

    public function __construct()
    {
        $this->config = new Configuration([
            'cloud' => [
                'cloud_name' => env("CLOUDINARY_CLOUD_NAME"),
                'api_key' => env("CLOUDINARY_API_KEY"),
                'api_secret' => env("CLOUDINARY_API_SECRET"),
                'url' => [
                    'secure' => true
                ]
            ]
        ]);
    }

    public function postImage($filePath, $cloudFolder)
    {
        $res = (new UploadApi($this->config))->upload($filePath, [
            'resource_type' => "image",
            'folder' => $cloudFolder
        ]);

        return [
            'image_url' => $res['url'],
            'public_id' => $res['public_id']
        ];
    }

    public function deleteImage($publicId)
    {
        (new UploadApi($this->config))->destroy($publicId, ['resource_tyoe' => "image"]);
    }
}
