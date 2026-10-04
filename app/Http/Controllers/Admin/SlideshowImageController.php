<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SlideshowImage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class SlideshowImageController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasPermission('manage_categories')) {
            abort(403);
        }

        $images = SlideshowImage::orderBy('sort_order')->get();
        return view('admin.slideshow-images.index', compact('images'));
    }

    public function list(): JsonResponse
    {
        $images = SlideshowImage::orderBy('sort_order')->get()->map(fn ($img) => [
            'id' => $img->id,
            'path' => $img->path,
            'sort_order' => $img->sort_order,
            'is_active' => $img->is_active,
        ]);

        return response()->json(['images' => $images]);
    }

    public function upload(Request $request): JsonResponse
    {
        if (!auth()->user()->hasPermission('manage_categories')) {
            abort(403);
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $path = $request->file('image')->store('slideshow', 'public');

        $maxOrder = SlideshowImage::max('sort_order');

        $image = SlideshowImage::create([
            'path' => $path,
            'sort_order' => $maxOrder === null ? 0 : $maxOrder + 1,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Image uploaded.',
            'image' => [
                'id' => $image->id,
                'path' => $image->path,
                'sort_order' => $image->sort_order,
                'is_active' => $image->is_active,
            ],
        ]);
    }

    public function toggle(SlideshowImage $slideshowImage): JsonResponse
    {
        if (!auth()->user()->hasPermission('manage_categories')) {
            abort(403);
        }

        $slideshowImage->update(['is_active' => !$slideshowImage->is_active]);

        return response()->json([
            'success' => true,
            'message' => $slideshowImage->is_active ? 'Image activated.' : 'Image deactivated.',
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        if (!auth()->user()->hasPermission('manage_categories')) {
            abort(403);
        }

        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:slideshow_images,id',
        ]);

        foreach ($request->order as $index => $id) {
            SlideshowImage::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true, 'message' => 'Order updated.']);
    }

    public function destroy(SlideshowImage $slideshowImage): JsonResponse
    {
        if (!auth()->user()->hasPermission('manage_categories')) {
            abort(403);
        }

        Storage::disk('public')->delete($slideshowImage->path);
        $slideshowImage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Image deleted.',
        ]);
    }
}
