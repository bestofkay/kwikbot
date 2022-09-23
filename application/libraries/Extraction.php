<?php
//error_reporting(0);
class Extraction
{

    public function mostFrequent($arr)
    {
        if(count($arr) > 0){
            $n = sizeof($arr) / sizeof($arr[0]);

            // Sort the array
            sort($arr);
            sort($arr, $n);

            // find the max frequency
            // using linear traversal
            $max_count = 1;
            $res = $arr[0];
            $curr_count = 1;
            for ($i = 1; $i < $n; $i++) {
                if ($arr[$i] == $arr[$i - 1])
                    $curr_count++;
                else {
                    if ($curr_count > $max_count) {
                        $max_count = $curr_count;
                        $res = $arr[$i - 1];
                    }
                    $curr_count = 1;
                }
            }

            if ($curr_count > $max_count) {
                $max_count = $curr_count;
                $res = $arr[$n - 1];
            }
            if ($max_count == 1) {
                $res = 'irregular';
            }

            return $res;
        }
    }

    public function extract($data){
        $trans_row=array();
        $new_comp=array();
        $new_comps=array();
        $outputArray=array();
        $first_date='';
        $method_pass='';
        $first_transaction=array();
        $first_amount='';
        $row_transaction=array();
        $json = json_decode($data, true);
        $page=0;
        $transaction_start=array();
        $t_sort=array();

        foreach($json as $value){
            foreach($value as $v){
                if($v['str'] != " " || empty($v['str'])){
                    $s_sort[]=array(
                        'str'=>$v['str'],
                        'y'=>$v['y'],
                        'x'=>$v['x'],
                    );
                    $t_sort[]=array(
                        'str'=>$v['str'],
                        'y'=>$v['y'],
                        'x'=>$v['x'],
                    );
                }
            }


            $columns2 = array_column($s_sort, 'y');
            $columns = array_column($s_sort, 'x');
            array_multisort($columns2, SORT_ASC, $columns, SORT_ASC, $s_sort);
            $sort_json[]=$s_sort;
        }
        usort($t_sort, function($a, $b) {
            return $a['y'] <=> $b['y'] ?: $a['x'] <=> $b['x'];
        });


        foreach($json as $value){ $page++; $result_start=array(); $fs=array(); $count=0;
            foreach($value as $val){$count++;
                $left_values[]=$val['x'];
                $fs[]=$val['y'];
                if(strtolower($val['str'])=='balance' || strtolower($val['str'])=='bal' || strtolower($val['str'])=='balan' || strtolower($val['str'])=='bala' || strtolower($val['str'])=='balanc'){
                    $result_start=array('x'=>$val['x'], 'y'=>$val['y'], 'page'=>$page, 'count'=>$count);
                    $co=0;
                    foreach($fs as $f){
                        if($f==$result_start['y']){ $co++; }
                    }
                    if($co > 2){
                        break;
                    }else{
                        $result_start=array();
                    }
                }

            }
            if(!empty($result_start)){
                $transaction_start[]=$result_start;
            }else{
                $transaction_start[]=array('x'=>0, 'y'=>0, 'page'=>$page, 'count'=>0);
            }

        }

        $left_values=array_unique($left_values, SORT_REGULAR);
        sort($left_values);
        $highest_left=$left_values[count($left_values)-1];
        $highest_left=round($highest_left * 0.5);
        $lowest_left=$left_values[0];
        //echo $lowest_left;


        ############## METHOD 1 ################

        $page=0;
        $method_pass=1;
        $is_fail=false;
        $transaction_data=array();
        $n_transaction_data=[];
        $top_start=0;
        $is_break=false;
        $is_done=true;
        $is_date=false;
        $is_date_test=false;
        $check_count=0;
        foreach($json as $value){ $page++; $count=0;
            $transaction_row=[];
            $starting_left=0;

            foreach($value as $da){$count++;
                foreach($transaction_start as $st){
                    if($count > $st['count'] && $page == $st['page'] && $da['y'] > $st['y']){
                        if(strtolower(trim($da['str'])) != 'opening bal' || strtolower(trim($da['str'])) != 'opening balance'){
                            if($lowest_left == $da['x'] && !$is_date_test){
                                $is_date_test=true;
                                if($this->test_date($da['str'])){
                                    $is_date=true;
                                }
                            }
                            if(($starting_left - $da['x']) < $highest_left){
                                $transaction_row[]=array('position'=>$da['x'], 'val'=>$da['str'], 'top'=>$da['y']);
                                $starting_left=$da['x'];
                            }else{
                                $transaction_data[]= $transaction_row;
                                $transaction_row=[];
                                $transaction_row[]=array('position'=>$da['x'], 'val'=>$da['str'], 'top'=>$da['y']);
                                $starting_left=$da['x'];
                            }

                        }
                    }
                }

            }

            if(count($transaction_row) > 0){
                $transaction_data[]= $transaction_row;
            }
            if(count($transaction_data) > 7){
                $check_count++;
            }
            $cc_transaction_data=$transaction_data;
            if($check_count==1){
                $new_array=array();
                $row=0;
                foreach($transaction_data as $arr){$related_array=array(); $y=0; $str=''; $row++;
                    foreach($arr as $r){
                        if($y < $r['top']){
                            $str .=' '. $r['val'];
                            //$related_array[]=$r['str'];
                            $y=$r['top'];
                        }else{
                            $related_array[]=$str;
                            $str = $r['val'];
                        }


                    }
                    $related_array[]=$str;
                    $new_array[]=$related_array;
                }
                $count_array=count($new_array);
                if($count_array > 0){
                    foreach($new_array as $arr){
                        $desc_array='';
                        $amount_array=0;
                        $bal_array=0;
                        $date_array=0;
                        $date_check=false;$amount_check=false;$balance_check=false;
                        foreach($arr as $r){

                            $chk_date=$this->test_date($r);
                            $chk_amount=$this->check_amount($r);
                            $chk_balance=$this->check_amount($r);
                            $desc= $r;
                            if(!$date_check){
                                if($chk_date){
                                    $date_array=$chk_date;
                                    $date_check=true;
                                    $desc='';
                                }
                            }
                            if(!$amount_check){
                                if($chk_amount){
                                    if($chk_amount != 0){
                                        $amount_array=$chk_amount;
                                        $amount_check=true;
                                        $desc='';
                                    }
                                }
                            }else{
                                if($chk_balance){
                                    $bal_array=$chk_balance;
                                    $balance_check=true;
                                    $desc='';
                                }
                            }
                            $desc= $desc;
                            $desc_array .=' '.$desc;

                        }
                        if($date_check && $amount_check && $balance_check && $desc_array!=' '){

                            $row_transaction[]=array('complete'=>1, 'date'=>$date_array, 'amount'=>$amount_array, 'balance'=>$bal_array, 'description'=>trim($desc_array));
                        }

                    }

                }
            }
            if($check_count==1 && ($row - count($new_array)) > 2){
                $is_fail=true;
            }
            if($is_fail){
                break;
            }
            $transaction_row=[];
            $n_transaction_data[]= $transaction_data;
            $transaction_data=[];


        }
        //print_r($n_transaction_data);
        if($is_fail){
            if($is_date){
                $n_transaction_data=$this->method2($json, $transaction_start, $starting_left);
                if($n_transaction_data){
                    $method_pass=2;
                }else{
                    $n_transaction_data=$this->method3($sort_json, $transaction_start, $starting_left,  $is_date);
                    if($n_transaction_data){
                        $method_pass=3;
                    }else{
                        $n_transaction_data=$this->method4($sort_json, $transaction_start, $starting_left);
                        if($n_transaction_data){
                            $method_pass=4;
                        }else{
                            $method_pass='';
                        }
                    }
                }
            }else{
                $n_transaction_data=$this->method3($sort_json, $transaction_start, $starting_left, $is_date);
                if($n_transaction_data){
                    $method_pass=3;
                }else{
                    $n_transaction_data=$this->method4($sort_json, $transaction_start, $starting_left);
                    if($n_transaction_data){
                        $method_pass=4;
                    }else{
                        $method_pass='';
                    }
                }
            }
        }
        $row_transaction=array();

        if($method_pass!='' || !empty($method_pass)){
            $new_transaction_data=[];
            $new_array=[];

            foreach($n_transaction_data as $thd){$related_array=[];
                foreach($thd as $td){$y=0;$str='';
                    foreach($td as $r){
                        if($y < $r['top'] && !$this->test_date($str) && !$this->check_amount($str)){
                            $str .=' '. $r['val'];
                            //$related_array[]=$r['str'];
                            $y=$r['top'];
                        }else{
                            $related_array[]=$str;
                            $str = $r['val'];
                            $y=$r['top'];
                        }
                    }
                    $related_array[]=$str;
                    $new_array[]=$related_array;
                    $related_array=[];
                }

                $new_transaction_data[]=$new_array;
                $new_array=[];
            }

            foreach($new_transaction_data as $arr){
                foreach($arr as $ro){
                    $count_a[]=count($ro);
                }
            }
            // print_r($count_a); exit;

            if(count($count_a) > 1){
                $most_occur=$this->mostFrequent($count_a);
            }else{
                $most_occur=$count_a[0];
            }

            //print_r($new_transaction_data); exit;
            foreach($new_transaction_data as $arr){
                $a=end($arr);
                foreach($arr as $ro){$cc=0;
                    $b=$ro;
                    $l_date= $date_array;
                    $desc_array='';
                    $amount_array=0;
                    $bal_array=0;
                    $date_array=0;
                    $date_check=false; $amount_check=false; $balance_check=false; $no_check=false;
                    $array_amount=array();
                    foreach($ro as $r){ $cc++;
                        // if($cc <= $most_occur){
                        $chk_date=$this->test_date($r);
                        $chk_amount=$this->check_amount($r);
                        $desc= $r;
                        if(!$date_check){
                            if($chk_date){
                                $date_array=$chk_date;
                                $date_check=true;
                                $desc='';
                            }
                        }
                        // if($date_check){
                        if($chk_amount){
                            $array_amount[]=$chk_amount;
                        }else{
                            if($desc == " "){

                            }else{
                                if(empty($desc_array)){ $desc_array =$desc;}
                                else{$desc_array .=' '.$desc;}
                            }

                        }

                        // }
                    }
                    $am=count($array_amount);
                    if($am > 1){
                        for($x=0; $x<$am; $x++){
                            if(($x != $am-1 || $x != $am-2)){
                                $new_amount[]=$array_amount[$x];
                            }
                        }

                        $m=count($new_amount);
                        if($m > 1){
                            $bal_array=$new_amount[$m-1];
                            if(strpos($bal_array, ".") !== false){
                                $amount_array= $new_amount[$m-2];
                            }else{
                                $bal_array=$new_amount[$m-2];
                                $amount_array= $new_amount[$m-3];
                            }
                        }else{
                            $amount_array=0; $bal_array=0;
                        }
                        if($date_check && $amount_array != 0 && $desc_array!=' '){
                            $row_transaction[]=array('complete'=>1, 'date'=>$date_array, 'amount'=>$amount_array, 'balance'=>$bal_array, 'description'=>trim($desc_array));
                        }
                        if(!$date_check && $amount_array != 0 && $desc_array!=' ' && $a === $b){
                            $row_transaction[]=array('complete'=>0, 'date'=>$l_date, 'amount'=>$amount_array, 'balance'=>$bal_array, 'description'=>trim($desc_array));
                        }
                    }

                }
                $new_row_transaction[]=$row_transaction;
                $row_transaction=[];
            }

            $count=0;
            $pg=0;
            // print_r($new_row_transaction); exit;
            foreach($new_row_transaction as $arr){$error=0; $count++; $pg++; $success=0;
                foreach($arr as $ro){
                    if($ro['complete']==1){
                        $trans_row[]=$ro;
                    }
                    if($pg==1 && $ro['complete']==0){
                        $error++;
                    }
                    if($pg==1  && $ro['complete']==1){
                        $success++;
                    }
                }

                $new_trans_array[]=$trans_row;
                $trans_row=[];
            }

            //print_r($new_trans_array) ;
            foreach($new_trans_array as $arr){
                foreach($arr as $ar){
                    $new_comps[]=array(
                        'date'=>$ar['date'],
                        'amount' =>  round($ar['amount'], 2),
                        'balance' => round($ar['balance'], 2),
                        'desc'=>$ar['description'],
                        'credit'=>0,
                        'debit'=>0
                    );
                }
            }



            $xx=0;
            foreach($new_comps as $n){
                if($n['amount'] == 0 ){
                    continue;
                }else{
                    $new_comp[]=array(
                        'date'=>$n['date'],
                        'amount'=>$n['amount'],
                        'balance'=>$n['balance'],
                        'desc'=>$n['desc'],
                        'credit'=>0,
                        'debit'=>0
                    );
                }
            }

            foreach($new_comp as $nc){
                if($nc['amount']==0 && $nc['balance']==0){
                    continue;
                }else{
                    $xx++;
                    if($xx==1){
                        $first_date=$nc['date'];
                        $first_amount=$nc['amount'];
                        break;
                    }

                }
            }

            $cou_ar=count($new_comp);
            $arr = end($new_comp);
            $second_date=$new_comp[1];
            $second_date=$second_date['date'];
            $last_date= $arr['date'];
            //return $second_date; exit;
            if ($first_date > $last_date) {
                if($second_date > $last_date){
                    $new_comp=array_reverse($new_comp);
                }else{
                    array_shift($new_comp);
                }

            }
            //return $new_comp;
            $y=0;
            $x=0;
            $type='';
            $prev_bal=0;
            foreach($new_comp as $nd){$x++; $y=0;
                $new_bal=$nd['balance'];
                $amount=$nd['amount'];

                $a_bal=$new_bal - $prev_bal;

                if(($a_bal < 0 && $amount > 0) || ($a_bal > 0 && $amount < 0)){
                    $type='debit';
                }
                elseif(($a_bal > 0 && $amount > 0) || ($a_bal < 0 && $amount < 0)){
                    $type='credit';
                }
                foreach($new_comp as &$value){$y++;
                    if($value['date'] === $nd['date'] && $value['amount'] === $nd['amount'] && $value['desc'] === $nd['desc'] && $value['balance'] === $nd['balance'] && $x==$y){
                        if($type=='credit'){
                            $value['credit']=$nd['amount'];
                            $value['debit']=0;
                        }
                        if($type=='debit'){
                            $value['credit']=0;
                            $value['debit']=$nd['amount'];
                        }
                        break;
                    }

                }

                $prev_bal=$nd['balance'];
            }
            //echo $first_amount;
            $cred_deb=array();
            $first_break=false;
            foreach($n_transaction_data as $outss){
                //if($out[])
                foreach($outss as $outs){

                    foreach($outs as $out){
                        $str=$out['val'];
                        //  if(strpos($str,".") !== false || strpos($str,":") !== false && strlen($str) > 0){
                        $strs=str_replace(',', '',$str);
                        $strs=str_replace(':', '.',$strs);
                        // echo (float) $strs.'<br>';
                        if($strs == $first_amount){
                            $first_transaction=array(
                                'length'=>strlen(floatval($strs)),
                                'val'=>floatval($strs),
                                'position'=>$out['position'],
                            );
                            $first_break=true;

                            break;
                        }

                        // }
                    }
                    if($first_break){
                        break;
                    }
                }
                if($first_break){
                    break;
                }
            }
            // print_r($first_transaction); exit;

            $deb_count=count($cred_deb);
            $percent=0;
            $cou=[];
            $t_type='';
            $is_br=false;
            $xx=0;
            foreach($new_comp as $newds){$xx++;
                // echo $newds['debit'];
                //if($xx > 1){
                if($xx > 1){
                    if(strlen($newds['amount']) == strlen($first_transaction['val'])){

                        foreach($n_transaction_data as $outss){
                            //if($out[])
                            foreach($outss as $outs){

                                foreach($outs as $out){
                                    $str=$out['val'];
                                    if(strpos($str,".") != false || strpos($str,":") != false){
                                        $strs=str_replace(',', '',$str);
                                        $strs=str_replace(':', '.',$strs);
                                    }
                                    //echo strlen(floatval($strs)).' '.$out['position'].':'.strlen($newds['amount']).' '.$first_transaction['position'].'<br>';
                                    if(strlen($newds['amount']) == strlen(floatval($strs)) && $first_transaction['position']==$out['position']){
                                        $t_type='Yes';
                                        if($newds['credit'] > 0){
                                            $t_type='credit';
                                        }
                                        if($newds['debit'] > 0){
                                            $t_type='debit';
                                        }
                                        $is_br=true;
                                        break;
                                    }
                                }
                                if($is_br){ break; }
                            }
                            if($is_br){ break; }
                        }
                        // }
                    }
                }
                if($is_br){ break; }
            }
            //print_r($first_transaction); exit;
            //echo $t_type; exit;

            $x=0;
            //echo $cc_amount; exit;
            foreach($new_comp as &$value){
                if($x==0){
                    if($t_type == 'debit'){
                        $value['credit']=0;
                        $value['debit']=$value['amount'];
                    }
                    elseif($t_type == 'creit'){
                        $value['credit']=$value['amount'];
                        $value['debit']=0;
                    }
                    break;
                }
                $x++;
            }

            $outputArray=[];
            $credit=0;
            $debit=0;
            foreach($new_comp as $n){

                if($n['credit'] == 0 && $n['debit'] == 0){}
                else{
                    $dess=substr($n['desc'], 0, 150);
                   $dess = str_replace(array('\'', '&quot;'), '', $dess);
                    $credit += $n['credit'];
                    $debit += $n['debit'];
                    $outputArray[]= array(
                        'date' =>  $n['date'],
                        'credit' =>  round($n['credit'], 2),
                        'debit' =>  round($n['debit'], 2),
                        'balance' => round($n['balance'], 2),
                        'description' => $dess
                    );
                   }

            }

            $sum_credit=$credit;
            $sum_debitt=$debit;

            $array_result=array(
                'method_pass'=>$method_pass,
                'array'=>$outputArray,
                'total_credit'=>$sum_credit,
                'total_debit'=>$sum_debitt
            );
            return $outputArray;

        }else{
            return false;
        }

     }



    public function method2($json, $transaction_start, $lowest_left){
        $page=0;
        $last_left=$lowest_left;
        $is_fail=false;
        $transaction_data=array();
        $n_transaction_data=[];
        $last_left=0;
        $top_start=0;
        $is_break=false;
        $is_done=true;
        $is_date=false;
        $is_date_test=false;
        $check_count=0;
        foreach($json as $value){ $page++; $count=0;
            $transaction_row=[];
            $starting_left=0;

            foreach($value as $da){$count++;
                foreach($transaction_start as $st){
                    if($count > $st['count'] && $page == $st['page'] && $da['y'] > $st['y']){
                        if(strtolower($da['str']) != 'opening bal' || strtolower($da['str']) != 'opening balance'){

                            if($da['x']==$starting_left){

                                if($last_left==$da['x']){
                                    // $last_str .=$da['str'];
                                    $transaction_row[]=array('position'=>$da['x'], 'val'=>$da['str'],'top'=>$da['y']);
                                    $last_left=$da['x'];
                                }else{
                                    $columns2 = array_column($transaction_row, 'top');
                                    $columns = array_column($transaction_row, 'position');
                                    array_multisort($columns2, SORT_ASC, $columns, SORT_ASC, $transaction_row);
                                    $transaction_data[]= $transaction_row;
                                    $transaction_row=[];
                                    $transaction_row[]=array('position'=>$da['x'], 'val'=>$da['str'],'top'=>$da['y']);
                                    $last_left=$da['x'];

                                }
                            }else{
                                $transaction_row[]=array('position'=>$da['x'], 'val'=>$da['str'],'top'=>$da['y']);
                                $last_left=$da['x'];
                            }
                        }
                    }
                }

            }

            if(count($transaction_row) > 0){
                $transaction_data[]= $transaction_row;
            }
            if(count($transaction_data) > 7){
                $check_count++;
            }
            if($check_count==1){
                $new_array=array();
                $row=0;
                foreach($transaction_data as $arr){$related_array=array(); $y=0; $str=''; $row++;
                    foreach($arr as $r){
                        if($y < $r['top']){
                            $str .=' '. $r['val'];
                            //$related_array[]=$r['str'];
                            $y=$r['top'];
                        }else{
                            $related_array[]=$str;
                            $str = $r['val'];
                        }


                    }
                    $related_array[]=$str;
                    $new_array[]=$related_array;
                }
                $count_array=count($new_array);
                if($count_array > 0){
                    foreach($new_array as $arr){
                        $desc_array='';
                        $amount_array=0;
                        $bal_array=0;
                        $date_array=0;
                        $date_check=false;$amount_check=false;$balance_check=false;
                        foreach($arr as $r){


                            $chk_date=$this->test_date($r);
                            $chk_amount=$this->check_amount($r);
                            $chk_balance=$this->check_amount($r);
                            $desc= $r;
                            if(!$date_check){
                                if($chk_date){
                                    $date_array=$chk_date;
                                    $date_check=true;
                                    $desc='';
                                }
                            }
                            if(!$amount_check){
                                if($chk_amount){
                                    if($chk_amount != 0){
                                        $amount_array=$chk_amount;
                                        $amount_check=true;
                                        $desc='';
                                    }
                                }
                            }else{
                                if($chk_balance){
                                    $bal_array=$chk_balance;
                                    $balance_check=true;
                                    $desc='';
                                }
                            }
                            $desc= $desc;
                            $desc_array .=' '.$desc;

                        }
                        if($date_check && $amount_check && $balance_check && $desc_array!=' '){
                            $row_transaction[]=array('complete'=>1, 'date'=>$date_array, 'amount'=>$amount_array, 'balance'=>$bal_array, 'description'=>trim($desc_array));
                        }

                    }

                }
            }
            if($check_count==1 && ($row - count($new_array)) > 2){
                $is_fail=true;
                return false;
                break;
            }
            $transaction_row=[];
            $n_transaction_data[]= $transaction_data;
            $transaction_data=[];
            return $n_transaction_data;

        }

    }

        ############## METHOD 3 ####################
    public function method3($json, $transaction_start, $starting_left, $is_date){

        $is_fail=false;
        $transaction_data=array();
        $n_transaction_data=[];
        $top_start=0;
        $is_break=false;
        $is_done=true;
        $is_date=false;
        $is_date_test=false;
        $check_count=0;
        $page=0;
        //echo $lowest_left;
        if($is_date){
            foreach($json as $value){ $page++; $count=0;
                $transaction_row=[];
                $starting_left=0;

                foreach($value as $da){$count++;
                    foreach($transaction_start as $st){
                        if($count > $st['count'] && $page == $st['page'] && $da['y'] > $st['y']){
                            if(strtolower($da['str']) != 'opening bal' || strtolower($da['str']) != 'opening balance'){

                                if($starting_left == $da['left']){
                                    $transaction_data[]= $transaction_row;
                                    $transaction_row=[];
                                    $transaction_row[]=array('position'=>$da['x'], 'val'=>$da['str'], 'top'=>$da['y']);
                                    $top_value=$da['top'];

                                }else{
                                    $transaction_row[]=array('position'=>$da['x'], 'val'=>$da['str'], 'top'=>$top_value);
                                    //$starting_left=$da['left'];
                                }

                            }
                        }
                    }

                }

                if(count($transaction_row) > 0){
                    $transaction_data[]= $transaction_row;
                }
                if(count($transaction_data) > 7){
                    $check_count++;
                }
                if($check_count==1){
                    $new_array=array();
                    $row=0;
                    foreach($transaction_data as $arr){$related_array=array(); $y=0; $str=''; $row++;
                        foreach($arr as $r){
                            if($y < $r['top']){
                                $str .=' '. $r['val'];
                                //$related_array[]=$r['str'];
                                $y=$r['top'];
                            }else{
                                $related_array[]=$str;
                                $str = $r['val'];
                            }


                        }
                        $related_array[]=$str;
                        $new_array[]=$related_array;
                    }
                    $count_array=count($new_array);
                    if($count_array > 0){
                        foreach($new_array as $arr){
                            $desc_array='';
                            $amount_array=0;
                            $bal_array=0;
                            $date_array=0;
                            $date_check=false;$amount_check=false;$balance_check=false;
                            foreach($arr as $r){


                                $chk_date=$this->test_date($r);
                                $chk_amount=$this->check_amount($r);
                                $chk_balance=$this->check_amount($r);
                                $desc= $r;
                                if(!$date_check){
                                    if($chk_date){
                                        $date_array=$chk_date;
                                        $date_check=true;
                                        $desc='';
                                    }
                                }
                                if(!$amount_check){
                                    if($chk_amount){
                                        if($chk_amount != 0){
                                            $amount_array=$chk_amount;
                                            $amount_check=true;
                                            $desc='';
                                        }
                                    }
                                }else{
                                    if($chk_balance){
                                        $bal_array=$chk_balance;
                                        $balance_check=true;
                                        $desc='';
                                    }
                                }
                                $desc= $desc;
                                $desc_array .=' '.$desc;

                            }
                            if($date_check && $amount_check && $balance_check && $desc_array!=' '){
                                $row_transaction[]=array('complete'=>1, 'date'=>$date_array, 'amount'=>$amount_array, 'balance'=>$bal_array, 'description'=>trim($desc_array));
                            }

                        }

                    }
                }
                if($check_count==1 && ($row - count($new_array)) > 2){
                    $is_fail=true;
                    return false;
                    break;
                }
                $transaction_row=[];
                $n_transaction_data[]= $transaction_data;
                $transaction_data=[];
                return $n_transaction_data;
            }
        }else{
            return false;
        }
    }

    ################### METHOD 4 ####################

    ############## METHOD 4 ####################
    public function method4($json, $transaction_start, $starting_left){
        $page=0;
        $is_fail=false;
        $transaction_data=array();
        $n_transaction_data=[];
        $top_start=0;
        $is_break=false;
        $is_done=true;
        $is_date=false;
        $is_date_test=false;
        $check_count=0;
        foreach($json as $value){ $page++; $count=0;
            $transaction_row=[];
            $starting_left=0;

            foreach($value as $da){$count++;
                foreach($transaction_start as $st){
                    if($count > $st['count'] && $page == $st['page'] && $da['y'] > $st['y']){
                        if(strtolower($da['str']) != 'opening bal' || strtolower($da['str']) != 'opening balance'){

                            if($starting_left == $da['left']){
                                $transaction_data[]= $transaction_row;
                                $transaction_row=[];
                                $transaction_row[]=array('position'=>$da['x'], 'val'=>$da['str'], 'top'=>$da['y']);
                                $top_value=$da['top'];

                            }else{
                                $transaction_row[]=array('position'=>$da['x'], 'val'=>$da['str'], 'top'=>$top_value);
                                //$starting_left=$da['left'];
                            }

                        }
                    }
                }

            }

            if(count($transaction_row) > 0){
                $transaction_data[]= $transaction_row;
            }
            if(count($transaction_data) > 7){
                $check_count++;
            }
            if($check_count==1){
                $new_array=array();
                $row=0;
                foreach($transaction_data as $arr){$related_array=array(); $y=0; $str=''; $row++;
                    foreach($arr as $r){
                        if($y < $r['top']){
                            $str .=' '. $r['val'];
                            //$related_array[]=$r['str'];
                            $y=$r['top'];
                        }else{
                            $related_array[]=$str;
                            $str = $r['val'];
                        }


                    }
                    $related_array[]=$str;
                    $new_array[]=$related_array;
                }
                $count_array=count($new_array);
                if($count_array > 0){
                    foreach($new_array as $arr){
                        $desc_array='';
                        $amount_array=0;
                        $bal_array=0;
                        $date_array=0;
                        $date_check=false;$amount_check=false;$balance_check=false;
                        foreach($arr as $r){

                            $chk_date=$this->test_date($r);
                            $chk_amount=$this->check_amount($r);
                            $chk_balance=$this->check_amount($r);
                            $desc= $r;
                            if(!$date_check){
                                if($chk_date){
                                    $date_array=$chk_date;
                                    $date_check=true;
                                    $desc='';
                                }
                            }
                            if(!$amount_check){
                                if($chk_amount){
                                    $amount_array=$chk_amount;
                                    $amount_check=true;
                                    $desc='';
                                }
                            }else{
                                if($chk_balance){
                                    $bal_array=$chk_balance;
                                    $balance_check=true;
                                    $desc='';
                                }
                            }
                            $desc= $desc;
                            $desc_array .=' '.$desc;

                        }
                        if($date_check && $amount_check && $balance_check && $desc_array!=' '){
                            $row_transaction[]=array('complete'=>1, 'date'=>$date_array, 'amount'=>$amount_array, 'balance'=>$bal_array, 'description'=>trim($desc_array));
                        }

                    }

                }
            }
            if($check_count==1 && ($row - count($new_array)) > 2){
                $is_fail=true;
                return false;
                break;
            }
            $transaction_row=[];
            $n_transaction_data[]= $transaction_data;
            $transaction_data=[];

        }
        return $n_transaction_data;
    }
    ########### FUNCTION THAT TEST DATE AND AMOUNT

    public function dateLiteral($date){
        $dates=strtolower($date);
        $t_array=array("th","st","nd","rd",",","of");
        $times=str_replace($t_array, ' ', $dates);
        $times=str_replace("jan", '01', $times);
        $times=str_replace("feb", '02', $times);
        $times=str_replace("mar", '03', $times);
        $times=str_replace("apr", '04', $times);
        $times=str_replace("may", '05', $times);
        $times=str_replace("jun", '06', $times);
        $times=str_replace("jul", '07', $times);
        $times=str_replace("aug", '08', $times);
        $times=str_replace("sep", '09', $times);
        $times=str_replace("oct", '10', $times);
        $times=str_replace("nov", '11', $times);
        $times=str_replace("dec", '12', $times);
        $times=str_replace("january", '01', $times);
        $times=str_replace("february", '02', $times);
        $times=str_replace("march", '03', $times);
        $times=str_replace("april", '04', $times);
        $times=str_replace("may", '05', $times);
        $times=str_replace("june", '06', $times);
        $times=str_replace("july", '07', $times);
        $times=str_replace("august", '08', $times);
        $times=str_replace("september", '09', $times);
        $times=str_replace("october", '10', $times);
        $times=str_replace("november", '11', $times);
        $times=str_replace("december", '12', $times);
        $times = trim(preg_replace('/\s+/',' ', $times));
        $times=preg_replace("#[^A-Za-z0-9 /-]#", ' ', $times);
        return $times;

    }

    public function test_date($date){
        $strdate=strtotime(date('Y-m-d'));
        $times=$date;
        $times=$this->dateLiteral($times);
        $t_array=array("/","-"," ");
        $times=str_replace($t_array, '/', $times);
        $time=explode('/', $times);
        if(count($time) >= 3){
            $t=strtotime($time[0].'-'.$time[1].'-'.$time[2]);
          
        }else{
            return false;
        }

        $date=date('Y-m-d', $t);
        $date_exp=explode('-', $date);
        if($date_exp[0] != '1970' && $date!=date('Y-m-d')){
            $date=$time[0].'-'.$time[1].'-'.$time[2];
            return $date;
        }else{
            return false;
        }
    }

    public function dateFormat($array)
    {
        $yr=(int)date('Y');

        $store1=[];
        $store2=[];
        $store3=[];
        foreach ($array as $e) {
            $times=$this->dateLiteral($e['date']);
            $t_array=array("/","-"," ");
            $times=str_replace($t_array, '/', $times);
            $time=explode('/', $times);
            if(strlen($time[0]) == 2 && strlen($time[1]) == 2 && strlen($time[2]) == 2){
                $store1[]=(int)$time[0];
                $store2[]=(int)$time[1];
                $store3[]=(int)('20'.$time[2]);
            }else{
                $store1[]=(int)$time[0];
                $store2[]=(int)$time[1];
                $store3[]=(int)$time[2];
            }

        }
        $max_store1=max($store1);
        $min_store1=min($store1);
        $max_store2=max($store2);
        $min_store2=min($store2);
        $max_store3=max($store3);
        $min_store3=min($store3);

        if($max_store1 > 31){
            $first='Y';
        }
        if($max_store2 > 31){
            $second='Y';
        }
        if($max_store3 > 31){
            $third='Y';
        }
        if($max_store1 > 12 && $max_store1 <= 31){
            $first='d';
        }
        if($max_store2 > 12 && $max_store2 <= 31){
            $second='d';
        }
        if($max_store3 > 12 && $max_store3 <= 31){
            $third='d';
        }
        if($max_store1 <= 12){
            $first='m';
        }
        if($max_store2 <= 12){
            $second='m';
        }
        if($max_store3 <= 12){
            $third='m';
        }
        return $first.'-'.$second.'-'.$third;


    }

    public function check_amount($data){
        $data=trim($data);
        if (strpos($data, '.') !== false) {
            $data = $data . 0;
        }
        if($this->test_date($data)){
            return false;
        }else{
            $data=strtolower($data);
            $data=str_replace('/ /g', '', $data);
            $data=str_replace('cr', '', $data);
            $data=str_replace('dr', '', $data);
            $data=str_replace('ngn ', '', $data);
            $data=str_replace('ngn', '', $data);
           
            $data=str_replace(',', '', $data);
            $data=str_replace(':', '.', $data);
           

            if(preg_match("/^-?\d+(.\d+)?$/", $data)){
                $data2 = str_replace('.', '', $data);
                if(strlen($data2) <= 12) {

                    if($this->startsWith($data2, "0") && strlen($data2) > 1 && strpos($data,".") == false){
                        return false;
                    }else{
                        return $data;
                    }

                }
                else{
                    return false;
                }
            }else{
                return false;
            }
        }
    }

    public function startsWith($string, $startString)
    {
        $len = strlen($startString);
        return (substr($string, 0, $len) === $startString);
    }

    public function utf8ize($d) {
        if (is_array($d)) {
            foreach ($d as $k => $v) {
                $d[$k] = $this->utf8ize($v);
            }
        } else if (is_string ($d)) {
            return utf8_encode($d);
        }
        return $d;
    }

}
