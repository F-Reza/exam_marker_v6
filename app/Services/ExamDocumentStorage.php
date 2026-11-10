<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

class ExamDocumentStorage
{
    /** Call after ExamDocument validation; never use the original filename as a path. */
    public static function put(?UploadedFile $file, string $directory): ?string
    {
        if (!$file) return null;
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['pdf', 'doc', 'docx'], true)) throw new RuntimeException('Unsupported exam document.');
        $path = $file->storeAs($directory, Str::random(40).'.'.$extension);
        if (!$path) throw new RuntimeException('The document could not be saved. Check private storage permissions.');
        return $path;
    }
}
