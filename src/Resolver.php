<?php

namespace Selmarinel\AllDifferentDirections;

class Resolver
{
    const ACCURACY = 2;
    const MAX_CASES = 100;
    const MAX_INSTRUCTIONS = 25;
    const MIN_NUMERIC_VALUE = -1000;
    const MAX_NUMERIC_VALUE = 1000;
    const MIN_CASE = 0;
    const MAX_CASE = 20;

    public function resolve($input)
    {
        return $this->generateOutput($this->throwPeople($this->parseInput($input)));
    }

    /**
     * @param $angle
     * @param $length
     * @return string
     */
    private function getX($angle, $length)
    {
        return number_format($length * cos(deg2rad($angle)), self::ACCURACY);
    }

    /**
     * @param $angle
     * @param $length
     * @return string
     */
    private function getY($angle, $length)
    {
        return number_format($length * sin(deg2rad($angle)), self::ACCURACY);
    }

    /**
     * @param $startAngle
     * @param $modifyAngle
     * @return mixed
     */
    private function getAngle($startAngle, $modifyAngle)
    {
        return $startAngle - $modifyAngle;
    }

    /**
     * Basic Validation
     * @param $numeric
     * @param int $from
     * @param int $to
     * @return bool
     */
    private function validateNumericData($numeric, $from = self::MIN_NUMERIC_VALUE, $to = self::MAX_NUMERIC_VALUE)
    {
        return ($numeric >= $from && $numeric <= $to);
    }

    /**
     * @param $input
     * @return array
     */
    private function parseInput($input)
    {
        $data = explode("\n", $input);
        $result = [];
        $ir = 0;
        foreach ($data as $key => $item) {
            if (is_numeric(trim($item))) {
                $item = intval(trim($item));
                if ($this->validateNumericData($item, static::MIN_CASE, static::MAX_CASE)) {
                    $result[$key][$item] = [];
                    $lockKey = $key;
                    $lockItem = $item;
                }
            } elseif($item !== '') {
                if (!isset($result[$key])) {
                    $ir += 1;
                }
                $parser = explode(" ", $item);
                if (isset($lockItem) && isset($lockKey)) {
                    if ($this->validateNumericData($parser[0]) &&
                        $this->validateNumericData($parser[1]) &&
                        $this->validateNumericData($parser[3]) &&
                        $this->validateNumericData($parser[5])
                    ) {
                        $result[$lockKey][$lockItem][$ir] = [
                            "startX" => $parser[0],
                            "startY" => $parser[1],
                            "startAngle" => $parser[3],
                            "startLength" => $parser[5],
                            "move" => $result[$lockKey][$lockItem][$ir]["move"] ?? [],
                        ];
                    }

                    if (count($parser) > 6) {
                        $array = array_splice($parser, 6);
                        $move = [];
                        $walk = [];
                        for ($i = 0; $i < count($array); $i += 4) {
                            if ($this->validateNumericData($array[$i + 1]) &&
                                $this->validateNumericData($array[$i + 3])
                            ) {
                                $walk[$array[$i]] = $array[$i + 1];
                                $walk[$array[$i + 2]] = $array[$i + 3];
                                $move[] = $walk;
                            }
                        }
                        $result[$lockKey][$lockItem][$ir]["move"] = $move;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param $angle
     * @param $length
     * @return object
     */
    private function modifyCoordinate($angle, $length)
    {
        return (object)[
            "X" => $this->getX($angle, $length),
            "Y" => $this->getY($angle, $length),
        ];
    }

    /**
     * @param $direction
     * @return object
     */
    private function calculateOneDirection($direction)
    {
        if (is_array($direction)) {
            $direction = (object)$direction;
        }
        $totalLength = $direction->startLength;
        $modify = $this->modifyCoordinate($direction->startAngle, $direction->startLength);
        $direction->startX += $modify->X;
        $direction->startY += $modify->Y;
        if ($direction->move && !empty($direction->move)) {
            foreach ($direction->move as $walk) {
                $walk = (object)$walk;
                $direction->startAngle = $this->getAngle($direction->startAngle, (-1) * $walk->turn);
                $modify = $this->modifyCoordinate($direction->startAngle, $walk->walk);
                $direction->startX += $modify->X;
                $direction->startY += $modify->Y;
                $totalLength += $walk->walk;
            }
        }
        return (object)[
            "x" => $direction->startX,
            "y" => $direction->startY,
            "angle" => $direction->startAngle,
            "length" => $totalLength,
        ];
    }

    /**
     * @param array $input
     * @return array
     */
    private function throwPeople(Array $input)
    {
        $result = [];
        foreach ($input as $index => $data) {
            foreach ($data as $countOfPeople => $manWhoToldData) {
                foreach ($manWhoToldData as $manWhoTold) {
                    $direction = $this->calculateOneDirection($manWhoTold);
                    $result[$index][] = $direction;
                }
            }
        }
        $throw = [];
        foreach ($result as $key => $item) {
            $throw[$key] = $this->calculateAVGCoordinates($item);
        }
        return $throw;
    }

    /**
     * @param $avgX
     * @param $avgY
     * @param $directions
     * @return string
     */
    private function calculateMaxAccuracy($avgX, $avgY, $directions)
    {
        $max = 0;
        foreach ($directions as $direction) {
            $length = sqrt(pow(($avgX - $direction->x), 2) + pow(($avgY - $direction->y), 2));
            if ($max < $length) {
                $max = $length;
            }
        }
        return number_format($max, self::ACCURACY);
    }

    /**
     * @param $direction
     * @return null|object
     */
    private function calculateAVGCoordinates($direction)
    {
        $averageX = 0;
        $averageY = 0;
        foreach ($direction as $item) {
            $averageX += $item->x;
            $averageY += $item->y;
        }

        if (count($direction)) {
            $x = number_format($averageX / count($direction), self::ACCURACY);
            $y = number_format($averageY / count($direction), self::ACCURACY);
            return [
                'x' => $x,
                'y' => $y,
                'length' => $this->calculateMaxAccuracy($x, $y, $direction),
            ];
        }
        return null;
    }

    /**
     * @param array $output
     * @return string
     */
    private function generateOutput(array $output)
    {
        $out = '';

        foreach ($output as $item) {
            $out .= "{$item['x']} {$item['y']} {$item['length']} \n";
        }

        return $out;
    }
}
