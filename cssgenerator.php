<?php

// parcours du dossier choisi de manière itérative
function listDir($dir){
    $path_dir = opendir($dir);
    $array_png = array();
    while($file = readdir($path_dir)){
        if($file == "." || $file == ".."){
            continue;
        }
        // chemin complet vers le fichier / dossier
        $path_png = $dir . DIRECTORY_SEPARATOR . $file;
        if(preg_match("%\.png$%i", $path_png)){

            $array_png[] = $path_png;

        }
    }
    return $array_png;
}

//parcours du dossier choisi de manière récursive
function recursivelistDir($dir){
    // affiche les png dans un dossier
    $path_dir = opendir($dir);
    $array_png = array();
    while($file = readdir($path_dir)){
        if($file == "." || $file == ".."){
            continue;
        }

        $path_png = $dir . DIRECTORY_SEPARATOR . $file;
        if(preg_match("%\.png$%i", $path_png)){

            $array_png[] = $path_png;

        }else if(is_dir($path_png)){

            $array_png = array_merge($array_png, recursivelistDir($path_png));
        }
        
    }
    return $array_png;
}

// récupération des tailles des png pour le png final
function sizeSprite($array, $columns, $size_selected=null, $padding){
    $size = ["width" => 0, "height" => 0];
    $total_height = 0;
    $max_width = 0;
    $row_width = 0;
    $row_height = 0;
    foreach($array as $key => $image){
        if ($size_selected===null){
            $sizeimage = getimagesize($image);
        }else{
            $sizeimage = [$size_selected, $size_selected];
        }
        $row_width += $sizeimage[0];
        $row_height = max($row_height, $sizeimage[1]);
        $max_width = max($max_width, $row_width);
        if ($columns > 0 && ($key + 1) % $columns === 0 || $key === count($array) - 1){
            if ($key !== count($array)-1){
                $row_height += $padding;
            }
            $row_width = 0;
            $total_height += $row_height;
            $row_height = 0;
        }
        if ($columns === 0 && $key !== count($array)){
            $row_width += $padding;
        }
        if ($key < $columns && $columns > 1){
            $row_width += $padding;
        }
    }
    $size["height"] = $total_height;
    $size["width"] = $max_width;
    return $size;
}

// création d'un png vide avec les tailles récupérées précèdemment
function createPng($array_size, $sprite_name){
    $sprite = imagecreatetruecolor($array_size["width"], $array_size["height"]);
    $transparency = imagecolorallocatealpha($sprite, 0, 0, 0, 127);
    imagefill($sprite, 0, 0, $transparency);
    imagesavealpha($sprite, true);
    // imagecolortransparent($sprite, $transparency);
    imagepng($sprite, "$sprite_name.png");
    return $sprite;
}

function resizePng($size, $png, $png_width, $png_height){
    $imagecopy = imagecreatetruecolor($size, $size);
    $png = imagecreatefrompng($png);
    $transparency = imagecolorallocatealpha($imagecopy, 0, 0, 0, 127);
    imagefill($imagecopy, 0, 0, $transparency);
    imagesavealpha($imagecopy, true);
    imagecopyresampled($imagecopy, $png, 0, 0, 0, 0, $size, $size, $png_width, $png_height);
    return $imagecopy;
}

function catPng($sprite, $array_png, $sprite_name, $columns, $padding, $size_selected){
    $sprite_width = 0;
    $sprite_height = 0;
    $max_height = 0;
    foreach($array_png as $key => $png){
        $png_gd = imagecreatefrompng($png);
        $sizeimage = getimagesize($png);

        if ($size_selected !=null){
            $png_gd = resizePng($size_selected, $png, $sizeimage[0], $sizeimage[1]);
            $png_width = $size_selected;
            $png_height = $size_selected;
        }else{
            $png_width = $sizeimage[0];
            $png_height = $sizeimage[1];
        }
        $max_height = max($max_height, $png_height);
        
        if ($columns > 0 && $key > 0 && $key % $columns === 0){
            $sprite_height += $max_height;
            $max_height = 0;
            $sprite_width = 0;
            if($key < count($array_png)){
            $sprite_height += $padding;
            }
        }if ( $columns > 1 && (($key + 1) % $columns === 0 || $key % $columns !== 0)){
            $sprite_width += $padding;
        }else if ($columns === 0 && $key > 0 && $key < count($array_png)){
            $sprite_width += $padding;
        }
        imagecopy($sprite, $png_gd, $sprite_width, $sprite_height, 0, 0, $png_width, $png_height );
        $sprite_width += $png_width; // ajouter la largeur de l'image pour changer de position
    }
    imagepng($sprite, "$sprite_name.png");
}


function sizePng($array_png){
    $array_png_size = array();
    foreach($array_png as $png){
        $array_png_size[$png]["width"] = getimagesize($png)[0];
        $array_png_size[$png]["height"] = getimagesize($png)[1];
    }
    return $array_png_size;
}

function writeHTML($array_png, $css_filename){
    $html_file = fopen("index.html", "w");
    $html_content = "<!DOCTYPE html>".
        "<html lang='en'>".
        "<head>".
            "<meta charset='UTF-8'>".
            "<meta name='viewport' content='width=device-width, initial-scale=1.0'>".
            "<link rel='stylesheet' href='$css_filename.css'>".
            "<title>Document</title>".
        "</head>".
    "<body>\n";
    foreach($array_png as $png){
        $png_name = pathinfo($png)['basename'];
        $png_name = str_replace(".png", "", $png_name);
        $html_content .= "\t<div class='" . $png_name . "'></div>\n";
    }
    $html_content .= "</body>";
    fwrite($html_file, $html_content);
}

function writeCSS($array_png, $array_png_size, $css_filename, $columns, $padding, $size_selected){
    $css_file = fopen("$css_filename.css", "w");
    if($columns === 0){
        $columns = count($array_png);
    }
    $css_content = "body{\n".
    "\n\width : fit-content;\n".
    "\n\tdisplay : grid;\n".
    "\tgrid-gap : $padding"."px;\n".
    "\tpadding : 0"."px;\n".
    "\tmargin : 0"."px;\n".
    "\tgrid-template-columns : repeat($columns, 1fr);\n".
    "}\n";
    $row_width = 0;
    $max_height = 0;
    $row = 0;
    foreach($array_png as $png){
        
        $png_width = $size_selected === null ? $array_png_size[$png]["width"] : $size_selected;
        $row_width += $png_width;
        $png_height = $size_selected === null ? $array_png_size[$png]["height"] : $size_selected;
        $background_size = $size_selected === null ? "" : "\n\tbackground-size: ". $size_selected ."px " . $size_selected ."px;\n";
        $png_name = pathinfo($png)['basename'];
        $png_name = str_replace(".png", "", $png_name);
        if ($png_height > $max_height && $row === 1){
            $max_height = $png_height;
        }
        $css_content.= ".$png_name{\n".
        "\twidth : " . $png_width . "px;\n".
        "\tpadding : 0"."px;\n".
        "\tmargin : 0"."px;\n".
        "\theight : " . $png_height . "px;\n".
        "\tbackground : url($png)" . "no-repeat ;\n".
        $background_size .
        "}\n";
        
    }
    fwrite($css_file, $css_content);
}

// function for final command line call
function call_cssgenerator($argv){
    // recursive 
    if (in_array("-r", $argv) || in_array("--recursive", $argv)){
        $array_png = recursivelistDir($argv[count($argv)-1]);
    }else{
        $array_png = listDir($argv[count($argv)-1]);
    }
    
    $size = null;
    for($i=1;$i<count($argv);$i++){
        if(in_array("-p", $argv) || in_array("--padding", $argv)){
            if($argv[$i]=="-p" || $argv[$i]=="--padding"){
                $padding = $argv[$i+1];
            }
        }else{
            $padding = 0;
        }
        
        if(in_array("-c", $argv) || in_array("--columns_number", $argv)){
            if($argv[$i]=="-c" || $argv[$i]=="--columns_number"){
                $columns = $argv[$i+1];
            }
        }else{
            $columns = 0;
        }
        // if -i, get the string for the sprite.png name
        if(in_array("-i", $argv) || in_array("--output-image", $argv)){
            if ($argv[$i]=="-i" || $argv[$i]=="--output-image"){
                $sprite_name = $argv[$i+1];
            }
        }else{
            $sprite_name = "sprite";
        }
        
        // if -o, get the size for each png to cat in final png
        if (in_array("-o", $argv) || in_array("--override-size", $argv)){
            if ($argv[$i]=="-o" || $argv[$i]=="--override-size"){
                $size = $argv[$i+1];
            }
        }
        // if -s , get the string for the style.css name
        if(in_array("-s", $argv) || in_array("--output-style", $argv)){
            if($argv[$i]=="-s" || $argv[$i]== "--output-style" ){
                $style_name = $argv[$i+1];
            }
        }else{
            $style_name = "style";
        }
        
    }
    print_r($columns);
    $array_size = sizeSprite($array_png, $columns, $size, $padding);
    $sprite = createPng($array_size, $sprite_name);
    catPng($sprite, $array_png, $sprite_name, $columns, $padding, $size);
    $array_png_size = sizePng($array_png, $columns, $size);
    writeCSS($array_png, $array_png_size, $style_name, $columns, $padding, $size);
    writeHTML($array_png, $style_name);
}

call_cssgenerator($argv);
var_dump($argv);
