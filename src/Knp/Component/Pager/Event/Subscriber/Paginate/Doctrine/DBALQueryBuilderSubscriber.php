<?php

namespace Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * DBALQueryBuilderSubscriber.php
 *
 * @author Vladimir Chub <v@chub.com.ua>
 */
class DBALQueryBuilderSubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        if ($event->target instanceof QueryBuilder) {
            /** @var $target QueryBuilder */
            $target = $event->target;

            // count results
            $countQb = clone $target;

            // remove order by as this is not allowed in subqueries on some platforms
            if ($countQb->getMaxResults() === null && $countQb->getFirstResult() == null) {
                $countQb->resetQueryPart('orderBy');
            }
            $qb = clone $target;
            $qb
                ->resetQueryParts()
                ->select('count(*) as cnt')
                ->from('(' . $countQb->getSQL() . ')', 'ololoshke_trololoshke')
            ;

            $event->count = $qb
                ->execute()
                ->fetchColumn(0)
            ;

            // if there is results
            $event->items = array();
            if ($event->count) {
                $qb = clone $target;
                $qb
                    ->setFirstResult($event->getOffset())
                    ->setMaxResults($event->getLimit())
                ;
                
                $event->items = $qb
                    ->execute()
                    ->fetchAll()
                ;
            }
            
            $event->stopPropagation();
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            'knp_pager.items' => array('items', 10 /*make sure to transform before any further modifications*/)
        );
    }
}
