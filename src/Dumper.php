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
use Smoq\DatabaseDumpBundle\Exception\InvalidFilePathException;

class Dumper
{
    protected Connection $conn;
    protected AbstractSchemaManager $schemaManager;

    /** @var Table[] */
    public array $tables;

    /** @var resource */
    public $file;

    public function __construct(public ManagerRegistry $doctrine)
    {
        $this->conn = $doctrine->getConnection();
        $this->schemaManager = $this->conn->createSchemaManager();
        $this->tables = $this->schemaManager->listTables();
    }

    /**
     * @return mixed[]
     */
    protected function getTableData(Table $table): array
    {
        $sql = "SELECT * FROM {$table->getName()}";

        $query = $this->conn->executeQuery($sql);
        $data = $query->fetchAllAssociative();

        return $data;
    }

    /**
     * @return mixed[]
     */
    protected function getTableColumns(Table $table): array
    {
        $sql = "SHOW COLUMNS FROM `{$table->getName()}`";

        $query = $this->conn->executeQuery($sql);
        $data = $query->fetchAllAssociative();

        $columns = [];

        foreach ($data as $col) {
            $columns[$col["Field"]] = null;
        }

        return [$columns];
    }

    /**
     * Gets the db schema
     * 
     * @return string[]
     */
    private function getSchema(): array
    {
        return $this->schemaManager->introspectSchema()->toSql($this->conn->getDatabasePlatform());
    }

    /**
     * Handles file creation & resetting
     */
    protected function openFile(string $filepath, bool $overwrite): void
    {
        if (!file_exists($filepath) || $overwrite) {
            $this->file = fopen($filepath, "w");
            file_put_contents($filepath, "");
        } else {
            throw new InvalidFilePathException('Please provide an empty filepath or explicitly set `$overwrite` to `true`');
        }
    }

    protected function write(string $content): void
    {
        fwrite($this->file, $content);
    }

    /**
     * Saves the SQL Schema to the file
     */
    protected function writeSchema(): void
    {
        $schemaRows = $this->getSchema();

        foreach ($schemaRows as $row) {
            $this->write($row . ";\n");
        }

        $this->write("\n\n");
    }
}
