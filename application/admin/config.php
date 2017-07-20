<?php 

return [
	'view_replace_str'       => [
		'__CSS__'    => '/static/admin/css',
        '__JS__'     => '/static/admin/js',
        '__IMG__'    => '/static/admin/images',
        '__I__'      => '/static/admin/i'
	],
	//分页配置
    'paginate'               => [
        'type'      => 'bootstrap',
        'var_page'  => 'page',
        'list_rows' => 20,
    ],
    
];