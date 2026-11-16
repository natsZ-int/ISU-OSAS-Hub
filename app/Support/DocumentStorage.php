<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Resolves document file paths across the storage layouts this application
 * has used over time.
 *
 * Files are persisted relative to the "local" disk (storage/app/private), but
 * older uploads used a redundant "private/" prefix or lived directly under
 * storage/app. Downloads must keep working for those records, so this helper
 * probes the known layouts instead of assuming a single physical location.
 */
final class DocumentStorage
{
    /**
     * Candidate relative paths to probe for a stored document path.
     *
     * @return list<string>
     */
    public static function candidates(string $storedPath): array
    {
        $storedPath = ltrim(str_replace('\\', '/', trim($storedPath)), '/');

        if ($storedPath === '' || str_contains($storedPath, "\0")) {
            return [];
        }

        $segments = explode('/', $storedPath);
        if (in_array('..', $segments, true)) {
            return [];
        }

        $candidates = [$storedPath];

        if (str_starts_with($storedPath, 'private/')) {
            $candidates[] = substr($storedPath, strlen('private/'));
        } else {
            $candidates[] = 'private/'.$storedPath;
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    /**
     * Return the absolute path of an existing document, or null when the file
     * cannot be found in any known layout.
     */
    public static function locate(string $storedPath): ?string
    {
        $roots = self::roots();
        if ($roots === []) {
            return null;
        }

        foreach (self::candidates($storedPath) as $candidate) {
            foreach ($roots as $root) {
                $absolute = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $candidate);
                $real = realpath($absolute);

                if ($real === false || ! is_file($real)) {
                    continue;
                }

                if (self::isWithinAllowedRoot($real, $roots)) {
                    return $real;
                }
            }
        }

        return null;
    }

    /**
     * Build a download response for the stored document, or null when the
     * file is missing.
     */
    public static function download(string $storedPath, string $filename, ?string $mimeType = null): ?BinaryFileResponse
    {
        $absolute = self::locate($storedPath);

        if ($absolute === null) {
            return null;
        }

        $headers = [];

        if (filled($mimeType)) {
            $headers['Content-Type'] = $mimeType;
        }

        return response()->download($absolute, $filename, $headers);
    }

    /**
     * Delete the physical file for a stored document path, whichever legacy
     * layout it lives under.
     */
    public static function delete(string $storedPath): bool
    {
        $absolute = self::locate($storedPath);

        if ($absolute === null) {
            return false;
        }

        return @unlink($absolute);
    }

    /**
     * Root directories a document may physically live under.
     *
     * @return list<string>
     */
    private static function roots(): array
    {
        $roots = [];

        foreach ([
            Storage::disk('local')->path(''),
            storage_path('app'),
        ] as $root) {
            $root = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) $root), DIRECTORY_SEPARATOR);

            if ($root !== '' && is_dir($root)) {
                $roots[$root] = $root;
            }
        }

        return array_values($roots);
    }

    /**
     * @param  list<string>  $roots
     */
    private static function isWithinAllowedRoot(string $realPath, array $roots): bool
    {
        $realPath = strtolower(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $realPath));

        foreach ($roots as $root) {
            $rootReal = realpath($root);

            if ($rootReal === false) {
                continue;
            }

            $rootReal = strtolower(rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rootReal), DIRECTORY_SEPARATOR));

            if ($realPath === $rootReal || str_starts_with($realPath, $rootReal.DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }
}
