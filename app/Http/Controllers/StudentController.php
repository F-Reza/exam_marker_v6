<?php
namespace App\Http\Controllers;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File;
class StudentController extends Controller {
 public function index(){ $u=request()->user(); abort_if($u->isStudent(),403); return view('students.index',['students'=>$u->organisationStudents()->latest()->paginate(20)]); }
 public function create(){ abort_if(request()->user()->isStudent(),403); return view('students.form',['student'=>new Student]); }
 public function store(Request $request){$this->checkLimit($request,1);$request->user()->organisationStudents()->create($this->data($request)+['user_id'=>$request->user()->organisationOwnerId()]);return redirect()->route('students.index')->with('success','Student created.');}
 public function edit(Student $student){$this->own($student);return view('students.form',compact('student'));}
 public function update(Request $request,Student $student){$this->own($student);$student->update($this->data($request));return redirect()->route('students.index')->with('success','Student updated.');}
 public function destroy(Student $student){$this->own($student);$student->delete();return back()->with('success','Student deleted.');}
 public function import(Request $request){
  abort_unless($request->user()->hasFeature('bulk_student_import'),403); $request->validate(['student_file'=>['required',File::types(['csv','txt'])->max(5*1024)]]);
  $rows=array_map('str_getcsv',file($request->file('student_file')->getRealPath(),FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)); if(!$rows) return back()->with('error','The import file is empty.');
  $header=array_map(fn($x)=>strtolower(trim($x)),$rows[0]); $required=['name']; abort_unless(!array_diff($required,$header),422,'CSV must contain a name column.');
  $records=[]; foreach(array_slice($rows,1) as $row){$r=array_combine($header,array_pad($row,count($header),null));if(!trim($r['name']??''))continue;$records[]=$r;}
  $this->checkLimit($request,count($records)); $owner=$request->user()->organisationOwnerId(); $parentAllowed=$request->user()->hasFeature('parent_details');
  foreach($records as $r) Student::create(['user_id'=>$owner,'name'=>trim($r['name']),'roll_number'=>$r['roll_number']??null,'grade'=>$r['grade']??null,'parent_name'=>$parentAllowed?($r['parent_name']??null):null,'parent_email'=>$parentAllowed?($r['parent_email']??null):null,'parent_mobile'=>$parentAllowed?($r['parent_mobile']??null):null,'alternate_contact'=>$parentAllowed?($r['alternate_contact']??null):null]);
  return back()->with('success',count($records).' students imported.');
 }
 public function template(){
  $csv="name,roll_number,grade,parent_name,parent_email,parent_mobile,alternate_contact\nAarav Sharma,101,10,,,,\n";
  return response($csv,200,['Content-Type'=>'text/csv','Content-Disposition'=>'attachment; filename="student-import-template.csv"']);
 }
 private function data(Request $r){$rules=['name'=>'required|string|max:100','roll_number'=>'nullable|string|max:50','grade'=>'nullable|string|max:50'];if($r->user()->hasFeature('parent_details'))$rules+=['parent_name'=>'nullable|string|max:100','parent_email'=>'nullable|email|max:150','parent_mobile'=>'nullable|string|max:20','alternate_contact'=>'nullable|string|max:20'];return $r->validate($rules);}
 private function checkLimit(Request $r,int $incoming){$limit=$r->user()->limit('student_limit',1);abort_if($r->user()->organisationStudents()->count()+$incoming>$limit,422,'Student limit reached for this plan.');}
 private function own(Student $s){abort_unless($s->user_id===request()->user()->organisationOwnerId()||request()->user()->is_admin,403);}
}
