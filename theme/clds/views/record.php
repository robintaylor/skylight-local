<?php

$subject_field = $this->skylight_utilities->getField("Subject");
$type_field = $this->skylight_utilities->getField("Type");
$bitstream_field = $this->skylight_utilities->getField("Bitstream");
$thumbnail_field = $this->skylight_utilities->getField("Thumbnail");
$parent_collection_field = $this->skylight_utilities->getField("Parent Collection");
$child_collection_field = $this->skylight_utilities->getField("Sub Collections");
$handle_prefix = $this->config->item('skylight_handle_prefix');
$filters = array_keys($this->config->item("skylight_filters"));


$type = 'Unknown';

if(isset($solr[$type_field])) {
    $type = "media-" . strtolower(str_replace(' ','-',$solr[$type_field][0]));
}


?>


<h1 class="itemtitle"><?php echo $record_title ?></h1>
<div class="tags">
    <?php

    if (isset($solr[$subject_field])) {
        foreach($solr[$subject_field] as $subject) {

            $orig_filter = urlencode($subject);

            $lower_orig_filter = strtolower($subject);
            $lower_orig_filter = urlencode($lower_orig_filter);

            echo '<a class="subject" href="./search/*:*/Subject:%22'.$lower_orig_filter.'%7C%7C%7C'.$orig_filter.'%22">'.$subject.'</a>';

        }
    }

    ?>
</div>

<div class="content">

    <?php
    $abstract_field = $this->skylight_utilities->getField("Abstract");
    if(isset($solr[$abstract_field])) {
        ?> <h3>Abstract</h3> <?php
        foreach($solr[$abstract_field] as $abstract) {
            echo '<p>'.$abstract.'</p>';
        }
    }
    ?>

    <table>
        <tbody>

        <?php foreach($recorddisplay as $key) {
            $element = $this->skylight_utilities->getField($key);
            if(isset($solr[$element])) {

                echo '<tr><th>'.$key.'</th><td>';
                foreach($solr[$element] as $index => $metadatavalue) {
                    // if it's a facet search
                    // make it a clickable search link
                    if(in_array($key, $filters)) {

                        $orig_filter = urlencode($metadatavalue);
                        $lower_orig_filter = strtolower($metadatavalue);
                        $lower_orig_filter = urlencode($lower_orig_filter);

                        echo '<a href="./search/*:*/' . $key . ':%22'.$lower_orig_filter.'%7C%7C%7C'.$orig_filter.'%22">'.$metadatavalue.'</a>';
                    }
                    else {
                        echo $metadatavalue;
                    }
                    if($index < sizeof($solr[$element]) - 1) {
                        echo '; ';
                    }
                }
                echo '</td></tr>';
            }

        }

        if(isset($solr[$parent_collection_field])) {
            echo '<tr><th>Parent Collection</th><td>';
            foreach($solr[$parent_collection_field] as $parent) {
                $find   = 'http://hdl.handle.net';
                $pos = strpos($parent, $find);

                if ($pos !== false)
                {

                    $parents= explode("|", $parent);

                    //todo move into config
                    $parent_link = str_replace("http://hdl.handle.net/". $handle_prefix."/", "./record/",$parents[0]);

                    $parent_name = (isset($parents[1]) ? $parents[1] : "Parent Collection");

                    echo '<a href="'.$parent_link.'">'.$parent_name.'</a>';


                }
                else{
                    echo $parent;
                }
                if($index < sizeof($solr[$parent_collection_field]) - 1) {
                    echo '; ';
                }


            }
            echo '</td></tr>';
        }
        if(isset($solr[$child_collection_field])) {
            echo '<tr><th>Sub Collections</th><td>';
            foreach($solr[$child_collection_field] as $child) {
                $find   = 'http://hdl.handle.net';
                $pos = strpos($child, $find);

                if ($pos !== false)
                {

                    $children= explode("|", $child);
                    //todo move into config
                    $link = str_replace("http://hdl.handle.net/". $handle_prefix."/", "./record/",$children[0]);
                    $name = $children[1];

                    echo '<a href="'.$link.'">'.$name.'</a>';

                }
                else{
                    echo $child;
                }
                if($index < sizeof($solr[$child_collection_field]) - 1) {
                    echo '; ';
                }


            }
            echo '</td></tr>';
        }

        ?>
        </tbody>
    </table>

    <?php
    if(isset($solr[$bitstream_field]) && $link_bitstream) {
    ?><div class="record_bitstreams"><?php
    //SR JIRA001-665 sort bitstreams by sequence to ensure they show in correct order
    $bitstream_array = array();


    foreach ($solr[$bitstream_field] as $bitstream_for_array)
    {
        $b_segments = explode("##", $bitstream_for_array);
        $b_seq = $b_segments[4];
        $bitstream_array[$b_seq] = $bitstream_for_array;
    }

    ksort($bitstream_array);




        $numThumbnails = 0;
        $mainImage = false;
        $videoFile = false;
        $audioFile = false;
        $audioLink = "";
        $videoLink = "";
        $b_seq =  "";

        //SR JIRA001-665 sort bitstreams by sequence to ensure they show in correct order
        //foreach($solr[$bitstream_field] as $bitstream) {
        foreach($bitstream_array as $bitstream) {

            $b_segments = explode("##", $bitstream);
            $b_filename = $b_segments[1];
            $b_handle = $b_segments[3];
            $b_seq = $b_segments[4];
            $b_handle_id = preg_replace('/^.*\//', '',$b_handle);
            $b_uri = './record/'.$b_handle_id.'/'.$b_seq.'/'.$b_filename;

            if (strpos($b_uri, ".jpg") > 0)
            {
                // is there a main image
                if (!$mainImage) {

                    $bitstreamLink = '<div class="main-image">';

                    $bitstreamLink .= '<a title = "' . $record_title . '" class="fancybox" rel="group" href="' . $b_uri . '"> ';
                    $bitstreamLink .= '<img class="record-main-image" src = "'. $b_uri .'">';
                    $bitstreamLink .= '</a>';

                    $bitstreamLink .= '</div>';

                    $mainImage = true;

                }
                else {

                    $t_uri = $b_uri . '.jpg';

                    $thumbnailLink[$numThumbnails] = '<div class="thumbnail-tile';
                    if($numThumbnails % 4 === 0) {
                        $thumbnailLink[$numThumbnails] .= ' first';
                    }
                    $thumbnailLink[$numThumbnails] .= '"><a title = "' . $record_title . '" class="fancybox" rel="group" href="' . $t_uri . '"> ';
                    $thumbnailLink[$numThumbnails] .= '<img src = "'.$t_uri.'" class="record-thumbnail" title="'. $record_title .'" /></a></div>';

                    $numThumbnails++;

                }

            }
            else if (strpos($b_uri, ".mp3") > 0) {

                $audioLink .= '<script src="http://api.html5media.info/1.1.6/html5media.min.js"></script>';
                $audioLink .= '<audio src="'.$b_uri.'" controls preload></audio>';

                $audioFile = true;
            }


            else if (strpos($b_uri, ".mp4") > 0)
            {
                $videoLink .= '<script src="http://api.html5media.info/1.1.6/html5media.min.js"></script>';
                $videoLink .= '<video width="320" height="200" controls> <source src="'.$b_uri.'" type="video/mp4">Sorry, it does not work</video>';

                $videoFile = true;
            }

            ?>
        <?php
        }

        if($mainImage) {

            echo $bitstreamLink;
            echo '<div class="clearfix"></div>';
        }

        $i = 0;
        $newStrip = false;
        if($numThumbnails > 0) {

            echo '<div class="thumbnail-strip">';

            foreach($thumbnailLink as $thumb) {

                if($newStrip)
                {

                    echo '</div><div class="clearfix"></div>';
                    echo '<div class="thumbnail-strip">';
                    echo $thumb;
                    $newStrip = false;
                }
                else {

                    echo $thumb;
                }

                $i++;

                // if we're starting a new thumbnail strip
                if($i % 4 === 0) {
                    $newStrip = true;
                }
            }

            echo '</div><div class="clearfix"></div>';
        }

        if($audioFile) {


            echo '<br>.<br>'.$audioLink;
        }

        if($videoFile) {

            echo '<br>.<br>'.$videoLink;
        }

        echo '</div><div class="clearfix"></div>';

        }

        echo '</div>';
        ?>
