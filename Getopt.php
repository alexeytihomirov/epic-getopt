<?php

class Getopt implements ArrayAccess
{
    public $map = [], $result = [], $arguments = [], $shortOptions;

    const VALUE_REQUIRED = ":";
    const VALUE_OPTIONAL = "::";
    const VALUE_NOT_ACCEPT = "";

    public function __construct($shortOptions = "", $longOptions = [])
    {
        $shortOptions = trim($shortOptions);
        if (!preg_match('/^[a-zA-Z0-9:]+$/', $shortOptions)) {
            throw new \InvalidArgumentException('Invalid short options format');
        }
        $shortOptions = preg_replace('/:{3,}/', '::', $shortOptions);
        $shortOptions = preg_replace('/^:+/', '', $shortOptions);

        $this->shortOptions = $shortOptions;
        $tokens = [];

        // Add optional short options
        $shortOptions = preg_replace_callback('/[a-zA-Z0-9]::/', function ($optional) use (&$tokens) {
            $this->setOption($optional[0][0], '-' . $optional[0][0], self::VALUE_OPTIONAL);
            return;
        }, $shortOptions);

        // Add required short options
        $shortOptions = preg_replace_callback('/[a-zA-Z0-9]:/', function ($optional) use (&$tokens) {
            $this->setOption($optional[0][0], '-' . $optional[0][0], self::VALUE_REQUIRED);
            return;
        }, $shortOptions);

        // Add not accept value short options
        foreach (str_split($shortOptions) as $option) {
            $this->setOption($option, '-' . $option, self::VALUE_NOT_ACCEPT);
        }

        // Add long options
        foreach ($longOptions as $longOption) {
            if (preg_match('/^(?<name>[a-zA-Z0-9]+)::$/', $longOption, $match)) {
                $this->setOption($match['name'], '--' . $match['name'], self::VALUE_OPTIONAL);
            } elseif (preg_match('/^(?<name>[a-zA-Z0-9]+):$/', $longOption, $match)) {
                $this->setOption($match['name'], '--' . $match['name'], self::VALUE_REQUIRED);
            } else {
                $this->setOption($longOption, '--' . $match['name'], self::VALUE_NOT_ACCEPT);
            }
        }
    }

    public function setOption($name, $options, $flag = "", $validate = null)
    {
        $optionsList = explode(",", $options);
        foreach ($optionsList as $option) {
            $option = trim($option);
            if ($option[0] == "-" && $option[1]) {
                $this->map[$option] = [
                    'name' => $name,
                    'type' => $flag,
                    'validation' => $validate
                ];
            }
        }

        return $this;
    }

    public function __invoke($arguments = null)
    {
        return $this->run($arguments);
    }

    public function run($arguments = null)
    {
        $this->arguments = null === $arguments ? $_SERVER['argv'] : $arguments;
        $argumentsCount = sizeof($this->arguments);
        for ($i = 1; $i < $argumentsCount; $i++) {
            if (isset($this->arguments[$i]) && $arg = $this->arguments[$i]) {
                if ($arg[0] == '-' && isset($arg[1]) && $arg[1] != '-') {
                    if (false === $this->parseShort($arg, $i)) {
                        break;
                    }
                } elseif ($arg[0] == '-' && isset($arg[1]) && $arg[1] == '-') {
                    if (false === $this->parseLong($arg, $i)) {
                        break;
                    }
                } else {
                    break;
                }
            }
        }
        unset($this->arguments[0]);

        // Run validaion
        foreach ($this->map as $optionItem) {
            if (!isset($this->result[$optionItem['name']])) {
                continue;
            }

            if (is_callable($optionItem['validation'])) {
                if (!$optionItem['validation']()) {
                    throw new \UnexpectedValueException('Validation failed for option ' . $optionItem['name']
                        . ' for value "' . $this->result[$optionItem['name']] . '"');
                }
            }


        }
        return $this->result;
    }

    protected function parseShort($arg, $index)
    {
        $arg = substr($arg, 1);
        $shortopts = str_split($arg);
        $optsCount = sizeof($shortopts);
        for ($i = 0; $i < $optsCount; $i++) {
            $value = null;
            if (isset($this->map['-' . $shortopts[$i]])) {
                $opt = $this->map['-' . $shortopts[$i]];
                switch ($opt['type']) {
                    case  self::VALUE_NOT_ACCEPT:
                        $this->setOptValue($opt['name'], false);
                        break;
                    case self::VALUE_REQUIRED:
                        if (isset($shortopts[$i + 1])) {
                            $this->setOptValue($opt['name'], str_replace(['"', "'", "="], "", substr($arg, $i + 1)));
                            break(2);
                        } else {
                            if (isset($this->arguments[$index + 1])) {
                                $this->setOptValue($opt['name'], str_replace(['"', "'"], "", $this->arguments[$index + 1]));
                                unset($this->arguments[$index + 1]);
                            }
                        }
                        break;
                    case self::VALUE_OPTIONAL:
                        if (isset($shortopts[$i + 1])) {
                            $this->setOptValue($opt['name'], str_replace(['"', "'", "="], "", substr($arg, $i + 1)));
                            break(2);
                        } else {
                            $this->setOptValue($opt['name'], false);
                        }
                        break;
                    default:
                        throw new InvalidArgumentException('Undefined option type "' . $opt['type'] . '" for "' . $opt['name'] . '"');
                        break;
                }
            }
        }
        unset($this->arguments[$index]);
    }

    protected function setOptValue($key, $value)
    {
        if ($value !== null) {
            if (!isset($this->result[$key])) {
                $this->result[$key] = $value;
            } elseif (is_array($this->result[$key])) {
                array_push($this->result[$key], $value);
            } else {
                $this->result[$key] = [$this->result[$key], $value];
            }
        }
    }

    protected function parseLong($arg, $index)
    {
        $option = substr($arg, 2);
        $parsedOpt = explode("=", $option);
        $name = $parsedOpt[0];
        $val = isset($parsedOpt[1]) ? $parsedOpt[1] : null;
        $value = null;
        if (isset($this->map['--' . $name])) {
            $opt = $this->map['--' . $name];
            switch ($opt['type']) {
                case  self::VALUE_NOT_ACCEPT:
                    $this->setOptValue($opt['name'], false);
                    break;
                case self::VALUE_REQUIRED:
                    if (isset($val)) {
                        $this->setOptValue($opt['name'], str_replace(['"', "'", "="], "", $val));
                    } else {
                        if (isset($this->arguments[$index + 1])) {
                            $this->setOptValue($opt['name'], str_replace(['"', "'"], "", $this->arguments[$index + 1]));
                            unset($this->arguments[$index + 1]);
                        }
                    }
                    break;
                case self::VALUE_OPTIONAL:
                    if (isset($val)) {
                        if ($result = str_replace(['"', "'", "="], "", substr($arg, strlen('--' . $name)))) {
                            $this->setOptValue($opt['name'], $result);
                        }
                    } else {
                        $this->setOptValue($opt['name'], false);
                    }
                    break;
                default:
                    throw new InvalidArgumentException('Undefined option type "' . $opt['type'] . '" for "' . $opt['name'] . '"');
                    break;
            }
        } else {
            return false;
        }
        unset($this->arguments[$index]);
    }

    public function offsetSet($offset, $value)
    {
        $this->setOptValue($offset, $value);
    }

    public function offsetExists($offset)
    {
        return isset($this->result[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->result[$offset]);
    }

    public function offsetGet($key)
    {
        return $this->result[$key];
    }
}