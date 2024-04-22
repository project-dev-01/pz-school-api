<?php

namespace App\Helpers;
use Illuminate\Container\Container;
class CommonHelper
{
      
      function removeComma($value)
      {
            return str_replace(',', '', $value);
      }


      function easyDump($data, $shouldDie = true)
      {
            echo '<pre>' . var_export($data, true) . '</pre>';
            if ($shouldDie)
                  die();
      }

      function EasyDumpQuery($qstr, $data)
      {
            var_dump(vsprintf(str_replace("?", "%s",  $qstr), $data));
            die();
      } 

      /* #Start Validation Helper */
      //Validation Latest Update: 2021-11-05 
      function isNullOrEmptyString($str)
      {
            return (!isset($str) || trim($str) === '');
      }

      function nullToEmptyString($str)
      {
            if (is_null($str))
                  return "";
            else
                  return $str;
      }

      function convertBlankNumberToNegativeOne($value)
      {
            if (isset($value) && $value == '')
                  $value = -1;
            return $value;
      }
      /* #End Validation Helper */

      //Updated 25/02/2022
      public function convertToOption($list, $selectedindex) {
            
            $container =  Container::getInstance();
            $objHelper = $container->make(ObjectHelper::class);
            if(!is_array($list))
            {
                  $list =  $objHelper->objectToArray($list);
            }

            $stringoption = '';        
            foreach ($list as $key => $value) {
                if ($value->id == $selectedindex)
                    $stringoption = $stringoption . '<option value="' . strval($value->id) .'" selected>' . $value->description . '</option>';
                else
                    $stringoption = $stringoption . '<option value="' . strval($value->id) . '">' . $value->description . '</option>';
            }
            return $stringoption;
      }

      public function convertStdToOption($list, $selectedindex) {
            $stringoption = '';        
            foreach ($list as $key => $value) {
                if ($value->id == $selectedindex)
                    $stringoption = $stringoption . '<option value="' . strval($value->id) .'" selected>' . $value->description . '</option>';
                else
                    $stringoption = $stringoption . '<option value="' . strval($value->id) . '">' . $value->description . '</option>';
            }
            return $stringoption;
      }


      public function getCurrentTime($format)
      {
            $now = new \DateTime('now', new \DateTimeZone('Asia/Singapore')); 

            if (isset($format) && $format == 'onlyDate')
                  $format = 'd/m/Y';
            else
                  $format = 'd/m/Y H:i:s';
            
            return $now->format($format);
      }

      public function getBladeCurrentTime($format)
      {
            $now = new \DateTime('now', new \DateTimeZone('Asia/Singapore')); 

            if (isset($format) && $format == 'onlyDate')
                  $format = 'd/m/Y';
            else
                  $format = 'd/m/Y, h:i a';
            
            return $now->format($format);
      }

      public function getSqlDatetimeFormat($date)
      {
           $formatDate =  \DateTime::createFromFormat('d/m/Y, h:i a',$date);
           return $formatDate->format('Y-m-d H:i:s');
      }

      public function getBladeDateFormat($format)
      {
            $get = strtotime($format);
            $changeFormat = date('d/m/Y',$get); 

            return $changeFormat;
      }

      public function getBladeDateTimeFormat($format)
      {
            $get = strtotime($format);
            $changeFormat = date('d/m/Y H:i:s',$get); 

            return $changeFormat;
      }
    
      public function getWorkdays($startDate, $endDate) {            
            $begin = strtotime($startDate);
            $end   = strtotime($endDate);
            if ($begin > $end) {
        
                return 0;
            } else {
                $no_days  = 0;
                while ($begin <= $end) {
                    $what_day = date("N", $begin);
                    if (!in_array($what_day, [6,7]) ) // 6 and 7 are weekend
                        $no_days++;
                    $begin += 86400; // +1 day
                };
        
                return $no_days;
            }                      
      }


      private function addheader()
      {
            header("Access-Control-Allow-Origin: *");
            header('Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept');
            header('Access-Control-Allow-Methods:POST');
            header('Content-Type: application/json');
      }

      public function generalReturnSingleRow($row)
      {            
            $result = json_encode($row);
            $this->addheader();
            return $result;
      }

      public function generalReturn($status_code, $status_msg, $result = null, $result_name = "data")
      {
            $row = array();    
            $row["statusCode"] = $status_code;
            $row["statusMsg"] = $status_msg;
            $row[$result_name] = $result;
            $result = json_encode($row);
            $this->addheader();
            return $result;
      }

      public function generateRandomString($length = 10) {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            $randomString = '';
            for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
            }
            return $randomString;
      }
}
