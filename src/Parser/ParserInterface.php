<?php

namespace App\Parser;

use App\Entity\Source;
use Doctrine\Common\Collections\ArrayCollection;

interface ParserInterface
{
    public function setSource(Source $source): ParserInterface;

    public function getSource(): Source;

    public function getCount(): int;

    public function hasErrors(): bool;

    public function getErrorMessage(): string;

    public function setErrorMessage(string $errorMessage): ParserInterface;

    public function getItems(): ArrayCollection;
}