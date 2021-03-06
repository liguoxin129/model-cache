<?php

declare(strict_types=1);

namespace Liguoxin129\ModelCache\Listener;

use Illuminate\Database\Events\TransactionCommitted;
use Liguoxin129\ModelCache\InvalidCacheManager;

class DeleteCacheInTransactionListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            TransactionCommitted::class,
        ];
    }

    public function process(object $event)
    {
        if (! $event instanceof TransactionCommitted) {
            return;
        }

        if ($event->connection->transactionLevel() === 0) {
            InvalidCacheManager::instance()->delete();
        }
    }
}
