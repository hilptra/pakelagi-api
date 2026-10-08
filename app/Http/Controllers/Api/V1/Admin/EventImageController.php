<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Events\DeleteEventImage;
use App\Actions\Events\ReorderEventImages;
use App\Actions\Events\UploadEventImages;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderEventImagesRequest;
use App\Http\Requests\UploadEventImageRequest;
use App\Http\Resources\AdminEventImageResource;
use App\Http\Resources\AdminEventResource;
use App\Models\Event;
use App\Models\EventImage;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;

#[Group('Admin - Event Images')]
class EventImageController extends Controller
{
    /**
     * Unggah foto dokumentasi event
     */
    public function store(
        UploadEventImageRequest $request,
        Event $event,
        UploadEventImages $uploadAction
    ): AnonymousResourceCollection {
        /** @var array<int, UploadedFile> $files */
        $files = $request->file('images');
        $caption = $request->validated('caption');

        $images = $uploadAction->execute($event, $files, $caption);

        return AdminEventImageResource::collection($images);
    }

    /**
     * Urutkan ulang foto dokumentasi event
     */
    public function reorder(
        ReorderEventImagesRequest $request,
        Event $event,
        ReorderEventImages $reorderAction
    ): AdminEventResource {
        /** @var array<int, int> $imageIds */
        $imageIds = $request->validated('image_ids');

        $reorderAction->execute($event, $imageIds);

        return new AdminEventResource($event->fresh(['images']));
    }

    /**
     * Hapus satu foto dokumentasi event
     */
    public function destroy(
        Event $event,
        EventImage $image,
        DeleteEventImage $deleteAction
    ): Response {
        $deleteAction->execute($event, $image);

        return response()->noContent();
    }
}
