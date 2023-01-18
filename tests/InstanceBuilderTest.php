<?php

namespace ComputeEngine\Test;

use PHPUnit\Framework\TestCase;
use Dotenv\Dotenv;

use ComputeEngine\InstanceBuilder;

class InstanceBuilderTest extends TestCase{

    private $instanceBuilder;

    public function setUp(): void{
        $dotenv = Dotenv::createImmutable('/mnt/d/WebDevelopment/ComputeEngine/');
        $dotenv->load();

        $this->instanceBuilder = new InstanceBuilder('/mnt/d/WebDevelopment/ComputeEngine/private/deploymentImages', '/var/lib/libvirt/images');
    }

    public function tearDown(): void{
        $session = ssh2_connect('127.0.0.1', 22);
        ssh2_auth_password($session, $_ENV['SSHUSERNAME'], $_ENV['SSHPASSWORD']);

        ssh2_exec($session, 'rm -f /var/lib/libvirt/images/*-deployment-*');
        ssh2_exec($session, 'rm -f /var/lib/libvirt/images/testing.qcow2');
        ssh2_exec($session, 'virsh destroy newServer && virsh undefine newServer');
        ssh2_exec($session, 'rm -f /var/lib/libvirt/images/newServer.qcow2');
    }

    public function imageDataProvider(){
        return [
            ['centos', '7'],
            ['debian', '11']
        ];
    }

    /**
     * @covers downloadDeploymentImage
     * @dataProvider imageDataProvider
     */
    public function testDownloadDeploymentImage($os, $version){
        $this->instanceBuilder->setOS($os)
            ->setVersion($version);

        $this->assertEquals(InstanceBuilder::class, $this->instanceBuilder->downloadDeploymentImage()::class);
        $this->assertFileExists("/var/lib/libvirt/images/$os-$version-deployment-image.qcow2.zst");
    }

    /**
     * @covers downloadDeploymentImage
     */
    public function testDownloadDeploymentImageThrowsErrorIfImageDoesNotExist(){
        $this->expectErrorMessage('Deployment image does not exist');

        $this->instanceBuilder->setOS('randomos')
            ->setVersion('3erre')
            ->downloadDeploymentImage();
    }

    /**
     * @covers unpackDeploymentImage
     * @dataProvider imageDataProvider
     */
    public function testUnpackDeploymentImage($os, $version){
        $this->instanceBuilder->setOS($os)
            ->setVersion($version)
            ->downloadDeploymentImage();

        $this->assertEquals(InstanceBuilder::class, $this->instanceBuilder->unpackDeploymentImage()::class);
        $this->assertFileExists("/var/lib/libvirt/images/$os-$version-deployment-image.qcow2");
    }

    /**
     * @covers moveToFinalName
     */
    public function testMoveToFinalName(){
        $name = 'testing';

        $this->instanceBuilder->setOS('centos')
            ->setVersion('7')
            ->downloadDeploymentImage()
            ->unpackDeploymentImage();

        $this->assertEquals(InstanceBuilder::class, $this->instanceBuilder->moveToFinalName($name)::class);
        $this->assertFileExists("/var/lib/libvirt/images/$name.qcow2");
    }

    /**
     * @covers installServer
     */
    public function testInstallServer(){
        $os = 'centos';
        $version = '7';
        $name = 'newServer';

        $this->instanceBuilder->setOS($os)
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

        $this->assertEquals(InstanceBuilder::class, $this->instanceBuilder->installServer($options)::class);
        $this->assertFileExists("/var/lib/libvirt/images/$name.qcow2");
    }
}