<?php

/*
 * This file is part of php-task library.
 *
 * (c) php-task
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Task\TaskBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Task\Execution\TaskExecutionInterface;
use Task\Storage\TaskExecutionRepositoryInterface;
use Task\TaskInterface;
use Task\TaskStatus;

/**
 * Repository for task-execution.
 */
class TaskExecutionRepository extends EntityRepository implements TaskExecutionRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(TaskInterface $task, \DateTime $scheduleTime)
    {
        return new TaskExecution($task, $task->getHandlerClass(), $scheduleTime, $task->getWorkload());
    }

    /**
     * {@inheritdoc}
     */
    public function save(TaskExecutionInterface $execution)
    {
        $this->_em->persist($execution);
        $this->_em->flush($execution);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(TaskExecutionInterface $execution)
    {
        $this->_em->remove($execution);
        $this->_em->flush($execution);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll($page = 1, $pageSize = null)
    {
        $query = $this->createQueryBuilder('e')
            ->innerJoin('e.task', 't')
            ->getQuery();

        if ($pageSize) {
            $query->setMaxResults($pageSize);
            $query->setFirstResult(($page - 1) * $pageSize);
        }

        return $query->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findPending(TaskInterface $task)
    {
        try {
            return $this->createQueryBuilder('e')
                ->innerJoin('e.task', 't')
                ->where('t.uuid = :uuid')
                ->andWhere('e.status in (:status)')
                ->setParameter('uuid', $task->getUuid())
                ->setParameter('status', [TaskStatus::PLANNED, TaskStatus::RUNNING])
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $e) {
            return;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findByTask(TaskInterface $task)
    {
        return $this->createQueryBuilder('e')
            ->innerJoin('e.task', 't')
            ->where('t.uuid = :uuid')
            ->setParameter('uuid', $task->getUuid())
            ->getQuery()
            ->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findScheduled(\DateTime $dateTime = null)
    {
        if ($dateTime === null) {
            $dateTime = new \DateTime();
        }

        return $this->createQueryBuilder('e')
            ->innerJoin('e.task', 't')
            ->where('e.status = :status AND e.scheduleTime < :dateTime')
            ->setParameter('status', TaskStatus::PLANNED)
            ->setParameter('dateTime', $dateTime)
            ->getQuery()
            ->getResult();
    }
}
