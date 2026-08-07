<?php
namespace App\Models;
use App\Enums\PayrollStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class PayrollPeriod extends Model { protected $fillable=['name','starts_on','ends_on','status','published_at','published_by']; protected $attributes=['status'=>'draft']; protected function casts(): array { return ['starts_on'=>'date','ends_on'=>'date','status'=>PayrollStatus::class,'published_at'=>'datetime']; } public function records(): HasMany { return $this->hasMany(PayrollRecord::class); } }
