<?php

declare(strict_types=1);

namespace Wakebit\LaravelCycle\TestsApp\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

/**
 * @Entity
 */
final class Article
{
    /**
     * @Column(type="primary")
     */
    private int $id;

    /**
     * @Column(type="string")
     */
    private string $title;

    /**
     * @Column(type="string")
     */
    private string $description;
}
