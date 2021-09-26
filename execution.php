<?php

// 定数定義
$J_MAX=10; // 条件MAX
$UMABAN_MAX=30; // 馬番MAX

// ヘッダー表示
echo "$_POST[race_name]の結果($_POST[year_s]～$_POST[year_e])";
echo "<br>";
echo "<br>更新：".date("Y/m/d H:i:s");
echo "<br>"."<br>";

// 開始年～終了年を配列に格納
$diff = $_POST['year_e'] - $_POST['year_s'];
for ($i=0;$i<=$diff;$i++){
    $year_array[$i]=$_POST['year_s']+$i;
}

// データ格納用配列
$umaban_array=array(); // 馬番用

// 条件1~10を確認 j:条件 i:番号
for ($j=0;$j<$J_MAX;$j++){
    // 「選択無し」以外の場合のみ格納処理を実行
    if($_POST["j".($j+1)."_race_name"] != "選択無し"){
        // レース名
        $race_name_array[$j]=$_POST["j".($j+1)."_race_name"];
        // 馬番格納
        for($i=0;$i<$UMABAN_MAX;$i++){
            $umaban_array[$j][$i]=$_POST["j".($j+1)."_umaban_".($i+1)];
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

// 条件1~10 > データベースからデータを取り出す
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
        $year_regexp=="^(".$year_regexp.")$";

        // 条件j:SQL実行
        $j_results[$j]=$keiba_wpdb->get_results("SELECT * FROM " . $race_name_array[$j] . " WHERE 馬番 REGEXP \"$umaban_regexp[$j]\" AND 年度 REGEXP \"$year_regexp\"");
    }else{
        break;
    }
}

// 年度ごとに上記SQL実行結果の馬名を格納
$tmp_results=array(); // 多次元配列→[year][0]=年度 [year][1...]=馬名
$win_rates=array();
$i=0;
foreach ($year_array as $year){
    $tmp_results[$i][0]=$year;
    foreach ($j_results as $result){
        foreach ($result as $row){
            if($row->年度 == $year){
                if(!(in_array($row->馬名,$tmp_results[$i]))){
                    array_push($tmp_results[$i],$row->馬名);
                }
            }
        }
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
    $year_regexp="^(".$year.")$";

    // SQL実行
    $year_results[$i]=$keiba_wpdb->get_results("SELECT * FROM $_POST[race_name] WHERE 年度 = ". $year . " AND 馬名 REGEXP \"$uma_name_regexp\"");

    // 勝率計算
    $first=0;
    $second=0;
    $third=0;
    $all=count($year_results[0]);
    foreach ($year_results[$i] as $row){
        if($row->着順==1){
            $first=1;
        }else if($row->着順==2){
            $second=1;
        }else if($row->着順==3){
            $third=1;
        }
    }
    $win_rates[$i][0]=($first/$all*100);
    $win_rates[$i][1]=(($first+$second)/$all*100);
    $win_rates[$i][2]=(($first+$second+$third)/$all*100);

    $i++;
}

$year="$_POST[year_s]";
$i=0;
foreach ($win_rates as $win_rate){
    echo $year;
    echo "<br>";
    echo "単勝率:".$win_rates[$i][0]."%"."<br/>";
    echo "連体率:".$win_rates[$i][1]."%"."<br/>";
    echo "複勝率:".$win_rates[$i][2]."%"."<br/>";
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
    <!-- <table class="table4" border="1">
        <tr><th>年度</th><th>馬名</th><th>馬番</th><th>着順</th></tr>
        <?php foreach ($year_results[0] as $row) : ?>
            <tr><td><?php echo $row->年度 ?></td><td><?php echo $row->馬名 ?></td><td><?php echo $row->馬番 ?></td><td><?php echo $row->着順 ?></td></tr>
        <?php endforeach; ?>
    </table> -->
    <table class="table4" border="1">
        <tr><th>年度</th><th>馬名</th><th>馬番</th><th>着順</th></tr>
        <?php foreach ($j_results[0] as $row) : ?>
            <tr><td><?php echo $row->年度 ?></td><td><?php echo $row->馬名 ?></td><td><?php echo $row->馬番 ?></td><td><?php echo $row->着順 ?></td></tr>
        <?php endforeach; ?>
    </table>
</body>
</html>