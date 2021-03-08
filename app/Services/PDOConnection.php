<?php

namespace Afatoga\Services;


class PDOConnection{

    private $connection;

    public function getConnection(){

        $this->connection = null;

        try {
            $this->connection = new \PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=UTF8", DB_USER, DB_PASSWORD);
        } catch (\PDOException $exception){
            echo "Connection failed: " . $exception->getMessage();
        }

        return $this->connection;
    }
}
