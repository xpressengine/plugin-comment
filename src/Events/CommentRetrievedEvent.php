<?php
/**
 * CommentRetrievedEvent.php
 *
 * PHP version 7
 *
 * @category    Comment
 * @package     Xpressengine\Plugins\Comment
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2019 Copyright XEHub Corp. <https://www.xehub.io>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        https://xpressengine.io
 */

namespace Xpressengine\Plugins\Comment\Events;

use Xpressengine\Http\Request;

/**
 * CommentRetrievedEvent
 *
 * @category    Comment
 * @package     Xpressengine\Plugins\Comment
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2019 Copyright XEHub Corp. <https://www.xehub.io>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        https://xpressengine.io
 */
class CommentRetrievedEvent
{
    public $request;

    /**
     * CommentRetrievedEvent constructor.
     *
     * @param Request $request request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
}
