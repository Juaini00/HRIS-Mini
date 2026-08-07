<?php
namespace App\Http\Controllers\Hris;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
class AnnouncementController extends Controller { public function index(Request $request): Response { $query=Announcement::with('author')->latest(); if(!$request->user()->isAdministrator()){$query->whereNotNull('published_at')->where('published_at','<=',now())->whereIn('audience',['all',$request->user()->role->value]);} return Inertia::render('hris/announcements',['announcements'=>$query->paginate(20),'canManage'=>$request->user()->isAdministrator()]); } public function store(Request $request): RedirectResponse { abort_unless($request->user()->isAdministrator(),403); $data=$request->validate(['title'=>['required','string','max:160'],'body'=>['required','string','max:5000'],'audience'=>['required','in:all,manager,employee'],'published_at'=>['nullable','date']]); Announcement::create([...$data,'author_id'=>$request->user()->id]); return back()->with('success','Pengumuman disimpan.'); } }
