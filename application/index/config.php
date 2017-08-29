<?php

return [
    'view_replace_str'       => [
        '__CSS__'        => '/static/index/css',
        '__JS__'         => '/static/index/js',
        '__IMG__'        => '/static/index/images',
        '__FONT__'        => '/static/index/fonts',
        '__I__'          => '/static/index/i',
        '__HTMLVENDOR__' =>  '/static/vendor',
    ],
    //分页配置
    'paginate'               => [
        'type'      => 'bootstrap',
        'var_page'  => 'page',
        'list_rows' => 20,
    ],

];
