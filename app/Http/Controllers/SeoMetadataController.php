<?php

namespace App\Http\Controllers;

use App\Models\SeoMetadata;
use Illuminate\Http\Request;
use App\Http\Resources\SeoMetadataResource;

class SeoMetadataController extends Controller
{
    private function authorizeModel(Request $request, $model, string $permission)
    {
        if (method_exists($model, 'getTenancyField')) {
            if ($model->{$model->getTenancyField()} !== $request->user()->hosting_company_id) {
                abort(403, 'This action is unauthorized.');
            }
        }

        if (!$request->user()->canPermission($permission)) {
            abort(403, 'This action is unauthorized.');
        }
    }

    public function show(Request $request)
    {
        $modelType = $request->input('model_type');
        $modelId = $request->input('model_id');

        $model = $modelType::findOrFail($modelId);
        $this->authorizeModel($request, $model, 'seo-metadata:view');

        $seoMetadata = SeoMetadata::where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->first();

        if (!$seoMetadata) {
            return response()->json(['message' => 'SEO metadata not found'], 404);
        }

        return new SeoMetadataResource($seoMetadata);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'model_type' => 'required|string',
            'model_id' => 'required|integer',
            'page_slug' => 'nullable|string|max:255|unique:seo_metadata,page_slug',
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
            'og_title' => 'nullable|string|max:255',
            'og_image_url' => 'nullable|string|max:255',
        ]);

        $model = $validated['model_type']::findOrFail($validated['model_id']);
        $this->authorizeModel($request, $model, 'seo-metadata:create');

        $seoMetadata = SeoMetadata::create($validated);

        return new SeoMetadataResource($seoMetadata);
    }

    public function update(Request $request, SeoMetadata $seoMetadata)
    {
        $validated = $request->validate([
            'page_slug' => 'nullable|string|max:255|unique:seo_metadata,page_slug,' . $seoMetadata->id,
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
            'og_title' => 'nullable|string|max:255',
            'og_image_url' => 'nullable|string|max:255',
        ]);

        $this->authorizeModel($request, $seoMetadata->model, 'seo-metadata:update');

        $seoMetadata->update($validated);

        return new SeoMetadataResource($seoMetadata);
    }
}
