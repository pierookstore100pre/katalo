<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use Config\Database;

class Ping extends BaseController
{
    public function index()
    {
        $db = Database::connect();
        $db->query("SET time_zone = '-05:00'");

        $row = $db->query('SELECT DATABASE() AS db')->getRow();

        return $this->response->setJSON([
            'status'  => 'ok',
            'message' => 'Katalo API conectada',
            'db'      => $row->db,
        ]);
    }
}
