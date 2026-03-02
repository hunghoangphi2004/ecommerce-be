<?php

namespace App\Helpers;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class CloudinaryHelper
{
    public static function upload($file, $folder = 'uploads')
    {
        $result = Cloudinary::uploadApi()->upload(
            $file->getRealPath(),
            [
                'folder' => $folder
            ]
        );

        return [
            'url' => $result['secure_url'],
            'public_id' => $result['public_id'],
        ];
    }

    public static function destroy($publicId)
    {
        if ($publicId) {
            Cloudinary::uploadApi()->destroy($publicId);
        }
    }
}