<?php

declare(strict_types=1);

/*
 * This file is part of the Slim 4 PHP application.
 *
 * (ɔ) Frugan <dev@frugan.it>
 *
 * This source file is subject to the GNU GPLv3 license that is bundled
 * with this source code in the file COPYING.
 */

namespace App\Factory\Translator\Loader;

class PoFileLoader extends \Symfony\Component\Translation\Loader\PoFileLoader
{
    #[\Override]
    protected function loadResource(string $resource): array
    {
        $stream = \Safe\fopen($resource, 'r');

        $defaults = [
            'context' => null,
            'ids' => [],
            'translated' => null,
        ];

        $messages = [];
        $item = $defaults;
        $flags = [];

        while ($line = fgets($stream)) {
            $line = trim($line);

            if ('' === $line) {
                // Whitespace indicated current item is done
                if (!\in_array('fuzzy', $flags, true)) {
                    $this->addMessage($messages, $item);
                }
                $item = $defaults;
                $flags = [];
            } elseif (str_starts_with($line, '#,')) {
                $flags = array_map('trim', explode(',', substr($line, 2)));
            } elseif (str_starts_with($line, 'msgctxt "')) {
                // Parse context
                $item['context'] = substr($line, 9, -1);
            } elseif (str_starts_with($line, 'msgid "')) {
                // We start a new msg so save previous (but keep context if present)
                $this->addMessage($messages, $item);
                $currentContext = $item['context']; // Preserve context
                $item = $defaults;
                $item['context'] = $currentContext; // Restore context
                $item['ids']['singular'] = substr($line, 7, -1);
            } elseif (str_starts_with($line, 'msgstr "')) {
                $item['translated'] = substr($line, 8, -1);
            } elseif ('"' === $line[0]) {
                $continues = isset($item['translated']) ? 'translated' : 'ids';

                if (\is_array($item[$continues])) {
                    $item[$continues][array_key_last($item[$continues])] .= substr($line, 1, -1);
                } else {
                    $item[$continues] .= substr($line, 1, -1);
                }
            } elseif (str_starts_with($line, 'msgid_plural "')) {
                $item['ids']['plural'] = substr($line, 14, -1);
            } elseif (str_starts_with($line, 'msgstr[')) {
                $size = strpos($line, ']');
                $item['translated'][(int) substr($line, 7, 1)] = substr($line, $size + 3, -1);
            }
        }

        // save last item
        if (!\in_array('fuzzy', $flags, true)) {
            $this->addMessage($messages, $item);
        }
        \Safe\fclose($stream);

        return $messages;
    }

    private function addMessage(array &$messages, array $item): void
    {
        if (!empty($item['ids']['singular'])) {
            $id = stripcslashes((string) $item['ids']['singular']);

            // Include context in the key if present
            if (!empty($item['context'])) {
                $id = $item['context']."\004".$id; // Use ASCII 4 as separator (standard gettext)
            }

            if (isset($item['ids']['plural'])) {
                $id .= '|'.stripcslashes($item['ids']['plural']);
            }

            $translated = (array) $item['translated'];
            ksort($translated);
            $count = array_key_last($translated);
            $empties = array_fill(0, $count + 1, '-');
            $translated += $empties;
            ksort($translated);

            $messages[$id] = stripcslashes(implode('|', $translated));
        }
    }
}
