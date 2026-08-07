<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LeaveType extends Model { protected $fillable=['name','annual_quota','is_paid','requires_attachment','is_active']; protected function casts(): array { return ['is_paid'=>'boolean','requires_attachment'=>'boolean','is_active'=>'boolean']; } }
