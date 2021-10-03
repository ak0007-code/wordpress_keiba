<?php

// 定数定義
$J_MAX=10; // 条件MAX
$UMABAN_MAX=25; // 馬番MAX
$WAKUBAN_MAX=15; // 枠番MAX

// 開始年～終了年を配列に格納
$diff = $_POST['year_e'] - $_POST['year_s'];
for ($i=0;$i<=$diff;$i++){
    $year_array[$i]=$_POST['year_s']+$i;
}

// データ格納用配列
$umaban_array=array(); // 馬番
$wakuban_array=array(); // 枠番

// 条件1~10を確認 j:条件 i:個別番号
for ($j=0;$j<$J_MAX;$j++){
    // 「選択無し」以外の場合のみ格納処理を実行
    if($_POST["j".($j+1)."_race_name"] != "選択無し"){
        // レース名
        $race_name_array[$j]=$_POST["j".($j+1)."_race_name"];
        // 馬番格納
        for($i=0;$i<$UMABAN_MAX;$i++){
            $umaban_array[$j][$i]=$_POST["j".($j+1)."_umaban_".($i+1)];
        }
        // 枠番格納
        for($i=0;$i<$WAKUBAN_MAX;$i++){
            $wakuban_array[$j][$i]=$_POST["j".($j+1)."_wakuban_".($i+1)];
        }
    }
}

// DBアクセス準備
require_once( dirname( __FILE__ ) . '/wp-load.php' );
global $wpdb;
$db_user = $wpdb->dbuser; //データベース接続ユーザーの取得
$db_passwd = $wpdb->dbpassword; //データベース接続用パスワードの取得
$db_host = $wpdb->dbhost; //データベースホストの取得
$keiba_wpdb = new wpdb($db_user, $db_passwd, 'keiba', $db_host);

// 結果フラグ 0:結果無し 1:結果有り
$j_result_flg=array();

// 条件1~10の結果をj_resultsに格納
for ($j=0;$j<$J_MAX;$j++){

    // 「選択無し」以外の場合
    if($_POST["j".($j+1)."_race_name"] != "選択無し"){
        // 馬番
        $umaban_regexp[$j]=".*";
        for($i=0;$i<$UMABAN_MAX;$i++){
            if($umaban_array[$j][$i]){
                if($umaban_regexp[$j]==".*"){
                    $umaban_regexp[$j]=(string)$umaban_array[$j][$i];
                }else{
                    $umaban_regexp[$j]="$umaban_regexp[$j]"."|".(string)$umaban_array[$j][$i];
                }
            }
        }

        // 枠番
        $wakuban_regexp[$j]=".*";
        for($i=0;$i<$WAKUBAN_MAX;$i++){
            if($wakuban_array[$j][$i]){
                if($wakuban_regexp[$j]==".*"){
                    $wakuban_regexp[$j]=(string)$wakuban_array[$j][$i];
                }else{
                    $wakuban_regexp[$j]="$wakuban_regexp[$j]"."|".(string)$wakuban_array[$j][$i];
                }
            }
        }

        // 年度
        $year_regexp=".*";
        foreach ($year_array as $year){
            if($year_regexp==".*"){
                $year_regexp=(string)$year;
            }else{
                $year_regexp="$year_regexp"."|".(string)$year;
            }
        }
        // 文字列整形
        $umaban_regexp[$j]="^(".$umaban_regexp[$j].")$";
        $wakuban_regexp[$j]="^(".$wakuban_regexp[$j].")$";
        $year_regexp=="^(".$year_regexp.")$";

        // 条件j:SQL実行
        $j_results[$j]=$keiba_wpdb->get_results("SELECT * FROM " . $race_name_array[$j] . " WHERE 馬番 REGEXP \"$umaban_regexp[$j]\" AND 枠番 REGEXP \"$wakuban_regexp[$j]\" AND 年度 REGEXP \"$year_regexp\"");
        if(count($j_results[$j])!=0){
            $j_result_flg[$j]=1;
        }
    }else{
        break;
    }
}

// 結果が0件(j_result_flgに1が無い)の場合、処理を終了する
if(!(in_array(1,$j_result_flg))){
    echo "条件にヒットする結果はありませんでした。";
    return 1;
}

// 年度ごとの結果をyear_resultsに格納
$tmp_results=array(); // 多次元配列→[year][0]=年度 [year][1]=馬名1 [year][2]=馬名2
$win_rates=array(); // 年度ごとの勝率→[year][0]=勝率 [year][1]=連体率 [year][2]=複勝率
$year_results=array();
$i=0;
foreach ($year_array as $year){
    $tmp_results[$i][0]=$year;
    foreach ($j_results as $result){
        if(count($result)==0){
            continue;
        }
        foreach ($result as $row){
            if($row->年度 == $year){
                if(!(in_array($row->馬名,$tmp_results[$i]))){
                    array_push($tmp_results[$i],$row->馬名);
                }
            }
        }
    }

    // 結果が0件(tmp_resultに年度情報しか入っていない)の場合、処理をスキップする
    if(count($tmp_results[$i])==1){
        continue;
    }
    
    // SQL実行用の馬名を正規表現で格納
    $uma_name_regexp=".*";
    for ($k=1;$k<count($tmp_results[$i]);$k++){
        if($uma_name_regexp==".*"){
            $uma_name_regexp=(string)$tmp_results[$i][$k];
        }else{
            $uma_name_regexp="$uma_name_regexp"."|".(string)$tmp_results[$i][$k];
        }
    }

    $uma_name_regexp="^(".$uma_name_regexp.")$";

    // SQL実行
    $year_results[$i]=$keiba_wpdb->get_results("SELECT * FROM $_POST[race_name] WHERE 年度 = ". $year . " AND 馬名 REGEXP \"$uma_name_regexp\"");

    // 年度ごとの勝率計算
    $first=0;
    $second=0;
    $third=0;
    $all=count($year_results[$i]);
    foreach ($year_results[$i] as $row){
        if($row->着順==1){
            $first=1;
        }else if($row->着順==2){
            $second=1;
        }else if($row->着順==3){
            $third=1;
        }
    }
    $win_rates[$i][0]=($first/$all*100); // 勝率
    $win_rates[$i][1]=(($first+$second)/$all*100); // 連体率
    $win_rates[$i][2]=(($first+$second+$third)/$all*100); // 複勝率

    $i++;
}

// 年度ごとの勝率を表示
$year="$_POST[year_s]";
$i=0;
foreach ($win_rates as $win_rate){
    echo $year;
    echo "<br>";
    echo "単勝率:".round($win_rates[$i][0],1)."%"."<br/>";
    echo "連体率:".round($win_rates[$i][1],1)."%"."<br/>";
    echo "複勝率:".round($win_rates[$i][2],1)."%"."<br/>";
    $i++;
    $year++;
}
?>

<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Hello!</title>
    <style>
        .table4 {
            border-collapse: collapse;
        }
        .table4 th {
            border: 1px solid gray;
            text-align: center;
            width: max-content;
            color: white;
            background: cyan;
        }
        .table4 td {
            border: 1px solid gray;
            text-align:left;
        }
    </style>
</head>
<body>
    <table class="table4" border="1">
        <tr><th>年度</th><th>馬名</th><th>馬番</th><th>枠番</th><th>着順</th></tr>
        <?php foreach ($year_results as $year_result) : ?>
            <?php foreach ($year_result as $row) : ?>
                <tr><td><?php echo $row->年度 ?></td><td><?php echo $row->馬名 ?></td><td><?php echo $row->馬番 ?></td><td><?php echo $row->枠番 ?></td><td><?php echo $row->着順 ?></td></tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </table>
</body>
</html>