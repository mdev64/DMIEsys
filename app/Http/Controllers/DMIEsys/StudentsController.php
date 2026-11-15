<?php

namespace App\Http\Controllers\DMIEsys;

use App\Http\Controllers\Controller;
use App\Imports\ExcelImport;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class StudentsController extends Controller
{
    /**
     * @return View
     */
    public function create()
    {
        if ($this->can('add student')) {
            $enter_student_bulk_auth = true;
        } else {
            abort(404);
        }
        $batches = [];
        for ($i = -1; $i < 5; $i++) {
            array_push($batches, 'E'.(date('Y', time()) + ($i - 2005)));
        }

        return view('dmiesys.academics.manage-students', compact('enter_student_bulk_auth', 'batches'));
    }

    /**
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        abort_unless(($this->can('add student')), 404);

        Validator::make($request->all(), [
            'student_list' => 'required|mimes:xls,xlsx|max:5120',
            'batch' => 'required|starts_with:E',
        ])->validate();

        $file = $request->file('student_list');
        $students = (new ExcelImport)->toArray($file);

        foreach ($students[0] as $student) {
            Validator::make($student, [
                'reg_no' => 'required|starts_with:'.$request['batch'].'|size:6',
                'name' => 'required|string',
                'phone' => 'nullable|max:15',
                'email' => 'required|email',
            ])->validate();
        }

        $new_records = 0;

        if (count($students[0]) > 0) {
            $existingStudentIds = Student::whereIn('student_id', array_column($students[0], 'reg_no'))
                ->pluck('student_id')
                ->map(fn (string $studentId) => strtoupper($studentId))
                ->flip();

            DB::beginTransaction();
            foreach ($students[0] as $student) {
                if (! $existingStudentIds->has(strtoupper($student['reg_no']))) {
                    $existingStudentIds->put(strtoupper($student['reg_no']), true);
                    try {
                        Student::create([
                            'student_id' => $student['reg_no'],
                            'name' => $student['name'],
                            'batch' => $request['batch'],
                            'phone' => $student['phone'],
                            'email' => $student['email'],
                        ]);
                        $new_records++;
                    } catch (\Throwable $th) {
                        DB::rollback();
                        $this->failed($th);

                        return back()->withErrors('There was a problem, please check the logs to see more about this!');
                    }
                }
            }
            DB::commit();
            $this->passed($new_records.' new students of '.$request['batch'].' batch were added');
        }

        return back()->with('toast_success', $new_records.' new students were added successfully!');
    }
}
