<?php

namespace Xpressengine\Plugins\Comment\Console\Commands;

use App\Console\Commands\SkinMake;
use ArrayAccess;
use Exception;

/**
 * Class MakeCommentSkin
 *
 * @package Xpressengine\Plugins\Comment\Console\Commands
 */
class MakeCommentSkin extends SkinMake
{
    protected $signature = 'make:comment_skin
                        {plugin : The plugin where the skin will be located}
                        {name : The name of skin to create}

                        {--id= : The identifier of skin. default "<plugin>@<name>"}
                        {--path= : The path of skin. Enter the path to under the plugin. ex) SomeDir/SkinDir}
                        {--class= : The class name of skin. default "<name>Skin"}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new board skin';

    /**
     * get title
     *
     * @return string
     */
    protected function getTitleInput()
    {
        return $this->option('title') ?: studly_case($this->getComponentName()) . ' Comment skin';
    }

    /**
     * get skin target
     *
     * @return string
     */
    protected function getSkinTarget()
    {
        return 'comment';
    }

    /**
     * makeUsable
     *
     * @param ArrayAccess|array $attr attributes
     * @return void
     * @throws Exception
     */
    protected function makeUsable($attr)
    {
        $plugin = $attr['plugin'];
        $path = $plugin->getPath($attr['path']);

        $this->makeSkinClass($attr);

//        rename($path . '/info.stub', $path . '/info.php');

        $viewFileNames = [
            'certify',
            'container',
            'create',
            'edit',
            'items',
            'reply',
            'voted',
            'votedModal'
        ];

        $replacePath = $plugin->getId() . '/' . $attr['path'];
        foreach ($viewFileNames as $fileName) {
            $stub = sprintf('%s/views/%s.blade.stub', $path, $fileName);
            if (file_exists($stub)) {
                $code = $this->files->get($stub);
                $code = str_replace('DummyPath', $replacePath, $code);
                $this->files->put($stub, $code);

                $rename = sprintf('%s/views/%s.blade.php', $path, $fileName);
                rename($stub, $rename);
            }
        }
    }

    /**
     * get stub path
     *
     * @return string
     */
    protected function getStubPath()
    {
        return __DIR__ . '/../stubs/commentSkin';
    }
}
