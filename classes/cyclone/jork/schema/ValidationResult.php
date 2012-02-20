<?php
namespace cyclone\jork\schema;

use cyclone\jork;

/**
 * @property-read array $info
 * @property-read array $warning
 * @property-read array $error
 */
class ValidationResult {

    private $_info = array();

    private $_warning = array();

    private $_error = array();

    public function __get($name) {
        $candidate = '_' . $name;
        if (property_exists(get_class($this), $candidate)) {
            return $this->$candidate;
        }
        throw new jork\Exception("property " . get_class($this) . '::$' . $name . ' doesn\'t exist');
    }

    public function merge(ValidationResult $other) {
        foreach($other->_info as $info) {
            $this->_info []= $info;
        }
        foreach($other->_warning as $warning) {
            $this->_warning []= $warning;
        }
        foreach($other->_error as $error) {
            $this->_error []= $error;
        }
    }

    public function add_info($info) {
        $this->_info []= $info;
    }

    public function add_warning($warning) {
        $this->_warning []= $warning;
    }

    public function add_error($error) {
        $this->_error []= $error;
    }

    public function render() {
        foreach ($this->_info as $info) {
            echo "[INFO] $info" . PHP_EOL;
        }
        foreach ($this->_warning as $warning) {
            echo "[WARNING] $warning" . PHP_EOL;
        }
        foreach ($this->_error as $error) {
            echo "[ERROR] $error" . PHP_EOL;
        }
    }
}