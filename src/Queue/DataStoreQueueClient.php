<?php

namespace zaboy\rest\Queue;

use ReputationVIP\QueueClient\QueueClient;

class DataStoreQueueClient extends QueueClient
{

    /**
     * @inheritdoc
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

}
