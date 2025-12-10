<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Http\Resources\ChecklistResource;
use App\Http\Resources\ChecklistItemResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ChecklistController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $checklists = Checklist::where('hosting_company_id', $request->user()->hosting_company_id)
            ->with('items') // Eager load items for efficiency
            ->latest()
            ->paginate(20);

        return ChecklistResource::collection($checklists);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'checklist_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'items' => 'sometimes|array',
            'items.*.item_description' => 'required|string|max:255',
            'items.*.item_order' => 'sometimes|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $checklist = null;
        DB::transaction(function () use ($request, $validator, &$checklist) {
            $validatedData = $validator->validated();

            $checklist = Checklist::create([
                'hosting_company_id' => $request->user()->hosting_company_id,
                'checklist_name' => $validatedData['checklist_name'],
                'description' => $validatedData['description'] ?? null,
            ]);

            if (isset($validatedData['items'])) {
                $order = 1;
                foreach ($validatedData['items'] as $itemData) {
                    $checklist->items()->create([
                        'item_description' => $itemData['item_description'],
                        'item_order' => $itemData['item_order'] ?? $order++,
                    ]);
                }
            }
        });

        return new ChecklistResource($checklist->load('items'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Checklist $checklist)
    {
        if (request()->user()->hosting_company_id !== $checklist->hosting_company_id) {
            abort(403, 'Unauthorized action.');
        }

        return new ChecklistResource($checklist->load('items'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Checklist $checklist)
    {
        if ($request->user()->hosting_company_id !== $checklist->hosting_company_id) {
            abort(403, 'Unauthorized action.');
        }

        $validator = Validator::make($request->all(), [
            'checklist_name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $checklist->update($validator->validated());

        return new ChecklistResource($checklist->load('items'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Checklist $checklist)
    {
        if (request()->user()->hosting_company_id !== $checklist->hosting_company_id) {
            abort(403, 'Unauthorized action.');
        }
        
        // The database schema should have cascading deletes set up for checklist_items.
        // If not, you would manually delete them here first.
        $checklist->delete();

        return response()->noContent();
    }

    // --- Checklist Item Management ---

    /**
     * Add a new item to a specific checklist.
     */
    public function storeItem(Request $request, Checklist $checklist)
    {
        if ($request->user()->hosting_company_id !== $checklist->hosting_company_id) {
            abort(403, 'Unauthorized action.');
        }

        $validator = Validator::make($request->all(), [
            'item_description' => 'required|string|max:255',
            'item_order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        // If order is not provided, append it to the end.
        if (!isset($validatedData['item_order'])) {
            $validatedData['item_order'] = ($checklist->items()->max('item_order') ?? 0) + 1;
        }

        $item = $checklist->items()->create($validatedData);

        return new ChecklistItemResource($item);
    }

    /**
     * Update a specific checklist item.
     */
    public function updateItem(Request $request, Checklist $checklist, ChecklistItem $item)
    {
        if ($request->user()->hosting_company_id !== $checklist->hosting_company_id || $item->checklist_id !== $checklist->id) {
            abort(403, 'Unauthorized action.');
        }

        $validator = Validator::make($request->all(), [
            'item_description' => 'sometimes|required|string|max:255',
            'item_order' => 'sometimes|required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $item->update($validator->validated());

        return new ChecklistItemResource($item);
    }

    /**
     * Remove a specific checklist item.
     */
    public function destroyItem(Request $request, Checklist $checklist, ChecklistItem $item)
    {
        if ($request->user()->hosting_company_id !== $checklist->hosting_company_id || $item->checklist_id !== $checklist->id) {
            abort(403, 'Unauthorized action.');
        }

        $item->delete();

        return response()->noContent();
    }
}
