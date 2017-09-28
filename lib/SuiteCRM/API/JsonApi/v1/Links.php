<?php

namespace SuiteCRM\API\JsonApi\v1;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use SuiteCRM\API\JsonApi\v1\Enumerator\LinksMessage;
use SuiteCRM\Utility\SuiteLogger as Logger;

/**
 * Class Links
 * @package SuiteCRM\API\JsonApi\v1
 * @see http://jsonapi.org/format/1.0/#document-links
 */
class Links implements LoggerAwareInterface
{
    /**
     * @var string $self
     */
    private $self;

    /**
     * @var string $first
     */
    private $first;

    /**
     * @var bool $hasPagination
     */
    private $hasPagination;

    /**
     * @var string $prev
     */
    private $prev;

    /**
     * @var string $next
     */
    private $next;

    /**
     * @var string $last
     */
    private $last;

    /**
     * @var string $href
     */
    private $href;

    /**
     * @var array $meta
     */
    private $meta;

    /**
     * @var Links related
     */
    private $related;

    /**
     * @var LoggerInterface Logger
     */
    private $logger;

    /**
     * Links constructor.
     */
    public function __construct()
    {
        $this->setLogger(new Logger());
    }

    public static function get()
    {
        return new self();
    }

    /**
     * @param string $url
     * @return Links
     */
    public function withSelf($url)
    {
        if ($this->validateUrl($url)) {
            $this->self = $url;
        } else {
            $this->logger->error(LinksMessage::INVALID_URL_PARAMETER);
        }

        return $this;
    }

    /**
     * Tells Links that you intend to display pagination links even if you do not set the pagination values.
     * @return Links
     */
    public function withPagination()
    {
        $this->hasPagination = true;

        return $this;
    }


    /**
     * @param string $url
     * @return Links
     */
    public function withFirst($url)
    {
        $this->hasPagination = true;
        if ($this->validateUrl($url)) {
            $this->first = $url;
        } else {
            $this->logger->error(LinksMessage::INVALID_URL_PARAMETER);
        }

        return $this;
    }

    /**
     * @param string $url
     * @return Links
     */
    public function withPrev($url)
    {
        $this->hasPagination = true;
        if ($this->validateUrl($url)) {
            $this->prev = $url;
        } else {
            $this->logger->error(LinksMessage::INVALID_URL_PARAMETER);
        }

        return $this;
    }

    /**
     * @param string $url
     * @return Links
     */
    public function withNext($url)
    {
        $this->hasPagination = true;
        if ($this->validateUrl($url)) {
            $this->next = $url;
        } else {
            $this->logger->error(LinksMessage::INVALID_URL_PARAMETER);
        }

        return $this;
    }

    /**
     * @param string $url
     * @return Links
     */
    public function withLast($url)
    {
        $this->hasPagination = true;
        if ($this->validateUrl($url)) {
            $this->last = $url;
        } else {
            $this->logger->error(LinksMessage::INVALID_URL_PARAMETER);
        }

        return $this;
    }

    /**
     * @param array $meta
     * @return Links
     */
    public function withMeta($meta)
    {
        if ($this->meta === null) {
            $this->meta = $meta;
        } else {
            $this->meta = array_merge($this->meta, $meta);
        }

        return $this;
    }


    /**
     * @param string $url
     * @return Links
     */
    public function withHref($url)
    {
        $this->href = $url;

        return $this;
    }

    /**
     * @param Links $related
     * @return Links
     */
    public function withRelated(Links $related)
    {
        $this->related = $related;

        return $this;
    }

    /**
     * @return array
     */
    public function getArray()
    {
        $response = array();
        if ($this->hasSelf()) {
            $response['self'] = $this->self;
        }

        if ($this->hasHref()) {
            $response['href'] = $this->href;
        }

        if ($this->hasMeta()) {
            $response['meta'] = $this->meta;
        }

        if ($this->hasPagination()) {
            if($this->first !== null) {
                $response['first'] = $this->first;
            }

            if($this->prev !== null) {
                $response['prev'] = $this->prev;
            }

            if($this->next !== null) {
                $response['next'] = $this->next;
            }

            if($this->last !== null) {
                $response['last'] = $this->last;
            }
        }

        if ($this->hasRelated()) {
            $response['related'] = $this->relate->getArray();
        }

        return $response;
    }

    /**
     * @return bool
     */
    private function hasPagination()
    {
        return $this->hasPagination;
    }

    /**
     * @return bool
     */
    private function hasSelf()
    {
        return $this->self !== null;
    }

    /**
     * @return bool
     */
    private function hasHref()
    {
        return $this->href !== null;
    }

    /**
     * @return bool
     */
    private function hasMeta()
    {
        return $this->meta !== null;
    }

    /**
     * @return bool
     */
    private function hasRelated()
    {
        return $this->related !== null;
    }

    /**
     * @param string $url
     * @return bool true === valid, false === invalid
     */
    private function validateUrl($url)
    {
        $isValid = filter_var(
            $url,
            FILTER_VALIDATE_URL
        );

        return false !== $isValid;
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}