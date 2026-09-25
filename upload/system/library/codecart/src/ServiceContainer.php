<?php
namespace CodeCart\Core;
final class ServiceContainer {
    private $registry; private $factories=array(); private $instances=array();
    public function __construct($registry){$this->registry=$registry;}
    public function set(string $id, callable $factory): void {$this->factories[$id]=$factory; unset($this->instances[$id]);}
    public function has(string $id): bool {return isset($this->instances[$id])||isset($this->factories[$id]);}
    public function get(string $id){if(isset($this->instances[$id]))return $this->instances[$id];if(!isset($this->factories[$id]))throw new \InvalidArgumentException('Unknown service: '.$id);return $this->instances[$id]=($this->factories[$id])($this->registry,$this);}
}
