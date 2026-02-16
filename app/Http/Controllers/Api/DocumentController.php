<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'document_type' => 'required|in:constitution,meeting_minutes,loan_agreement,member_id,financial_report,policy,other',
            'file' => 'required|file|max:10240',
            'meeting_id' => 'nullable|exists:meetings,id',
            'loan_id' => 'nullable|exists:loans,id',
            'is_public' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('documents', $fileName, 'public');

            $document = Document::create([
                'title' => $request->title,
                'description' => $request->description,
                'document_type' => $request->document_type,
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'file_type' => $file->getClientMimeType(),
                'member_id' => $request->user()->member_id,
                'uploaded_by' => $request->user()->id,
                'meeting_id' => $request->meeting_id,
                'loan_id' => $request->loan_id,
                'is_public' => $request->is_public ?? false,
            ]);

            ActivityLog::log(
                $request->user()->id,
                'document_upload',
                "Document uploaded: {$document->title}",
                $document
            );

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully',
                'data' => $document
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => 'No file uploaded'
        ], 400);
    }

    public function index(Request $request)
    {
        $query = Document::with('uploadedBy', 'member', 'meeting', 'loan');

        if ($request->has('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if (!$request->user()->hasRole('super_admin')) {
            $query->where(function ($q) use ($request) {
                $q->where('is_public', true)
                  ->orWhere('uploaded_by', $request->user()->id)
                  ->orWhere('member_id', $request->user()->member_id);
            });
        }

        $documents = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $documents
        ]);
    }

    public function show(Request $request, $id)
    {
        $document = Document::with('uploadedBy', 'member', 'meeting', 'loan')
            ->findOrFail($id);

        if (!$document->is_public && 
            $document->uploaded_by !== $request->user()->id && 
            $document->member_id !== $request->user()->member_id &&
            !$request->user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $document
        ]);
    }

    public function download(Request $request, $id)
    {
        $document = Document::findOrFail($id);

        if (!$document->is_public && 
            $document->uploaded_by !== $request->user()->id && 
            $document->member_id !== $request->user()->member_id &&
            !$request->user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if (!Storage::disk('public')->exists($document->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);
        }

        $document->increment('download_count');

        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }

    public function destroy(Request $request, $id)
    {
        $document = Document::findOrFail($id);

        if ($document->uploaded_by !== $request->user()->id && 
            !$request->user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        Storage::disk('public')->delete($document->file_path);

        $document->delete();

        ActivityLog::log(
            $request->user()->id,
            'document_delete',
            "Document deleted: {$document->title}",
            $document
        );

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully'
        ]);
    }
}