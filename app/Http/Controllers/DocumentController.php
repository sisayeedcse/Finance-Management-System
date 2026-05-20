<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\ActivityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index()
    {
        return response()->json(['data' => Document::orderByDesc('created_at')->get()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:300',
            'file' => 'nullable|file|max:50000',
            'url' => 'nullable|url|max:1000',
        ]);

        // If file present, store the file
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $storedPath = $file->store('documents', 'local');

            $document = Document::create([
                'title' => $request->title,
                'original_filename' => $file->getClientOriginalName(),
                'stored_filename' => basename($storedPath),
                'file_path' => $storedPath,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => $request->user()->id,
            ]);

            ActivityService::log('upload_document', "Uploaded document: {$request->title}", $request->user()->id);
            return response()->json($document, 201);
        }

        // If URL provided (link-only document), create a record without file
        if ($request->filled('url')) {
            $document = Document::create([
                'title' => $request->title,
                'file_path' => $request->url,
                'uploaded_by' => $request->user()->id,
            ]);
            ActivityService::log('upload_document', "Added document link: {$request->title}", $request->user()->id);
            return response()->json($document, 201);
        }

        return response()->json(['error' => 'No file or URL provided'], 400);
    }

    public function download(string $id)
    {
        $document = Document::findOrFail($id);

        // If file_path is a URL, redirect to it
        if (filter_var($document->file_path, FILTER_VALIDATE_URL)) {
            return redirect($document->file_path);
        }

        // Otherwise, download from storage
        return Storage::download($document->file_path, $document->original_filename);
    }

    public function destroy(string $id, Request $request)
    {
        $document = Document::findOrFail($id);
        if ($document->file_path) {
            Storage::delete($document->file_path);
        }
        ActivityService::log('delete_document', "Deleted document {$id}", $request->user()->id);
        $document->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
