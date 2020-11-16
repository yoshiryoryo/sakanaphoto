<?php

namespace App\Http\Controllers;

use App\Photo;
use App\Comment;
use App\Http\Requests\StorePhoto;
use App\Http\Requests\StoreComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    public function __construct() 
    {
        // 認証が必要
        $this->middleware('auth')->except(['index', 'download', 'show']);
    }

   

    // 写真一覧
    public function index() 
    {
        /* 
        withメソッドは、リレーションを事前にロードしておくメソッド、引数で渡したリレーションが定義されたテーブル情報を先に
        まとめて取得することでデータベースへの通信回数を少なくする
        */
        $photos = Photo::with(['owner', 'likes'])
        /*
        paginateはページ送り機能を実現する。getの代わりにpaginateを使うことで、JSONレスポンスでも示したtotalやcurrent_pageといった
        情報が自動的に追加される
        */
        ->orderBy(Photo::CREATED_AT, 'desc')->paginate();

        return $photos;
    }

    /**
    *写真詳細
    * @param string $id
    * @return Photo
    *引数でpathパラメータidを受け取る、写真データが見つからない場合は404を返却
    */
    public function show(string $id) 
    {
        $photo = Photo::where('id', $id)
        ->with(['owner', 'comments.author', 'likes'])->first();

        return $photo ?? abort(404);
    }

     /**
     * 写真投稿
     * @param StorePhoto $request
     * @return \Illuminate\Http\Response
     */
     public function create(StorePhoto $request) 
     {
        // 写真の拡張子を取得
        $extension = $request->photo->extension();

        $photo = new Photo();

        // インスタンス生成時に割り当てられたランダムなID値と本来の拡張子を組み合わせたファイル名とする
        $photo->filename = $photo->id . '.' . $extension;

        // S3にファイルを保存する
        // 第三引数の'public'はファイルを公開状態で保存するため
        // cloud()を読んだ場合はconfig/filesystems.php の cloud の設定にしたがって使用されるストレージが決まります。
        Storage::cloud()
        ->putFileAs('', $request->photo, $photo->filename, 'public');
        

        // データべ－スエラー時にファイル削除を行うためのトランザクションを利用する
        DB::beginTransaction();

        try {
            Auth::user()->photos()->save($photo);
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            // DBとの不整合を避けるためにアップロードしたファイルを削除
            Storage::cloud()->delete($photo->filename);
            throw $exception;
        }

        // リソースの新規作成なのでレスポンスコードは201(CREATED)を返却する
        return response($photo, 201);
    }

    /**
     * 写真ダウンロード
     * @param Photo $photo
     * @return \Illuminate\Http\Response
     */
    public function download(Photo $photo) 
    {
        // 写真の存在チェック
        if (! Storage::cloud()->exists($photo->filename)) {
            abort(404);
        }
        
        /* Content-Dispositionにattachmentおよびfilenameを指定することで、レスポンスの内容をWebページとして表示するのではなく、 
        ダウンロードさせるために保存ダイアログを開くようにブラウザに指示
        */
        $disposition = 'attachment; filename="' . $photo->filename . '"';
        $headers = [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => $disposition,
        ];

        return response(Storage::cloud()->get($photo->filename), 200, $headers);
    }


    /**
     * コメント投稿
     * @param Photo $photo
     * @param StoreComment $request
     * @return \Illuminate\Http\Response
     */
    public function addComment(Photo $photo, StoreComment $request) 
    {
        $comment = new Comment();
        $comment->content = $request->get('content');
        $comment->user_id = Auth::user()->id;
        $photo->comments()->save($comment);

        // authorリレーションをロードするためにコメントを取得しなおす
        $new_comment = Comment::where('id', $comment->id)->with('author')->first();

        return response($new_comment, 201);
    }

    /**
     * いいね
     * @param string $id
     * @return array
     */
    public function like(string $id)
    {
        $photo = Photo::where('id', $id)->with('likes')->first();

        if (! $photo) {
            abort(404);
        }
        /*
         何回実行してもいいねが1個しかつかないように、特定の写真及びログインユーザーに紐づくいいねを
         削除して(detach)から新たに追加(attach)する
        */
        $photo->likes()->detach(Auth::user()->id);
        $photo->likes()->attach(Auth::user()->id);

        return ["photo_id" => $id];
    }

    /**
     * いいね解除
     * @param string $id
     * @return array
     */
    public function unlike(string $id) 
    {
        $photo = Photo::where('id', $id)->with('likes')->first();

        if (! $photo) {
            abort(404);
        }

        $photo->likes()->detach(Auth::user()->id);

        return ["photo_id" => $id];
    }
}
