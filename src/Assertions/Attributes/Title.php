<?php

namespace Nahid\Apily\Assertions\Attributes;

#[\Attribute] class Title implements \Stringable
{

    public function __construct(protected string $title)
    {
    }
    public function __toString(): string
    {
        return $this->title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}