<?php

namespace App\Http\Controllers\DMIEsys;

use App\Http\Controllers\Controller;
use App\Imports\MarksImport;
use App\Models\Assignment;
use App\Models\AssignmentMark;
use App\Models\Course;
use App\Models\EndExamQuestion;
use App\Models\EndMark;
use App\Models\GeneralCourse;
use App\Models\MidExamQuestion;
use App\Models\MidMark;
use App\Models\Practical;
use App\Models\PracticalMark;
use App\Models\Quiz;
use App\Models\QuizMark;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Tutorial;
use App\Models\TutorialMark;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class MarksController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index()
    {
        if ($this->can('delete mark')) {
            $delete_marks_auth = true;
        } else {
            abort(404);
        }

        $courses = Course::where('created_at', '>', date('Y-m-d', strtotime('-3 years')))->where('status', 'Complete')->paginate(10);

        // dd($courses);

        return view('dmiesys.academics.manage-marks', compact('delete_marks_auth', 'courses'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View
     */
    public function create()
    {
        if ($this->can('add mark')) {
            $manage_marks_auth = true;
        } else {
            abort(404);
        }

        return view('dmiesys.academics.manage-marks', compact('manage_marks_auth'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        abort_unless(($this->can('add mark')), 404);

        Validator::make($request->all(), [
            'mark_sheet' => 'required|mimes:xlsx|max:5120',
        ])->validate();

        $file = $request->file('mark_sheet');
        $marks = Excel::toArray(new MarksImport, $file);
        $marks = $marks[0];

        if (! isset($marks[10])) {
            return back()->withErrors(['The uploaded file does not match the marks sheet template.']);
        }

        // Validating General Information table
        $info = [];
        for ($row = 1; $row <= 10; $row++) {
            if ($row < 3 && ($marks[$row][1] ?? null) == null) {
                return back()->withErrors(['Empty cell in Information table: Row '.($row + 1)]);
            }
            $info[$marks[$row][0] ?? ''] = $marks[$row][1] ?? null;
        }

        // Validating course code and course id
        Validator::make($info, [
            'Course Code' => 'required|exists:general_courses,course_code',
            'Course ID' => 'required|exists:courses,course_id',
        ])->validate();

        // Validating course status
        $course = Course::where('course_id', '=', $info['Course ID'])->first();
        if ($course->status == 'Complete') {
            return back()->withErrors(['This course was marked Complete and you can not upload another marks sheet without deleting the old marks']);
        }

        // Validating sum of Information Table
        $componentWeights = 0;
        foreach (['Assignments', 'Tutorials', 'Quizzes', 'Practicals', 'Mid Exam', 'End Exam'] as $componentName) {
            $componentWeights += (float) ($info[$componentName] ?? 0);
        }
        if ($componentWeights != 100) {
            return back()->withErrors(['Sum of the components in Information table not equal to 100']);
        }

        // Collecting students and get last row number
        $studentIds = [];
        $lastrownumber = 3;
        for ($row = 3; $row <= 500; $row++) {
            if (($marks[$row][3] ?? null) == null) {
                break;
            }
            $studentIds[] = strtoupper((string) $marks[$row][3]);
            $lastrownumber = $row + 1;
        }

        if ($studentIds === []) {
            return back()->withErrors(['There are no students in the uploaded marks sheet.']);
        }

        // Validating all the students with a single query
        $knownStudentIds = Student::whereIn('student_id', $studentIds)->pluck('student_id')
            ->map(fn (string $studentId) => strtoupper($studentId))->all();
        $unknownStudentIds = array_diff($studentIds, $knownStudentIds);
        if ($unknownStudentIds !== []) {
            return back()->withErrors(['student_id' => 'The selected student id is invalid: '.implode(', ', $unknownStudentIds)]);
        }

        // get the course informations
        $generelcourseinfo = GeneralCourse::where('course_code', '=', $info['Course Code'])->first();

        $now = now();
        $userId = auth()->id();

        DB::beginTransaction();

        try {
            // update student table
            Student::whereIn('student_id', $studentIds)->update(['hasMarks' => 1]);

            // update student enrollement table
            $enrollments = [];
            for ($row = 3; $row < $lastrownumber; $row++) {
                $enrollments[] = [
                    'student_id' => $marks[$row][3],
                    'name' => $marks[$row][4],
                    'course_id' => $info['Course ID'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            foreach (array_chunk($enrollments, 500) as $chunk) {
                StudentEnrollment::insert($chunk);
            }

            // add marks to each of the marks tables
            foreach ($this->markComponents() as $component) {
                $totalItems = (int) $generelcourseinfo->{$component['total']};
                if (! $totalItems) {
                    continue;
                }

                $definitions = $component['definition']::where('course_code', '=', $info['Course Code'])
                    ->get()->keyBy($component['code_column']);

                $markRows = [];
                for ($i = 1; $i <= $totalItems; $i++) {
                    $code = $component['prefix'].$i;
                    $definition = $definitions->get($code);
                    if (! $definition) {
                        throw new \RuntimeException($code.' is not defined for the base course '.$info['Course Code']);
                    }
                    $column = $component['offset'] + $i;

                    for ($row = 3; $row < $lastrownumber; $row++) {
                        $markRows[] = [
                            'student_id' => $marks[$row][3],
                            $component['code_column'] => $code,
                            'course_id' => $info['Course ID'],
                            'course_code' => $info['Course Code'],
                            'mark' => $marks[$row][$column] * 100 / $marks[2][$column],
                            'lo_1_ref' => $definition->lo_1,
                            'lo_2_ref' => $definition->lo_2,
                            'lo_3_ref' => $definition->lo_3,
                            'lo_4_ref' => $definition->lo_4,
                            'lo_5_ref' => $definition->lo_5,
                            'lo_6_ref' => $definition->lo_6,
                            'created_by' => $userId,
                            'updated_by' => $userId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                foreach (array_chunk($markRows, 500) as $chunk) {
                    $component['marks']::insert($chunk);
                }
            }

            // mark course as complete
            $course->status = 'Complete';
            $course->assignmentweight = $marks[5][1] ?? 0;
            $course->tutorialweight = $marks[6][1] ?? 0;
            $course->quizweight = $marks[7][1] ?? 0;
            $course->practicalweight = $marks[8][1] ?? 0;
            $course->midexamweight = $marks[9][1] ?? 0;
            $course->endexamweight = $marks[10][1] ?? 0;
            $course->save();
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->failed($th);

            return back()->withErrors('System failed to enter the marks. Please check your data again.');
        }

        DB::commit();
        $this->passed($info['Course ID'].' Marks were added.');

        return back()->with('toast_success', $info['Course ID'].' marks updated successfully!');
    }

    /**
     * The mark sheet layout: which base course counter, definition model, marks model
     * and first column (zero based, exclusive) belongs to each assessment component.
     *
     * @return array<int, array{total: string, definition: class-string, marks: class-string, code_column: string, prefix: string, offset: int}>
     */
    private function markComponents(): array
    {
        return [
            ['total' => 'total_assignments', 'definition' => Assignment::class, 'marks' => AssignmentMark::class, 'code_column' => 'assignment_code', 'prefix' => 'assignment', 'offset' => 4],
            ['total' => 'total_tutorials', 'definition' => Tutorial::class, 'marks' => TutorialMark::class, 'code_column' => 'tutorial_code', 'prefix' => 'tutorial', 'offset' => 11],
            ['total' => 'total_quizzes', 'definition' => Quiz::class, 'marks' => QuizMark::class, 'code_column' => 'quiz_code', 'prefix' => 'quiz', 'offset' => 17],
            ['total' => 'total_practicals', 'definition' => Practical::class, 'marks' => PracticalMark::class, 'code_column' => 'practical_code', 'prefix' => 'practical', 'offset' => 20],
            ['total' => 'total_midquestions', 'definition' => MidExamQuestion::class, 'marks' => MidMark::class, 'code_column' => 'mid_question_code', 'prefix' => 'midquestion', 'offset' => 28],
            ['total' => 'total_endquestions', 'definition' => EndExamQuestion::class, 'marks' => EndMark::class, 'code_column' => 'end_question_code', 'prefix' => 'endquestion', 'offset' => 33],
        ];
    }

    /**
     * Delete the specified resource in storage.
     *
     * @return RedirectResponse
     */
    public function destroy(string $course_id)
    {
        abort_unless(($this->can('delete mark')), 404);

        DB::beginTransaction();
        try {
            AssignmentMark::where('course_id', $course_id)->delete();
            TutorialMark::where('course_id', $course_id)->delete();
            PracticalMark::where('course_id', $course_id)->delete();
            QuizMark::where('course_id', $course_id)->delete();
            MidMark::where('course_id', $course_id)->delete();
            EndMark::where('course_id', $course_id)->delete();
            StudentEnrollment::where('course_id', $course_id)->delete();
        } catch (\Throwable $th) {
            $this->failed($th);
            DB::rollback();

            return back()->withErrors('There was a problem, please check the logs to see more about this!');
        }

        try {
            $course = Course::where('course_id', '=', $course_id)->first();
            $course->assignmentweight = 0;
            $course->tutorialweight = 0;
            $course->practicalweight = 0;
            $course->quizweight = 0;
            $course->midexamweight = 0;
            $course->endexamweight = 0;
            $course->status = 'Ongoing';
            $course->save();
        } catch (\Throwable $th) {
            $this->failed($th);
            DB::rollback();

            return back()->withErrors('There was a problem, please check the logs to see more about this!');
        }

        $this->passed($course_id.' Marks deleted');

        DB::commit();

        return back()->with('toast_success', $course_id.' marks deleted successfully!');
    }
}
