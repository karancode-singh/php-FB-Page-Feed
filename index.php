<?php
    ini_set("display_errors",1);

    require_once('db.php');    // Database connection
    require_once( 'config.php'); // Config facebook app id and secret
    $token = $config['App_ID']."|".$config['App_Secret']; // Making app token by its id and secret
    $pageFBID = "126976547314225"; //CSI Page ID
    
    feedExtract($pageFBID,$token); // Obtain feed of the page.


function feedExtract($pageFBID)
{
    global $token, $connection;
    
    $query = "TRUNCATE `feed`"; // Query to clear old data
    mysqli_query($connection,$query); // Execute Query
    
    // Fetch page posts
    $response = file_get_contents_curl("https://graph.facebook.com/v2.6/$pageFBID/feed?fields=message,story,shares,likes.limit(1).summary(true)&access_token=".$token);
    
    // Decode json data into an array
    $get_data = json_decode($response,true);
    
    // Loop to extract data
    for($ic=0;$ic<count($get_data['data']);$ic++)
    {
        //Extract page postsdata into variables
        $story = array_key_exists('message', $get_data['data'][$ic]) ? $get_data['data'][$ic]['message'] : $get_data['data'][$ic]['story'];
        $likes = array_key_exists('likes', $get_data['data'][$ic]) ? $get_data['data'][$ic]['likes']['summary']['total_count'] : 0;
        $shares = array_key_exists('shares', $get_data['data'][$ic]) ? $get_data['data'][$ic]['shares']['count'] : 0;
        
        // Put data in sql query values
        $dataFeed = "(
        '".mysqli_real_escape_string($connection,$story)."',
        '".mysqli_real_escape_string($connection,$likes)."',
        '".mysqli_real_escape_string($connection,$shares)."')";
            
        //Execute query to enter data into the database
        mysqli_query($connection,"INSERT INTO `feed` (`post_description` ,  `no_of_likes` ,  `no_of_shares`) VALUES $dataFeed");
        
    }
    return  1;
}


//File get content with curl method 
function file_get_contents_curl($url)
{
  $curl = curl_init($url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
  $data = curl_exec($curl);
  curl_close($curl);
  return $data;
}

    $query = "SELECT * FROM `feed` ORDER BY no_of_likes desc, no_of_shares desc"; //Query to retrieve data from database
    $result = mysqli_query($connection,$query); // execute query
?>


<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        
        <title>Facebook Pages Feed</title>

        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
        <style type="text/css">
                @import url("http://fonts.googleapis.com/css?family=Lato:100,300,400,700,900,400italic");
                @import url("//netdna.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.css");
                body
                {
                    background-color: #406F5C;
                }
                h2
                {
                    font-weight: normal;
                    font-size: 32px;
                }
                .panel-heading
                {
                    font-size: 16px;
                    font-weight: bold;
                }
                .jumbotron
                {
                    background-color: #DFF0D8;
                }
                .element 
                {
                    position: relative;
                    top: 50%;
                    transform: translateY(-50%);
                    color: #567D7A;
                    
                }
        </style>
    </head>
    
<body>
    
    <header class="jumbotron container-fluid">
        <div class="container">
            <div class="row">
                <div class="col-xs-12 col-sm-5" align=center>
                    <img src="CSI%20Logo.png" alt="CSI NSIT"/>
                </div>
            
                <div class="col-xs-12 col-sm-6 col-sm-offset-1" style="height: 401px;" align=center>        
                    <h2 align=center class="element">Facebook page posts in order of most Likes and most Shares</h2>
                </div>
            </div>
    </header>
        
    <div class="container">
       
        <?php
        while($rowsFeed = mysqli_fetch_array($result))
        {
            ?>
            <div class="row">
                <div class="panel panel-success"> 
    
                    <div class="panel-heading">
                        Post number: <?php echo $rowsFeed['s.no'];?>
                        <div style="float: right;">
                            <span class="fa fa-thumbs-up"></span> <?php echo $rowsFeed['no_of_likes'];?>
                            &nbsp;&nbsp;
                            <span class="fa fa-share"></span> <?php echo $rowsFeed['no_of_shares'];?>
                        </div>
                    </div>

                    <div class="panel-body">
                        <p class="desc"><?php echo $rowsFeed['post_description'];?></p>
                    </div> 
                </div>
            </div>
            <?php
            
        }
        ?>
        
    </div>
    
</body>
</html>