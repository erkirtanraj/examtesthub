<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebsiteContent;
use Illuminate\Http\JsonResponse;

class WebsiteContentApiController extends Controller
{
    public function index(): JsonResponse
    {
        $items = WebsiteContent::query()->where('is_published', true)
            ->orderBy('sort_order')->orderBy('id')->get();

        return response()->json([
            'data' => $items->groupBy('type')->map(fn ($group) => $group->map(fn ($item) => [
                'id' => $item->id,
                'key' => $item->key,
                'title' => $item->title,
                'slug' => $item->slug,
                'content' => $item->content,
                'sort_order' => $item->sort_order,
            ])->values()),
        ])->header('Cache-Control', 'public, max-age=60');
    }

    public function page(string $slug): JsonResponse
    {
        $page = WebsiteContent::query()->whereIn('type', ['page', 'legal'])
            ->where('slug', $slug)->where('is_published', true)->firstOrFail();

        return response()->json(['data' => [
            'title' => $page->title,
            'slug' => $page->slug,
            'content' => $page->content,
        ]])->header('Cache-Control', 'public, max-age=60');
    }
}
