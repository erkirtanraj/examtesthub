<?php
use Illuminate\Support\Facades\Artisan;
Artisan::command('about:examtesthub',function(){ $this->info('ExamTestHub application foundation'); })->purpose('Display the ExamTestHub application identity');
