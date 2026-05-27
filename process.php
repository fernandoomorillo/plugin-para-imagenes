<?php
header('Content-Type: application/json');

require_once 'ImageResizer.php';

$uploadDir = __DIR__ . '/uploads/';
$outputDir = __DIR__ . '/output/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    
    $file = $_FILES['image'];
    $width = isset($_POST['width']) && is_numeric($_POST['width']) ? (int)$_POST['width'] : 0;
    $height = isset($_POST['height']) && is_numeric($_POST['height']) ? (int)$_POST['height'] : 0;
    $quality = isset($_POST['quality']) && is_numeric($_POST['quality']) ? (int)$_POST['quality'] : 80;
    $outputFormat = isset($_POST['format']) ? strtolower($_POST['format']) : 'original';
    $maintainRatio = isset($_POST['maintainRatio']) && $_POST['maintainRatio'] === 'true';
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Error uploading the file.']);
        exit;
    }

    $fileName = basename($file['name']);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    $allowedFormats = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($fileExt, $allowedFormats)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file format. Only JPG, PNG, GIF, and WebP are allowed.']);
        exit;
    }

    $uniqueName = uniqid() . '_' . $fileName;
    $uploadPath = $uploadDir . $uniqueName;

    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        try {
            $resizer = new ImageResizer($uploadPath);
            
            if ($width > 0 && $height > 0) {
                $resizer->resize($width, $height, $maintainRatio);
            }
            
            $finalExt = $fileExt;
            if ($outputFormat !== 'original') {
                if ($outputFormat === 'jpeg') $finalExt = 'jpg';
                else $finalExt = $outputFormat;
            }
            
            $baseNameWithoutExt = pathinfo($uniqueName, PATHINFO_FILENAME);
            $outputUniqueName = 'resized_' . $baseNameWithoutExt . '.' . $finalExt;
            $outputPath = $outputDir . $outputUniqueName;
            
            $resizer->save($outputPath, $quality, $outputFormat);

            $outputUrl = 'output/' . $outputUniqueName;
            
            unlink($uploadPath);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Image processed successfully', 
                'url' => $outputUrl,
                'filename' => $outputUniqueName
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error processing image: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file.']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
