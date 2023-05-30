<?php

namespace Smoq\DatabaesDumpBundle;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Schema\AbstractSchemaManager;

class Dumper
{
    private Connection $conn;
    private AbstractSchemaManager $schemaManager;

    /** @var Table[] */
    public array $tables;

    public array $data;

    public function __construct(private ManagerRegistry $doctrine)
    {
        $this->conn = $doctrine->getConnection();
        $this->schemaManager = $this->conn->createSchemaManager();
        $this->tables = $this->schemaManager->listTables();
    }

    /**
     * Get full database as an associative array
     * 
     * @param array $exclude the tables to exclude from the dump
     */
    public function dump(array $exclude): array
    {
        foreach ($this->tables as $table) {
            if (!\in_array($table->getName(), $exclude)) {
                $this->dumpTable($table);
            }
        }

        return $this->data;
    }

    /**
     * [INTERNAL] fills data property with table data
     */
    private function dumpTable(Table $table)
    {
        $sql = "SELECT * FROM {$table->getName()}";

        $query = $this->conn->executeQuery($sql);
        $res = $query->fetchAllAssociative();

        $this->data[$table->getName()] = $res;
    }

    public function getTableNames(array $exclude): array
    {
        $tables = [];

        foreach ($this->tables as $table) {
            if (!\in_array($table->getName(), $exclude)) {
                $tables[] = $table->getName();
            }
        }

        return $tables;
    }
}
