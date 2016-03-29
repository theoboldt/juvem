<?php
use Symfony\Component\DependencyInjection\ContainerBuilder;

/** @var ContainerBuilder $container */
$container->setParameter('app.version', 'v1');


$fileRevision = function($path, $raw = false) use($container) {
	$rootDir = $container->getParameter('kernel.root_dir');
	$pathFull = $rootDir.'/../web/'.$path;

	if (is_file($pathFull) && is_readable($pathFull)) {
		return hash_file('sha256', $pathFull, $raw);
	} else {
		return 0;
	}
};

$container->setParameter('app.version.hash.js', $fileRevision('js/all.min.js'));
$container->setParameter('app.version.hash.css', $fileRevision('css/all.min.css'));

$container->setParameter('app.version.integrity.js', base64_encode($fileRevision('js/all.min.js', true)));
