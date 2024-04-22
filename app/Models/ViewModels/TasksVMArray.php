<?php

namespace App\Models\ViewModels;


class TasksVMArray extends \ArrayObject
{
    public function offsetSet($key, $val)
    {
        if ($val instanceof TasksVM) {
            return parent::offsetSet($key, $val);
        }
        throw new \InvalidArgumentException('Value must be a TasksVMArray'); // * update as needed
    }

    public function convertArrayToVMArray($array)
    {
        if (is_array($array)) {
            $object = new TasksVM(); //this should be custom VM class require update when copy and paste // * update as needed                  
            $class =  get_class();
            $classArray = new $class();
            foreach ($array as &$row) {
                $classArray[] =  $object->getInstance($row);
            }
            return $classArray;
        } else {
            return null;
        }
    }

    public function validate()
    {
        foreach ($this as $row) {
            $row->validate();
        }
        return $this;
    }

    public function toArray()
    {
        //return (array) $this; // cannot use this because of parent is extended from Elequent Model
        $array = array();
        foreach ($this as $row) {
            $array[] =  $row->toArray();
        }
        return $array;
    }

    public function toJSON()
    {
        $array = array();
        foreach ($this as $row) {
            $array[] =  $row->toArray();
        }
        return json_encode($array);
    }

    function where($criteriaArray)
    {
        if (is_array($criteriaArray)) {
            $class =  get_class();
            $classArray = new $class();
            foreach ($this as $row) {
                foreach ($criteriaArray as $key => $value) {
                    if ($row->{$key} ==  $value)
                        $classArray[] =  $row;
                }
            }
            return $classArray;
        } else {
            return null;
        }
    }

    function merge($array)
    {
        // * update as needed
        $class =  get_class();
        if ($array instanceof TasksVMArray) {

            $array = $array->toArray();
            $thisArray = $this->toArray();
            $margedArray = array_merge($thisArray, $array);
            $this->convertArrayToVMArray($margedArray);

            if (count($array) > 0) {
                $margedArray = array_merge($thisArray, $array);
                return $this->convertArrayToVMArray($margedArray);
            } else {
                return $this;
            }
        } else {
            return $this;
        }
    }
}
