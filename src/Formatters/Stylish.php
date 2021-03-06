<?php

namespace Differ\Formatters;

use function Differ\Utils\toString;
use function Differ\Utils\isAssoc;

function getStylishStatus(int $status)
{
    switch ($status) {
        case 0:
            return " ";
        case 1:
            return "+";
        case -1:
            return "-";
    }
    return '';
}

function stylish(mixed $node, string $replacer = " ", int $spacesCount = 2): string
{

    $iter = function ($node, $depth) use (&$iter, $replacer, $spacesCount) {
        $isNode = (
            isAssoc($node) && array_key_exists('data', $node)
            ) || (
                !isAssoc($node) && is_array($node) && array_key_exists('data', $node[0])
            );

        if (!is_array($node)) {
            return toString($node);
        }

        $indentSize = $depth * $spacesCount;
        $currentIndent = str_repeat($replacer, $indentSize);
        $bracketIndent = str_repeat($replacer, $indentSize - $spacesCount);
        $openBracket = array_key_exists('type', $node)  && $node['type'] === 'array' ? "[" : "{";
        $closeBracket = array_key_exists('type', $node) && $node['type'] === 'array' ? "]" : "}";

        if (!$isNode) {
            $newCurrentIndent = str_repeat($replacer, $indentSize + $spacesCount);
            $maped = array_map(
                fn($key, $val) => "{$newCurrentIndent}{$key}: {$iter($val, $depth + 2)}",
                array_keys($node),
                $node
            );

            $result = [$openBracket, ...$maped, "{$bracketIndent}{$closeBracket}"];
            return implode("\n", $result);
        }

        $lines = array_reduce($node, function ($acc, $item) use (&$iter, $currentIndent, $depth) {

            $data = $item['data'];
            $statusCode = $item['meta']['status'];
            $status = getStylishStatus($statusCode);
            $key = array_key_first($data);
            $val = $data[$key];
            if ($statusCode === 2) {
                $removed = $val[0]['data'];
                $added = $val[1]['data'];
                $removedVal = !is_array($removed[$key]) ? toString($removed[$key]) : $removed[$key];
                $addedVal = !is_array($added[$key]) ? toString($added[$key]) : $added[$key];

                return [...$acc, !is_array($removedVal) ?
                    "{$currentIndent}- {$key}: {$removedVal}" :
                        "{$currentIndent}- {$key}: {$iter($removedVal, $depth + 2)}", !is_array($addedVal) ?
                            "{$currentIndent}+ {$key}: {$addedVal}" :
                                "{$currentIndent}+ {$key}: {$iter($addedVal, $depth + 2)}"];
            }
            return [...$acc, "{$currentIndent}{$status} {$key}: {$iter($val, $depth + 2)}"];
        }, []);

        $result = [$openBracket, ...$lines, "{$bracketIndent}{$closeBracket}"];

        return implode("\n", $result);
    };

    return $iter($node, 1);
}
