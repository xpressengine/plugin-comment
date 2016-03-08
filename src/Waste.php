<?php
namespace Xpressengine\Plugins\Comment;

use Xpressengine\Plugins\Comment\Models\Comment;
use Xpressengine\Trash\WasteInterface;
use XeTrash;

class Waste implements WasteInterface
{
    /**
     * 휴지통 이름 반환
     *
     *
     * @return string
     */
    public static function name()
    {
        return Handler::PLUGIN_PREFIX;
    }

    /**
     * 휴지통 비우기 처리할 때 수행해야 할 코드 입력
     * TrashManager 에서 휴지통 비우기(clean()) 가 처리될 때 사용
     *
     * @return void
     */
    public static function clean()
    {
        $plugin = app('xe.plugin.comment');
        $handler = $plugin->getHandler();

        $model = $handler->createModel();
        $comments = $model->newQuery()->where('status', 'trash')->get();

        foreach ($comments as $comment) {
            $handler->remove($comment);
        }
    }

    /**
     * 휴지통 패키지에서 각 휴지통의 상태를 알 수 있도록 정보를 반환
     * 휴지통을 비우기 전에 각 휴지통에 얼마만큼의 정보가 있는지 알려주기 위한 인터페이스
     *
     * @return string
     */
    public static function summary()
    {
        $plugin = app('xe.plugin.comment');
        $handler = $plugin->getHandler();

        $model = $handler->createModel();
        $count = $model->newQuery()->where('status', 'trash')->count();

        // todo: translation
        return sprintf('휴지통에 %s건의 문서가 있습니다.', $count);
    }
}
