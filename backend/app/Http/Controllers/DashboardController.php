<?php
namespace App\Http\Controllers;
use App\Models\{LoanRequest, Repayment};
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\{DB, Gate};

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse {
        Gate::authorize('dashboard', LoanRequest::class);
        $counts = LoanRequest::select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status');
        $disbursed = (float) LoanRequest::whereIn('status', ['approved', 'closed'])->sum('amount');
        $repaid = (float) Repayment::sum('amount');
        return response()->json(['data' => [
            'counts' => collect(['pending', 'approved', 'rejected', 'closed'])->mapWithKeys(fn ($s) => [$s => (int) ($counts[$s] ?? 0)]),
            'total_disbursed' => $disbursed,
            'total_repaid' => $repaid,
            'total_outstanding' => $disbursed - $repaid,
        ]]);
    }
}
