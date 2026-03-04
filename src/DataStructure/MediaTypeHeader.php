<?php

namespace AKlump\CheckPages\DataStructure;

/**
 * A specialized header object for headers that follow media-type semantics.
 *
 * Use this class when you need to work with headers that contain one or more
 * media types (e.g., `Accept`, `Content-Type`), often separated by commas,
 * and potentially containing parameters (e.g., `; charset=utf-8`).
 *
 * This class performs:
 * 1. CSV splitting (e.g., `text/html, application/json` -> `['text/html', 'application/json']`).
 * 2. Parameter stripping (e.g., `text/html; charset=utf-8` -> `text/html`).
 * 3. Trimming of whitespace around media types.
 *
 * DO NOT use this class for headers that are NOT media-type-ish or where commas
 * and semicolons are part of the raw data (e.g., `Date`, `Set-Cookie`, `Server`).
 * For generic, raw header storage, use the parent `HttpHeader` class instead.
 */
class MediaTypeHeader extends HttpHeader
{
    /** @return string[] */
    public function getMediaTypes(): array
    {
        $all = [];
        foreach ($this->getLines() as $line) {
            $parts = preg_split('/\s*,\s*/', $line) ?: [];
            foreach ($parts as $part) {
                $type = explode(';', $part, 2)[0] ?? '';
                $type = trim($type);
                if ($type !== '') {
                    $all[] = $type;
                }
            }
        }

        return array_values($all);
    }

    public function __toString(): string
    {
        return $this->getMediaTypes()[0] ?? '';
    }
}
