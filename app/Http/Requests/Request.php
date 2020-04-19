<?php

namespace App\Http\Requests;

class Request extends \Illuminate\Http\Request
{
    public function expectcsJson()
    {
        return true;
    }

    public function wantsJson()
    {
        return true;
    }
}
