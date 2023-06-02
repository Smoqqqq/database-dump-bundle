<?php

declare(strict_types=1);

/**
 * DatabaseDumpBundle - Paul Le Flem <contact@paul-le-flem.fr>
 */

namespace Smoq\DatabaseDumpBundle;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Schema\AbstractSchemaManager;

class Dumper
{
    protected Connection $conn;
    protected AbstractSchemaManager $schemaManager;

    /** @var Table[] */
    public array $tables;
    public array $data;

    public string $filepath;
    public $file;

    public function __construct(public ManagerRegistry $doctrine)
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

    /**
     * Gets the db schema
     */
    public function getSchema()
    {
        return $this->schemaManager->introspectSchema()->toSql($this->conn->getDatabasePlatform());
    }

    /**
     * Handles file creation & resetting
     */
    protected function openFile(string $filepath, bool $overwride)
    {
        if (!file_exists($filepath) || $overwride) {
            $this->filepath = $filepath;
            $this->file = fopen($filepath, "w");
            file_put_contents($this->filepath, "");
        } else {
            throw new \Exception('Please provide an empty filepath or explicitly set `$overwride` to `true`');
        }
    }

    protected function write(string $content)
    {
        fwrite($this->file, $content);
    }

    /**
     * Saves the SQL Schema to the file
     */
    protected function writeSchema()
    {
        $schemaRows = $this->getSchema();

        foreach ($schemaRows as $row) {
            $this->write($row . ";\n");
        }

        $this->write("\n\n");
    }
}
