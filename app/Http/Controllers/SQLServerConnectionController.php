<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class SQLServerConnectionController extends Controller
{
    public function checkConnection()
    {
        try {
            $connection = config('database.connections.sqlsrv');
            
            $pdo = new \PDO(
                "sqlsrv:Server={$connection['host']};Database={$connection['database']}",
                $connection['username'],
                $connection['password']
            );

            // Set PDO attributes for error handling
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            return response()->json(['message' => 'Connection successful']);
        } catch (\PDOException $e) {
            return response()->json(['message' => 'Connection failed: ' . $e->getMessage()], 500);
        }
    }
}
