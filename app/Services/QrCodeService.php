<?php

namespace App\Services;

use InvalidArgumentException;
use RuntimeException;

class QrCodeService
{
    private const VERSION = 5;

    private const SIZE = 17 + 4 * self::VERSION;

    private const DATA_CODEWORDS = 108;

    private const ECC_CODEWORDS = 26;

    public function codeFor(string $type, int $id): string
    {
        if (! in_array($type, ['location', 'product'], true) || $id < 1) {
            throw new InvalidArgumentException('A QR code requires a supported record type and a positive ID.');
        }

        return implode('|', ['W1', $type === 'location' ? 'L' : 'P', (string) $id, $this->signature($type, $id)]);
    }

    /**
     * @return array{type: string, id: int}|null
     */
    public function decode(string $code): ?array
    {
        if (preg_match('/\AW1\|([LP])\|([1-9][0-9]{0,17})\|([a-f0-9]{32})\z/i', $code, $matches) !== 1) {
            return null;
        }

        $type = strtolower($matches[1]) === 'l' ? 'location' : 'product';
        $id = (int) $matches[2];
        $signature = strtolower($matches[3]);

        if ($id < 1 || ! hash_equals($this->signature($type, $id), $signature)) {
            return null;
        }

        return ['type' => $type, 'id' => $id];
    }

    public function toSvg(string $payload): string
    {
        $matrix = $this->matrix($payload);
        $quietZone = 4;
        $dimension = self::SIZE + 2 * $quietZone;
        $path = [];

        foreach ($matrix as $row => $modules) {
            foreach ($modules as $column => $isDark) {
                if ($isDark) {
                    $path[] = 'M'.($column + $quietZone).' '.($row + $quietZone).'h1v1h-1z';
                }
            }
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$dimension.' '.$dimension.'" role="img" aria-label="QR code">'
            .'<rect width="100%" height="100%" fill="#fff"/>'
            .'<path d="'.implode('', $path).'" fill="#000"/>'
            .'</svg>';
    }

    private function signature(string $type, int $id): string
    {
        $key = (string) config('app.key');
        if ($key === '') {
            throw new RuntimeException('APP_KEY must be configured before generating QR codes.');
        }

        return substr(hash_hmac('sha256', "warehouse-qr:v1:{$type}:{$id}", $key), 0, 32);
    }

    /**
     * @return array<int, array<int, bool>>
     */
    private function matrix(string $payload): array
    {
        $codewords = $this->codewords($payload);
        $function = array_fill(0, self::SIZE, array_fill(0, self::SIZE, false));
        $modules = array_fill(0, self::SIZE, array_fill(0, self::SIZE, false));

        $this->addFinder($modules, $function, 3, 3);
        $this->addFinder($modules, $function, self::SIZE - 4, 3);
        $this->addFinder($modules, $function, 3, self::SIZE - 4);
        $this->addAlignment($modules, $function, 30, 30);

        for ($index = 8; $index < self::SIZE - 8; $index++) {
            $this->setFunction($modules, $function, 6, $index, $index % 2 === 0);
            $this->setFunction($modules, $function, $index, 6, $index % 2 === 0);
        }

        $this->setFormatInformation($modules, $function, 0);
        $bits = [];
        foreach ($codewords as $codeword) {
            for ($bit = 7; $bit >= 0; $bit--) {
                $bits[] = (($codeword >> $bit) & 1) === 1;
            }
        }

        $bitIndex = 0;
        $upward = true;
        for ($right = self::SIZE - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right--;
            }

            for ($step = 0; $step < self::SIZE; $step++) {
                $row = $upward ? self::SIZE - 1 - $step : $step;

                for ($offset = 0; $offset < 2; $offset++) {
                    $column = $right - $offset;
                    if ($function[$row][$column]) {
                        continue;
                    }

                    $modules[$row][$column] = $bits[$bitIndex] ?? false;
                    $bitIndex++;
                }
            }

            $upward = ! $upward;
        }

        $bestModules = null;
        $bestPenalty = PHP_INT_MAX;
        for ($mask = 0; $mask < 8; $mask++) {
            $candidate = $modules;
            for ($row = 0; $row < self::SIZE; $row++) {
                for ($column = 0; $column < self::SIZE; $column++) {
                    if (! $function[$row][$column] && $this->mask($mask, $row, $column)) {
                        $candidate[$row][$column] = ! $candidate[$row][$column];
                    }
                }
            }

            $candidateFunction = $function;
            $this->setFormatInformation($candidate, $candidateFunction, $mask);
            $penalty = $this->penalty($candidate);

            if ($penalty < $bestPenalty) {
                $bestPenalty = $penalty;
                $bestModules = $candidate;
            }
        }

        if ($bestModules === null) {
            throw new RuntimeException('The QR code matrix could not be generated.');
        }

        return $bestModules;
    }

    /**
     * @return array<int, int>
     */
    private function codewords(string $payload): array
    {
        $bytes = array_values(unpack('C*', $payload) ?: []);
        if (count($bytes) > 106) {
            throw new InvalidArgumentException('The QR code payload exceeds the supported length.');
        }

        $bits = [];
        $this->appendBits($bits, 0b0100, 4);
        $this->appendBits($bits, count($bytes), 8);
        foreach ($bytes as $byte) {
            $this->appendBits($bits, $byte, 8);
        }

        $capacity = self::DATA_CODEWORDS * 8;
        for ($index = 0; $index < min(4, $capacity - count($bits)); $index++) {
            $bits[] = false;
        }
        while (count($bits) % 8 !== 0) {
            $bits[] = false;
        }

        $data = [];
        for ($offset = 0; $offset < count($bits); $offset += 8) {
            $byte = 0;
            for ($bit = 0; $bit < 8; $bit++) {
                $byte = ($byte << 1) | (int) $bits[$offset + $bit];
            }
            $data[] = $byte;
        }

        for ($pad = 0xEC; count($data) < self::DATA_CODEWORDS; $pad = $pad === 0xEC ? 0x11 : 0xEC) {
            $data[] = $pad;
        }

        return [...$data, ...$this->remainder($data)];
    }

    /**
     * @param  array<int, bool>  $bits
     */
    private function appendBits(array &$bits, int $value, int $length): void
    {
        for ($index = $length - 1; $index >= 0; $index--) {
            $bits[] = (($value >> $index) & 1) === 1;
        }
    }

    /**
     * @param  array<int, int>  $data
     * @return array<int, int>
     */
    private function remainder(array $data): array
    {
        $divisor = array_fill(0, self::ECC_CODEWORDS, 0);
        $divisor[self::ECC_CODEWORDS - 1] = 1;
        $root = 1;

        for ($index = 0; $index < self::ECC_CODEWORDS; $index++) {
            for ($coefficient = 0; $coefficient < self::ECC_CODEWORDS; $coefficient++) {
                $divisor[$coefficient] = $this->multiply($divisor[$coefficient], $root);
                if ($coefficient + 1 < self::ECC_CODEWORDS) {
                    $divisor[$coefficient] ^= $divisor[$coefficient + 1];
                }
            }
            $root = $this->multiply($root, 2);
        }

        $result = array_fill(0, self::ECC_CODEWORDS, 0);
        foreach ($data as $byte) {
            $factor = $byte ^ $result[0];
            array_shift($result);
            $result[] = 0;

            for ($index = 0; $index < self::ECC_CODEWORDS; $index++) {
                $result[$index] ^= $this->multiply($divisor[$index], $factor);
            }
        }

        return $result;
    }

    private function multiply(int $left, int $right): int
    {
        $result = 0;
        for ($bit = 0; $bit < 8; $bit++) {
            if (($right & 1) !== 0) {
                $result ^= $left;
            }

            $left <<= 1;
            if (($left & 0x100) !== 0) {
                $left ^= 0x11D;
            }
            $right >>= 1;
        }

        return $result;
    }

    /**
     * @param  array<int, array<int, bool>>  $modules
     * @param  array<int, array<int, bool>>  $function
     */
    private function addFinder(array &$modules, array &$function, int $centerX, int $centerY): void
    {
        for ($dy = -4; $dy <= 4; $dy++) {
            for ($dx = -4; $dx <= 4; $dx++) {
                $x = $centerX + $dx;
                $y = $centerY + $dy;
                if ($x < 0 || $x >= self::SIZE || $y < 0 || $y >= self::SIZE) {
                    continue;
                }

                $distance = max(abs($dx), abs($dy));
                $isDark = $distance <= 3 && ($distance === 3 || $distance <= 1);
                $this->setFunction($modules, $function, $x, $y, $isDark);
            }
        }
    }

    /**
     * @param  array<int, array<int, bool>>  $modules
     * @param  array<int, array<int, bool>>  $function
     */
    private function addAlignment(array &$modules, array &$function, int $centerX, int $centerY): void
    {
        for ($dy = -2; $dy <= 2; $dy++) {
            for ($dx = -2; $dx <= 2; $dx++) {
                $distance = max(abs($dx), abs($dy));
                $this->setFunction($modules, $function, $centerX + $dx, $centerY + $dy, $distance !== 1);
            }
        }
    }

    /**
     * @param  array<int, array<int, bool>>  $modules
     * @param  array<int, array<int, bool>>  $function
     */
    private function setFunction(array &$modules, array &$function, int $x, int $y, bool $isDark): void
    {
        $modules[$y][$x] = $isDark;
        $function[$y][$x] = true;
    }

    /**
     * @param  array<int, array<int, bool>>  $modules
     * @param  array<int, array<int, bool>>  $function
     */
    private function setFormatInformation(array &$modules, array &$function, int $mask): void
    {
        $data = (1 << 3) | $mask;
        $remainder = $data;
        for ($index = 0; $index < 10; $index++) {
            $remainder = ($remainder << 1) ^ ((($remainder >> 9) & 1) * 0x537);
        }
        $format = (($data << 10) | $remainder) ^ 0x5412;

        for ($index = 0; $index <= 5; $index++) {
            $this->setFunction($modules, $function, 8, $index, (($format >> $index) & 1) !== 0);
        }
        $this->setFunction($modules, $function, 8, 7, (($format >> 6) & 1) !== 0);
        $this->setFunction($modules, $function, 8, 8, (($format >> 7) & 1) !== 0);
        $this->setFunction($modules, $function, 7, 8, (($format >> 8) & 1) !== 0);
        for ($index = 9; $index < 15; $index++) {
            $this->setFunction($modules, $function, 14 - $index, 8, (($format >> $index) & 1) !== 0);
        }

        for ($index = 0; $index < 8; $index++) {
            $this->setFunction($modules, $function, self::SIZE - 1 - $index, 8, (($format >> $index) & 1) !== 0);
        }
        for ($index = 8; $index < 15; $index++) {
            $this->setFunction($modules, $function, 8, self::SIZE - 15 + $index, (($format >> $index) & 1) !== 0);
        }

        $this->setFunction($modules, $function, 8, self::SIZE - 8, true);
    }

    private function mask(int $mask, int $row, int $column): bool
    {
        return match ($mask) {
            0 => ($row + $column) % 2 === 0,
            1 => $row % 2 === 0,
            2 => $column % 3 === 0,
            3 => ($row + $column) % 3 === 0,
            4 => (intdiv($row, 2) + intdiv($column, 3)) % 2 === 0,
            5 => ($row * $column) % 2 + ($row * $column) % 3 === 0,
            6 => (($row * $column) % 2 + ($row * $column) % 3) % 2 === 0,
            7 => (($row + $column) % 2 + ($row * $column) % 3) % 2 === 0,
        };
    }

    /**
     * @param  array<int, array<int, bool>>  $matrix
     */
    private function penalty(array $matrix): int
    {
        $score = 0;
        $darkCount = 0;

        for ($row = 0; $row < self::SIZE; $row++) {
            $darkCount += count(array_filter($matrix[$row]));
            $score += $this->linePenalty($matrix[$row]);
        }

        for ($column = 0; $column < self::SIZE; $column++) {
            $line = [];
            for ($row = 0; $row < self::SIZE; $row++) {
                $line[] = $matrix[$row][$column];
            }
            $score += $this->linePenalty($line);
        }

        for ($row = 0; $row < self::SIZE - 1; $row++) {
            for ($column = 0; $column < self::SIZE - 1; $column++) {
                if ($matrix[$row][$column] === $matrix[$row][$column + 1]
                    && $matrix[$row][$column] === $matrix[$row + 1][$column]
                    && $matrix[$row][$column] === $matrix[$row + 1][$column + 1]) {
                    $score += 3;
                }
            }
        }

        $score += intdiv(abs($darkCount * 20 - self::SIZE * self::SIZE * 10), self::SIZE * self::SIZE) * 10;

        return $score;
    }

    /**
     * @param  array<int, bool>  $line
     */
    private function linePenalty(array $line): int
    {
        $score = 0;
        $runColor = $line[0];
        $runLength = 1;

        for ($index = 1; $index < count($line); $index++) {
            if ($line[$index] === $runColor) {
                $runLength++;
                if ($runLength === 5) {
                    $score += 3;
                } elseif ($runLength > 5) {
                    $score++;
                }
            } else {
                $runColor = $line[$index];
                $runLength = 1;
            }
        }

        $patterns = [[true, false, true, true, true, false, true, false, false, false, false],
            [false, false, false, false, true, false, true, true, true, false, true]];
        for ($index = 0; $index <= count($line) - 11; $index++) {
            $window = array_slice($line, $index, 11);
            if (in_array($window, $patterns, true)) {
                $score += 40;
            }
        }

        return $score;
    }
}
