<?php

if($_POST['race_name']=="日本ダービー"){
    echo "日本ダービーが選択されました。";
}elseif($_POST['race_name']=="安田記念"){
    echo "安田記念が選択されました。";
}else{
    echo "番号を選択してください。";
}

echo "<br>";

if($_POST['year']=="2020"){
    echo "2020が選択されました。";
}elseif($_POST['year']=="2019"){
    echo "2019が選択されました。";
}else{
    echo "番号を選択してください。";
} 

echo "<br>更新：".date("Y/m/d H:i:s");
echo "<br>"."<br>";

require_once( dirname( __FILE__ ) . '/wp-load.php' );

global $wpdb;
$rows = $wpdb->get_results("SELECT * FROM test.wp_products");
foreach ($rows as $row)
{
    echo $row->id.",".$row->name.",".$row->price;
    echo "<br>";
}
?>

<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Hello!</title>
    <link rel="stylesheet" href="test1.css">
</head>
<body>
    <h1>Hello!</h1>
    <p>テーブルを表示します。</p>
    <table class="table4" border="1">
        <tr><th>レース名</th><th>年度</th></tr>
        <tr><td><?php echo $_POST['race_name'] ?></td><td><?php echo $_POST['year'] ?></td></tr>
    </table>
</body>
</html>
