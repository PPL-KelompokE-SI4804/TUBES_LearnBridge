<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name'=>'Admin LearnBridge','email'=>'admin@learnbridge.test','role'=>'admin'],
            ['name'=>'Akademi Digital Demo','email'=>'institusi@learnbridge.test','role'=>'institusi'],
            ['name'=>'Pelajar Demo','email'=>'pelajar@learnbridge.test','role'=>'pelajar'],
        ] as $item) {
            User::updateOrCreate(['email'=>$item['email']], $item + ['password'=>Hash::make('Belajar123!')]);
        }
        $institutionUser=User::where('email','institusi@learnbridge.test')->firstOrFail();
        DB::table('institutions')->updateOrInsert(['user_id'=>$institutionUser->id], ['name'=>'Akademi Digital Demo','type'=>'Lembaga pelatihan','description'=>'Institusi contoh untuk mencoba alur LearnBridge.','verification_status'=>'approved','created_at'=>now(),'updated_at'=>now()]);
        $institution=DB::table('institutions')->where('user_id',$institutionUser->id)->first();
        DB::table('categories')->updateOrInsert(['name'=>'Pemrograman Web'],['slug'=>'pemrograman-web','status'=>'active','description'=>'Dasar pengembangan aplikasi web.','created_at'=>now(),'updated_at'=>now()]);
        $categoryId=DB::table('categories')->where('name','Pemrograman Web')->value('id');
        DB::table('courses')->updateOrInsert(['institution_id'=>$institution->id,'title'=>'Dasar Laravel'],['category_id'=>$categoryId,'description'=>'Contoh kursus untuk menguji proses pendaftaran dan belajar.','status'=>'published','passing_score'=>70,'created_at'=>now(),'updated_at'=>now()]);
        $courseId=DB::table('courses')->where('institution_id',$institution->id)->where('title','Dasar Laravel')->value('id');
        DB::table('materials')->updateOrInsert(['program_type'=>'course','program_id'=>$courseId,'title'=>'Pengenalan Laravel'],['body'=>'Laravel membantu pengembangan aplikasi web dengan pola MVC.','position'=>1,'created_at'=>now(),'updated_at'=>now()]);
        $materialId=DB::table('materials')->where('program_type','course')->where('program_id',$courseId)->where('title','Pengenalan Laravel')->value('id');
        DB::table('quizzes')->updateOrInsert(['material_id'=>$materialId,'title'=>'Kuis pengenalan'],['passing_score'=>70,'created_at'=>now(),'updated_at'=>now()]);
        $quizId=DB::table('quizzes')->where('material_id',$materialId)->where('title','Kuis pengenalan')->value('id');
        DB::table('questions')->updateOrInsert(['quiz_id'=>$quizId,'prompt'=>'Apa kepanjangan MVC?'],['options'=>json_encode(['Model View Controller','Main View Code','Module Version Control']),'correct_option'=>0,'points'=>1,'created_at'=>now(),'updated_at'=>now()]);
        $learner=User::where('email','pelajar@learnbridge.test')->firstOrFail();
        DB::table('enrollments')->updateOrInsert(['user_id'=>$learner->id,'program_type'=>'course','program_id'=>$courseId],['status'=>'active','enrolled_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        DB::table('announcements')->insertOrIgnore(['user_id'=>$institutionUser->id,'title'=>'Selamat datang di kelas','body'=>'Silakan mulai dari materi pertama.','audience'=>'all','published_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
    }
}
