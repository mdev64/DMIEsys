<?php

namespace App\Console\Commands;

use App\Mail\RemindToMeetStudent;
use App\Models\Student;
use App\Models\User;
use App\Traits\Notify;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class RemindAdvisorsToLogComments extends Command
{
    use Notify;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'RemindAdvisorsToLogComments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will check the student list and send a email to the advisors who did not meet their students for past 3 months. This will happens only in Tuesday.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $oneMonthAgo = now()->subMonth();
        $twoMonthsAgo = now()->subMonths(2);

        $studentsByAdvisor = Student::where('graduated', 0)
            ->whereNotNull('student_advisor')
            ->where('last_advisory_report', '<=', $oneMonthAgo)
            ->get()
            ->groupBy('student_advisor');

        $advisors = User::active()->whereIn('id', $studentsByAdvisor->keys())->get();

        foreach ($advisors as $advisor) {
            $students = $studentsByAdvisor->get($advisor->id);
            $students_older_than_6mo = $students
                ->filter(fn (Student $student) => Carbon::parse($student->last_advisory_report)->lte($twoMonthsAgo));

            $body = [
                'advisor' => $advisor->title.' '.$advisor->name,
                'students' => $students,
            ];

            Mail::to($advisor->email)->send(new RemindToMeetStudent($body));

            if ($students_older_than_6mo->isNotEmpty()) {
                $reg_no_list = '('.$students_older_than_6mo->pluck('student_id')->implode(',').')';

                $message = '<span class="bg-red-200 text-red-800 text-xs font-semibold mr-2 px-2.5 py-0.5 rounded dark:bg-red-200 dark:text-red-900">Needs Attention</span> '.$body['advisor'].' did not meet with their students '.$reg_no_list.' or record anything for more than two months. Please request that they meet with their students.';
                $this->notifyHOD($message);
            }
        }
    }
}
