<?php
/**
 * web.php
 *
 * Web画面で使用するURLとControllerの対応を定義するルーティングファイルです。
 *
 * 主な役割：
 * - アクセスされたURLに対して、どのControllerのどのメソッドを実行するか決める
 * - GET /login でログイン画面を表示する
 * - POST /login でログイン処理を実行する
 * - POST /logout でログアウト処理を実行する
 * - ログイン後のスタッフ画面へのルートを定義する
 */

return [
    ['GET',  '/',       'AuthController@showLogin'],
    ['GET',  '/login',  'AuthController@showLogin'],
    ['POST', '/login',  'AuthController@login'],
    ['POST', '/logout', 'AuthController@logout'],

    // スタッフ画面
    ['GET', '/staff', 'StaffController@index'],

    // スタッフ代理注文
    ['GET', '/staff/order/entry', 'StaffController@orderEntry'],
    ['GET', '/staff/order/menu',  'StaffController@orderMenu'],
];