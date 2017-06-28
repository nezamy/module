<?php
namespace System;
class Modules
{
    public $path;
    public $core = [];
    private $modules = [];
    private $current;
    private static $instance;


    public static function instance()
    {
        if (null === static::$instance) {
            static::$instance = new static;
        }
        return static::$instance;
    }

    public function __construct() {
        $this->path = app()->config->get('app.modules');
        $this->core = app()->config->get('autoload.modules');
    }

    // public function getFileDocBlock($file)
    // {
    //     $docComments = array_filter(
    //         token_get_all( file_get_contents( $file ) ), function($entry) {
    //             return $entry[0] == T_DOC_COMMENT;
    //         }
    //     );
    //     $fileDocComment = array_shift( $docComments );
    //     return $fileDocComment[1];
    // }

    //need to cach
    public function update()
    {
        $modules = [];
        $modules_status = df('osc.modules');
        $newModules = false;

        foreach(glob($this->path.DS.'*', GLOB_ONLYDIR) as $dir)
        {
            if(file_exists($dir.DS.'module.php'))
            {
                $name = basename($dir);
                if(in_array($name, $this->core)) {
                    $status = 'on';
                    if(array_key_exists($name, $modules_status) && $status != $modules_status[$name]['active']) {
                        $newModules = true;
                    }
                } elseif (array_key_exists($name, $modules_status)) {
                    $status = $modules_status[$name]['active'];
                } else {
                    $status = 'off';
                    $newModules = true;
                }

                $modules[$name] = [
                    'id' => $name,
                    'name' => ucfirst($name),
                    'description' => '',
                    'active' => $status
                ];

                if(in_array($name, $this->core)){
                    $modules[$name]['core'] = true;
                }
            }
        }

        if($newModules) {
            df('osc.modules', $modules);
        }

        $this->modules = $modules;
    }

    public function info(array $info) {
        isset($info['name']) && $this->modules [$this->current]['name'] = $info['name'];
        isset($info['description']) && $this->modules [$this->current]['description'] = $info['description'];
    }

    public function list() {
        // $this->update();
        return $this->modules;
    }

    public function autoload()
    {
        $this->modules = df('osc.modules');
        $app = OS::instance();

        foreach ($this->core as $module) {
            $this->current = $module;
            require_once $this->path . DS . $module . DS . 'module.php';
        }

        foreach ($this->modules as $key => $module)
        {
        	if($module['active'] == 'on') {
        		if ( is_file($mod = $this->path . DS . $module['id'] . DS . 'module.php') ) {
                    $this->current = $key;
        			include_once($mod);
        		} else {
                    pre('error load module '. $key, 6);
        		}
        	}
        }
    }
}
