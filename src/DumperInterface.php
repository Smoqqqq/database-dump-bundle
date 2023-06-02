<?php

namespace Smoq\DatabaseDumpBundle;

interface DumperInterface
{
    public function dumpToFile(string $filepath, array $exclude, bool $overwrite);
}
