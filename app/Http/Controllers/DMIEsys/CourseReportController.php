<?php

namespace App\Http\Controllers\DMIEsys;

use App\Http\Controllers\Controller;
use App\Models\AssignmentMark;
use App\Models\Course;
use App\Models\EndMark;
use App\Models\GeneralCourse;
use App\Models\MidMark;
use App\Models\PracticalMark;
use App\Models\QuizMark;
use App\Models\StudentEnrollment;
use App\Models\TutorialMark;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CourseReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index()
    {
        if ($this->can('see ilo achievement')) {
            $course_ilo_auth = true;
        } else {
            abort(404);
        }

        // $courses = Course::where('status', '=', 'Complete')->paginate(2);

        return view('dmiesys.academics.student-attainment', compact('course_ilo_auth'));
    }

    /**
     * Display the specified resource.
     *
     * @return RedirectResponse|View
     */
    public function show(string $course_id)
    {
        abort_unless(($this->can('see ilo achievement')), 404);

        try {
            // return error is course id not complete
            $course = Course::where('course_id', '=', $course_id)->first();
            if (! $course || $course->status != 'Complete') {
                return redirect(route('course-report.index'))->withErrors(['This course is not complete yet or not exist.']);
            }

            // get student list
            $students = StudentEnrollment::where('course_id', '=', $course_id)->get();

            $basecourse = GeneralCourse::where('course_code', '=', $course->course_code)->first();
            $studentmarks = [];

            // create allmarks array
            $allmarks = [];
            for ($i = 0; $i < 101; $i++) {
                $allmarks[$i] = null;
            }
            $maxmark = 0;
            $minmark = 100;
            $avgmark = 0;
            $totalmarks = 0;
            $lomap = [];
            $lomaplbl = [];
            for ($i = 0; $i < $basecourse->total_los; $i++) {
                $lomap[$i] = 0;
                $lomaplbl[$i] = 'LO '.($i + 1);
            }

            // load every mark of the course once, instead of six queries per student
            $loTotals = [];
            foreach ([AssignmentMark::class, TutorialMark::class, QuizMark::class, PracticalMark::class, MidMark::class, EndMark::class] as $markModel) {
                $courseMarks = $markModel::query()->where('course_id', '=', $course_id)->toBase()
                    ->get(['student_id', 'mark', 'lo_1_ref', 'lo_2_ref', 'lo_3_ref', 'lo_4_ref', 'lo_5_ref', 'lo_6_ref']);
                foreach ($courseMarks as $courseMark) {
                    for ($i = 1; $i < 7; $i++) {
                        $loTotals[$courseMark->student_id][$i] = ($loTotals[$courseMark->student_id][$i] ?? 0) + $courseMark->mark * $courseMark->{'lo_'.$i.'_ref'} / 100;
                    }
                }
            }

            // calculate final marks
            foreach ($students as $student) {
                for ($i = 1; $i < 7; $i++) {
                    $student['lo'.$i] = $loTotals[$student->student_id][$i] ?? 0;
                }

                $student['total'] = $student['lo1'] + $student['lo2'] + $student['lo3'] + $student['lo4'] + $student['lo5'] + $student['lo6'];

                $student['lo1'] = round($student['lo1'], 0);
                $student['lo2'] = round($student['lo2'], 0);
                $student['lo3'] = round($student['lo3'], 0);
                $student['lo4'] = round($student['lo4'], 0);
                $student['lo5'] = round($student['lo5'], 0);
                $student['lo6'] = round($student['lo6'], 0);
                $student['total'] = round($student['total'], 0);

                if ($student['total'] != 0) {
                    array_push($studentmarks, [
                        'student_id' => $student->student_id,
                        'name' => $student['name'],
                        'lo1' => $student['lo1'],
                        'lo2' => $student['lo2'],
                        'lo3' => $student['lo3'],
                        'lo4' => $student['lo4'],
                        'lo5' => $student['lo5'],
                        'lo6' => $student['lo6'],
                        'total' => $student['total'],
                    ]);
                }

                for ($i = 0; $i < $basecourse->total_los; $i++) {
                    if ($basecourse['lo_'.($i + 1)] <= $student['lo'.($i + 1)] * 2) {
                        $lomap[$i]++;
                    }
                }

                if ($student['total'] > $maxmark) {
                    $maxmark = $student['total'];
                }

                if ($student['total'] < $minmark) {
                    $minmark = $student['total'];
                }
                $totalmarks += $student['total'];

                $allmarks[(int) ($student['total'])]++;
            }

            if (count($studentmarks) > 0) {
                $avgmark = round($totalmarks / count($studentmarks), 1);
            }

            if ($minmark > 0) {
                $firstindex = $minmark - 1;
            } else {
                $firstindex = $minmark;
            }

            if ($maxmark < 100) {
                $lastindex = $maxmark + 1;
            } else {
                $lastindex = $maxmark;
            }

            for ($i = $firstindex; $i <= $lastindex; $i++) {
                if ($allmarks[$i] == null) {
                    $allmarks[$i] = 0;
                }
            }
        } catch (\Throwable $th) {
            $this->failed($th);

            return back()->withErrors('There was a problem, please check the logs to see more about this!');
        }

        $this->passed($course_id.' course report was viewed');

        return view('dmiesys.academics.view-course-report', compact('course', 'basecourse', 'studentmarks', 'allmarks', 'maxmark', 'minmark', 'avgmark', 'lomap', 'lomaplbl'));
    }
}
