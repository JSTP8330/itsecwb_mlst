<?php
/**
 * Re-encode profile pictures with GD to reduce polyglot / malicious payload risk.
 */
declare(strict_types=1);

/**
 * @param string $absPath Absolute path to the uploaded file
 * @param string $ext Lowercase extension without dot: jpg, jpeg, png, bmp
 * @return string|null Absolute path to final JPEG file, or null on failure
 */
function itsec_reencode_profile_picture(string $absPath, string $ext): ?string {
    if (!is_readable($absPath)) {
        return null;
    }

    $im = null;
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            $im = @imagecreatefromjpeg($absPath);
            break;
        case 'png':
            $src = @imagecreatefrompng($absPath);
            if ($src === false) {
                return null;
            }
            $w = imagesx($src);
            $h = imagesy($src);
            if ($w <= 0 || $h <= 0) {
                imagedestroy($src);
                return null;
            }
            $im = imagecreatetruecolor($w, $h);
            if ($im === false) {
                imagedestroy($src);
                return null;
            }
            $white = imagecolorallocate($im, 255, 255, 255);
            imagefill($im, 0, 0, $white);
            imagecopy($im, $src, 0, 0, 0, 0, $w, $h);
            imagedestroy($src);
            break;
        case 'bmp':
            if (!function_exists('imagecreatefrombmp')) {
                return null;
            }
            $im = @imagecreatefrombmp($absPath);
            break;
        default:
            return null;
    }

    if ($im === false || $im === null) {
        return null;
    }

    $dir = dirname($absPath);
    $base = pathinfo($absPath, PATHINFO_FILENAME);
    $target = $dir . '/' . $base . '.jpg';

    $ok = imagejpeg($im, $target, 85);
    imagedestroy($im);

    if (!$ok || !is_file($target)) {
        @unlink($target);
        return null;
    }

    if ($target !== $absPath && is_file($absPath)) {
        @unlink($absPath);
    }

    return $target;
}
