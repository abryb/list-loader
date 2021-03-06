<?php

/*
 * This file is part of staccato list component
 *
 * (c) Krystian Karaś <dev@karashome.pl>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Staccato\Component\ListLoader\Repository;

abstract class ListableRepository
{
    /**
     * @var array
     */
    protected $filters = array();

    /**
     * @var array
     */
    protected $sorters = array();

    /**
     * Find matching objects and compose list.
     *
     * @param int $limit limit of objects per page (0 = no limit)
     * @param int $page  find page
     *
     * @return array result set
     */
    abstract public function find(int $limit = 0, int $page = 0) : array;

    /**
     * Count number of matching objects.
     *
     * @return int
     */
    abstract public function count() : int;

    /**
     * Set new filter.
     *
     * @param string $name
     * @param mixed $value
     *
     * @return self
     */
    public function filterBy(string $name, $value) : ListableRepository
    {
        $this->filters[$name] = $value;

        return $this;
    }

    /**
     * Set filters.
     *
     * @param array $filters
     *
     * @return self
     */
    public function setFilters(array $filters) : ListableRepository
    {
        foreach ($filters as $f => $v) {
            $this->filterBy($f, $v);
        }

        return $this;
    }

    /**
     * Order list.
     *
     * @param string|null $name sorter name
     * @param string      $type asc or desc
     *
     * @return ListableRepository
     */
    public function orderBy(?string $name = null, ?string $type = 'ASC') : ListableRepository
    {
        $this->sorters[] =
            [
                'name' => $name,
                'type' => $type
            ];

        return $this;
    }

    /**
     * @param array $sorters
     * @return ListableRepository
     */
    public function setSorters(array $sorters) : ListableRepository
    {
        if ( isset($sorters['name'], $sorters['type'])) {
            $this->orderBy($sorters['name'], $sorters['type']);
        }
        foreach ($sorters as $k => $s) {
            if (isset($s['name'], $s['type'])) {
                $this->orderBy($s['name'], $s['type']);
            }
        }

        return $this;
    }
}
