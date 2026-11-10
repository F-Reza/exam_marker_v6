<?php
// Source releases omit generated/private data. Create only empty runtime folders.
$root=dirname(__DIR__);
foreach(['bootstrap/cache','storage/app/private','storage/app/public','storage/framework/cache/data','storage/framework/sessions','storage/framework/views','storage/logs'] as $directory){
    $path=$root.'/'.$directory;
    if(!is_dir($path)&&!mkdir($path,0770,true)&&!is_dir($path))throw new RuntimeException('Cannot create runtime directory: '.$directory);
}
