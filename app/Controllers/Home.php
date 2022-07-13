<?php namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        return $this->response->setJSON([
            'code'    => 200,
            'status'  => 'OK',
            'message' => 'Welcome to CodeIgniter 4 Dynamic Image Library created by Ghivarra Senandika Rushdie'
        ]);
    }
}