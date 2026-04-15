<?php
/* header("Cache-Control: no-cache, no-store, must-revalidate"); //this code block is only necessary if ./  or ./?tags= wont update. not a local copy problem
header("X-LiteSpeed-Cache-Control: no-cache"); */
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>making it happen</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://fonts.googleapis.com/css?family=Inter' rel='stylesheet'>
    <link href='style.css' rel='stylesheet'>
</head>

<?php
//db variables
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "test_db";
$table = 'files';

//connecting to the db 
$db = new mysqli($servername, $username, $password, $dbname);
if ($db->connect_error){
    die("Connection failed: " . $db->connect_error);
}

//reloads the page (PRG)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'upload.php';
}
 
?>


<body>

    <div class="layout">
        <div class="tag-title">
            <h2>TAGS</h2>   
        </div>

        <div class="tag-list">
            <?php
            
            //use the result of the thumbnail display query to extract all relevant tags
            $dataMissingTags = filterShownThumbnails($db, $table);
            $findMissingTags = [];
            while($row = $dataMissingTags->fetch_assoc()){
                array_push($findMissingTags,"'".$row['modal']."'");
            }
            $relevantFiles = implode(',',$findMissingTags);

            //displays the relevant tags based on the relevant files
            if(!empty($relevantFiles)) {
                $relevantTags = $db->query("SELECT DISTINCT tag FROM $table WHERE modal in ($relevantFiles) ORDER BY tag") or die($db->error);
                tagUrlLogic('tag', $relevantTags);
            }
            

            ?>
        </div>

        <form class="form-box" method="post" enctype="multipart/form-data"> 
            <input type="file" name="file" id="uploadedVideo" accept="video/*" title="PLEASE ONLY 30MB, but 45MB works..."><br/>           <!--name can be anything-->
            <input class="upload-interaction" type="text" name="tags" placeholder='Tags (ex: zac,mh3u,urn)' title="COMMA SEPARATED WITH NO SPACES!!!!&#013;example:&#013;wizards,fall_guys,coca_cola"><br/>
            <input class="upload-interaction" type="password" name="password" placeholder='Password'><br/>
            <input type='hidden' name="thumbnail" id='uploadedThumbnail' style="display: none;"><br/>
            
            <canvas id="canvas" style="display: none;"></canvas>

            <button type="submit" value="Upload">UPLOAD</button>
        </form>

        <div class="mini-player">
            <video id="frameGrabPlayer"></video>
        </div>

        <main class="content">
            <div class="content-grid">
                <!-- <img id="imgTest"></img> -->
                <?php

                //query thumbnails based on tag selection
                $dataThumbnail = filterShownThumbnails($db, $table);
                while($row = $dataThumbnail->fetch_assoc()){    //display the thumbnails
                    echo "<div class='card'><img data-modal='{$row['modal']}' src='{$row['thumbnail']}'></div>";
                } 

                ?>
            </div>

            <div id="modal" class="modal">
                <span class="close"></span>
                <video id="modalPlayer" controls autoplay loop>
                    <source id="modalSource" src="" type="video/mp4">
                    Modal not working.
                </video>
            </div>
        </main>

    </div>

    <script> //learn more JS ts is tough
        const contentGrid = document.querySelector('.content-grid');
        const modal = document.getElementById('modal');
        const modalPlayer = document.getElementById('modalPlayer');
        const modalSource = document.getElementById('modalSource');
        const closeButton = document.querySelector('.close');

        contentGrid.addEventListener('click', event => {
            if (event.target.tagName === 'IMG') {
                const modalFile = event.target.getAttribute('data-modal');
                if (modalFile) {
                    modalSource.src = modalFile;
                    modalPlayer.load();
                    modal.style.display = 'flex';
                }
            }
        });

        closeButton.addEventListener('click', () => {
            modal.style.display = 'none';
            modalPlayer.pause();
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.style.display = 'none';
                modalPlayer.pause();
            }
        });

        const frameGrabPlayer = document.getElementById('frameGrabPlayer');
        const uploadedVideo = document.getElementById('uploadedVideo');
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d');
        //const img = document.getElementById('imgTest');
        const uploadedThumbnail = document.getElementById('uploadedThumbnail');

        uploadedVideo.addEventListener('change', (event)=>{
            if (uploadedVideo.files[0]) {
                const file = uploadedVideo.files[0];

                frameGrabPlayer.autoplay = true;
                frameGrabPlayer.muted = true;
                frameGrabPlayer.playsInline = true;
                frameGrabPlayer.src = URL.createObjectURL(file);
                frameGrabPlayer.controls = true;
            }

        });

        function grabFrame() {
            canvas.width = frameGrabPlayer.videoWidth;
            canvas.height = frameGrabPlayer.videoHeight;
            ctx.drawImage(frameGrabPlayer, 0, 0);

            const imgData = canvas.toDataURL('image/jpeg', 0.5);
            uploadedThumbnail.value = imgData;
            //img.src = imgData;
            frameGrabPlayer.pause(); //dont need to play the file in the background after grabbing the frame
        }


        //BIG THANKS TO PROFESSOR STEVE https://gist.github.com/prof3ssorSt3v3/efcf21c32b1d15e20fa48f57139776a2
        document.addEventListener('DOMContentLoaded', ()=>{
            frameGrabPlayer.addEventListener('canplay', (ev)=>{
                console.log('canplay', ev.target.videoWidth, ev.target.videoHeight);
                console.log(ev.target.clientWidth, ev.target.clientHeight);
                console.log(ev.target.currentSrc, ev.target.duration, ev.target.currentTime);
                grabFrame();
            });
            
            frameGrabPlayer.addEventListener('canplaythrough', (ev)=>{
                //this is our own autoplay
                console.log('Enough loaded to play through whole video');
                frameGrabPlayer.play();
            });
            
            frameGrabPlayer.addEventListener('load', (ev)=>{
                //video has loaded entirely
                console.log('video loaded');
            });
            
            frameGrabPlayer.addEventListener('error', (err)=>{
                console.log('Failed to load video', err.message);
            });
        });
            

    </script>

    <?php

    function tagUrlLogic($filterBy, $data){
        while($row = $data->fetch_assoc()){
            $currentFilters = [];
            if (!empty($_GET['tags']) ) {       //avoids undefined array warnings when tags DNE on a fresh instance ex ./?tags=
                $currentFilters = explode(',',$_GET['tags']);
            }
            $currentFilters = array_filter($currentFilters);  //removes the empty entry when all tags are disabled

            if(in_array($row[$filterBy],$currentFilters)) { //modify the href to remove this tag from the URL if it's already selected
                $updatedFilter = array_diff($currentFilters, [$row[$filterBy]]);
                $updatedFilter = implode(',',$updatedFilter);

                echo "<a class='selected-tag' href=\"?tags=$updatedFilter\">○ {$row[$filterBy]}</a></br>";
            }

            else { //modify the href to include this tag in the URL if it's not selected
                array_push($currentFilters, $row[$filterBy]);
                $updatedFilters = implode(',',$currentFilters);

                echo "<a href=\"?tags=$updatedFilters\">○ {$row[$filterBy]}</a></br>";
            }

        }

    }

    function filterShownThumbnails($db, $table) { //CONTAINS ALL THE RELEVANT THUMBNAILS
        if (isset($_GET['tags']) && !empty($_GET['tags'])){ //filter which videos are shown
            $currentTags = explode(',',$_GET['tags']);
            
            $currentTags = array_map(function($tag) {
                return "'" . $tag . "'";
            }, explode(',', $_GET['tags']));

            $sqlTags = implode(',',$currentTags);
            $totalTags = sizeof($currentTags); 

            $result = $db->query("SELECT thumbnail, modal FROM $table WHERE tag IN ($sqlTags) GROUP BY thumbnail HAVING COUNT(DISTINCT tag) = $totalTags") or die($db->error);         //LEARN THIS MORE IT DOESNT MAKE CLEAR SENSE
        }
        else { //ALL THUMBNAILS SHOWN BY DEFAULT
            $result = $db->query("SELECT DISTINCT thumbnail, modal FROM $table") or die($db->error);
        }
        return $result;
    }

    //EOF
    $db->close();
    
    ?>

    

</body>
</html>