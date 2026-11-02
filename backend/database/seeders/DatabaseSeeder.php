<?php
namespace Database\Seeders;
use App\Models\{LoanRequest, User};
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void {
        $admin = User::factory()->create(['name' => 'Admin User', 'email' => 'admin@example.com', 'role' => 'admin']);
        $applicants = [
            User::factory()->create(['name' => 'Alice Applicant', 'email' => 'alice@example.com']),
            User::factory()->create(['name' => 'Bob Applicant', 'email' => 'bob@example.com']),
        ]; // every seeded user's password is "password"

        $statuses = ['pending', 'approved', 'approved', 'rejected', 'closed'];
        foreach (range(0, 9) as $i) {
            $status = $statuses[$i % 5];
            $loan = LoanRequest::factory()->for($applicants[$i % 2])->create([
                'status' => $status,
                'approved_by' => $status === 'pending' ? null : $admin->id,
                'approved_at' => $status === 'pending' ? null : now()->subDays(20),
                'remarks' => $status === 'pending' ? null : ($status === 'rejected' ? 'Insufficient income proof.' : 'Documents verified.'),
            ]);
            if ($status === 'approved') {
                foreach ([1, 2] as $n) {
                    $loan->repayments()->create(['amount' => $loan->amount / 10, 'paid_on' => now()->subDays(10 * (3 - $n)), 'reference_no' => "REF-{$loan->id}-{$n}"]);
                }
            }
            if ($status === 'closed') {
                $loan->repayments()->create(['amount' => $loan->amount, 'paid_on' => now()->subDays(3), 'reference_no' => "REF-{$loan->id}-F"]);
            }
        }
    }
}
