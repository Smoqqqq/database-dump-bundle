<?php

namespace Smoq\DatabaseDumpBundle;

interface DumperInterface
{
    /**
     * @param string $filepath
     * @param string[] $exclude
     * @param bool $overwrite
     */
    public function dumpToFile(string $filepath, array $exclude, bool $overwrite): void;
}
