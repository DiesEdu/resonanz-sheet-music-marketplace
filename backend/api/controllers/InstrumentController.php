<?php
namespace SheetMusic\Controllers;

use SheetMusic\Config\Database;
use SheetMusic\Models\Instrument;

class InstrumentController
{
    private $instrument;

    public function __construct()
    {
        $database = new Database();
        $db = $database->getConnection();
        $this->instrument = new Instrument($db);
    }

    public function handleRequest($method, $id = null)
    {
        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->getOne((int) $id);
                } else {
                    $this->getAll();
                }
                break;
            default:
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
        }
    }

    private function getAll()
    {
        $instruments = $this->instrument->getAll();
        http_response_code(200);
        echo json_encode($instruments);
    }

    private function getOne($id)
    {
        $instrument = $this->instrument->getById($id);

        if (!$instrument) {
            http_response_code(404);
            echo json_encode(['error' => 'Instrument not found']);
            return;
        }

        http_response_code(200);
        echo json_encode($instrument);
    }
}
?>
