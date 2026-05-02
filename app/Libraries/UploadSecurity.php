<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;
use RuntimeException;

class UploadSecurity
{
    private const LOGO_ALLOWED_EXT = ['png', 'jpg', 'jpeg', 'svg'];
    private const LOGO_ALLOWED_MIME = ['image/png', 'image/jpeg', 'image/svg+xml'];
    private const LOGO_MAX_SIZE_KB = 500;
    private const PROOF_ALLOWED_EXT = ['png', 'jpg', 'jpeg', 'pdf'];
    private const PROOF_ALLOWED_MIME = ['image/png', 'image/jpeg', 'application/pdf'];
    private const PROOF_MAX_SIZE_KB = 5120;

    public function uploadLogo(UploadedFile $file): string
    {
        if (! $file->isValid()) {
            throw new RuntimeException('File logo tidak valid.');
        }

        if ($file->getSizeByUnit('kb') > self::LOGO_MAX_SIZE_KB) {
            throw new RuntimeException('Ukuran logo maksimal 500KB.');
        }

        $extension = strtolower((string) $file->getExtension());
        if (! in_array($extension, self::LOGO_ALLOWED_EXT, true)) {
            throw new RuntimeException('Ekstensi logo tidak diizinkan.');
        }

        $tempPath = $file->getTempName();
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo !== false ? finfo_file($finfo, $tempPath) : null;
        if ($finfo !== false) {
            finfo_close($finfo);
        }

        if (! is_string($mimeType) || ! in_array($mimeType, self::LOGO_ALLOWED_MIME, true)) {
            throw new RuntimeException('MIME type logo tidak valid.');
        }

        $fileName   = bin2hex(random_bytes(16)) . '.' . $extension;
        $targetPath = ROOTPATH . 'public/assets/img/uploads/logos/';

        if (! is_dir($targetPath) && ! mkdir($targetPath, 0755, true) && ! is_dir($targetPath)) {
            throw new RuntimeException('Gagal menyiapkan folder upload logo.');
        }

        $file->move($targetPath, $fileName, true);

        return 'assets/img/uploads/logos/' . $fileName;
    }

    public function uploadProof(int $userId, UploadedFile $file): string
    {
        if (! $file->isValid()) {
            throw new RuntimeException('File bukti tidak valid.');
        }

        if ($file->getSizeByUnit('kb') > self::PROOF_MAX_SIZE_KB) {
            throw new RuntimeException('Ukuran bukti maksimal 5MB.');
        }

        $extension = strtolower((string) $file->getExtension());
        if (! in_array($extension, self::PROOF_ALLOWED_EXT, true)) {
            throw new RuntimeException('Ekstensi bukti tidak diizinkan.');
        }

        $tempPath = $file->getTempName();
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo !== false ? finfo_file($finfo, $tempPath) : null;
        if ($finfo !== false) {
            finfo_close($finfo);
        }

        if (! is_string($mimeType) || ! in_array($mimeType, self::PROOF_ALLOWED_MIME, true)) {
            throw new RuntimeException('MIME type bukti tidak valid.');
        }

        $fileName   = bin2hex(random_bytes(16)) . '.' . $extension;
        $targetPath = ROOTPATH . 'public/assets/img/uploads/proofs/' . $userId . '/';

        if (! is_dir($targetPath) && ! mkdir($targetPath, 0755, true) && ! is_dir($targetPath)) {
            throw new RuntimeException('Gagal menyiapkan folder upload bukti.');
        }

        $file->move($targetPath, $fileName, true);

        return 'assets/img/uploads/proofs/' . $userId . '/' . $fileName;
    }
}
