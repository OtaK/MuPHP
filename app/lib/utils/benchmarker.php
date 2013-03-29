<?php

    /*
     * Copyright 2013 Mathieu "OtaK_" Amiot <m.amiot@otak-arts.com> http://mathieu-amiot.fr/
     *
     * Licensed under the Apache License, Version 2.0 (the "License");
     * you may not use this file except in compliance with the License.
     * You may obtain a copy of the License at
     *
     *      http://www.apache.org/licenses/LICENSE-2.0
     *
     * Unless required by applicable law or agreed to in writing, software
     * distributed under the License is distributed on an "AS IS" BASIS,
     * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
     * See the License for the specific language governing permissions and
     * limitations under the License.
     *
     */

    /**
     * @package    MuPHP
     * @subpackage Performance
     * @author     Mathieu AMIOT <m.amiot@otak-arts.com>
     * @copyright  Copyright (c) 2013, Mathieu AMIOT
     * @version    1.0
     * @changelog
     *      1.0 : initial release
     */
    namespace MuPHP\Performance;
    /**
     * Allows to benchmark a part of code, with different units/precision
     */
    class benchmarker
    {
        private
            $start,
            $end,
            $unit,
            $textUnit,
            $digits,
            $totalTime;

        public function __construct()
        {
            $this->reset();
        }

        /**
         * Changes the unit currently in use
         * @param string $unit
         */
        public function setUnit($unit = 'ms')
        {
            switch ($unit)
            {
                case 'us':
                case 'µs':
                    $this->unit = 1e6;
                    $this->textUnit = 'µs';
                    break;
                case 's':
                    $this->unit = 1;
                    $this->textUnit = 's';
                    break;
                default:
                    $this->unit = 1e3;
                    $this->textUnit = 'ms';
            }
        }

        /**
         * @param string $text
         * @param bool   $echo
         * @return string
         */
        static public function out($text, $echo = true)
        {
            $res = $text.(PHP_SAPI == 'cli' ? "\n" : "<br />");
            if ($echo) echo $res;
            return $res;
        }

        public function setPrecision($digits = 3)
        {
            $this->digits = $digits;
        }

        /**
         * Starts the benchmarking
         */
        public function start()
        {
            $this->start = self::getmicrotime();
        }

        /**
         * Stops the benchmarking
         */
        public function end()
        {
            $this->end = self::getmicrotime();
        }

        /**
         * Resets internal counters
         */
        public function reset()
        {
            $this->end = $this->start = $this->totalTime = 0;
            $this->unit = 1e3;
            $this->textUnit = 'ms';
            $this->digits = 3;
        }

        /**
         * Outputs time elapsed between start() and end() calls with supplied $label
         * @param string $label
         * @param bool   $echo
         * @return string
         */
        public function output($label, $echo = true)
        {
            $elapsedTime = $this->getElapsedTime();
            $text = $this->textUnit;
            $res = sprintf("%.{$this->digits}f", $elapsedTime);

            $this->totalTime += $elapsedTime;
            return self::out("{$label} : {$res} {$text} used", $echo);
        }

        /**
         * Gets the current elapsed time between start() and end() in a numeric format
         * @return float
         */
        public function getElapsedTime()
        {
            if (!isset($this->unit)) $this->setUnit();
            return ($this->end * $this->unit) - ($this->start * $this->unit);
        }

        /**
         * Outputs total time elapsed between successive calls of start() end() and output()
         */
        public function outputTotalTime()
        {
            self::out("Total time : ".$this->totalTime." ".$this->textUnit." used");
        }

        /**
         * Private helper to get current microtime
         * @return float
         */
        static private function getmicrotime()
        {
            if (PHP_MAJOR_VERSION >= 5)
                return microtime(true);
            list($usec, $sec) = explode(" ", microtime());
            return ((float)$usec + (float)$sec);
        }

        /**
         * Adds the total time of the other benchmarker to this current one
         * @param benchmarker $otherBenchmarker
         */
        public function addTotalTime(benchmarker $otherBenchmarker)
        {
            $this->totalTime += $otherBenchmarker->totalTime;
        }
    }
