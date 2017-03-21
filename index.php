<?php

class MyException extends Exception
{

}

class Core
{
    const ACCURACY = 2;
    const MAX_CASES = 100;
    const MAX_INSTRUCTIONS = 25;
    const MIN_NUMERIC_VALUE = -1000;
    const MAX_NUMERIC_VALUE = 1000;
    const MIN_CASE = 0;
    const MAX_CASE = 20;

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

    /** Temporary input for example
     * @var string
     */
    private $input = "3
87.342 34.30 start 0 walk 10.0
2.6762 75.2811 start -45.0 walk 40 turn 40.0 walk 60
58.518 93.508 start 270 walk 50 turn 90 walk 40 turn 13 walk 5
2
30 40 start 90 walk 5
40 50 start 180 walk 10 turn 90 walk 5
0";

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
     * @throws MyException
     */
    private function parseInput($input)
    {
        if (!$input) {
            $input = $this->input;
        }
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
                } else {
                    throw new MyException("Incorrect Input data. Test case {$item} starts with an integer between 1 and 20");
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
                            "move" => ($result[$lockKey][$lockItem][$ir]["move"]) ?: [],
                        ];
                    } else {
                        throw new MyException("Incorrect Input data.
                         All numeric inputs must be real numbers in the range [" . static::MIN_NUMERIC_VALUE . "," .
                            static::MAX_NUMERIC_VALUE . "]");
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
                            } else {
                                throw new MyException("Incorrect Input data.
                                    All numeric inputs must be real numbers in the range [" . static::MIN_NUMERIC_VALUE . "," .
                                    static::MAX_NUMERIC_VALUE . "]");
                            }
                        }
                        $result[$lockKey][$lockItem][$ir]["move"] = $move;

                        if (count($result[$lockKey][$lockItem][$ir]["move"]) > static::MAX_INSTRUCTIONS) {
                            throw new MyException("Incorrect Input data. Each person’s directions must contain at most 25 instructions.");
                        }
                    }
                }
            }
        }
        if (count($result) > 100) {
            throw new MyException("Incorrect Input data. Input must consists of up to 100100 test cases.");
        }
        if (empty($result)) {
            throw new MyException("Error in input data");
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
     * @throws MyException
     */
    private function throwPeople(Array $input)
    {
        $result = [];
        foreach ($input as $index => $data) {
            foreach ($data as $countOfPeople => $manWhoToldData) {
                if ($countOfPeople == 0) {
                    /**
                     * Exit
                     */
                    continue 2;
                }
                if (is_array($manWhoToldData) && $countOfPeople == count($manWhoToldData)) {
                    foreach ($manWhoToldData as $manWhoTold) {
                        $direction = $this->calculateOneDirection($manWhoTold);
                        $result[$index][] = $direction;
                    }
                } else {
                    throw new MyException("Incorrect Input data. Count of people not equal number of answers");
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
     * @throws MyException
     */
    private function calculateMaxAccuracy($avgX, $avgY, $directions)
    {
        if (!empty($directions)) {
            $max = 0;
            foreach ($directions as $direction) {
                $length = sqrt(pow(($avgX - $direction->x), 2) + pow(($avgY - $direction->y), 2));
                if ($max < $length) {
                    $max = $length;
                }
            }
            return number_format($max, self::ACCURACY);
        } else {
            throw new MyException("Incorrect average coordinates");
        }
    }

    /**
     * @param $direction
     * @return null|object
     * @throws MyException
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
            return (object)[
                "x" => $x,
                "y" => $y,
                "length" => $this->calculateMaxAccuracy($x, $y, $direction)
            ];
        }
        return null;
    }

    /**
     * @param array $output
     * @return string
     */
    private function generateOutput(Array $output)
    {
        $out = "";
        if (!empty($output)) {
            foreach ($output as $item) {
                $out .= "$item->x $item->y $item->length \n";
            }
        }
        return $out;
    }

    /**
     * @param null $input
     * @return string
     * @throws MyException
     */
    private function process($input = null)
    {
        return $this->generateOutput($this->throwPeople($this->parseInput($input)));
    }

    /**
     * @param $fileName
     * @return string
     * @throws MyException
     */
    private function processFile($fileName)
    {
        try {
            return file_get_contents($fileName);
        } catch (Exception $exception) {
            throw new MyException($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @param $post
     * @return string
     */
    public function submit($post)
    {
        try {
            $request = (object)array_merge($post, $_FILES);
            if ($request->inputFile && $request->inputFile['name']) {
                $text = $this->process($this->processFile($request->inputFile['tmp_name']));
                $_POST["inputText"] = $this->processFile($request->inputFile['tmp_name']);
                return nl2br($text);
            } elseif ($request->inputText) {
                return nl2br($this->process($request->inputText));
            }
            throw new MyException("Incorrect input data");
        } catch (MyException $myException) {
            return $myException->getMessage();
        }
    }
}

$service = new Core();
if ($_POST && !empty($_POST)) {
    echo $service->submit($_POST);
} ?>
<form method="POST" action="index.php" enctype="multipart/form-data">
    <div>
        <label for="text">Input Text
        </label>
        <textarea id="text" name="inputText" cols="40" rows="20"><?= $_POST["inputText"] ?></textarea>
    </div>
    <div>
        <label for="file">Or Upload File</label>
        <input type="file" name="inputFile" id="file">
    </div>
    <button type="submit">Submit</button>
</form>
