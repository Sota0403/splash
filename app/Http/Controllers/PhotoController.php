<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePhoto;
use App\Http\Requests\StoreComment;
use App\Comment;
use App\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class PhotoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'download','show']);;
    }

    public function index()
    {
      $photos = Photo::with(['owner'])->orderby("created_at", 'desc')->paginate();

      return $photos;
    }

    public function create(StorePhoto $request)
    {
      $extension = $request->photo->extension();

      $photo = new Photo();

      $photo->filename = $photo->id . '.' . $extension;

      Storage::cloud()->putFileAs('', $request->photo, $photo->filename, 'public');

      DB::beginTransaction();

      // dd($photo);

      try {
          Auth::user()->photos()->save($photo);
          // $this->$photo->user_id = Auth::user()->id;
          // dd($photo);
          // $this->$photo->save();
          DB::commit();
      } catch (\Exception $exception) {
          DB::rollback();
          
          Storage::cloud()->delete($photo->filename);
          throw $exception;
      }
      return response($photo, 201);
    }

    public function download(Photo $photo)
    {
      // dd($photo);
      if (! Storage::cloud()->exists($photo->filename)) {
          abort(404);
      }
      $disposition = 'attachment; filename="' . $photo->filename .'"';
      $headers = [
          'Content-Type' => 'application/octet-stream',
          'Content-Disposition' => $disposition,
      ];

      return response(Storage::cloud()->get($photo->filename), 200, $headers);
    }

    public function show(String $id)
    {
      $photo = Photo::where('id', $id)->with(['owner', 'comments.authors'])->first();

      return $photo ?? abort(404);
    }

    public function addComment(Photo $photo, StoreComment $request)
    {
        $comment = new Comment();
        $comment->content = $request->get('content');
        $comment->user_id = Auth::user()->id;
        $photo->comments()->save($comment);

        $new_comment = Comment::where('id', $comment->id)->with('author')->first();

        return response($new_comment, 201);
    }

}
