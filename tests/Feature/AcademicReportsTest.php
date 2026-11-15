<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\AssignmentMark;
use App\Models\Course;
use App\Models\GeneralCourse;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class AcademicReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_report_renders_with_a_constant_number_of_queries(): void
    {
        $user = $this->createUser('see ilo achievement');
        $this->actingAs($user);
        $this->seedBaseCourse();
        $this->seedCourse('PR101-18', 'Complete');

        $this->seedStudent('E18000');
        $this->enroll('E18000', 'PR101-18');
        foreach (['E18001' => 80, 'E18002' => 50, 'E18003' => 60, 'E18004' => 70] as $studentId => $mark) {
            $this->seedStudent($studentId);
            $this->enroll($studentId, 'PR101-18');
            $this->seedMark($studentId, 'PR101-18', $mark);
        }

        DB::enableQueryLog();
        $response = $this->get('/dmiesys/student-attainment/course-report/PR101-18');
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $response->assertOk();
        $response->assertViewHas('maxmark', 80.0);
        $response->assertViewHas('minmark', 0.0);
        $response->assertViewHas('avgmark', 65.0);
        $response->assertViewHas('studentmarks', fn (array $studentmarks) => count($studentmarks) === 4);
        $response->assertViewHas('lomap', [4, 4]);
        $this->assertLessThan(20, $queryCount);
    }

    public function test_course_report_of_an_incomplete_course_redirects_back_to_the_selection(): void
    {
        $user = $this->createUser('see ilo achievement');
        $this->actingAs($user);
        $this->seedBaseCourse();
        $this->seedCourse('PR101-18', 'Ongoing');

        $this->get('/dmiesys/student-attainment/course-report/PR101-18')
            ->assertRedirect(route('course-report.index'))
            ->assertSessionHasErrors();
    }

    public function test_student_report_renders_for_a_student_with_marks(): void
    {
        $user = $this->createUser('see attainment report');
        $this->actingAs($user);
        $this->seedBaseCourse();
        $this->seedCourse('PR101-18', 'Complete');
        $this->seedStudent('E18001');
        $this->seedMark('E18001', 'PR101-18', 80);

        $response = $this->get('/dmiesys/student-attainment/student-report/E18001');

        $response->assertOk();
        $response->assertViewHas('coursedetails', function (array $coursedetails) {
            return count($coursedetails) === 1
                && $coursedetails[0]['course_id'] === 'PR101-18'
                && $coursedetails[0]['final_mark'] == 80
                && $coursedetails[0]['passed_los'] === 2;
        });
        $response->assertViewHas('summary', fn (array $summary) => $summary['passed_cc'] === 1 && $summary['angle_c'] == 180);
    }

    public function test_marks_sheet_upload_stores_marks_and_completes_the_course(): void
    {
        $user = $this->createUser('add mark');
        $this->actingAs($user);
        $this->seedBaseCourse();
        $this->seedCourse('PR101-18', 'Ongoing');
        $this->seedStudent('E18001');
        $this->seedStudent('E18002');

        $this->post('/dmiesys/manage-marks/marks', ['mark_sheet' => $this->marksSheet(['E18001' => 40, 'E18002' => 25])])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('toast_success');

        $this->assertSame(2, AssignmentMark::count());
        $mark = AssignmentMark::where('student_id', 'E18001')->first();
        $this->assertEquals(80, $mark->mark);
        $this->assertEquals(60, $mark->lo_1_ref);
        $this->assertEquals($user->id, $mark->created_by);
        $this->assertNotNull($mark->created_at);
        $this->assertSame(2, StudentEnrollment::where('course_id', 'PR101-18')->count());
        $this->assertSame(2, Student::where('hasMarks', 1)->count());
        $this->assertSame('Complete', Course::find('PR101-18')->status);
    }

    public function test_marks_sheet_with_an_unknown_student_is_rejected_without_saving_anything(): void
    {
        $user = $this->createUser('add mark');
        $this->actingAs($user);
        $this->seedBaseCourse();
        $this->seedCourse('PR101-18', 'Ongoing');
        $this->seedStudent('E18001');

        $this->post('/dmiesys/manage-marks/marks', ['mark_sheet' => $this->marksSheet(['E18001' => 40, 'E18999' => 25])])
            ->assertSessionHasErrors('student_id');

        $this->assertSame(0, AssignmentMark::count());
        $this->assertSame(0, StudentEnrollment::count());
        $this->assertSame('Ongoing', Course::find('PR101-18')->status);
    }

    public function test_marks_sheet_with_a_zero_maximum_mark_rolls_everything_back(): void
    {
        $user = $this->createUser('add mark');
        $this->actingAs($user);
        $this->seedBaseCourse();
        $this->seedCourse('PR101-18', 'Ongoing');
        $this->seedStudent('E18001');

        $this->post('/dmiesys/manage-marks/marks', ['mark_sheet' => $this->marksSheet(['E18001' => 40], 0)])
            ->assertSessionHasErrors();

        $this->assertSame(0, AssignmentMark::count());
        $this->assertSame(0, StudentEnrollment::count());
        $this->assertSame(0, Student::where('hasMarks', 1)->count());
        $this->assertSame('Ongoing', Course::find('PR101-18')->status);
    }

    private function seedBaseCourse(): void
    {
        GeneralCourse::create([
            'course_code' => 'PR101', 'course_name' => 'Manufacturing', 'credit' => 3, 'genre' => 'cc',
            'lo_1' => 60, 'lo_2' => 40, 'total_los' => 2, 'total_assignments' => 1,
        ]);
        Assignment::create([
            'assignment_code' => 'assignment1', 'course_code' => 'PR101', 'assignment_name' => 'Assignment 1',
            'lo_1' => 60, 'lo_2' => 40, 'lo_3' => 0, 'lo_4' => 0, 'lo_5' => 0, 'lo_6' => 0,
        ]);
    }

    private function seedCourse(string $courseId, string $status): void
    {
        Course::create([
            'course_id' => $courseId, 'course_code' => 'PR101', 'course_name' => 'Manufacturing', 'year' => '2026',
            'semester' => 1, 'batch' => 'E18', 'period' => 'Jan - May', 'status' => $status,
            'coordinator' => 'A', 'coordinator_username' => 'a', 'moderator' => 'B', 'moderator_username' => 'b',
            'secondexaminer' => 'C', 'secondexaminer_username' => 'c', 'instructorincharge' => 'D', 'instructorincharge_username' => 'd',
        ]);
    }

    private function seedStudent(string $studentId): void
    {
        Student::create(['student_id' => $studentId, 'name' => 'Student '.$studentId, 'email' => $studentId.'@email.com', 'batch' => 'E18']);
    }

    private function enroll(string $studentId, string $courseId): void
    {
        StudentEnrollment::create(['student_id' => $studentId, 'name' => 'Student '.$studentId, 'course_id' => $courseId]);
    }

    private function seedMark(string $studentId, string $courseId, int $mark): void
    {
        AssignmentMark::create([
            'student_id' => $studentId, 'assignment_code' => 'assignment1', 'course_id' => $courseId, 'course_code' => 'PR101',
            'mark' => $mark, 'lo_1_ref' => 60, 'lo_2_ref' => 40, 'lo_3_ref' => 0, 'lo_4_ref' => 0, 'lo_5_ref' => 0, 'lo_6_ref' => 0,
        ]);
    }

    /**
     * Builds a marks sheet in the layout of the official template: the information
     * table lives in columns A-B and the students start at row 4 from column D.
     *
     * @param  array<string, int>  $studentMarks
     */
    private function marksSheet(array $studentMarks, int $maximumMark = 50): UploadedFile
    {
        $rows = array_fill(0, 11, array_fill(0, 6, null));
        $information = [
            1 => ['Course Code', 'PR101'], 2 => ['Course ID', 'PR101-18'], 3 => ['Year', 2026], 4 => ['Semester', 1],
            5 => ['Assignments', 100], 6 => ['Tutorials', 0], 7 => ['Quizzes', 0], 8 => ['Practicals', 0], 9 => ['Mid Exam', 0], 10 => ['End Exam', 0],
        ];
        $rows[0][0] = 'General Information';
        foreach ($information as $row => [$label, $value]) {
            $rows[$row][0] = $label;
            $rows[$row][1] = $value;
        }
        $rows[2][5] = $maximumMark;

        $row = 3;
        foreach ($studentMarks as $studentId => $mark) {
            $rows[$row][3] = $studentId;
            $rows[$row][4] = 'Student '.$studentId;
            $rows[$row][5] = $mark;
            $row++;
        }

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1', true);
        $path = tempnam(sys_get_temp_dir(), 'marks').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'marks.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
