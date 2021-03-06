<?php

/*
 * This file is part of staccato list component
 *
 * (c) Krystian Karaś <dev@karashome.pl>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Staccato\Component\ListLoader\Behavior\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Staccato\Component\ListLoader\Repository\Doctrine\ListableRepository;

trait ListableBehavior
{
    /**
     * Create ListableRepository based on this repository.
     *
     * @return ListableRepository
     */
    public function createList(): ListableRepository
    {
        return new ListableRepository($this);
    }

    /**
     * Create new instance of QueryBuilder.
     *
     * @return QueryBuilder
     */
    protected function createListableQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('e');
    }

    /**
     * Set conditions on QueryBilder by filters.
     *
     * @param QueryBuilder $qb
     * @param array        $filters
     */
    protected function setQueryBuilderFilters(QueryBuilder $qb, array $filters): void
    {
        // Should be overriden in repository
    }

    /**
     * Set sorting on QueryBilder.
     *
     * @param QueryBuilder $qb
     * @param string|null  $name
     * @param string       $type
     */
    protected function setQueryBuilderSorter(QueryBuilder $qb, $name, $type): void
    {
        // Should be overriden in repository
    }

    /**
     * Check if column prefix exists and append if not.
     *
     * @param QueryBuilder $qb
     * @param string       $columnName
     *
     * @return string
     */
    protected function columnPrefix(QueryBuilder $qb, $columnName) : string
    {
        if (false === strpos($columnName, '.')) {
            $columnName = $qb->getRootAliases()[0].'.'.$columnName;
        }

        return (string) $columnName;
    }

    /**
     * Set order on QueryBuilder.
     *
     * @param QueryBuilder $qb
     * @param string       $columnName column name
     * @param string       $type       type of sort (asc or desc)
     * @param bool         $add        add order
     *
     * @return self
     */
    protected function orderBy(QueryBuilder $qb, $columnName, $type, $add = false)
    {
        $type = strtoupper($type);

        if ($type !== 'ASC' && $type !== 'DESC') {
            $type = 'ASC';
        }

        $orderBy = $add ? 'addOrderBy' : 'orderBy';

        $qb->$orderBy($this->columnPrefix($qb, $columnName), $type);

        return $this;
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $columnName
     * @param mixed       $value
     * @param bool         $or
     * @param bool         $not
     *
     * @return $this
     */
    protected function like(QueryBuilder $qb, $columnName, $value, bool $or = false, bool $not = false)
    {
        $where = $or ? 'orWhere' : 'andWhere';
        $like = $not ? 'notLike' : 'like';

        $qb->$where(
            $qb->expr()->$like(
                $this->columnPrefix($qb, $columnName),
                $qb->expr()->literal($value)
            )
        );

        return $this;
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $columnName
     * @param string|array $value
     * @param bool         $or
     * @param bool         $not
     *
     * @return $this
     */
    protected function equal(QueryBuilder $qb, $columnName, $value, bool $or = false, bool $not = false)
    {
        $where = $or ? 'orWhere' : 'andWhere';

        if (is_array($value)) {
            $eq = $not ? 'notIn' : 'in';
        } else {
            $eq = $not ? 'neq' : 'eq';
            $value = $qb->expr()->literal($value);
        }

        $qb->$where(
             $qb->expr()->$eq(
                 $this->columnPrefix($qb, $columnName),
                 $value
             )
         );

        return $this;
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $columnName
     * @param mixed        $value
     * @param bool         $or
     * @param bool         $equal
     *
     * @return $this
     */
    protected function greaterThan(QueryBuilder $qb, $columnName, $value, bool $or = false, bool $equal = false)
    {
        $where = $or ? 'orWhere' : 'andWhere';
        $cmp = $equal ? 'gte' : 'gt';

        $qb->$where(
            $qb->expr()->$cmp(
                $this->columnPrefix($qb, $columnName),
                $qb->expr()->literal($value)
            )
        );

        return $this;
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $columnName
     * @param mixed        $value
     * @param bool         $or
     * @param bool         $equal
     *
     * @return $this
     */
    protected function lessThan(QueryBuilder $qb, $columnName, $value, bool $or = false, bool $equal = false)
    {
        $where = $or ? 'orWhere' : 'andWhere';
        $cmp = $equal ? 'lte' : 'lt';

        $qb->$where(
            $qb->expr()->$cmp(
                $this->columnPrefix($qb, $columnName),
                $qb->expr()->literal($value)
            )
        );

        return $this;
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $columnName
     * @param mixed        $dateOrRange
     *
     * @return $this
     */
    protected function dateRange(QueryBuilder $qb, $columnName, $dateOrRange)
    {
        if (is_array($dateOrRange)) {
            if (isset($dateOrRange['from']) && is_string($dateOrRange['from'])) {
                try {
                    $dateOrRange['from'] = new \DateTime($dateOrRange['from']);
                } catch (\Exception $e) {
                    unset($dateOrRange['from']);
                }
            }

            if (isset($dateOrRange['to']) && is_string($dateOrRange['to'])) {
                try {
                    $dateOrRange['to'] = new \DateTime($dateOrRange['to']);
                } catch (\Exception $e) {
                    unset($dateOrRange['to']);
                }
            }
        } elseif (is_string($dateOrRange)) {
            try {
                $dateOrRange = new \DateTime($dateOrRange);
            } catch (\Exception $e) {
                $dateOrRange = null;
            }
        } else {
            $dateOrRange = null;
        }

        if ($dateOrRange instanceof \DateTime) {
            $dateOrRange = array(
                'from' => $dateOrRange,
                'to' => $dateOrRange,
            );
        }

        if (is_array($dateOrRange)) {
            if (isset($dateOrRange['from']) && $dateOrRange['from'] instanceof \DateTime) {
                $this->andGreaterThanEqual($qb, $columnName, $dateOrRange['from']->format('Y-m-d').' 00:00:00');
            }

            if (isset($dateOrRange['to']) && $dateOrRange['to'] instanceof \DateTime) {
                $this->andLessThanEqual($qb, $columnName, $dateOrRange['to']->format('Y-m-d').' 23:59:59');
            }
        }

        return $this;
    }

    /**
     * Alias for greaterThan.
     *
     * @param QueryBuilder $qb
     * @param string       $columnName
     * @param mixed        $value
     *
     * @return self
     */
    protected function andGreaterThan(QueryBuilder $qb, $columnName, $value)
    {
        return $this->greaterThan($qb, $columnName, $value, false, false);
    }

    /**
     * Alias for greaterThan.
     *
     * @param QueryBuilder $qb
     * @param string       $columnName
     * @param mixed        $value
     *
     * @return self
     */
    protected function orGreaterThan(QueryBuilder $qb, $columnName, $value)
    {
        return $this->greaterThan($qb, $columnName, $value, true, false);
    }

    /**
     * Alias for greaterThanEqual.
     *
     * @param QueryBuilder $qb
     * @param string       $columnName
     * @param mixed        $value
     *
     * @return self
     */
    protected function andGreaterThanEqual(QueryBuilder $qb, $columnName, $value)
    {
        return $this->greaterThan($qb, $columnName, $value, false, true);
    }

    /**
     * Alias for greaterThanEqual.
     *
     * @param QueryBuilder $qb
     * @param string       $columnName
     * @param mixed        $value
     *
     * @return self
     */
    protected function orGreaterThanEqual(QueryBuilder $qb, $columnName, $value)
    {
        return $this->greaterThan($qb, $columnName, $value, true, true);
    }

    /**
     * Alias for lessThan.
     *
     * @param QueryBuilder $qb
     * @param string       $columnName
     * @param mixed        $value
     *
     * @return self
     */
    protected function andLessThan(QueryBuilder $qb, $columnName, $value)
    {
        return $this->lessThan($qb, $columnName, $value, false, false);
    }

    /**
     * Alias for lessThan.
     *
     * @param QueryBuilder $qb
     * @param string       $columnName
     * @param mixed        $value
     *
     * @return self
     */
    protected function orLessThan(QueryBuilder $qb, $columnName, $value)
    {
        return $this->lessThan($qb, $columnName, $value, true, false);
    }

    /**
     * Alias for lessThanEqual.
     *
     * @param QueryBuilder $qb
     * @param string       $columnName
     * @param mixed        $value
     *
     * @return self
     */
    protected function andLessThanEqual(QueryBuilder $qb, $columnName, $value)
    {
        return $this->lessThan($qb, $columnName, $value, false, true);
    }

    /**
     * Alias for lessThanEqual.
     *
     * @param QueryBuilder $qb
     * @param string       $columnName
     * @param mixed        $value
     *
     * @return self
     */
    protected function orLessThanEqual(QueryBuilder $qb, $columnName, $value)
    {
        return $this->lessThan($qb, $columnName, $value, true, true);
    }

    /**
     * Alias for like.
     *
     * @param QueryBuilder $qb
     * @param string       $columnName
     * @param mixed        $value
     *
     * @return self
     */
    protected function andLike(QueryBuilder $qb, $columnName, $value)
    {
        return $this->like($qb, $columnName, $value, false, false);
    }

    /**
     * Alias for like.
     *
     * @param QueryBuilder $qb
     * @param string       $columnName
     * @param mixed        $value
     *
     * @return self
     */
    protected function orLike(QueryBuilder $qb, $columnName, $value)
    {
        return $this->like($qb, $columnName, $value, true, false);
    }

    /**
     * Alias for like.
     *
     * @param QueryBuilder $qb
     * @param string       $columnName
     * @param mixed        $value
     *
     * @return self
     */
    protected function andNotLike(QueryBuilder $qb, $columnName, $value)
    {
        return $this->like($qb, $columnName, $value, false, true);
    }

    /**
     * Alias for like.
     *
     * @param QueryBuilder $qb
     * @param string       $columnName
     * @param mixed        $value
     *
     * @return self
     */
    protected function orNotLike(QueryBuilder $qb, $columnName, $value)
    {
        return $this->like($qb, $columnName, $value, true, true);
    }

    /**
     * Alias for equal.
     *
     * @param QueryBuilder $qb
     * @param string       $columnName
     * @param mixed        $value
     *
     * @return self
     */
    protected function andEqual(QueryBuilder $qb, $columnName, $value)
    {
        return $this->equal($qb, $columnName, $value, false, false);
    }

    /**
     * Alias for equal.
     *
     * @param QueryBuilder $qb
     * @param string       $columnName
     * @param mixed        $value
     *
     * @return self
     */
    protected function orEqual(QueryBuilder $qb, $columnName, $value)
    {
        return $this->equal($qb, $columnName, $value, true, false);
    }

    /**
     * Alias for equal.
     *
     * @param QueryBuilder $qb
     * @param string       $columnName
     * @param mixed        $value
     *
     * @return self
     */
    protected function andNotEqual(QueryBuilder $qb, $columnName, $value)
    {
        return $this->equal($qb, $columnName, $value, false, true);
    }

    /**
     * Alias for equal.
     *
     * @param QueryBuilder $qb
     * @param string       $columnName
     * @param mixed        $value
     *
     * @return self
     */
    protected function orNotEqual(QueryBuilder $qb, $columnName, $value)
    {
        return $this->equal($qb, $columnName, $value, true, true);
    }
}
