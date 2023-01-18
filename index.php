<?php

require_once('vendor/autoload.php');

use Dotenv\Dotenv;

use ComputeEngine\Helpers;
use ComputeEngine\InstanceBuilder;

$dotenv = Dotenv::createImmutable('/mnt/d/WebDevelopment/ComputeEngine/');
$dotenv->load();

$instance = new InstanceBuilder('/mnt/d/WebDevelopment/ComputeEngine/private/deploymentImages', '/var/lib/libvirt/images');

$bench = new Ubench;

$os = 'centos';
        $version = '7';
        $name = 'newServer';

        $instance->setOS($os)
            ->setVersion($version)
            ->downloadDeploymentImage()
            ->unpackDeploymentImage()
            ->moveToFinalName($name);

        $options = [
            'name' => $name,
            'memory' => '2048',
            'vcpus' => '2',
            'disk' => "/var/lib/libvirt/images/$name.qcow2"
        ];

$bench->start();

$instance->installServer($options);

$bench->end();

Helpers::printBenchmarkStats($bench);