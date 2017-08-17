<?php

namespace Staccato\Component\ListLoader\Behavior\Doctrine;

use Staccato\Component\ListLoader\Repository\Doctrine\ListableRepository;

interface ListableInterface
{
    /**
     * Create ListableRepository based on this repository.
     *
     * @return ListableRepository
     */
    public function createList(): ListableRepository;
}