<?php

declare(strict_types=1);

/**
 * DatabaseDumpBundle - Paul Le Flem <contact@paul-le-flem.fr>
 */

namespace Smoq\DatabaseDumpBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Smoq\DatabaseDumpBundle\DependencyInjection\DatabaseDumpExtension;

class DatabaseDumpBundle extends Bundle
{

    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $ext = new DatabaseDumpExtension([], $container);
    }

    public function getContainerExtension()
    {
        return new DatabaseDumpExtension();
    }
}
