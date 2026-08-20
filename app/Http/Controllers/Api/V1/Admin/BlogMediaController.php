<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Blog\UploadBlogImageRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogMediaController extends Controller
{
    public function store(UploadBlogImageRequest $request): JsonResponse
    {
        $file = $request->file('image');
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());

        $safeName = Str::uuid()->toString().'.'.$extension;
        $path = $file->storeAs('blog/featured', $safeName, 'public');

        return response()->json([
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
        ], 201);
    }
}
