<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Announcement extends Model { protected $fillable=['author_id','title','body','audience','department_id','location_id','published_at','notified_at']; public function author(): BelongsTo { return $this->belongsTo(User::class, 'author_id'); } public function reads(): HasMany { return $this->hasMany(AnnouncementRead::class); } protected function casts(): array { return ['published_at'=>'datetime','notified_at'=>'datetime']; } }
