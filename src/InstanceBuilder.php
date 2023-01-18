<?php

namespace ComputeEngine;

class InstanceBuilder{
    private $deploymentImageLocation;
    private $installLocation;

    private $os;
    private $version;

    private $deploymentImageExtension = 'deployment-image.qcow2.zst';

    private $sshSession;

    public function __construct($deploymentLocation, $installLocation){
        $this->deploymentImageLocation = $deploymentLocation;
        $this->installLocation = $installLocation;

        $this->sshSession = ssh2_connect('127.0.0.1', 22);
        ssh2_auth_password($this->sshSession, $_ENV['SSHUSERNAME'], $_ENV['SSHPASSWORD']);
    }

    public function setOS($os): self{
        $this->os = $os;

        return $this;
    }

    public function setVersion($version): self{
        $this->version = $version;

        return $this;
    }

    public function downloadDeploymentImage(): self{
        if(!file_exists("{$this->deploymentImageLocation}/{$this->os}-{$this->version}-{$this->deploymentImageExtension}")){
            throw new \Exception('Deployment image does not exist');
        }

        if(file_exists("{$this->installLocation}/{$this->os}-{$this->version}-{$this->deploymentImageExtension}")){
            return $this;
        }

        ssh2_exec($this->sshSession, "cp {$this->deploymentImageLocation}/{$this->os}-{$this->version}-{$this->deploymentImageExtension} {$this->installLocation}/{$this->os}-{$this->version}-{$this->deploymentImageExtension}");
        
        return $this;
    }

    public function unpackDeploymentImage(): self{
        if(file_exists("{$this->installLocation}/{$this->os}-{$this->version}-deployment-image.qcow2")){
            return $this;
        }

        ssh2_exec($this->sshSession, "cd {$this->installLocation} && zstd -d -T0 {$this->installLocation}/{$this->os}-{$this->version}-{$this->deploymentImageExtension}");

        return $this;
    }

    public function moveToFinalName($name): self{
        ssh2_exec($this->sshSession, "mv {$this->installLocation}/{$this->os}-{$this->version}-{$this->deploymentImageExtension} {$this->installLocation}/$name.qcow2");

        return $this;
    }

    public function installServer($options): self{
        ssh2_exec($this->sshSession, "virt-install --name {$options['name']} --memory {$options['memory']} --vcpus {$options['vcpus']} --disk {$options['disk']} --import --os-variant generic --noautoconsole");

        return $this;
    }

}