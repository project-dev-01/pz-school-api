<?php

namespace App\Models\ViewModels;

use Illuminate\Container\Container;
use App\Helper\GeneralUtility;


class TasksVM
{

    protected GeneralUtility $utility;

    public  $PlanStatus;
    public  $SprintId;
    public  $AssignTo;
    public  $TaskName;
    public  $Weight;
    public  $Cost;
    public  $Type;
    public  $PlannedStartDate;
    public  $PlannedEndDate;
    public  $ActualStartDate;
    public  $ActualEndDate;
    public  $Remark;
    public  $StatusId;
    public  $statusCode;
    public  $statusMsg;

    public function __construct(array $data = array())
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }

        $container = Container::getInstance();
        $this->utility = $container->make(GeneralUtility::class);
    }

    public function toArray(): array
    {
        $exclude_keys = ["utility"];
        $array = $this->utility->objectToArray($this, $exclude_keys);  //we cannot use (object) $this; key will return * infront like *client_name            
        return $array;
    }

    public function toJSON(): string|false
    {
        $array = $this->utility->objectToArray($this);  //we cannot use (object) $this; key will return * infront like *client_name
        return json_encode($array);
    }

    public function getInstance($row): TasksVM
    {
        $object = null;
        if (is_array($row)) {
            if (array_keys($row) !== range(0, count($row) - 1)) {
                //if associate array
                $row = (object) $row;
            }
        }

        if (is_array($row)) {
            // * update as needed
            $object =
                new TasksVM([
                    'PlanStatus'     => $this->utility->nullToEmptyString($row[0]),
                    'SprintId'       => $this->utility->nullToEmptyString($row[1]),
                    'AssignTo'       => $this->utility->nullToEmptyString($row[2]),
                    'TaskName'       => $row[3], // validated field
                    'Weight'         => $row[4], // validated field
                    'Cost'           => $row[5], // validated field
                    'Type'           => $this->utility->nullToEmptyString($row[6]),
                    'PlannedStartDate' => $this->utility->nullToEmptyString($row[7]),
                    'PlannedEndDate' => $this->utility->nullToEmptyString($row[8]),
                    'ActualStartDate' => $this->utility->nullToEmptyString($row[9]),
                    'ActualEndDate'  => $this->utility->nullToEmptyString($row[10]),
                    'Remark'         => $row[11], // validated field
                    'StatusId'       => $this->utility->nullToEmptyString($row[12]),
                    'statusCode'     => null,
                    'statusMsg'      => null
                ]);
        } else if (is_object($row)) {
            $object =  $this->utility->stdToTypeClass($row, get_class()); // only for data copy 
            $object->utility = $this->utility; // require to reinitiallization all from above costructor
        }
        return  $object;
    }

    public function validate(): TasksVM
    {
        // * update as needed
        $statusCode = 1;
        $statusMsg = '';

        if ($this->utility->isNullOrEmptyString($this->PlanStatus)) {
            $statusCode = -1;
            $statusMsg = $statusMsg . 'Plan Status is Required; ';
        }

        if ($this->utility->isNullOrEmptyString($this->AssignTo)) {
            $statusCode = -1;
            $statusMsg = $statusMsg . 'Assign To is Required; ';
        }

        if ($this->utility->isNullOrEmptyString($this->TaskName)) {
            $statusCode = -1;
            $statusMsg = $statusMsg . 'Task Name is Required; ';
        }

        if ($this->utility->isNullOrEmptyString($this->Type)) {
            $statusCode = -1;
            $statusMsg = $statusMsg . 'Type is Required; ';
        }

        if ($this->utility->isNullOrEmptyString($this->StatusId)) {
            $statusCode = -1;
            $statusMsg = $statusMsg . 'Task Status is Required; ';
        }

        if ($this->utility->isNullOrEmptyString($this->PlannedStartDate)) {
            $statusCode = -1;
            $statusMsg = $statusMsg . 'Planned Start Date is Required; ';
        }

        if ($this->utility->isNullOrEmptyString($this->PlannedEndDate)) {
            $statusCode = -1;
            $statusMsg = $statusMsg . 'Planned End Date is Required; ';
        }

        if ($this->utility->isNullOrEmptyString($this->ActualStartDate)) {
            $statusCode = -1;
            $statusMsg = $statusMsg . 'Actual Start Date is Required; ';
        }

        if ($this->utility->isNullOrEmptyString($this->ActualEndDate)) {
            $statusCode = -1;
            $statusMsg = $statusMsg . 'Actual End Date is Required; ';
        }

        if (!($this->utility->isDateTime($this->PlannedStartDate))) {
            $statusCode = -1;
            $statusMsg = $statusMsg . 'Planned Start Date is not a Valid date; ; ';
        }

        if (!($this->utility->isDateTime($this->PlannedEndDate))) {
            $statusCode = -1;
            $statusMsg = $statusMsg . 'Planned End Date is not a Valid date; ';
        }

        if (!($this->utility->isDateTime($this->ActualStartDate))) {
            $statusCode = -1;
            $statusMsg = $statusMsg . 'Actual Start Date is not a Valid date; ';
        }

        if (!($this->utility->isDateTime($this->ActualEndDate))) {
            $statusCode = -1;
            $statusMsg = $statusMsg . 'Actual End Date is not a Valid date; ';
        }

        $this->statusCode = $statusCode;
        $this->statusMsg = $statusMsg;
        return $this;
    }
}
