<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

class TestController extends Controller
{



    //bài 1
//    function twoSum() {
//        $array = [2,7,9,10,44,4,7,8,5,1];
//        $target = 9;
//        $map = [];
//        $map_all = [];
//        $usedValues = [];
//        foreach ( $array as $key => $value){
//            $result = $target - $value;
//            if (isset($map[$result]) and !isset($usedValues[$value]) and !isset($usedValues[$result])){
//                $map_all[] = [$map[$result],$key];
//                $usedValues[$value] = true;
//                $usedValues[$result] = true;
//            }
//            if (!isset($map[$value])) {
//                $map[$value] = $key;
//            }
//        }
//        return $map_all;
//    }

     //bài 3
//    public function twoSum()
//    {
//        $s = 'pwwkew';
//        $maxLen = 0; // độ đài chuỗi;
//        $left = 0; // dánh dấu vị trí trong chuỗi
//        $set =[] ; // lưu vị trí chuỗi,
//
//        for ($right = 0; $right < strlen($s) ;$right ++){
//            $char = $s[$right];  // lấy ký tự trong chuỗi $s
//
//            //nếu gặp ký tự tồn tại trong mảng $set sẽ loại bỏ
//
//            while (isset($set[$char])){
//                unset($set[$s[$left]]);
//                $left++;
//            }
//
//            $set[$char] = true;
//
//            $maxLen = max($maxLen,$right - $left +1);
//        }
//        return $maxLen;
//    }
    //bài 4
//    public function twoSum()
//    {
//        $nums1 = [1,2];
//        $nums2 = [3,4];
//
//        $i  = $j = 0;
//        $merged = [];
//
//        while ($i < count($nums1) and $j < count($nums2)){
//            if ($nums1[$i] < $nums2[$j]){
//                $merged[] = $nums1[$i];
//                $i++;
//            }else{
//                $merged[] = $nums2[$j];
//                $j++;
//            }
//        }
//
//        while ($i <  count($nums1)){
//            $merged[] = $nums1[$i];
//            $i++;
//        }
//
//        while ($j <  count($nums2)){
//            $merged[] = $nums2[$j];
//            $j++;
//        }
//
//        $n = count($merged);
//
//        // neu tong tham so la le thi chia luon
//
//        if ($n % 2 == 1){
//            return $merged[$n / 2];
//        }
//
//        //neu tong tham so la chan
//        $mid1 = $merged[$n /2 -1];
//        $mid2 = $merged[$n /2];
//
//        return  ($mid1 + $mid2) /2;
//    }
    //bài 5
//    public function twoSum()
//    {
//        $s = "cbbd";
//
//        $n = strlen($s);
//        if ($n < 2){
//            return $s;
//        }
//        $bestStart = 0;
//        $bestLen = 1;
//
//        $expand = function ($l , $r) use ( $s ,$n ,&$bestLen, &$bestStart )  {
//                while ($l >= 0 and  $r < $n and $s[$l] === $s[$r] ){
//                    $curLen = $r -$l +1;
//                    if ($curLen > $bestLen){
//                        $bestLen = $curLen;
//                        $bestStart = $l;
//                    }
//                    $l -- ;
//                    $r ++ ;
//                }
//        };
//        for ($i = 0; $i < $n; $i++){
//            $expand($i,$i);
//            $expand($i,$i + 1);
//        }
//        return substr($s,$bestStart,$bestLen);
//
//
//
//    }
    //bài 6
    public function twoSum()
    {


    }

}
//bài 1 có 1 mảng và 1 số đích  , tìm trong mảng cặp số  cộng với nhau bằng số đích  , nhưng phải khác nhau về key ( hoặc value)
//
//bài 3 cho 1 chuỗi ký tự  , tìm chuỗi con lớn nhất , nhưng chuỗi con phải không có ký tự trùng nhau.
//bài 4 cho 2 mảng  ký tự đã được sắp xếp nó là int  , tìm trung vị của 2 mảng số đã.
//bài 5 cho 1 mảng ký tự , tìm ký tự đối xứng vd  babad => cần tìm bab hoặc aba => độ dài chuỗi đối xứng là 3.
