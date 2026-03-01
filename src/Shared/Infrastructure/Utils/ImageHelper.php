<?php

namespace Shared\Infrastructure\Utils;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ImageHelper 
{
    /**
     * Store and resize an image to AWS S3
     * @param mixed $image
     * @param string $path
     * @return string|null
     */
    public static function storeAndResize($image, $path)
    {
        try {
            Log::info('Starting image processing for S3', ['path' => $path]);

            // Create Intervention Image instance
            if (is_string($image) && !is_file($image)) {
                $interventionImage = Image::make($image);
                Log::info('Processing binary image data');
            } else {
                $interventionImage = Image::make($image);
                Log::info('Processing uploaded file', [
                    'original_name' => $image->getClientOriginalName() ?? 'N/A',
                    'mime_type' => $image->getMimeType() ?? 'N/A'
                ]);
            }

            // Resize image
            $resizedImage = self::resizeImage($interventionImage);
            
            // Generate unique filename with timestamp
            $fileName = time() . '_' . Str::uuid() . '.jpg';
            
            // Full path in S3
            $s3Path = $path . '/' . $fileName;

            // Store in S3
            $result = Storage::disk('s3')->put(
                $s3Path, 
                $resizedImage->encode('jpg', 80)->stream(),
                'public'
            );

            if (!$result) {
                throw new \Exception('Failed to upload image to S3');
            }

            // Get the full URL
            $url = Storage::disk('s3')->url($s3Path);

            Log::info('Image successfully uploaded to S3', [
                'path' => $s3Path,
                'url' => $url
            ]);

            return $url;
        } catch (\Exception $e) {
            Log::error('Failed to process and upload image to S3', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Resize image maintaining aspect ratio
     * @param \Intervention\Image\Image $image
     * @return \Intervention\Image\Image
     */
    private static function resizeImage($image)
    {
        try {
            $maxWidth = 1200;
            $maxHeight = 1200;

            $width = $image->width();
            $height = $image->height();

            Log::info('Original image dimensions', [
                'width' => $width,
                'height' => $height
            ]);

            if ($width > $maxWidth || $height > $maxHeight) {
                $image->resize($maxWidth, $maxHeight, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

                Log::info('Image resized', [
                    'new_width' => $image->width(),
                    'new_height' => $image->height()
                ]);
            }

            // Optimize image
            $image->encode('jpg', 80);

            return $image;
        } catch (\Exception $e) {
            Log::error('Failed to resize image', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Delete image from S3
     * @param string $url Full S3 URL of the image
     * @return bool
     */
    public static function deleteImage($url)
    {
        try {
            // Extract the path from the URL
            $path = parse_url($url, PHP_URL_PATH);
            // Remove the bucket name and leading slash if present
            $path = ltrim($path, '/');
            $path = preg_replace('/^' . config('filesystems.disks.s3.bucket') . '\//', '', $path);

            Log::info('Attempting to delete image from S3', [
                'url' => $url,
                'path' => $path
            ]);

            if (Storage::disk('s3')->exists($path)) {
                $deleted = Storage::disk('s3')->delete($path);
                
                Log::info('Image deletion result', [
                    'success' => $deleted,
                    'path' => $path
                ]);
                
                return $deleted;
            }

            Log::warning('Image not found in S3', ['path' => $path]);
            return false;
        } catch (\Exception $e) {
            Log::error('Failed to delete image from S3', [
                'error' => $e->getMessage(),
                'url' => $url
            ]);
            return false;
        }
    }

    /**
     * Cache image data with tags
     * @param string $key
     * @param mixed $data
     * @param array $tags
     * @param int $minutes
     * @return void
     */
    public static function cacheImageData($key, $data, array $tags = [], $minutes = 60)
    {
        try {
            if (!empty($tags)) {
                Cache::tags($tags)->put($key, $data, now()->addMinutes($minutes));
            } else {
                Cache::put($key, $data, now()->addMinutes($minutes));
            }

            Log::info('Image data cached', [
                'key' => $key,
                'tags' => $tags,
                'expires_in' => $minutes
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cache image data', [
                'error' => $e->getMessage(),
                'key' => $key
            ]);
        }
    }

    /**
     * Get cached image data
     * @param string $key
     * @param array $tags
     * @return mixed
     */
    public static function getCachedImageData($key, array $tags = [])
    {
        try {
            if (!empty($tags)) {
                return Cache::tags($tags)->get($key);
            }
            return Cache::get($key);
        } catch (\Exception $e) {
            Log::error('Failed to get cached image data', [
                'error' => $e->getMessage(),
                'key' => $key
            ]);
            return null;
        }
    }

    /**
     * Clear image cache
     * @param string $key
     * @param array $tags
     * @return void
     */
    public static function clearImageCache($key = null, array $tags = [])
    {
        try {
            if ($key && empty($tags)) {
                Cache::forget($key);
            } elseif (!empty($tags)) {
                Cache::tags($tags)->flush();
            }

            Log::info('Image cache cleared', [
                'key' => $key,
                'tags' => $tags
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to clear image cache', [
                'error' => $e->getMessage(),
                'key' => $key,
                'tags' => $tags
            ]);
        }
    }
} 