<?php

$modalDir = "./modals/";
$passMatch = "havefun"; //wouldnt you like to know the password :^)
$passCheck = $_POST['password'] == $passMatch;        //POST is for non files, FILES is for files, and GET is related to URLs
$user = null; //$_POST['user'];
$thumbnailB64 = $_POST['thumbnail'];
$tags = explode(',',$_POST['tags']);
//array_push($tags, $user);

//check for successful upload
if(isset($_FILES['file']) && $_FILES['file']['error'] == 0 && $passCheck) {
    $file = $_FILES["file"]["name"];
    $fileName = pathinfo($file, PATHINFO_FILENAME);
    $fileExtension = pathinfo($file, PATHINFO_EXTENSION); 
    $uniqueName = $fileName . "_" . uniqid() . "." . $fileExtension; //prevent dupes

    $modalPath = $modalDir.$uniqueName;

    //move file to the target path
    if (move_uploaded_file($_FILES["file"]["tmp_name"], $modalPath)) { //uploaded files are moved to a temp directory, where they have a temp_name, so make sure to move the temp named file first 
        foreach($tags as $tag) {
            $tag = strtolower($tag);
            $sql="INSERT INTO files (filename, thumbnail, user, tag, modal) VALUES ('$uniqueName','$thumbnailB64','$user','$tag','$modalPath')";
            if($db->query($sql) == true){
                if($tag === array_key_last($tags)){
                    echo "File uploaded and saved to DB";
                }
            }
            else{
                echo "Error: ".$sql." Error Details: ".$conn->error;
            }
        }

    }
    else{
        echo "Error moving the file";
    }
}
else {
    echo "File upload unsuccessful";
}


header("Location: /");
exit();

?>

