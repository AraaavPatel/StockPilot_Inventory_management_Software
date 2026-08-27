<?php

namespace App\Core;

use RuntimeException;

/**
 * UploadValidator
 *
 * NOTE: No controller in this project currently accepts file uploads —
 * `products.image_path` exists in the schema but nothing writes to it
 * yet. This class exists so that when that feature is built, it's built
 * on top of hardened defaults instead of ad-hoc code later. Wire it up
 * from a controller like:
 *
 *   $path = (new UploadValidator())->store($_FILES['image'], 'products');
 *
 * It deliberately does NOT trust:
 *   - the original filename (never used for anything but the extension)
 *   - the client-supplied MIME type ($_FILES[...]['type'])
 *   - the extension alone (also verifies real content via getimagesize/finfo)
 */
class UploadValidator
{
    /** @var array<string,string> extension => allowed real MIME type */
    private array $allowed = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
    ];

    private int $maxBytes;
    private string $baseDir;

    public function __construct(int $maxBytes = 2 * 1024 * 1024, ?string $baseDir = null)
    {
        $this->maxBytes = $maxBytes;
        // storage/ sits outside public/ (see .htaccess deny-all there);
        // callers should copy the validated file into public/uploads
        // themselves only after this passes, using a fresh random name.
        $this->baseDir = $baseDir ?? __DIR__ . '/../../public/uploads';
    }

    /**
     * Validate one $_FILES[...] entry and move it to a random filename.
     * Throws RuntimeException with a user-safe message on any failure.
     * Returns the stored filename (not a full path or URL).
     */
    public function store(array $file, string $subdir = ''): string
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new RuntimeException('Invalid upload.');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload failed. Please try again.');
        }

        if ($file['size'] <= 0 || $file['size'] > $this->maxBytes) {
            throw new RuntimeException('File is too large or empty.');
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Invalid upload.');
        }

        // Never trust the client-sent extension or MIME — verify the
        // actual file content with fileinfo, which reads magic bytes.
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($file['tmp_name']);

        $extension = array_search($realMime, $this->allowed, true);
        if ($extension === false) {
            throw new RuntimeException('Only JPG, PNG, and WEBP images are allowed.');
        }

        // Belt-and-braces: confirm it decodes as a real image, which
        // rejects e.g. a polyglot file that merely spoofs magic bytes.
        if (@getimagesize($file['tmp_name']) === false) {
            throw new RuntimeException('That file is not a valid image.');
        }

        $targetDir = rtrim($this->baseDir, '/') . ($subdir !== '' ? '/' . trim($subdir, '/') : '');
        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            throw new RuntimeException('Could not save upload.');
        }

        // Fully random filename — the original filename is never used
        // for anything, which removes path-traversal and overwrite risk.
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $targetDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException('Could not save upload.');
        }

        chmod($destination, 0644);

        return ($subdir !== '' ? trim($subdir, '/') . '/' : '') . $filename;
    }
}
