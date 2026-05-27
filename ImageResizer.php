<?php

class ImageResizer {
    private $imagePath;
    private $imageInfo;
    private $image;

    public function __construct($imagePath) {
        if (!file_exists($imagePath)) {
            throw new Exception("File does not exist.");
        }
        $this->imagePath = $imagePath;
        $this->imageInfo = getimagesize($imagePath);
        $this->loadImage();
    }

    private function loadImage() {
        $mime = $this->imageInfo['mime'];
        switch ($mime) {
            case 'image/jpeg':
                $this->image = imagecreatefromjpeg($this->imagePath);
                break;
            case 'image/png':
                $this->image = imagecreatefrompng($this->imagePath);
                imagepalettetotruecolor($this->image);
                imagealphablending($this->image, true);
                imagesavealpha($this->image, true);
                break;
            case 'image/gif':
                $this->image = imagecreatefromgif($this->imagePath);
                break;
            case 'image/webp':
                $this->image = imagecreatefromwebp($this->imagePath);
                break;
            default:
                throw new Exception("Unsupported image format.");
        }
    }

    public function resize($newWidth, $newHeight, $maintainAspectRatio = true) {
        $originalWidth = $this->imageInfo[0];
        $originalHeight = $this->imageInfo[1];

        if ($maintainAspectRatio) {
            $ratio = $originalWidth / $originalHeight;
            if ($newWidth / $newHeight > $ratio) {
                $newWidth = $newHeight * $ratio;
            } else {
                $newHeight = $newWidth / $ratio;
            }
        }

        $newWidth = max(1, (int)$newWidth);
        $newHeight = max(1, (int)$newHeight);

        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        if ($this->imageInfo['mime'] == 'image/png' || $this->imageInfo['mime'] == 'image/webp') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        } else {
            $white = imagecolorallocate($newImage, 255, 255, 255);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $white);
        }

        imagecopyresampled(
            $newImage, $this->image,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $originalWidth, $originalHeight
        );

        imagedestroy($this->image);
        $this->image = $newImage;
    }

    public function save($outputPath, $quality = 80, $outputFormat = null) {
        $mime = $this->imageInfo['mime'];
        if ($outputFormat && $outputFormat !== 'original') {
            $formatMap = [
                'jpeg' => 'image/jpeg',
                'jpg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp'
            ];
            $formatLower = strtolower($outputFormat);
            if (isset($formatMap[$formatLower])) {
                $mime = $formatMap[$formatLower];
            }
        }
        
        $quality = max(0, min(100, (int)$quality));

        $result = false;
        switch ($mime) {
            case 'image/jpeg':
                $result = imagejpeg($this->image, $outputPath, $quality);
                break;
            case 'image/png':
                $pngQuality = 9 - round(($quality / 100) * 9);
                $result = imagepng($this->image, $outputPath, $pngQuality);
                break;
            case 'image/gif':
                $result = imagegif($this->image, $outputPath);
                break;
            case 'image/webp':
                $result = imagewebp($this->image, $outputPath, $quality);
                break;
        }

        return $result;
    }

    public function output($quality = 80, $outputFormat = null) {
        $mime = $this->imageInfo['mime'];
        header('Content-Type: ' . $mime);
        $this->save(null, $quality, $outputFormat);
    }

    public function __destruct() {
        if ($this->image) {
            imagedestroy($this->image);
        }
    }
}
