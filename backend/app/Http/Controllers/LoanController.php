<?php
namespace App\Http\Controllers;
use App\Http\Requests\{StoreLoanRequest, UpdateLoanStatusRequest};
use App\Http\Resources\LoanResource;
use App\Models\LoanRequest;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class LoanController extends Controller
{
    public function index(Request $request) {
        $request->validate(['status' => 'nullable|in:pending,approved,rejected,closed', 'search' => 'nullable|string|max:100']);
        $user = $request->user();
        $loans = LoanRequest::with('user')->withSum('repayments', 'amount')
            ->when(! $user->isAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->search, fn ($q, $s) => $q->where('purpose', 'like', "%{$s}%"))
            ->latest('id')->paginate(10)->withQueryString();
        return LoanResource::collection($loans);
    }

    public function store(StoreLoanRequest $request): JsonResponse {
        $loan = $request->user()->loans()->create($request->validated());
        return LoanResource::make($loan->load('user')->loadSum('repayments', 'amount'))->response()->setStatusCode(201);
    }

    public function show(LoanRequest $loan): LoanResource {
        Gate::authorize('view', $loan);
        return LoanResource::make($loan->load(['user', 'repayments', 'documents'])->loadSum('repayments', 'amount'));
    }

    public function updateStatus(UpdateLoanStatusRequest $request, LoanRequest $loan): LoanResource {
        // Conditional update keeps this atomic: two admins cannot both decide the same loan.
        $changed = LoanRequest::whereKey($loan->id)->where('status', 'pending')->update([
            'status' => $request->status, 'remarks' => $request->remarks,
            'approved_by' => $request->user()->id, 'approved_at' => now(),
        ]);
        if (! $changed) {
            throw ValidationException::withMessages(['status' => 'Only pending loans can be approved or rejected.']);
        }
        return LoanResource::make($loan->refresh()->load('user')->loadSum('repayments', 'amount'));
    }
}
