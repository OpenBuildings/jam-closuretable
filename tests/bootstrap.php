<?php 

require_once __DIR__.'/../vendor/autoload.php';

Kohana::modules(array(
	'database'  => MODPATH.'database',
	'jam'       => __DIR__.'/../modules/jam',
	'jam-closuretable' => __DIR__.'/..',
));

function test_autoload($class)
{
	$file = str_replace('_', '/', $class);

	if ($file = Kohana::find_file('tests/classes', $file))
	{
		require_once $file;
	}
}

spl_autoload_register('test_autoload');

Kohana::$config
	->load('database')
		->set(Kohana::TESTING, array(
			'type'       => 'PDO',
			'connection' => array(
                'dsn' => 'mysql:host=localhost;dbname=test-jam-closuretable',
				'username'   => 'root',
				'password'   => '',
				'persistent' => TRUE,
			),
            'identifier' => '`',
			'table_prefix' => '',
			'charset'      => 'utf8',
			'caching'      => FALSE,
		));

Kohana::$environment = Kohana::TESTING;
