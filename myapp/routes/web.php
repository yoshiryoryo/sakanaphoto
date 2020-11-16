<?php


// laravelのルート定義は上から順にマッチしたルートに制御が渡される
// 写真ダウンロード
Route::get('/photos/{photo}/download', 'PhotoController@download');

// APIのURL以外のリクエストに対してはindexテンプレートを返す
// 画面遷移はフロントエンドのVueRouterが制御する
Route::get('/{any?}', fn() => view('index'))->where('any', '.+');


