<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Events\CreateEvent;
use App\Actions\Events\DeleteEvent;
use App\Actions\Events\UpdateEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListAdminEventsRequest;
use App\Http\Requests\SaveEventRequest;
use App\Http\Resources\AdminEventListResource;
use App\Http\Resources\AdminEventResource;
use App\Models\Event;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

#[Group('Admin - Event')]
class EventController extends Controller
{
    private const DEFAULT_PER_PAGE = 20;

    /**
     * Daftar semua event (admin)
     */
    public function index(ListAdminEventsRequest $request): AnonymousResourceCollection
    {
        $filters = $request->filters();

        $events = Event::query()
            ->filter($filters)
            ->sortedBy($filters['sort'] ?? 'date_desc')
            ->paginate((int) ($filters['per_page'] ?? self::DEFAULT_PER_PAGE))
            ->withQueryString();

        return AdminEventListResource::collection($events);
    }

    /**
     * Buat event baru (admin)
     */
    public function store(SaveEventRequest $request, CreateEvent $createEvent): JsonResponse
    {
        $validated = $request->validated();
        $coverFile = $request->file('cover_image');

        $event = $createEvent->execute($validated, $coverFile);

        return (new AdminEventResource($event->load(['images'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Detail event (admin)
     */
    public function show(Event $event): AdminEventResource
    {
        return new AdminEventResource($event->load(['images']));
    }

    /**
     * Perbarui data event (admin)
     */
    public function update(SaveEventRequest $request, Event $event, UpdateEvent $updateEvent): AdminEventResource
    {
        $validated = $request->validated();
        $coverFile = $request->file('cover_image');
        $removeCover = (bool) $request->boolean('remove_cover');

        $event = $updateEvent->execute($event, $validated, $coverFile, $removeCover);

        return new AdminEventResource($event->load(['images']));
    }

    /**
     * Hapus event (admin)
     */
    public function destroy(Event $event, DeleteEvent $deleteEvent): Response
    {
        $deleteEvent->execute($event);

        return response()->noContent();
    }
}
