<?php

namespace App\Http\Controllers\DMIEsys;

use App\Http\Controllers\Controller;
use App\Imports\ExcelImport;
use App\Mail\SetStudentAdvisor;
use App\Models\Student;
use App\Models\User;
use App\Traits\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class StudentAdvisorController extends Controller
{
    use Notify;

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index()
    {
        $user = User::find(auth()->user()->id);

        if ($user->hasRole('Head of the Department')) {
            $advisory_commitment_auth = true;
        } else {
            abort(404);
        }

        $lecturers = User::role(['Head of the Department', 'Professor', 'Senior Lecturer', 'Lecturer', 'Contract Basis Lecturer', 'Probationary Lecturer', 'Visiting Lecture', 'Temporary Lecturer'])->active()->with('getStudents')->get(['id', 'title', 'name']);

        return view('dmiesys.academics.student-affairs', compact('advisory_commitment_auth', 'lecturers'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View
     */
    public function create()
    {
        if ($this->can('add student advisor')) {
            $enter_student_advisor_single_auth = true;
        } else {
            abort(404);
        }

        return view('dmiesys.academics.student-affairs', compact('enter_student_advisor_single_auth'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function createBulk()
    {
        if ($this->can('add student advisor')) {
            $enter_student_advisor_bulk_auth = true;
        } else {
            abort(404);
        }

        return view('dmiesys.academics.student-affairs', compact('enter_student_advisor_bulk_auth'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        abort_unless(($this->can('add student advisor')), 404);

        Validator::make($request->all(), [
            'batch' => 'required|starts_with:E',
            'student_advisor_list' => 'required|mimes:xlsx|max:5120',
        ])->validate();

        $file = $request->file('student_advisor_list');

        $filename = $file->getClientOriginalName();
        if (! (Str::contains($filename, 'v1.0'))) {
            return back()->withErrors('You upload an older version of a template. Please download and use the latest template version.');
        } elseif (! (Str::contains($filename, $request->batch))) {
            return back()->withErrors('It seems you have uploaded a wrong file. (Complete the blank spaces in the file name if you did not do it previously.)');
        }

        $advisors = Excel::toArray(new ExcelImport, $file);
        $advisors = $advisors[0];

        // validate all the students in the system
        // validate advisor's email address
        foreach ($advisors as $advisor) {
            Validator::make($advisor, [
                'student_reg_no' => 'required',
                'advisor_email' => 'required',
            ])->validate();
        }

        $students = Student::whereIn('student_id', array_column($advisors, 'student_reg_no'))->get()
            ->keyBy(fn (Student $student) => Str::upper($student->student_id));
        $lecturerIds = User::whereIn('email', array_column($advisors, 'advisor_email'))->get(['id', 'email'])
            ->mapWithKeys(fn (User $lecturer) => [Str::lower($lecturer->email) => $lecturer->id]);

        foreach ($advisors as $advisor) {
            if (! $students->has(Str::upper($advisor['student_reg_no']))) {
                return back()->withErrors('The student '.$advisor['student_reg_no'].' is not in the system.');
            }
            if (! $lecturerIds->has(Str::lower($advisor['advisor_email']))) {
                return back()->withErrors('There is no user with the email '.$advisor['advisor_email'].'.');
            }
        }

        DB::beginTransaction();
        try {
            foreach ($advisors as $advisor) {
                $student = $students->get(Str::upper($advisor['student_reg_no']));
                $student->student_advisor = $lecturerIds->get(Str::lower($advisor['advisor_email']));
                $student->last_advisory_report = now()->subMonths(2)->toDateString();
                $student->save();
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->failed($th);

            return back()->withErrors('Something went wrong. Please check your data.');
        }
        DB::commit();

        return back()->with('toast_success', "Students' advisory list updated successfully!");

        // dd($advisors);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id)
    {
        abort_unless(($this->can('add student advisor')), 404);

        $validated = $request->validate([
            'student_id' => 'required|exists:students',
            'advisor' => 'required|exists:users,id',
            'send-a-mail' => 'sometimes|in:on',
        ]);

        $advisor = User::findOrFail($validated['advisor']);

        try {
            $student = Student::findOrFail($validated['student_id']);
            $student->student_advisor = $validated['advisor'];
            $student->last_advisory_report = now()->subMonths(2)->toDateString();
            $student->save();

            $body = [
                'advisor' => $advisor['title'].' '.$advisor['name'],
                'email' => $advisor['email'],
                'phone' => $advisor['phone'],
                'student' => $student['name'],
            ];
            $message = 'You have been appointed as an academic advisor for <b>'.$student->student_id.'</b>';
            $this->notifyUser($advisor, $message);
            if ($request->has('send-a-mail')) {
                Mail::to($student->email)->send(new SetStudentAdvisor($body));
            }
            $this->passed('Set a advisor for '.$validated['student_id']);

            return back()->with('toast_success', 'Student record updated!');
        } catch (\Throwable $th) {
            return back()->withErrors('Something went wrong!');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
