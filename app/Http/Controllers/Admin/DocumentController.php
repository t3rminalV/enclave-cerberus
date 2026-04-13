<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Event;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DocumentController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(Event $event)
    {
        $documents = Document::with(['uploader', 'teams'])
            ->where('event_id', $event->id)
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Admin/Documents/Index', [
            'event' => $event,
            'documents' => $documents,
            'teams' => $event->teams()->get(),
        ]);
    }

    public function store(Request $request, Event $event)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'required|in:public,all_volunteers,accepted_only,specific_teams',
            'team_ids' => 'array',
            'team_ids.*' => 'integer|exists:teams,id',
            'file' => 'required|file|max:25600', // 25MB
            'notify' => 'boolean',
        ]);

        $disk = config('filesystems.default');
        $file = $request->file('file');
        $path = $file->store("documents/{$event->slug}", $disk);

        $document = Document::create([
            'event_id' => $event->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'disk' => $disk,
            'path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'visibility' => $validated['visibility'],
            'uploaded_by' => auth()->id(),
        ]);

        if ($validated['visibility'] === 'specific_teams' && !empty($validated['team_ids'])) {
            $document->teams()->sync($validated['team_ids']);
        }

        if ($request->boolean('notify')) {
            $users = $this->getVisibleUsers($document, $event);
            $this->notifications->notifyDocumentShared($document, $users->all());
        }

        return back()->with('success', 'Document uploaded.');
    }

    public function update(Request $request, Event $event, Document $document)
    {
        abort_if($document->event_id !== $event->id, 404);

        $validated = $request->validate([
            'visibility' => 'required|in:public,all_volunteers,accepted_only,specific_teams',
            'team_ids' => 'array',
            'team_ids.*' => 'integer|exists:teams,id',
        ]);

        $document->update(['visibility' => $validated['visibility']]);

        if ($validated['visibility'] === 'specific_teams') {
            $document->teams()->sync($validated['team_ids'] ?? []);
        } else {
            $document->teams()->detach();
        }

        return back()->with('success', 'Document updated.');
    }

    public function download(Event $event, Document $document)
    {
        abort_if($document->event_id !== $event->id, 404);

        // Admin can always download
        return Storage::disk($document->disk)->download($document->path, $document->original_filename);
    }

    public function destroy(Event $event, Document $document)
    {
        abort_if($document->event_id !== $event->id, 404);
        Storage::disk($document->disk)->delete($document->path);
        $document->delete();
        return back()->with('success', 'Document deleted.');
    }

    private function getVisibleUsers(Document $document, Event $event)
    {
        if ($document->visibility === 'public') {
            return collect();
        }

        return User::whereHas('applications', function ($q) use ($event, $document) {
            $q->where('event_id', $event->id);
            if ($document->visibility === 'accepted_only' || $document->visibility === 'specific_teams') {
                $q->where('status', 'accepted');
            } else {
                $q->whereIn('status', ['submitted', 'under_review', 'accepted']);
            }
        })->when($document->visibility === 'specific_teams', function ($q) use ($document) {
            $teamIds = $document->teams()->pluck('teams.id');
            $q->whereHas('teams', fn($tq) => $tq->whereIn('teams.id', $teamIds));
        })->get();
    }
}
