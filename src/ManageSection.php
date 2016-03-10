<?php
namespace Xpressengine\Plugins\Comment;

use View;
use App\Sections\DynamicFieldSection;
use App\Sections\ToggleMenuSection;
use App\Sections\SkinSection;
use Xpressengine\User\Models\UserGroup;

class ManageSection
{
    /**
     * 관리자 댓글 설정 영역 제공
     * 처리는 controller 에서
     *
     * @param string $targetInstanceId
     * @return \Illuminate\View\View
     */
    public function setting($targetInstanceId)
    {
        $plugin = app('xe.plugin.comment');
        /** @var Handler $handler */
        $handler = $plugin->getHandler();

        $instanceId = $handler->getInstanceId($targetInstanceId);
        $config = $handler->getConfig($instanceId);

        $permission = $handler->getPermission($instanceId);

        $mode = function ($action) use ($permission) {
            return $permission->pure($action) ? 'manual' : 'inherit';
        };

        $allGroup = UserGroup::get();
        $permArgs = [
            'create' => [
                'mode' => $mode('create'),
                'grant' => $permission['create'],
                'title' => 'create',
                'groups' => $allGroup,
            ],
            'download' => [
                'mode' => $mode('download'),
                'grant' => $permission['download'],
                'title' => 'download',
                'groups' => $allGroup,
            ]
        ];

        $skinSection = (new SkinSection())->setting($plugin->getId(), $instanceId);

        $model = $handler->createModel();
        $dynamicFieldSection = (new DynamicFieldSection(str_replace('.', '_', $config->name)))
            ->setting($model->getConnection());
        $toggleMenuSection = (new ToggleMenuSection())->setting($plugin->getId(), $instanceId);

        return View::make(
            sprintf('%s::views.setting', $plugin->getId()),
            [
                'instanceId' => $instanceId,
                'config' => $config,
                'permArgs' => $permArgs,
                'skinSection' => $skinSection,
                'dynamicFieldSection' => $dynamicFieldSection,
                'toggleMenuSection' => $toggleMenuSection
            ]
        );
    }
}
