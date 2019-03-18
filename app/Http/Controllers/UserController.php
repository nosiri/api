<?php

namespace App\Http\Controllers;

use App\User;
use http\Env\Request;

class UserController extends Controller {
    /**
     * Retrieve the user for the given ID.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id) {
        return User::findOrFail($id);
    }

}