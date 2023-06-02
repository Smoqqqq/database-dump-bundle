<?php

namespace Smoq\DatabaseDumpBundle;

use Doctrine\ORM\Mapping\ClassMetadata;

class  SqlDumper extends Dumper
{
    private array $dependencyTree = [];
    private array $entityClasses = [];

    /**
     * Generates the .sql file for creating the database,
     * and saves it to the given filepath
     */
    public function dumpToFile(string $filepath, bool $overwride = false)
    {
        $this->openFile($filepath, $overwride);
        $this->writeSchema();

        $this->getEntityClasses(dirname(getcwd(), 1) . "/src/Entity", "App\Entity");
        $this->getEntityDependencies($this->entityClasses);
        $entities = $this->getEntityOrder();

        $this->createEntityInsertStatements($entities);
    }

    private function createEntityInsertStatements(array $entities)
    {
        foreach ($entities as $entity) {
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

    private function createInsertStatement(array $entity)
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
                } else if (null === $col) {
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

    private function getEntityDependencies(array $entityClasses)
    {
        $em = $this->doctrine->getManager();
        dd($em->getClassMetadata("App\Entity\Training"), $em->getClassMetadata("App\Entity\Role\Teacher\Teacher"));
        foreach ($entityClasses as $class) {
            if (!isset($this->dependencyTree[$class])) {
                $dependencies = $this->getSingleEntityDependencies($class);

                $this->dependencyTree[$class] = $dependencies;
            }
        }
    }

    private function getSingleEntityDependencies(string $class)
    {
        $em = $this->doctrine->getManager();
        /** @var ClassMetadata */
        $classMetadata = $em->getClassMetadata($class);
        $dependencies = [
            "dependencies" => [],
            "table" => $classMetadata->getTableName()
        ];

        if ($class === "App\Entity\Training") {
            dd($classMetadata->getAssociationMappings());
        }

        foreach ($classMetadata->getAssociationMappings() as $dependency) {

            // Handle ManyToMany join tables
            if ($dependency["type"] === 8) {
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
    private function getEntityClasses(string $dir, string $namespace)
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
    private function getEntityOrder()
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

    private function getDependantEntities(array $entity, array $entities, array $called)
    {
        foreach ($entity["dependencies"] as $key => $dependecy) {

            $data = [
                "class" => $key,
                "table" => $dependecy["table"]
            ];

            if (!in_array($data, $called, true)) {
                $called[] = $data;
                [$entities, $called] = $this->getDependantEntities($dependecy, $entities, $called);
                $entities[] = $data;
            }
        }

        return [$entities, $called];
    }
}
