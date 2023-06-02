<?php

declare(strict_types=1);

/**
 * DatabaseDumpBundle - Paul Le Flem <contact@paul-le-flem.fr>
 */

namespace Smoq\DatabaseDumpBundle;

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Class used to dump the database to an sql file
 */
class SqlDumper extends Dumper implements DumperInterface
{
    private array $dependencyTree = [];
    /** @var string[] */
    private array $entityClasses = [];
    /** @var array{'table': string} */
    private array $joinTables = [];

    /**
     * Generates the .sql file for creating and filling the database with data,
     * and saves it to the given filepath
     * 
     * @param string $filepath the path to save the file to. The given file extension MUST be the same as the $format param
     * @param string[] $exclude the tables to exclude from the dump
     * @param bool $overwrite weither or not to overwrite if the file exists
     */
    public function dumpToFile(string $filepath, array $exclude = [], bool $overwrite = false): void
    {
        $this->openFile($filepath, $overwrite);
        $this->writeSchema();

        $this->getEntityClasses(dirname(getcwd(), 1) . "/src/Entity", "App\Entity");
        $this->getEntityDependencies($this->entityClasses);
        $entities = $this->getEntityOrder();

        $this->createEntityInsertStatements($entities);
        $this->createJoinTablesInsertStatements();
    }

    /**
     * @param mixed[] $entities
     */
    private function createEntityInsertStatements(array $entities): void
    {
        foreach ($entities as $entity) {
            $this->createInsertStatement($entity);
        }
    }

    private function createJoinTablesInsertStatements(): void
    {
        foreach ($this->joinTables as $entity) {
            $this->createInsertStatement($entity);
        }
    }

    private function getInsertSchemaStatement(array $entity, array $data): string
    {
        $sql = "INSERT INTO `{$entity["table"]}` (";
        $i = 0;

        foreach ($data[0] as $key => $value) {
            if ($i > 0) {
                $sql .= ", ";
            }

            $sql .= "`{$key}`";
            $i++;
        }

        $sql .= ")";

        return $sql;
    }

    private function createInsertStatement(array $entity): void
    {
        $query = $this->conn->executeQuery("SELECT * FROM {$entity["table"]}");
        $data = $query->fetchAllAssociative();

        if (count($data) === 0) {
            return;
        }

        $this->write("-- Fill '{$entity["table"]}' table\n");

        $insertSchema = $this->getInsertSchemaStatement($entity, $data);

        $rowCount = 0;

        $sql = "";

        foreach ($data as $row) {

            $sql .= "(";
            $colCount = 0;

            foreach ($row as $col) {
                if ('string' === gettype($col)) {
                    $sql .= "'" . addslashes($col) . "'";
                } elseif (null === $col) {
                    $sql .= "NULL";
                } else {
                    $sql .= $col;
                }

                if ($colCount < count($row) - 1) {
                    $sql .= ", ";
                }

                $colCount++;
            }

            if ($rowCount % 10 === 0) {
                $sql .= ");";
                $this->write($insertSchema . " VALUES \n" . $sql . "\n\n");
                $sql = "";
            } elseif ($rowCount === count($data) - 1) {
                $sql .= ");\n";
            } else {
                $sql .= "),\n";
            }

            $rowCount++;
        }

        if ('' !== $sql) {
            $this->write($insertSchema . " VALUES \n" . $sql . "\n\n");
        }
    }

    private function getEntityDependencies(array $entityClasses): void
    {
        foreach ($entityClasses as $class) {
            if (!isset($this->dependencyTree[$class])) {
                $dependencies = $this->getSingleEntityDependencies($class);

                $this->dependencyTree[$class] = $dependencies;
            }
        }
    }

    private function getSingleEntityDependencies(string $class): array
    {
        $em = $this->doctrine->getManager();

        /** @var ClassMetadata */
        $classMetadata = $em->getClassMetadata($class);
        $dependencies = [
            "dependencies" => [],
            "table" => $classMetadata->getTableName()
        ];

        foreach ($classMetadata->getAssociationMappings() as $dependency) {

            // Handle ManyToMany join tables
            if ($dependency["type"] === 8 && $dependency["isOwningSide"] && !isset($this->joinTables[$dependency["joinTable"]["name"]])) {
                $this->joinTables[$dependency["joinTable"]["name"]] = [
                    "table" => $dependency["joinTable"]["name"]
                ];
            }

            if (!$dependency["inversedBy"] && !$dependency["isOwningSide"]) {
                continue;
            }

            $dependencies["dependencies"][$dependency["targetEntity"]] = $this->getSingleEntityDependencies($dependency['targetEntity']);
        }

        return $dependencies;
    }

    /**
     * Recursively gets entity classes
     */
    private function getEntityClasses(string $dir, string $namespace): void
    {
        $files = array_diff(scandir($dir), [".", "..", ".gitignore"]);

        foreach ($files as $file) {
            $filepath = $dir . "/" . $file;
            if (is_dir($filepath)) {
                $this->getEntityClasses($filepath, $namespace . "\\" . $file);
            } else {
                $this->entityClasses[] = $namespace . "\\" . str_replace(".php", "", $file);
            }
        }
    }

    /**
     * Get the entity order from the dependency tree
     */
    private function getEntityOrder(): array
    {
        $entities = [];
        $called = [];

        foreach ($this->dependencyTree as $key => $entity) {

            $data = [
                "class" => $key,
                "table" => $entity["table"]
            ];

            if (!in_array($data, $called, true)) {
                $called[] = $data;
                [$entities, $called] = $this->getDependantEntities($entity, $entities, $called);
                $entities[] = $data;
            }
        }

        return $entities;
    }

    private function getDependantEntities(array $entity, array $entities, array $called): array
    {
        foreach ($entity["dependencies"] as $key => $dependency) {
            $data = [
                "class" => $key,
                "table" => $dependency["table"]
            ];

            if (!in_array($data, $called, true)) {
                $called[] = $data;
                [$entities, $called] = $this->getDependantEntities($dependency, $entities, $called);
                $entities[] = $data;
            }
        }

        return [$entities, $called];
    }
}
