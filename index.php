<!DOCTYPE html>
<html lang="en">

<head>

<!--

8888888b.                    d8b                                 888
888  "Y88b                   Y8P                                 888
888    888                                                       888
888    888  .d88b.  .d8888b  888  .d88b.  88888b.   .d88b.   .d88888
888    888 d8P  Y8b 88K      888 d88P"88b 888 "88b d8P  Y8b d88" 888
888    888 88888888 "Y8888b. 888 888  888 888  888 88888888 888  888
888  .d88P Y8b.          X88 888 Y88b 888 888  888 Y8b.     Y88b 888
8888888P"   "Y8888   88888P' 888  "Y88888 888  888  "Y8888   "Y88888
                                      888
                                 Y8b d88P
                                  "Y88P"

888                    888       888          888      888
888                    888   o   888          888      888
888                    888  d8b  888          888      888
88888b.  888  888      888 d888b 888  8888b.  888  .d88888  .d88b.
888 "88b 888  888      888d88888b888     "88b 888 d88" 888 d88""88b
888  888 888  888      88888P Y88888 .d888888 888 888  888 888  888
888 d88P Y88b 888      8888P   Y8888 888  888 888 Y88b 888 Y88..88P
88888P"   "Y88888      888P     Y888 "Y888888 888  "Y88888  "Y88P"
              888
         Y8b d88P
          "Y88P"

Welcome to the Matrix, well, not quite, but I'm sure over 95% of
internet users don't trawl through website source code, especially
not a website I wrote! Hopefully you find something interesting
here, if not here's a joke for your troubles:

There's two fish in a tank, and one says "How do you drive this thing?"

-ifor
@iforwms

-->

   <meta charset="utf-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>unst.uk</title>
   </head>
   <style type="text/css">
      .thumbnail {
         width: 200px;
         height: 200px;
         overflow: hidden;
         display: inline-block;
         margin: 10px;
      }
      .song {
         display: inline-block;
         background-color: red;
         padding: 10px;
         margin: 10px;
      }
      .more-songs {
         display: none;
      }
      .show-songs:checked + .more-songs {
         display: block;
      }
   </style>
<body>

<?php

if(isset($_POST['keywords'])){

   // Define keywords
   $keywords = explode(' ', $_POST['keywords']);
   if(!isset($keywords[1])) {
      $keywords[1] = '';
   }


   // Set YQL path
   $root = "https://query.yahooapis.com/v1/public/yql";


   // Build query
   $query  = 'SELECT * FROM lastfm.track.search WHERE limit="100" AND page="0" AND api_key="9fdc838fec183c66ff1be03586d6040a" AND track="'.$keywords[0].' '.$keywords[1].'";';
   $query .= 'SELECT * FROM flickr.photos.search WHERE text="'.$keywords[0].' '.$keywords[1].'" AND api_key="47e7c04139b2db4265e0009ba196d7d4" LIMIT 10;';
   $query .= 'SELECT * FROM deviantart.search WHERE query="'.$keywords[0].' '.$keywords[1].'" LIMIT 10;';
   $query = "SELECT * FROM query.multi WHERE queries='".$query."'";


   // Build URL
   $url = $root . '?q=' . rawurlencode($query) . "&format=json&diagnostics=true&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys";
   $url = str_replace('%2A', '*', $url);
   $url = str_replace('%27', "'", $url);


   // Initiate CURL request
   $ch = curl_init();
   curl_setopt($ch, CURLOPT_URL,$url);
   curl_setopt($ch, CURLOPT_HEADER, 0);
   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
   $output = curl_exec($ch);


   // PArse results
   $data =  json_decode($output);
   $results = $data->query->results->results;


   // DEBUG
   // echo '<pre>';
   // print_r($results);
   // die();
?>

<!-- Display results -->
<h1>Results for <?= $keywords[0].' '.$keywords[1]; ?></h1>
<h4><a href="index.php">Search again</a></h4>

<?php

   // Show related song titles
   echo '<section><h3>Song Titles</h3>'; // <small>('.$results[0]->lfm->results->totalResults.' hits)</small>
   if($results[0]->lfm->results->totalResults > 0) {
      $songs = $results[0]->lfm->results->trackmatches->track;

      if(isset($keywords[1])) {
         $pattern = "/(\b$keywords[0]\b.+\b$keywords[1]\b|\b$keywords[1]\b.+\b$keywords[0]\b)/i";
      }
      else {
         $pattern = "/(\b$keywords[0]\b)/i";
      }

      $i = 0;
      $closeDiv = '';
      foreach ($songs as $song){
         preg_match($pattern, $song->name, $matches);
         if(count($songs > 5)){
            if($i == 5){
               echo '<div>More songs: <input type="checkbox" class="show-songs"><div class="more-songs">';
               $closeDiv = '</div></div>';
               $i++;
            }
         }
         if(!empty($matches)){
            echo "<p class='song'><a href='$song->url' target='_blank'>$song->name</a> by $song->artist</p>";
            $i++;
         }
      }
      echo $closeDiv.'</section>';
   }
   else {
      // echo '<section><h3>Song Titles</h3>';
      echo '<p>Nothing, nada, zero, zilch. New keywords please.</p></section>';
   }


   // Show related images - flickr
   echo '<section><h3>Flickr</h3>';
   if(isset($results[1]->photo)){
      $photos = $results[1]->photo;
      $i = 0;
      foreach ($photos as $image){
         echo "<div class='thumbnail'><img src='https://farm".$image->farm.".staticflickr.com/".$image->server."/".$image->id."_".$image->secret.".jpg'></div>";
         $i++;
         if($i == 5) break;
      }
   }
   else {
      echo '<p>Consider yourself stuk. Try some other keywords.</p>';
   }
   echo '</section>';

   // Show related images - deviantart
   echo '<section><h3>Devianart</h3>';
   if(isset($results[2]->root->retval)){
      $photos = $results[2]->root->retval;
      $i = 0;
      foreach ($photos as $image){
         $imageInfo = $image->item->thumbnail;
         $imageUrl = $imageInfo[0]->url;
         echo "<div class='thumbnail'><img src='$imageUrl'></div>";
         $i++;
         if($i == 5) break;
      }
   }
   else {
      echo '<p>Consider yourself stuk. Try some other keywords.</p>';
   }
   echo '</section>';


}
else {
?>
   <h1>Enter some words</h1>
   <form method="post">
      <label for="keywords" class="sr-only">Enter keywords (max. 2): </label>
      <input type="text" name="keywords" id="keywords">
      <input type="submit" value="Unstuk me">
   </form>

<?php
}
?>
</body>
</html>
