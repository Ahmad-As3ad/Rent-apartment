<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UsersController extends Controller
{
    function index()
    {
        $users = [
            ['id' => 1, 'name' => "ahmad"],
            ['id' => 2, 'name' => "khaled"],
            ['id' => 3, 'name' => "mohammed"]
        ];
        // foreach ($users as $user)
        // {
        //     echo $user['id'] . '  , ' . $user['name'] . "\n";
        // }
     return response()->json($users);
    }
    public function Checkuser(){



    }
}
