<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Contracts;

use Illuminate\Http\Request;
use Pradeepdev\SmartFilter\Collections\FilterCollection;

/**
 * Contract for request parser implementations.
 *
 * A Parser reads raw request data and produces a structured FilterCollection.
 * This abstraction allows swapping the request format (query string, JSON body,
 * headers, etc.) without touching any other layer.
 */
interface ParserContract
{
    /**
     * Parse the request and return a collection of resolved filter inputs.
     */
    public function parse(Request $request): FilterCollection;
}
