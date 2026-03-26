<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    /**
     * Upload a file to the given folder inside storage/app/public/.
     *
     * @param  UploadedFile  $file
     * @param  string        $folder   e.g. 'avatars', 'doctors', 'lab-reports'
     * @return string                  Relative path stored in DB  e.g. "avatars/abc123.jpg"
     */
    public function upload(UploadedFile $file, string $folder = 'uploads'): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path     = $file->storeAs($folder, $filename, 'public');
        return $path; // e.g. "avatars/uuid.jpg"
    }

    /**
     * Delete a previously stored file.
     *
     * @param  string  $path  Relative path as stored in DB
     */
    public function delete(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Replace an existing file (delete old, upload new).
     */
    public function replace(UploadedFile $file, ?string $oldPath, string $folder = 'uploads'): string
    {
        $this->delete($oldPath);
        return $this->upload($file, $folder);
    }
}
