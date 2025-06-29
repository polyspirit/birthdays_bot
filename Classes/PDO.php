<?php

namespace Classes;

class PDO
{
    private Database $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function getDatabase(): Database
    {
        return $this->database;
    }
}
