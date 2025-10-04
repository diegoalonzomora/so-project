<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<?php
$conn = mysqli_connect(
    'localhost',
    'root',
    '',
    'cloudbeds'
);

if(isset($conn)){
    echo "DB is connected";
}

?>