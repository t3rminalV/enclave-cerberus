<?php

namespace App\Http\Controllers\Volunteer;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Event;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DocumentController extends Controller
{
    public function index(Event $event)
    {
        $user = auth()->user();
        $application = $user->getApplicationForEvent($event->id);

        $allDocuments = Document::with('teams')
            ->where('event_id', $event->id)
            ->get();

        $visibleDocuments = $allDocuments->filter(
            fn($doc) => $doc->isVisibleToUser($user, $event)
        )->values();

        return Inertia::render('Volunteer/Documents', [
            'event' => $event,
            'documents' => $visibleDocuments,
        ]);
    }

    public function download(Event $event, Document $document)
    {
        abort_if($document->event_id !== $event->id, 404);

        $user = auth()->user();
        abort_unless($document->isVisibleToUser($user, $event), 403);

        AuditLog::record('document.downloaded', $document, [], ['event_id' => $event->id], $user);

        return Storage::disk($document->disk)->download($document->path, $document->original_filename);
    }
}
