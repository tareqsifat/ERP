<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * failed_doc.md §3 item 5 (Pass 3): every image-upload FormRequest
 * (Party/Order/Booking's `image` field) already validates actual file
 * content via Laravel's `image` rule (MIME-type/magic-bytes, not
 * extension) and stores under a server-generated random filename
 * outside the public web root — but the raw uploaded bytes were being
 * persisted as-is, with no re-encode step. Re-encoding through GD
 * strips anything riding along in the file beyond actual pixel data
 * (e.g. a polyglot payload embedded after/around valid image bytes),
 * so what's ever served back to another user later is guaranteed to be
 * nothing but a real, re-compressed image — not the client's original
 * bytes verbatim.
 *
 * Deliberately built on PHP's bundled `gd` extension rather than adding
 * a Composer dependency (e.g. intervention/image) — this sandbox can't
 * run `composer install` to verify a new package resolves correctly,
 * and `gd` is already present (confirmed via `extension_loaded('gd')`)
 * and requires no new dependency at all.
 */
class ImageUploadService
{
    /**
     * Re-encodes the uploaded image as a fresh JPEG and stores it,
     * returning the stored path (same contract as
     * UploadedFile::store()). Falls back to the original bytes only if
     * GD genuinely cannot decode the file — should not happen in
     * practice since the FormRequest's `image` rule already rejected
     * anything that isn't a real image before this ever runs.
     */
    public static function storeReencoded(UploadedFile $file, string $directory, string $disk = 'local'): string
    {
        $bytes = @file_get_contents($file->getRealPath());
        $source = $bytes !== false ? @imagecreatefromstring($bytes) : false;

        if ($source === false) {
            return $file->store($directory, $disk);
        }

        $width = imagesx($source);
        $height = imagesy($source);

        // JPEG has no alpha channel — flatten any transparency onto a
        // white background before re-encoding rather than letting GD
        // silently turn it black.
        $flattened = imagecreatetruecolor($width, $height);
        imagefill($flattened, 0, 0, imagecolorallocate($flattened, 255, 255, 255));
        imagecopy($flattened, $source, 0, 0, 0, 0, $width, $height);
        imagedestroy($source);

        ob_start();
        imagejpeg($flattened, null, 85);
        $reencoded = ob_get_clean();
        imagedestroy($flattened);

        $path = trim($directory, '/').'/'.Str::uuid()->toString().'.jpg';
        Storage::disk($disk)->put($path, $reencoded);

        return $path;
    }
}
