<?php
if($_POST['tuka']=="1ban"){
echo "1番が選択されました。";
}elseif($_POST['tuka']=="2ban"){
echo "2番が選択されました。";
}elseif($_POST['tuka']=="3ban"){
echo "3番が選択されました。";
}elseif($_POST['tuka']=="4ban"){
echo "4番が選択されました。";
}else{
echo "番号を選択してください。";
}
echo "<br>更新：".date("Y/m/d H:i:s");
?>

<body>
    <p>test1</p>
</body>