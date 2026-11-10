<?php
namespace App\Http\Controllers;
use App\Models\{Student,User};
use App\Services\QuotaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Validator};
class StudentController extends Controller {
 public function index(){ $u=request()->user();abort_if($u->isStudent()&&!$u->isPlatformAdmin(),403);$students=$u->isPlatformAdmin()?Student::query():$u->organisationStudents();return view('students.index',['students'=>$students->latest()->paginate(20)]); }
 public function create(){abort_if(request()->user()->isStudent()&&!request()->user()->isPlatformAdmin(),403);return view('students.form',['student'=>new Student]);}
 public function store(Request $request){
  $data=$request->validate($this->rules($request));
  DB::transaction(function()use($request,$data){$owner=User::whereKey($request->user()->organisationOwnerId())->lockForUpdate()->firstOrFail();app(QuotaService::class)->assertStudents($owner,1);Student::create($data+['user_id'=>$owner->id]);},3);
  return redirect()->route('students.index')->with('success','Student created.');
 }
 public function edit(Student $student){$this->own($student);return view('students.form',compact('student'));}
 public function update(Request $request,Student $student){$this->own($student);$student->update($request->validate($this->rules($request)));return redirect()->route('students.index')->with('success','Student updated.');}
 public function destroy(Student $student){$this->own($student);$student->delete();return back()->with('success','Student deleted.');}
 public function import(Request $request){
  abort_unless($request->user()->hasFeature('bulk_student_import'),403);
  $request->validate(['student_file'=>'required|file|max:5120|extensions:csv,txt']);
  $stream=fopen($request->file('student_file')->getRealPath(),'rb');$records=[];$rowNumber=1;
  try{
   $header=fgetcsv($stream);abort_if(!$header,422,'The CSV file is empty.');
   $header=array_map(fn($value)=>strtolower(trim((string)$value)), $header);$header[0]=preg_replace('/^\xEF\xBB\xBF/','',$header[0]);
   abort_if(!in_array('name',$header,true)||count($header)!==count(array_unique($header))||in_array('',$header,true),422,'CSV needs unique named columns including name.');
   while(($row=fgetcsv($stream))!==false){$rowNumber++;if($row===[null])continue;
    abort_if(count($row)!==count($header),422,'CSV row '.$rowNumber.' has a different number of fields from its header.');
    $record=array_combine($header,$row);$validator=Validator::make($record,$this->rules($request));
    abort_if($validator->fails(),422,'CSV row '.$rowNumber.': '.$validator->errors()->first());$records[]=$validator->validated();
    abort_if(count($records)>5000,422,'Import up to 5000 students at a time.');
   }
  } finally {fclose($stream);}
  abort_if(!$records,422,'The CSV contains no student rows.');
  DB::transaction(function()use($request,$records){$owner=User::whereKey($request->user()->organisationOwnerId())->lockForUpdate()->firstOrFail();app(QuotaService::class)->assertStudents($owner,count($records));foreach($records as $record)Student::create($record+['user_id'=>$owner->id]);},3);
  return back()->with('success',count($records).' students imported.');
 }
 public function template(){return response("name,roll_number,grade,parent_name,parent_email,parent_mobile,alternate_contact\nAarav Sharma,101,10,,,,\n",200,['Content-Type'=>'text/csv','Content-Disposition'=>'attachment; filename="student-import-template.csv"']);}
 private function rules(Request $request):array{
  $rules=['name'=>'required|string|max:100','roll_number'=>'nullable|string|max:50','grade'=>'nullable|string|max:50'];
  if($request->user()->hasFeature('parent_details'))$rules+=['parent_name'=>'nullable|string|max:100','parent_email'=>'nullable|email|max:150','parent_mobile'=>'nullable|string|max:20','alternate_contact'=>'nullable|string|max:20'];return $rules;
 }
 private function own(Student $student):void{abort_unless($student->user_id===request()->user()->organisationOwnerId()||request()->user()->isPlatformAdmin(),403);}
}