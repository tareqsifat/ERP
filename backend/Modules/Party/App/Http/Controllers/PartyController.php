<?php

namespace Modules\Party\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Party\App\Http\Requests\StorePartyRequest;
use Modules\Party\App\Http\Requests\UpdatePartyRequest;
use Modules\Party\App\Http\Resources\PartyResource;
use Modules\Party\App\Models\Party;

/**
 * PRD v1 §3.10 / §4.9 (Party List, Add New Buyer/Supplier), extended by
 * PRD v2 §4.9 with the Subcontractor type.
 */
class PartyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // failed_doc.md §10: page size is capped, never client-unbounded.
        $perPage = min((int) $request->integer('per_page', 20), 100);

        $parties = Party::query()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($q2) => $q2->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->orderBy('name')
            ->paginate($perPage);

        return $this->ok(PartyResource::collection($parties));
    }

    public function show(Party $party): JsonResponse
    {
        return $this->ok(new PartyResource($party));
    }

    public function store(StorePartyRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            // sdd.md §8: stored outside the public web root, served later
            // via a signed URL route rather than a direct public path.
            $data['image_path'] = $request->file('image')->store('parties', 'local');
        }
        unset($data['image']);

        $party = Party::create($data);

        return $this->created(new PartyResource($party));
    }

    public function update(UpdatePartyRequest $request, Party $party): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($party->image_path) {
                Storage::disk('local')->delete($party->image_path);
            }
            $data['image_path'] = $request->file('image')->store('parties', 'local');
        }
        unset($data['image']);

        $party->fill($data);
        $party->save();

        return $this->ok(new PartyResource($party));
    }

    public function destroy(Party $party): JsonResponse
    {
        // sdd.md §5: soft delete only. FK from Orders/vouchers etc. is
        // `restrict` on delete, so a party with real activity against it
        // cannot even reach a hard delete by accident later.
        $party->delete();

        return $this->noContent();
    }
}
