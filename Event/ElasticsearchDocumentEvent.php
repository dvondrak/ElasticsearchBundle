<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Event;

use ONGR\ElasticsearchBundle\Client\Connection;
use ONGR\ElasticsearchBundle\Document\DocumentInterface;

/**
 * Event to be dispatched in various Elasticsearch methods, containing associated document.
 */
class ElasticsearchDocumentEvent extends ElasticsearchEvent
{
    /**
     * @var DocumentInterface
     */
    protected $document;

    /**
     * @param Connection        $connection
     * @param DocumentInterface $document
     */
    public function __construct(Connection $connection, DocumentInterface $document)
    {
        $this->connection = $connection;
        $this->document = $document;
    }

    /**
     * Returns document associated with the event.
     *
     * @return DocumentInterface
     */
    public function getDocument()
    {
        return $this->document;
    }
}