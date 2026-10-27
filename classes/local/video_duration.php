<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Read video duration from uploaded media rather than request parameters.
 *
 * @package   mod_videorubric
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videorubric\local;

/**
 * Bounded-memory reader for MP4/QuickTime and WebM container timestamps.
 */
final class video_duration {
    /**
     * Get video duration in whole seconds, rounded upwards.
     *
     * @param string $pathname Uploaded temporary file.
     * @param string $extension Filename extension.
     * @return int|null Duration or null if metadata cannot be trusted.
     */
    public static function from_file(string $pathname, string $extension): ?int {
        $handle = @fopen($pathname, 'rb');
        if (!$handle) {
            return null;
        }
        try {
            $size = filesize($pathname);
            if ($size === false || $size < 8) {
                return null;
            }
            if (strtolower($extension) === 'webm') {
                $seconds = self::read_webm($handle, $size);
            } else {
                $seconds = self::read_mp4($handle, $size);
            }
            if ($seconds === null || !is_finite($seconds) || $seconds <= 0 || $seconds >= PHP_INT_MAX) {
                return null;
            }
            return (int)ceil($seconds);
        } finally {
            fclose($handle);
        }
    }

    /**
     * Read MP4/QuickTime movie metadata without loading the media payload.
     *
     * @param resource $handle File handle.
     * @param int $size File size.
     * @return float|null Seconds.
     */
    private static function read_mp4($handle, int $size): ?float {
        $seconds = null;
        $stack = [$size];
        $iterations = 0;
        while ($stack && ftell($handle) < $size && ++$iterations <= 100000) {
            $end = end($stack);
            if (ftell($handle) >= $end) {
                array_pop($stack);
                continue;
            }
            $position = ftell($handle);
            if ($position + 8 > $end) {
                return null;
            }
            $header = fread($handle, 8);
            if (strlen($header) !== 8) {
                return null;
            }
            $atomsize = unpack('N', substr($header, 0, 4))[1];
            $type = substr($header, 4, 4);
            $headersize = 8;
            if ($atomsize === 1) {
                $extended = fread($handle, 8);
                if (strlen($extended) !== 8) {
                    return null;
                }
                $parts = unpack('Nhigh/Nlow', $extended);
                $atomsize = $parts['high'] * 4294967296 + $parts['low'];
                $headersize = 16;
            } else if ($atomsize === 0) {
                $atomsize = $end - $position;
            }
            if ($atomsize < $headersize || $atomsize > $end - $position) {
                return null;
            }
            $atomend = $position + $atomsize;
            if (in_array($type, ['moov', 'trak', 'mdia', 'mvex'], true)) {
                $stack[] = $atomend;
                continue;
            }
            if (in_array($type, ['mvhd', 'mdhd'], true)) {
                $payload = fread($handle, min(32, $atomsize - $headersize));
                if (strlen($payload) >= 4 && (ord($payload[0]) === 0 || ord($payload[0]) === 1)) {
                    $version = ord($payload[0]);
                    $offset = $version === 1 ? 20 : 12;
                    $timescale = strlen($payload) >= $offset + 4 ?
                        unpack('N', substr($payload, $offset, 4))[1] : null;
                    $offset += 4;
                    if ($timescale && $timescale > 0) {
                        $durationbytes = $version === 1 ? 8 : 4;
                        if (strlen($payload) >= $offset + $durationbytes) {
                            $duration = self::big_endian_integer(substr($payload, $offset, $durationbytes));
                            if ($duration > 0 && $duration < (2 ** ($durationbytes * 8)) - 1) {
                                $seconds = max($seconds ?? 0, $duration / $timescale);
                            }
                        }
                    }
                }
            }
            fseek($handle, $atomend);
        }
        return $seconds;
    }

    /**
     * Read WebM Duration, and fall back to block timestamps for browser recordings.
     *
     * @param resource $handle File handle.
     * @param int $size File size.
     * @return float|null Seconds.
     */
    private static function read_webm($handle, int $size): ?float {
        $signature = fread($handle, 4);
        if ($signature !== "\x1a\x45\xdf\xa3") {
            return null;
        }
        rewind($handle);
        $scale = 1000000;
        $duration = null;
        $maxblock = null;
        $clustertime = 0;
        $insegment = false;
        $iterations = 0;
        while (ftell($handle) < $size && ++$iterations <= 2000000) {
            $elementid = self::ebml_integer($handle, true);
            $length = self::ebml_integer($handle, false);
            if ($elementid === null || $length === null) {
                return null;
            }
            $start = ftell($handle);
            $amount = $length[0] ?? ($size - $start);
            if ($amount < 0 || $amount > $size - $start) {
                return null;
            }
            $end = $start + $amount;
            $id = $elementid[0];
            if ($id === 0x18538067) {
                $insegment = true;
                continue;
            } else if ($insegment && $id === 0x1F43B675) {
                $clustertime = 0;
                continue;
            } else if ($insegment && $id === 0x2AD7B1 && $amount > 0 && $amount <= 8) {
                $scale = self::big_endian_integer(fread($handle, $amount));
                if ($scale <= 0) {
                    return null;
                }
            } else if ($insegment && $id === 0x4489 && ($amount === 4 || $amount === 8)) {
                $bytes = fread($handle, $amount);
                $value = unpack($amount === 4 ? 'G' : 'E', $bytes)[1];
                if (is_finite($value) && $value > 0) {
                    $duration = max($duration ?? 0, $value);
                }
            } else if ($insegment && $id === 0xE7 && $amount > 0 && $amount <= 8) {
                $clustertime = self::big_endian_integer(fread($handle, $amount));
            } else if ($insegment && ($id === 0xA3 || $id === 0xA1) && $amount >= 4) {
                $bytes = fread($handle, min($amount, 12));
                $first = ord($bytes[0]);
                $trackbytes = 1;
                while ($trackbytes <= 4 && !($first & (0x80 >> ($trackbytes - 1)))) {
                    $trackbytes++;
                }
                if ($trackbytes <= 4 && strlen($bytes) >= $trackbytes + 2) {
                    $timestamp = unpack('n', substr($bytes, $trackbytes, 2))[1];
                    if ($timestamp >= 32768) {
                        $timestamp -= 65536;
                    }
                    $maxblock = max($maxblock ?? 0, $clustertime + $timestamp);
                }
            } else if (in_array($id, [0x1A45DFA3, 0x18538067, 0x1549A966, 0x1F43B675, 0xA0], true)) {
                // Descend only into known containers; large video payloads are skipped.
                continue;
            }
            fseek($handle, $end);
        }
        if (!$insegment || $iterations > 2000000) {
            return null;
        }
        $seconds = $duration === null ? null : $duration * $scale / 1000000000;
        if ($maxblock !== null && $maxblock > 0) {
            $seconds = max($seconds ?? 0, $maxblock * $scale / 1000000000);
        }
        return $seconds;
    }

    /**
     * Read EBML identifier or size variable-length integer.
     *
     * @param resource $handle File handle.
     * @param bool $identifier Whether to retain marker bits.
     * @return array|null Parsed value (null value denotes unknown length).
     */
    private static function ebml_integer($handle, bool $identifier): ?array {
        $firstbyte = fread($handle, 1);
        if ($firstbyte === '') {
            return null;
        }
        $first = ord($firstbyte);
        $mask = 0x80;
        $length = 1;
        while (($first & $mask) === 0 && $length < ($identifier ? 4 : 8)) {
            $mask >>= 1;
            $length++;
        }
        if (($first & $mask) === 0) {
            return null;
        }
        $value = $identifier ? $first : $first & ($mask - 1);
        for ($i = 1; $i < $length; $i++) {
            $byte = fread($handle, 1);
            if ($byte === '') {
                return null;
            }
            $value = ($value * 256) + ord($byte);
        }
        if (!$identifier && $value === (2 ** (7 * $length)) - 1) {
            return [null];
        }
        return [$value];
    }

    /**
     * Read a 1-8 byte unsigned big-endian value.
     *
     * @param string $bytes Raw bytes.
     * @return int Value.
     */
    private static function big_endian_integer(string $bytes): int {
        $value = 0;
        for ($i = 0; $i < strlen($bytes); $i++) {
            $value = ($value * 256) + ord($bytes[$i]);
        }
        return $value;
    }
}
