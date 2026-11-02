<?php
namespace App\Http\Controllers;
use App\Http\Requests\StoreDocumentRequest;
use App\Jobs\SummarizeDocument;
use App\Http\Resources\DocumentResource;
use App\Models\LoanRequest;
use Illuminate\Http\JsonResponse;

class DocumentController extends Controller
{
    public function store(StoreDocumentRequest $request, LoanRequest $loan): JsonResponse {
        $file = $request->file('file');
        $document = $loan->documents()->create([
            'file_path' => $file->store("documents/{$loan->id}"), // private "local" disk
            'original_name' => $file->getClientOriginalName(),
            'summary_status' => 'pending',
        ]);
        SummarizeDocument::dispatch($document);
        return DocumentResource::make($document)->response()->setStatusCode(201);
    }
}
