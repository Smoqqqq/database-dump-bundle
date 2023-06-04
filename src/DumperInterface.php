<?php

namespace Smoq\DatabaseDumpBundle;

interface DumperInterface
{
    /**
     * Dump the entire database
     *
     * @param string $filepath
     * @param string[] $exclude
     * @param bool $overwrite
     */
    public function dump(string $filepath, array $exclude, bool $overwrite): void;

    /**
     * Dump only the schema
     *
     * @param string $filepath
     * @param string[] $exclude
     * @param bool $overwrite
     */
    public function dumpSchema(string $filepath, bool $overwrite): void;
}
