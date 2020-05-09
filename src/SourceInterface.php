<?php


namespace Airlite;


interface SourceInterface
{
    /**
     * @param array $filter
     * @param callable $processor receives an array that represents a chunk of data
     * @return bool
     */
    public function fetch(array $filter, callable $processor);
}