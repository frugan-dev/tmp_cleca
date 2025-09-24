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

class MoFileLoader extends \Symfony\Component\Translation\Loader\MoFileLoader
{
    #[\Override]
    protected function loadResource(string $resource): array
    {
        $stream = \Safe\fopen($resource, 'r');

        $stat = \Safe\fstat($stream);

        if ($stat['size'] < self::MO_HEADER_SIZE) {
            throw new InvalidResourceException('MO stream content has an invalid format.');
        }
        $magic = \Safe\unpack('V1', \Safe\fread($stream, 4));
        $magic = hexdec(substr(dechex(current($magic)), -8));

        if (self::MO_LITTLE_ENDIAN_MAGIC === $magic) {
            $isBigEndian = false;
        } elseif (self::MO_BIG_ENDIAN_MAGIC === $magic) {
            $isBigEndian = true;
        } else {
            throw new InvalidResourceException('MO stream content has an invalid format.');
        }

        // formatRevision
        $this->readLong($stream, $isBigEndian);
        $count = $this->readLong($stream, $isBigEndian);
        $offsetId = $this->readLong($stream, $isBigEndian);
        $offsetTranslated = $this->readLong($stream, $isBigEndian);
        // sizeHashes
        $this->readLong($stream, $isBigEndian);
        // offsetHashes
        $this->readLong($stream, $isBigEndian);

        $messages = [];

        for ($i = 0; $i < $count; ++$i) {
            $pluralId = null;
            $translated = null;

            fseek($stream, $offsetId + $i * 8);

            $length = $this->readLong($stream, $isBigEndian);
            $offset = $this->readLong($stream, $isBigEndian);

            if ($length < 1) {
                continue;
            }

            fseek($stream, $offset);
            $singularId = \Safe\fread($stream, $length);

            if (str_contains($singularId, "\000")) {
                [$singularId, $pluralId] = explode("\000", $singularId);
            }

            fseek($stream, $offsetTranslated + $i * 8);
            $length = $this->readLong($stream, $isBigEndian);
            $offset = $this->readLong($stream, $isBigEndian);

            if ($length < 1) {
                continue;
            }

            fseek($stream, $offset);
            $translated = \Safe\fread($stream, $length);

            if (str_contains($translated, "\000")) {
                $translated = explode("\000", $translated);
            }

            // Handle context: check if singularId contains ASCII 4 separator
            $context = null;
            if (str_contains($singularId, "\004")) {
                [$context, $singularId] = explode("\004", $singularId, 2);
            }

            $ids = ['singular' => $singularId, 'plural' => $pluralId];
            $item = compact('ids', 'translated', 'context');

            if (!empty($item['ids']['singular'])) {
                $id = $item['ids']['singular'];

                // Include context in the key if present
                if (!empty($item['context'])) {
                    $id = $item['context']."\004".$id; // Use ASCII 4 as separator (standard gettext)
                }

                if (isset($item['ids']['plural'])) {
                    $id .= '|'.$item['ids']['plural'];
                }
                $messages[$id] = stripcslashes(implode('|', (array) $item['translated']));
            }
        }

        \Safe\fclose($stream);

        return array_filter($messages);
    }

    private function readLong($stream, bool $isBigEndian): int
    {
        $result = \Safe\unpack($isBigEndian ? 'N1' : 'V1', \Safe\fread($stream, 4));
        $result = current($result);

        return (int) substr((string) $result, -8);
    }
}
