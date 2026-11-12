<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PortalController extends Controller
{
    private array $modules = [
        'institusi' => ['table'=>'institutions','roles'=>['admin','institusi'],'title'=>'Institusi','fields'=>['name'=>'Nama institusi','type'=>'Jenis','description'=>'Deskripsi','website'=>'Situs web','verification_status'=>'Status verifikasi']],
        'akun' => ['table'=>'users','roles'=>['admin'],'title'=>'Akun pengguna','fields'=>['name'=>'Nama','email'=>'Email','role'=>'Peran (pelajar/institusi/admin)']],
        'keterampilan' => ['table'=>'skills','roles'=>['pelajar'],'title'=>'Keterampilan','fields'=>['name'=>'Nama keterampilan']],
        'kursus' => ['table'=>'courses','roles'=>['admin','institusi','pelajar'],'title'=>'Kursus','fields'=>['institution_id'=>'ID institusi (admin)','title'=>'Judul','category_id'=>'ID kategori','description'=>'Deskripsi','status'=>'Status (draft/published)','passing_score'=>'Nilai lulus']],
        'bootcamp' => ['table'=>'bootcamps','roles'=>['admin','institusi','pelajar'],'title'=>'Bootcamp','fields'=>['institution_id'=>'ID institusi (admin)','title'=>'Judul','category_id'=>'ID kategori','description'=>'Deskripsi','status'=>'Status (draft/published)','passing_score'=>'Nilai lulus']],
        'materi' => ['table'=>'materials','roles'=>['admin','institusi','pelajar'],'title'=>'Materi','fields'=>['program_type'=>'Jenis program (course/bootcamp)','program_id'=>'ID program','title'=>'Judul materi','body'=>'Isi materi','position'=>'Urutan']],
        'pendaftaran' => ['table'=>'enrollments','roles'=>['admin','institusi','pelajar'],'title'=>'Pendaftaran','fields'=>['program_type'=>'Jenis program','program_id'=>'ID program','status'=>'Status']],
        'progres' => ['table'=>'material_progress','roles'=>['pelajar','admin'],'title'=>'Progres belajar','fields'=>['material_id'=>'ID materi','percent'=>'Persentase (0-100)','completed'=>'Selesai (0/1)']],
        'catatan' => ['table'=>'material_notes','roles'=>['pelajar'],'title'=>'Catatan belajar','fields'=>['material_id'=>'ID materi','body'=>'Catatan']],
        'kuis' => ['table'=>'quizzes','roles'=>['admin','institusi','pelajar'],'title'=>'Kuis','fields'=>['material_id'=>'ID materi','title'=>'Judul','passing_score'=>'Nilai lulus']],
        'soal' => ['table'=>'questions','roles'=>['admin','institusi'],'title'=>'Soal kuis','fields'=>['quiz_id'=>'ID kuis','prompt'=>'Pertanyaan','options'=>'Pilihan (pisahkan dengan |)','correct_option'=>'Nomor jawaban benar (mulai 0)','points'=>'Poin']],
        'sertifikat' => ['table'=>'certificates','roles'=>['admin','pelajar'],'title'=>'Sertifikat','fields'=>['user_id'=>'ID pelajar','program_type'=>'Jenis program','program_id'=>'ID program']],
        'bookmark' => ['table'=>'bookmarks','roles'=>['pelajar'],'title'=>'Bookmark','fields'=>['program_type'=>'Jenis program','program_id'=>'ID program']],
        'forum' => ['table'=>'discussions','roles'=>['admin','pelajar'],'title'=>'Diskusi forum','fields'=>['title'=>'Judul','body'=>'Isi diskusi']],
        'komentar' => ['table'=>'comments','roles'=>['pelajar','admin'],'title'=>'Komentar','fields'=>['discussion_id'=>'ID diskusi','body'=>'Isi komentar']],
        'ulasan' => ['table'=>'reviews','roles'=>['pelajar','admin'],'title'=>'Ulasan dan rating','fields'=>['program_type'=>'Jenis program','program_id'=>'ID program','rating'=>'Rating (1-5)','body'=>'Ulasan']],
        'pengumuman' => ['table'=>'announcements','roles'=>['admin','institusi','pelajar'],'title'=>'Pengumuman','fields'=>['title'=>'Judul','body'=>'Isi','audience'=>'Audiens (all/pelajar/institusi)']],
        'laporan' => ['table'=>'reports','roles'=>['admin','pelajar'],'title'=>'Laporan konten','fields'=>['content_type'=>'Jenis konten','content_id'=>'ID konten','category'=>'Kategori laporan','description'=>'Deskripsi']],
    ];

    public function index()
    {
        $cards = collect($this->modules)->filter(fn ($m) => in_array(auth()->user()->role, $m['roles'], true));
        return view('dashboard', compact('cards'));
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate(['name'=>'required|string|max:120','headline'=>'nullable|string|max:180','bio'=>'nullable|string|max:5000','profile_public'=>'nullable|boolean','certificates_public'=>'nullable|boolean']);
        $data = $request->validate(['headline'=>'nullable|string|max:180','bio'=>'nullable|string|max:5000','profile_public'=>'nullable|boolean','certificates_public'=>'nullable|boolean']);
        auth()->user()->update($data + ['profile_public'=>$request->boolean('profile_public'),'certificates_public'=>$request->boolean('certificates_public')]);
        return back()->with('success','Profil berhasil diperbarui.');
    }

    public function updateInstitutionProfile(Request $request)
    {
        abort_unless(auth()->user()->isInstitution(),403);
        $data=$request->validate(['name'=>'required|string|max:160','type'=>'nullable|string|max:100','description'=>'nullable|string|max:5000','website'=>'nullable|url|max:255','profile_public'=>'nullable|boolean','certificates_public'=>'nullable|boolean']);
        $data['profile_public']=$request->boolean('profile_public'); $data['certificates_public']=$request->boolean('certificates_public');
        $institution=DB::table('institutions')->where('user_id',auth()->id())->first(); abort_unless($institution,404);
        DB::table('institutions')->where('id',$institution->id)->update($data+['updated_at'=>now()]);
        return back()->with('success','Profil institusi berhasil diperbarui.');
    }

    public function publicProfile(int $id)
    {
        $user=DB::table('users')->where('id',$id)->where('profile_public',true)->first(); abort_unless($user,404);
        $skills=DB::table('skills')->where('user_id',$id)->orderBy('name')->get();
        $certificates=$user->certificates_public ? DB::table('certificates')->where('user_id',$id)->whereNull('revoked_at')->orderByDesc('issued_at')->get() : collect();
        return view('public-profile',compact('user','skills','certificates'));
    }

    public function publicInstitution(int $id)
    {
        $institution=DB::table('institutions')->where('id',$id)->where('profile_public',true)->where('verification_status','approved')->first(); abort_unless($institution,404);
        $courses=DB::table('courses')->where('institution_id',$id)->where('status','published')->get();
        $bootcamps=DB::table('bootcamps')->where('institution_id',$id)->where('status','published')->get();
        $certificates=collect();
        if ($institution->certificates_public) {
            $courseIds=$courses->pluck('id'); $bootcampIds=$bootcamps->pluck('id');
            $certificates=DB::table('certificates')->join('users','users.id','=','certificates.user_id')->where('users.certificates_public',true)->whereNull('certificates.revoked_at')->where(fn($q)=>$q->where(fn($a)=>$a->where('certificates.program_type','course')->whereIn('certificates.program_id',$courseIds))->orWhere(fn($a)=>$a->where('certificates.program_type','bootcamp')->whereIn('certificates.program_id',$bootcampIds)))->select('certificates.verification_code','certificates.program_type','certificates.program_id','certificates.issued_at')->get();
        }
        return view('public-institution',compact('institution','courses','bootcamps','certificates'));
    }

    public function show(string $module)
    {
        $config = $this->config($module);
        abort_unless(in_array(auth()->user()->role, $config['roles'], true), 403);
        if ($module === 'pendaftaran') abort_unless(auth()->user()->isAdmin(),403);
        $rows = $this->queryFor($config)->latest('id')->limit(100)->get();
        $quizQuestions = $module === 'kuis' ? DB::table('questions')->orderBy('id')->get()->groupBy('quiz_id') : collect();
        $canCreate = in_array(auth()->user()->role, $config['roles'], true) && !in_array(auth()->user()->role, ['pelajar'], true) && !in_array($module,['akun','institusi'],true);
        if (in_array($module, ['keterampilan','catatan','forum','komentar','ulasan','bookmark','progres','laporan'])) $canCreate = true;
        if (in_array($module, ['pendaftaran','sertifikat','pengumuman'])) $canCreate = auth()->user()->isAdmin() || in_array(auth()->user()->role, ['institusi'], true);
        $categories = in_array($module, ['kursus','bootcamp'], true) ? DB::table('categories')->when(!auth()->user()->isAdmin(), fn ($q) => $q->where('status','active'))->orderBy('name')->get() : collect();
        return view('module', compact('module','config','rows','canCreate','quizQuestions','categories'));
    }

    public function store(Request $request, string $module)
    {
        $config = $this->config($module);
        abort_if($module === 'akun',405);
        abort_unless(in_array(auth()->user()->role, $config['roles'], true), 403);
        if ($module === 'sertifikat') abort_unless(auth()->user()->isAdmin(),403,'Hanya admin yang dapat menerbitkan sertifikat.');
        $data = $request->validate($this->rules($module));
        if ($module === 'akun') {
            $data['email']=strtolower($data['email']);
            abort_if(DB::table('users')->where('email',$data['email'])->exists(),422,'Email sudah digunakan.');
        }
        if ($module === 'soal') {
            $data['options'] = array_values(array_filter(array_map('trim', explode('|', $data['options']))));
            abort_unless(isset($data['options'][$data['correct_option']]),422,'Nomor jawaban benar harus sesuai jumlah pilihan.');
            if (auth()->user()->isInstitution()) $this->assertOwnedQuiz($data['quiz_id']);
        }
        if ($module === 'keterampilan') abort_if(DB::table('skills')->where('user_id',auth()->id())->whereRaw('LOWER(name) = ?', [mb_strtolower($data['name'])])->exists(),422,'Keterampilan tersebut sudah ada di profil Anda.');
        if (in_array($module, ['kursus','bootcamp','materi','kuis','pengumuman']) && auth()->user()->isInstitution()) {
            $institution = auth()->user()->institution;
            abort_unless($institution && $institution->verification_status === 'approved', 403, 'Institusi harus diverifikasi admin.');
            if (in_array($module, ['kursus','bootcamp'])) $data['institution_id'] = $institution->id;
            if ($module === 'materi' || $module === 'kuis') $this->assertOwnedProgram($data, $module);
            if ($module === 'pengumuman') $data['audience'] = 'all';
        }
        $table = $config['table'];
        if (in_array($table, ['skills','material_notes','discussions','comments','reviews','bookmarks','material_progress','reports','enrollments'])) $data['user_id'] = auth()->id();
        if ($module === 'institusi' && !auth()->user()->isAdmin()) { $data['user_id'] = auth()->id(); $data['verification_status'] = 'pending'; }
        if ($module === 'pendaftaran') { $data['user_id'] = auth()->id(); $data['enrolled_at'] = now(); }
        if ($module === 'sertifikat') {
            abort_unless(DB::table('enrollments')->where('user_id',$data['user_id'])->where('program_type',$data['program_type'])->where('program_id',$data['program_id'])->where('status','active')->exists(),422,'Pelajar harus terdaftar pada program.');
            $materialIds=DB::table('materials')->where('program_type',$data['program_type'])->where('program_id',$data['program_id'])->pluck('id');
            foreach(DB::table('quizzes')->whereIn('material_id',$materialIds)->get() as $quiz) {
                $best=(int) DB::table('quiz_attempts')->where('quiz_id',$quiz->id)->where('user_id',$data['user_id'])->max('score');
                abort_unless($best >= $quiz->passing_score,422,'Pelajar harus lulus semua kuis program sebelum sertifikat diterbitkan.');
            }
            $data['issued_by'] = auth()->id(); $data['verification_code'] = Str::upper(Str::random(16)); $data['issued_at'] = now();
        }
        if ($module === 'pengumuman') $data['published_at'] = now();
        DB::table($table)->insert($data + ['created_at'=>now(),'updated_at'=>now()]);
        return back()->with('success', $config['title'].' berhasil disimpan.');
    }

    public function destroy(string $module, int $id)
    {
        $config = $this->config($module); $this->authorizeModule($module);
        if ($module === 'institusi') abort_unless(auth()->user()->isAdmin(),403);
        if ($module === 'sertifikat') abort_unless(auth()->user()->isAdmin(),403);
        abort_if($module==='akun' && $id===auth()->id(),422,'Akun yang sedang digunakan tidak dapat dihapus.');
        $row = $this->queryFor($config)->where('id',$id)->firstOrFail();
        if (!auth()->user()->isAdmin()) {
            if (in_array($module,['kursus','bootcamp'])) abort_unless(auth()->user()->isInstitution(),403);
            elseif (in_array($module,['materi','kuis','soal'])) abort_unless(auth()->user()->isInstitution(),403);
            else abort_unless(($row->user_id ?? null) === auth()->id(),403);
        }
        if ($module==='pendaftaran' && auth()->user()->role==='pelajar') { DB::table('enrollments')->where('id',$id)->update(['status'=>'cancelled','updated_at'=>now()]); return back()->with('success','Pendaftaran dibatalkan.'); }
        if ($module === 'sertifikat' && auth()->user()->isAdmin()) DB::table('certificates')->where('id',$id)->update(['revoked_at'=>now(),'revocation_reason'=>'Dibatalkan admin','updated_at'=>now()]);
        else DB::table($config['table'])->where('id',$id)->delete();
        return back()->with('success', 'Data berhasil diproses.');
    }

    public function update(Request $request, string $module, int $id)
    {
        $config=$this->config($module); $this->authorizeModule($module);
        if ($module === 'akun') {
            $data=$request->validate(['name'=>'required|string|max:120','email'=>'required|email|unique:users,email,'.$id,'role'=>'required|in:pelajar,institusi,admin']);
            DB::table('users')->where('id',$id)->update($data+['updated_at'=>now()]);
            return back()->with('success','Akun berhasil diperbarui.');
        }
        abort_unless(!in_array($module,['sertifikat','pendaftaran','bookmark','progres','laporan']),405);
        $row=$this->queryFor($config)->where('id',$id)->firstOrFail();
        if (!auth()->user()->isAdmin()) {
            if (in_array($module,['kursus','bootcamp'])) abort_unless(auth()->user()->isInstitution(),403);
            elseif (in_array($module,['materi','kuis','soal'])) abort_unless(auth()->user()->isInstitution(),403);
            else abort_unless(($row->user_id ?? null) === auth()->id(),403);
        }
        if (auth()->user()->isInstitution() && in_array($module,['materi','kuis','soal'])) {
            if ($module==='materi') $this->assertOwnedProgram(['program_type'=>$row->program_type,'program_id'=>$row->program_id],$module);
            if ($module==='kuis') $this->assertOwnedProgram(['material_id'=>$row->material_id],$module);
            if ($module==='soal') $this->assertOwnedQuiz($row->quiz_id);
        }
        $data=$request->validate($this->rules($module));
        if ($module==='soal') { $data['options']=array_values(array_filter(array_map('trim',explode('|',$data['options'])))); abort_unless(isset($data['options'][$data['correct_option']]),422,'Nomor jawaban benar harus sesuai jumlah pilihan.'); }
        if ($module==='keterampilan') abort_if(DB::table('skills')->where('user_id',auth()->id())->whereRaw('LOWER(name) = ?', [mb_strtolower($data['name'])])->where('id','<>',$id)->exists(),422,'Keterampilan tersebut sudah ada di profil Anda.');
        if (in_array($module,['kursus','bootcamp']) && auth()->user()->isInstitution()) {
            $inst=auth()->user()->institution; abort_unless($inst && $inst->verification_status==='approved',403);
            $data['institution_id']=$inst->id; unset($data['status']);
        }
        if (in_array($module,['materi','kuis']) && auth()->user()->isInstitution()) $this->assertOwnedProgram($data,$module);
        DB::table($config['table'])->where('id',$id)->update($data+['updated_at'=>now()]);
        return back()->with('success','Perubahan berhasil disimpan.');
    }

    public function enroll(Request $request)
    {
        abort_unless(auth()->user()->role === 'pelajar',403);
        $data = $request->validate(['program_type'=>'required|in:course,bootcamp','program_id'=>'required|integer']);
        $table = $data['program_type'] === 'course' ? 'courses' : 'bootcamps';
        $program = DB::table($table)->where('id',$data['program_id'])->where('status','published')->first();
        abort_unless($program, 404);
        DB::table('enrollments')->updateOrInsert(['user_id'=>auth()->id(),'program_type'=>$data['program_type'],'program_id'=>$program->id], ['status'=>'active','enrolled_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        return back()->with('success','Pendaftaran berhasil.');
    }

    public function verifyCertificate(string $code)
    {
        $certificate = DB::table('certificates')->where('verification_code',$code)->first();
        return view('certificate', compact('certificate'));
    }

    public function verifyInstitution(Request $request, int $id)
    {
        abort_unless(auth()->user()->isAdmin(),403);
        $data=$request->validate(['verification_status'=>'required|in:approved,rejected','verification_note'=>'required_if:verification_status,rejected|nullable|string|max:2000']);
        abort_unless(DB::table('institutions')->where('id',$id)->exists(),404);
        $note=$data['verification_note'] ?? null;
        DB::transaction(function () use ($id, $data, $note) {
            DB::table('institutions')->where('id',$id)->update(['verification_status'=>$data['verification_status'],'verification_note'=>$note,'updated_at'=>now()]);
            DB::table('institution_verifications')->insert(['institution_id'=>$id,'admin_id'=>auth()->id(),'status'=>$data['verification_status'],'notes'=>$note,'verified_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        });
        return back()->with('success','Status verifikasi institusi diperbarui.');
    }

    public function resolveReport(Request $request, int $id)
    {
        abort_unless(auth()->user()->isAdmin(),403);
        $data=$request->validate(['status'=>'required|in:resolved,dismissed','resolution_note'=>'required|string|max:2000']);
        DB::table('reports')->where('id',$id)->update($data+['handled_by'=>auth()->id(),'updated_at'=>now()]);
        return back()->with('success','Laporan telah ditindaklanjuti.');
    }

    public function submitQuiz(Request $request, int $quiz)
    {
        abort_unless(auth()->user()->role === 'pelajar',403);
        $quizRow = DB::table('quizzes')->find($quiz); abort_unless($quizRow,404);
        $material = DB::table('materials')->find($quizRow->material_id);
        $enrolled = DB::table('enrollments')->where('user_id',auth()->id())->where('program_id',$material->program_id)->where('program_type',$material->program_type)->exists();
        abort_unless($enrolled,403,'Daftar program terlebih dahulu.');
        $questions = DB::table('questions')->where('quiz_id',$quiz)->get();
        abort_if($questions->isEmpty(), 422, 'Kuis belum memiliki soal.');
        $answers = $request->input('answers', []); $points = 0; $max = 0;
        foreach ($questions as $q) { $max += $q->points; if ((int)($answers[$q->id] ?? -1) === $q->correct_option) $points += $q->points; }
        $score = (int) round($points * 100 / max(1,$max));
        DB::table('quiz_attempts')->insert(['quiz_id'=>$quiz,'user_id'=>auth()->id(),'score'=>$score,'answers'=>json_encode($answers),'submitted_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        return back()->with('success', "Kuis dikumpulkan. Nilai Anda: {$score}.");
    }

    private function config(string $key): array { abort_unless(isset($this->modules[$key]),404); return $this->modules[$key]; }
    private function authorizeModule(string $module): void { abort_unless(in_array(auth()->user()->role,$this->config($module)['roles'],true),403); }
    private function rules(string $module): array
    {
        $rules = ['name'=>'required|string|max:150','email'=>'required|email','role'=>'required|in:pelajar,institusi,admin','type'=>'nullable|string|max:100','description'=>'nullable|string','website'=>'nullable|url|max:255','title'=>'required|string|max:180','body'=>'nullable|string','institution_id'=>'required|integer|exists:institutions,id','category_id'=>'nullable|integer|exists:categories,id','status'=>'nullable|in:draft,published,archived','passing_score'=>'nullable|integer|min:0|max:100','program_type'=>'required_with:program_id|in:course,bootcamp','program_id'=>'required_with:program_type|integer','position'=>'nullable|integer|min:0','material_id'=>'required|integer|exists:materials,id','percent'=>'nullable|integer|min:0|max:100','completed'=>'nullable|boolean','quiz_id'=>'required|integer|exists:quizzes,id','prompt'=>'required|string','options'=>'required|string','correct_option'=>'required|integer|min:0','points'=>'required|integer|min:1','user_id'=>'nullable|integer|exists:users,id','rating'=>'required|integer|min:1|max:5','audience'=>'nullable|in:all,pelajar,institusi','content_type'=>'required|string|max:100','content_id'=>'required|integer','category'=>'required|string|max:100'];
        $selected = array_keys($this->config($module)['fields']); $out=[]; foreach ($selected as $key) if (isset($rules[$key])) $out[$key] = $rules[$key];
        if ($module === 'ulasan') $out['rating']='required|integer|min:1|max:5';
        if ($module === 'forum' || $module === 'komentar' || $module === 'catatan' || $module === 'materi' || $module === 'pengumuman' || $module === 'laporan') $out['body']='required|string';
        return $out;
    }
    private function assertOwnedProgram(array $data, string $module): void
    {
        if ($module === 'kuis') {
            $material=DB::table('materials')->find($data['material_id']); abort_unless($material,404);
            $type=$material->program_type; $id=$material->program_id;
        } else { $type=$data['program_type'] ?? 'course'; $id=$data['program_id']; }
        $table = $type === 'bootcamp' ? 'bootcamps' : 'courses'; $institutionId = auth()->user()->institution->id;
        abort_unless(DB::table($table)->where('id',$id)->where('institution_id',$institutionId)->exists(),403);
    }
    private function assertOwnedQuiz(int $quizId): void
    {
        $quiz=DB::table('quizzes')->find($quizId); abort_unless($quiz,404);
        $this->assertOwnedProgram(['material_id'=>$quiz->material_id],'kuis');
    }
    private function queryFor(array $config)
    {
        $query = DB::table($config['table']); $user = auth()->user();
        if (in_array($config['table'], ['skills','material_notes','material_progress','bookmarks','enrollments','reviews','reports','comments']) && !$user->isAdmin() && !$user->isInstitution()) $query->where('user_id',$user->id);
        if ($config['table']==='enrollments' && $user->isInstitution()) {
            $institutionId=optional($user->institution)->id ?? 0;
            $courseIds=DB::table('courses')->where('institution_id',$institutionId)->pluck('id');
            $bootcampIds=DB::table('bootcamps')->where('institution_id',$institutionId)->pluck('id');
            $query->where(fn($q)=>$q->where(fn($a)=>$a->where('program_type','course')->whereIn('program_id',$courseIds))->orWhere(fn($a)=>$a->where('program_type','bootcamp')->whereIn('program_id',$bootcampIds)));
        }
        if ($config['table'] === 'certificates' && !$user->isAdmin()) $query->where('user_id',$user->id);
        if ($config['table'] === 'institutions' && !$user->isAdmin()) $query->where('user_id',$user->id);
        if (in_array($config['table'], ['courses','bootcamps']) && $user->isInstitution()) $query->where('institution_id',optional($user->institution)->id ?? 0);
        if (in_array($config['table'], ['courses','bootcamps']) && !$user->isAdmin() && !$user->isInstitution()) $query->where('status','published');
        if (in_array($config['table'],['materials','quizzes','questions']) && !$user->isAdmin()) {
            $materialIds=[];
            if ($user->isInstitution()) {
                $institutionId=optional($user->institution)->id ?? 0;
                $courseIds=DB::table('courses')->where('institution_id',$institutionId)->pluck('id');
                $bootcampIds=DB::table('bootcamps')->where('institution_id',$institutionId)->pluck('id');
                $materialIds=DB::table('materials')->where(fn($q)=>$q->where(fn($a)=>$a->where('program_type','course')->whereIn('program_id',$courseIds))->orWhere(fn($a)=>$a->where('program_type','bootcamp')->whereIn('program_id',$bootcampIds)))->pluck('id');
            } else {
                foreach(DB::table('enrollments')->where('user_id',$user->id)->where('status','active')->get() as $enrollment) {
                    $table=$enrollment->program_type==='bootcamp'?'bootcamps':'courses';
                    if(DB::table($table)->where('id',$enrollment->program_id)->where('status','published')->exists()) $materialIds=array_merge($materialIds,DB::table('materials')->where('program_type',$enrollment->program_type)->where('program_id',$enrollment->program_id)->pluck('id')->all());
                }
            }
            if ($config['table']==='materials') $query->whereIn('id',$materialIds);
            if ($config['table']==='quizzes') $query->whereIn('material_id',$materialIds);
            if ($config['table']==='questions') $query->whereIn('quiz_id',DB::table('quizzes')->whereIn('material_id',$materialIds)->pluck('id'));
        }
        if ($config['table'] === 'discussions' && !$user->isAdmin()) $query->where(fn($q)=>$q->where('user_id',$user->id)->orWhere('status','visible'));
        if ($config['table'] === 'announcements' && !$user->isAdmin() && !$user->isInstitution()) $query->whereNotNull('published_at');
        if ($config['table'] === 'announcements' && $user->isInstitution()) $query->where('user_id',$user->id);
        return $query;
    }
}
