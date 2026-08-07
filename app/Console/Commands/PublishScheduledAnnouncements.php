<?php
namespace App\Console\Commands;
use App\Models\Announcement;
use Illuminate\Console\Command;
class PublishScheduledAnnouncements extends Command { protected $signature='nusahr:publish-announcements'; protected $description='Publikasikan pengumuman yang telah mencapai jadwal'; public function handle(): int { $count=Announcement::whereNotNull('published_at')->where('published_at','<=',now())->count(); $this->info("{$count} pengumuman tersedia."); return self::SUCCESS; } }
