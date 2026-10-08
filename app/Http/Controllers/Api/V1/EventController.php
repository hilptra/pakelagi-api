<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListEventsRequest;
use App\Http\Resources\EventListResource;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Event')]
class EventController extends Controller
{
    private const DEFAULT_PER_PAGE = 12;

    /**
     * Daftar event publik
     */
    public function index(ListEventsRequest $request): AnonymousResourceCollection
    {
        $filters = $request->filters();

        $events = Event::query()
            ->published()
            ->filter($filters)
            ->sortedBy($filters['sort'] ?? (($filters['type'] ?? null) === 'upcoming' ? 'date_asc' : 'date_desc'))
            ->paginate((int) ($filters['per_page'] ?? self::DEFAULT_PER_PAGE))
            ->withQueryString();

        return EventListResource::collection($events);
    }

    /**
     * Detail event publik berdasarkan slug
     */
    public function show(string $slug): EventResource
    {
        $event = Event::query()
            ->published()
            ->with(['images'])
            ->where('slug', $slug)
            ->firstOrFail();

        return new EventResource($event);
    }
}
